# KAIH MCP Client

Interactive client untuk berkomunikasi dengan KAIH MCP Server.

## 🚀 Quick Start

```bash
# Install dependencies
npm install

# Build TypeScript
npm run build

# Run client (akan connect ke server)
npm run dev
```

## 📖 Cara Kerja

Client ini menggunakan stdio untuk berkomunikasi dengan MCP Server. Saat menjalankan `npm run dev`, client akan:

1. Spawn process MCP Server (`../mcp-server/dist/index.js`)
2. Connect via stdio transport
3. List semua tools yang tersedia
4. Tampilkan menu interaktif untuk memilih action

## 🎯 Menu Interaktif

```
Pilih aksi:
1. Lihat daftar guru
2. Cari guru (nama/NIP)
3. Lihat detail guru
4. Tambah guru baru
5. Ubah data guru
6. Hapus guru
7. Lihat statistik guru
```

Pilih nomor (1-7) dan ikuti petunjuk di layar.

## 📝 Contoh Output

### Lihat Daftar Guru
```json
[
  {
    "id": 1,
    "nip": "19810315 198703 2 002",
    "nama_guru": "Rina Wijaya",
    "jabatan": "Guru Bahasa Indonesia",
    "kelas": "Kelas 7A",
    "alamat": "Jl. Merdeka 123",
    "no_hp": "081234567890"
  }
]
```

### Tambah Guru Baru
```json
{
  "id": 16,
  "message": "Guru Ahmad Putra berhasil ditambahkan"
}
```

## 🔧 Architecture

Client menggunakan `@modelcontextprotocol/sdk` untuk:
- Connect ke server via stdio
- List tools dari server
- Call tools dengan parameters
- Parse dan display results

## 📦 Dependencies

- `@modelcontextprotocol/sdk` - MCP SDK
- `node:readline` - CLI input

## 🛠️ Development

Untuk debugging:
```bash
# Build saja
npm run build

# Jalankan dengan logging (uncomment console.log di src/index.ts)
npm start
```

