import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import {
  ListToolsRequestSchema,
  CallToolRequestSchema,
  TextContent,
  Tool,
} from "@modelcontextprotocol/sdk/types.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import * as mysql from "mysql2/promise.js";

// Database configuration
const dbConfig = {
  host: "localhost",
  user: "root",
  password: "",
  database: "kaih",
  charset: "utf8mb4",
};

// Create pool untuk koneksi database
let pool: mysql.Pool;

async function initDatabase() {
  pool = mysql.createPool(dbConfig);
}

// Tool Definitions
const tools: Tool[] = [
  {
    name: "list_guru",
    description:
      "Menampilkan daftar semua guru atau mencari guru berdasarkan nama/NIP",
    inputSchema: {
      type: "object" as const,
      properties: {
        search: {
          type: "string",
          description: "Cari guru berdasarkan nama atau NIP (opsional)",
        },
        limit: {
          type: "number",
          description: "Jumlah data yang ditampilkan (default: 20)",
        },
      },
      required: [],
    },
  },
  {
    name: "get_guru",
    description: "Mendapatkan detail guru berdasarkan ID",
    inputSchema: {
      type: "object" as const,
      properties: {
        id: {
          type: "number",
          description: "ID guru",
        },
      },
      required: ["id"],
    },
  },
  {
    name: "create_guru",
    description: "Menambah guru baru",
    inputSchema: {
      type: "object" as const,
      properties: {
        nip: {
          type: "string",
          description: "Nomor Induk Pegawai",
        },
        nama_guru: {
          type: "string",
          description: "Nama guru",
        },
        jabatan: {
          type: "string",
          description:
            "Jabatan (misal: Guru Bahasa Indonesia, Guru Matematika, etc)",
        },
        kelas: {
          type: "string",
          description: "Kelas yang diampu (opsional)",
        },
        alamat: {
          type: "string",
          description: "Alamat guru",
        },
        no_hp: {
          type: "string",
          description: "Nomor HP guru",
        },
      },
      required: ["nip", "nama_guru", "jabatan"],
    },
  },
  {
    name: "update_guru",
    description: "Mengubah data guru yang sudah ada",
    inputSchema: {
      type: "object" as const,
      properties: {
        id: {
          type: "number",
          description: "ID guru",
        },
        nip: {
          type: "string",
          description: "Nomor Induk Pegawai (opsional)",
        },
        nama_guru: {
          type: "string",
          description: "Nama guru (opsional)",
        },
        jabatan: {
          type: "string",
          description: "Jabatan (opsional)",
        },
        kelas: {
          type: "string",
          description: "Kelas yang diampu (opsional)",
        },
        alamat: {
          type: "string",
          description: "Alamat guru (opsional)",
        },
        no_hp: {
          type: "string",
          description: "Nomor HP guru (opsional)",
        },
      },
      required: ["id"],
    },
  },
  {
    name: "delete_guru",
    description: "Menghapus guru dari database",
    inputSchema: {
      type: "object" as const,
      properties: {
        id: {
          type: "number",
          description: "ID guru yang akan dihapus",
        },
      },
      required: ["id"],
    },
  },
  {
    name: "guru_stats",
    description: "Mendapatkan statistik guru (total, per jabatan, per kelas)",
    inputSchema: {
      type: "object" as const,
      properties: {},
      required: [],
    },
  },
];

// Tool Handlers
async function listGuru(
  search?: string,
  limit: number = 20
): Promise<Record<string, unknown>[]> {
  const connection = await pool.getConnection();
  try {
    let query = "SELECT id, nip, nama_guru, jabatan, kelas, alamat, no_hp FROM guru";
    const params: string[] = [];

    if (search) {
      query +=
        " WHERE nama_guru LIKE ? OR nip LIKE ? OR jabatan LIKE ? OR kelas LIKE ?";
      const searchTerm = `%${search}%`;
      params.push(searchTerm, searchTerm, searchTerm, searchTerm);
    }

    query += " LIMIT ?";
    params.push(limit.toString());

    const [rows] = await connection.query(query, params);
    return rows as Record<string, unknown>[];
  } finally {
    connection.release();
  }
}

async function getGuru(id: number): Promise<Record<string, unknown> | null> {
  const connection = await pool.getConnection();
  try {
    const [rows] = await connection.query(
      "SELECT id, nip, nama_guru, jabatan, kelas, alamat, no_hp FROM guru WHERE id = ?",
      [id]
    );
    const result = rows as Record<string, unknown>[];
    return result.length > 0 ? result[0] : null;
  } finally {
    connection.release();
  }
}

async function createGuru(data: {
  nip: string;
  nama_guru: string;
  jabatan: string;
  kelas?: string;
  alamat?: string;
  no_hp?: string;
}): Promise<{ id: number; message: string }> {
  const connection = await pool.getConnection();
  try {
    const [result] = await connection.query(
      "INSERT INTO guru (nip, nama_guru, jabatan, kelas, alamat, no_hp) VALUES (?, ?, ?, ?, ?, ?)",
      [
        data.nip,
        data.nama_guru,
        data.jabatan,
        data.kelas || null,
        data.alamat || null,
        data.no_hp || null,
      ]
    );

    const insertResult = result as mysql.ResultSetHeader;
    return {
      id: insertResult.insertId,
      message: `Guru ${data.nama_guru} berhasil ditambahkan`,
    };
  } finally {
    connection.release();
  }
}

async function updateGuru(data: {
  id: number;
  nip?: string;
  nama_guru?: string;
  jabatan?: string;
  kelas?: string;
  alamat?: string;
  no_hp?: string;
}): Promise<{ message: string }> {
  const connection = await pool.getConnection();
  try {
    const updateFields: string[] = [];
    const updateParams: (string | number | null)[] = [];

    if (data.nip !== undefined) {
      updateFields.push("nip = ?");
      updateParams.push(data.nip);
    }
    if (data.nama_guru !== undefined) {
      updateFields.push("nama_guru = ?");
      updateParams.push(data.nama_guru);
    }
    if (data.jabatan !== undefined) {
      updateFields.push("jabatan = ?");
      updateParams.push(data.jabatan);
    }
    if (data.kelas !== undefined) {
      updateFields.push("kelas = ?");
      updateParams.push(data.kelas || null);
    }
    if (data.alamat !== undefined) {
      updateFields.push("alamat = ?");
      updateParams.push(data.alamat || null);
    }
    if (data.no_hp !== undefined) {
      updateFields.push("no_hp = ?");
      updateParams.push(data.no_hp || null);
    }

    if (updateFields.length === 0) {
      return { message: "Tidak ada data yang diubah" };
    }

    updateParams.push(data.id);
    const query = `UPDATE guru SET ${updateFields.join(", ")} WHERE id = ?`;

    await connection.query(query, updateParams);
    return { message: "Guru berhasil diperbarui" };
  } finally {
    connection.release();
  }
}

async function deleteGuru(id: number): Promise<{ message: string }> {
  const connection = await pool.getConnection();
  try {
    // Check if guru exists
    const [rows] = await connection.query(
      "SELECT nama_guru FROM guru WHERE id = ?",
      [id]
    );
    const result = rows as Record<string, unknown>[];

    if (result.length === 0) {
      throw new Error(`Guru dengan ID ${id} tidak ditemukan`);
    }

    const nama = result[0].nama_guru;

    // Delete guru
    await connection.query("DELETE FROM guru WHERE id = ?", [id]);

    // Reset wali_kelas_id for students who had this guru
    await connection.query(
      "UPDATE siswa SET wali_kelas_id = NULL WHERE wali_kelas_id = ?",
      [id]
    );

    return { message: `Guru ${nama} dan data terkaitnya berhasil dihapus` };
  } finally {
    connection.release();
  }
}

async function getGuruStats(): Promise<Record<string, unknown>> {
  const connection = await pool.getConnection();
  try {
    const [totalResult] = await connection.query(
      "SELECT COUNT(*) as total FROM guru"
    );
    const total = (totalResult as Record<string, unknown>[])[0].total;

    const [jabatanResult] = await connection.query(
      "SELECT jabatan, COUNT(*) as jumlah FROM guru GROUP BY jabatan"
    );

    const [kelasResult] = await connection.query(
      "SELECT kelas, COUNT(*) as jumlah FROM guru WHERE kelas IS NOT NULL AND kelas != '' GROUP BY kelas"
    );

    const [waliResult] = await connection.query(
      "SELECT COUNT(*) as total FROM guru WHERE kelas IS NOT NULL AND kelas != ''"
    );

    return {
      total_guru: total,
      guru_per_jabatan: jabatanResult,
      guru_per_kelas: kelasResult,
      total_wali_kelas: (waliResult as Record<string, unknown>[])[0].total,
    };
  } finally {
    connection.release();
  }
}

// Process Tool Calls
async function processToolCall(
  toolName: string,
  toolInput: Record<string, unknown>
): Promise<string> {
  try {
    if (toolName === "list_guru") {
      const result = await listGuru(
        toolInput.search as string | undefined,
        (toolInput.limit as number) || 20
      );
      return JSON.stringify(result, null, 2);
    } else if (toolName === "get_guru") {
      const result = await getGuru(toolInput.id as number);
      return JSON.stringify(result, null, 2);
    } else if (toolName === "create_guru") {
      const result = await createGuru(toolInput as Parameters<typeof createGuru>[0]);
      return JSON.stringify(result, null, 2);
    } else if (toolName === "update_guru") {
      const result = await updateGuru(toolInput as Parameters<typeof updateGuru>[0]);
      return JSON.stringify(result, null, 2);
    } else if (toolName === "delete_guru") {
      const result = await deleteGuru(toolInput.id as number);
      return JSON.stringify(result, null, 2);
    } else if (toolName === "guru_stats") {
      const result = await getGuruStats();
      return JSON.stringify(result, null, 2);
    } else {
      throw new Error(`Unknown tool: ${toolName}`);
    }
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error occurred";
    return JSON.stringify({ error: errorMessage });
  }
}

// Main Server Setup
async function main() {
  // Initialize database
  await initDatabase();
  console.error("✓ Database pool initialized");

  const server = new Server(
    {
      name: "KAIH-MCP-Server",
      version: "1.0.0",
    },
    {
      capabilities: {
        tools: {},
      },
    }
  );

  // Tool list handler
  server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools,
  }));

  // Tool call handler
  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const toolInput = request.params.arguments as Record<string, unknown>;
    const result = await processToolCall(request.params.name, toolInput);

    return {
      content: [
        {
          type: "text",
          text: result,
        } as TextContent,
      ],
    };
  });

  // Start server
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("✓ KAIH MCP Server started and listening on stdio");
}

main().catch(console.error);
