import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";
import * as readline from "readline";

let client: Client;
let transport: StdioClientTransport;

// Initialize client
async function initClient() {
  // Create and connect stdio client transport
  transport = new StdioClientTransport({
    command: "node",
    args: ["../mcp-server/dist/index.js"],
  });

  client = new Client(
    {
      name: "KAIH-MCP-Client",
      version: "1.0.0",
    },
    {
      capabilities: {},
    }
  );

  await client.connect(transport);
  console.log("✓ Connected to KAIH MCP Server\n");
}

// List all available tools
async function listTools() {
  const tools = await client.listTools();
  console.log(
    "\n📋 Available Tools (" + tools.tools.length + "):\n"
  );
  tools.tools.forEach((tool, idx) => {
    console.log(`${idx + 1}. ${tool.name}`);
    console.log(`   ${tool.description}`);
  });
}

// Call a tool
async function callTool(toolName: string, toolInput: Record<string, unknown>) {
  console.log(`\n🔧 Calling: ${toolName}`);
  console.log(`   Input: ${JSON.stringify(toolInput)}`);

  const result = await client.callTool({
    name: toolName,
    arguments: toolInput,
  });

  console.log(`   Result:`);
  const content = result.content as { type: string; text: string }[];
  if (content[0].type === "text") {
    const text = content[0].text;
    try {
      const parsed = JSON.parse(text);
      console.log(`   ${JSON.stringify(parsed, null, 2)}`);
    } catch {
      console.log(`   ${text}`);
    }
  }
}

// Interactive prompt
function createPrompt() {
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
  });

  const question = (prompt: string): Promise<string> => {
    return new Promise((resolve) => {
      rl.question(prompt, resolve);
    });
  };

  return { rl, question };
}

// Example commands
async function runExamples() {
  const { question, rl } = createPrompt();

  console.log("\n🎯 KAIH MCP Client - Interactive Mode\n");
  console.log("Pilih aksi:");
  console.log("1. Lihat daftar guru");
  console.log("2. Cari guru (nama/NIP)");
  console.log("3. Lihat detail guru");
  console.log("4. Tambah guru baru");
  console.log("5. Ubah data guru");
  console.log("6. Hapus guru");
  console.log("7. Lihat statistik guru");

  const choice = await question("\nPilihan (1-7): ");

  switch (choice.trim()) {
    case "1":
      await callTool("list_guru", {});
      break;

    case "2": {
      const search = await question("Cari (nama/NIP): ");
      await callTool("list_guru", { search: search.trim(), limit: 10 });
      break;
    }

    case "3": {
      const id = await question("ID guru: ");
      await callTool("get_guru", { id: parseInt(id) });
      break;
    }

    case "4": {
      console.log("\n📝 Tambah Guru Baru:\n");
      const nip = await question("NIP: ");
      const nama = await question("Nama: ");
      const jabatan = await question("Jabatan: ");
      const kelas = await question("Kelas (kosongkan jika tidak): ");
      const alamat = await question("Alamat: ");
      const no_hp = await question("No HP: ");

      await callTool("create_guru", {
        nip: nip.trim(),
        nama_guru: nama.trim(),
        jabatan: jabatan.trim(),
        ...(kelas.trim() && { kelas: kelas.trim() }),
        ...(alamat.trim() && { alamat: alamat.trim() }),
        ...(no_hp.trim() && { no_hp: no_hp.trim() }),
      });
      break;
    }

    case "5": {
      console.log("\n✏️ Ubah Data Guru:\n");
      const id = await question("ID guru: ");
      const nama = await question("Nama baru (kosongkan jika tidak): ");
      const jabatan = await question("Jabatan baru (kosongkan jika tidak): ");
      const kelas = await question("Kelas baru (kosongkan jika tidak): ");

      const updateData: Record<string, unknown> = {
        id: parseInt(id),
      };
      if (nama.trim()) updateData.nama_guru = nama.trim();
      if (jabatan.trim()) updateData.jabatan = jabatan.trim();
      if (kelas.trim()) updateData.kelas = kelas.trim();

      await callTool("update_guru", updateData);
      break;
    }

    case "6": {
      const id = await question("ID guru yang akan dihapus: ");
      await callTool("delete_guru", { id: parseInt(id) });
      break;
    }

    case "7":
      await callTool("guru_stats", {});
      break;

    default:
      console.log("Pilihan tidak valid!");
  }

  rl.close();
}

// Main
async function main() {
  try {
    await initClient();
    await listTools();
    await runExamples();
    await transport.close();
  } catch (error) {
    console.error("Error:", error);
    process.exit(1);
  }
}

main();
