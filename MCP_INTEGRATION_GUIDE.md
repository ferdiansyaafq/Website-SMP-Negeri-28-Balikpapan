# MCP Integration Guide

Panduan lengkap untuk mengintegrasikan KAIH MCP Server dengan berbagai sistem.

## 🤖 Integrasi dengan Claude (via Claude Desktop)

Claude Desktop memungkinkan integrasi MCP Server untuk memberikan akses data real-time kepada AI.

### Setup Claude Desktop

1. **Install Claude Desktop** (jika belum): https://claude.ai/download

2. **Lokasi config file:**
   - Windows: `%APPDATA%\Claude\claude_desktop_config.json`
   - macOS: `~/Library/Application Support/Claude/claude_desktop_config.json`
   - Linux: `~/.config/Claude/claude_desktop_config.json`

3. **Edit config file** (buat jika belum ada):

```json
{
  "mcpServers": {
    "kaih": {
      "command": "node",
      "args": ["C:\\laragon\\www\\kaih\\mcp-server\\dist\\index.js"],
      "disabled": false,
      "env": {
        "NODE_ENV": "production"
      }
    }
  }
}
```

**Adjust path** sesuai lokasi mcp-server di sistem Anda.

4. **Restart Claude Desktop**

Setelah restart, Claude akan memiliki akses ke tools: `list_guru`, `get_guru`, `create_guru`, `update_guru`, `delete_guru`, `guru_stats`.

### Contoh Prompt di Claude

```
"Tambahkan guru baru bernama Ahmad Putra dengan NIP 19900101 202001 1 001, 
jabatan Guru Matematika, dan kelas Kelas 7A. 
Kemudian tampilkan semua guru yang mengajar di Kelas 7A."
```

Claude akan otomatis:
1. Call `create_guru` dengan data Ahmad Putra
2. Call `list_guru` dengan search "Kelas 7A"
3. Menampilkan hasil dan summary

---

## 🔌 Integrasi dengan Node.js Application

Contoh penggunaan langsung dalam aplikasi Node.js:

```typescript
import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";

// Initialize
const transport = new StdioClientTransport({
  command: "node",
  args: ["./mcp-server/dist/index.js"],
});

const client = new Client(
  {
    name: "my-app",
    version: "1.0.0",
  },
  {
    capabilities: {},
  }
);

await client.connect(transport);

// Use tools
const result = await client.callTool({
  name: "list_guru",
  arguments: { search: "Rina", limit: 10 },
});

console.log(result);
```

---

## 🌐 Integrasi dengan REST API

Jika ingin expose MCP sebagai REST API:

### Backend Express.js

```typescript
import express from "express";
import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";

const app = express();
app.use(express.json());

let mcpClient: Client;

// Initialize MCP Client
async function initMCP() {
  const transport = new StdioClientTransport({
    command: "node",
    args: ["./mcp-server/dist/index.js"],
  });

  mcpClient = new Client(
    { name: "api-server", version: "1.0.0" },
    { capabilities: {} }
  );

  await mcpClient.connect(transport);
}

// REST endpoints
app.get("/api/guru", async (req, res) => {
  try {
    const result = await mcpClient.callTool({
      name: "list_guru",
      arguments: {
        search: req.query.search as string,
        limit: parseInt(req.query.limit as string) || 20,
      },
    });

    const data = JSON.parse((result.content[0] as any).text);
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: (error as Error).message });
  }
});

app.post("/api/guru", async (req, res) => {
  try {
    const result = await mcpClient.callTool({
      name: "create_guru",
      arguments: req.body,
    });

    const data = JSON.parse((result.content[0] as any).text);
    res.json(data);
  } catch (error) {
    res.status(500).json({ error: (error as Error).message });
  }
});

app.listen(3000, async () => {
  await initMCP();
  console.log("✓ API Server with MCP running on http://localhost:3000");
});
```

### Frontend (JavaScript/TypeScript)

```typescript
// List guru
async function listGuru() {
  const response = await fetch("/api/guru?limit=10");
  return response.json();
}

// Create guru
async function createGuru(guruData: any) {
  const response = await fetch("/api/guru", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(guruData),
  });
  return response.json();
}
```

---

## 🔄 Workflow Automation

### Contoh: Auto-sync Guru dari Sistem Lain

```typescript
import axios from "axios";
import { Client } from "@modelcontextprotocol/sdk/client/index.js";

async function syncGuruFromExternalSystem() {
  // Fetch dari sistem eksternal
  const externalGurus = await axios.get("https://api.external.com/guru");

  const client = await initMCPClient();

  // Sync each guru
  for (const guru of externalGurus.data) {
    const result = await client.callTool({
      name: "create_guru",
      arguments: {
        nip: guru.id,
        nama_guru: guru.name,
        jabatan: guru.position,
        kelas: guru.class || null,
      },
    });

    console.log(`✓ Synced: ${guru.name}`);
  }
}

// Run setiap hari
schedule.scheduleJob("0 0 * * *", syncGuruFromExternalSystem);
```

---

## 📊 Monitoring & Logging

Tambahan di MCP Server untuk logging:

```typescript
// Di mpc-server/src/index.ts, tambahkan logging:
async function processToolCall(
  toolName: string,
  toolInput: Record<string, unknown>
): Promise<string> {
  const timestamp = new Date().toISOString();
  console.error(`[${timestamp}] Tool: ${toolName}`);
  console.error(`[${timestamp}] Input: ${JSON.stringify(toolInput)}`);

  try {
    const result = await callTool(toolName, toolInput);
    console.error(`[${timestamp}] Success`);
    return result;
  } catch (error) {
    console.error(`[${timestamp}] Error: ${error}`);
    throw error;
  }
}
```

---

## 🔐 Security Considerations

1. **Database Access**: MCP Server terhubung langsung ke MySQL
   - Pastikan credential aman (use environment variables)
   - Implement role-based access control di database

2. **Input Validation**: Semua input dari client harus divalidasi
   - Gunakan schema validation
   - Sanitize strings untuk mencegah SQL injection (sudah handled oleh mysql2)

3. **Rate Limiting**: Implementasikan rate limit untuk production
   - Limit jumlah requests per IP
   - Limit jumlah records yang dapat di-delete

4. **Encryption**: Jika transmisi data sensitif, encrypt via TLS
   - Setup MCP Server di port 3000+
   - Use SSL certificates

---

## 🚀 Deployment

### Production Setup

1. **Build MCP Server**
   ```bash
   cd mcp-server
   npm ci
   npm run build
   ```

2. **Setup Environment Variables** (.env)
   ```
   DB_HOST=localhost
   DB_USER=production_user
   DB_PASS=secure_password
   DB_NAME=kaih
   NODE_ENV=production
   ```

3. **Update database config** di `mcp-server/src/index.ts`

4. **Run via PM2** (process manager)
   ```bash
   npm install -g pm2
   pm2 start dist/index.js --name "kaih-mcp-server"
   pm2 startup
   pm2 save
   ```

---

## 📈 Performance Tips

1. **Database Indexing**
   ```sql
   CREATE INDEX idx_guru_nip ON guru(nip);
   CREATE INDEX idx_guru_nama ON guru(nama_guru);
   CREATE INDEX idx_guru_kelas ON guru(kelas);
   ```

2. **Caching** (optional)
   ```typescript
   const cache = new Map();
   // Cache list_guru results for 5 minutes
   ```

3. **Connection Pool**
   - Current: 10 max connections
   - Adjust di `mcp-server/src/index.ts` jika needed

---

## ❓ FAQ

**Q: Bisa diintegrasikan dengan Langchain?**
A: Ya, Langchain memiliki MCP integration. Setup tools dari MCP Server.

**Q: Gimana cara backup data sebelum delete guru?**
A: Implement soft-delete dengan timestamp atau trigger MySQL untuk archive data.

**Q: Bisa update guru_stats lebih sering?**
A: Ya, tulis cron job yang call `guru_stats` dan store di Redis/cache.

