# KAIH MCP (Model Context Protocol) - Server & Client

Implementasi Model Context Protocol untuk Sistem Informasi KAIH dengan fokus pada manajemen data Guru (CRUD operations).

## 📋 Daftar Isi

- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Menjalankan Server](#menjalankan-server)
- [Menjalankan Client](#menjalankan-client)
- [Tools yang Tersedia](#tools-yang-tersedia)
- [Contoh Penggunaan](#contoh-penggunaan)

## 🚀 Instalasi

### Prerequisites
- Node.js 18+ dan npm
- MySQL (sudah terhubung dengan KAIH)
- TypeScript

### Setup Server

```bash
cd mcp-server
npm install
npm run build
```

### Setup Client

```bash
cd mcp-client
npm install
npm run build
```

## ⚙️ Konfigurasi

Konfigurasi database sudah di-hardcode dalam `mcp-server/src/index.ts`:

```typescript
const dbConfig = {
  host: "localhost",
  user: "root",
  password: "",
  database: "kaih",
  charset: "utf8mb4",
};
```

Jika konfigurasi berbeda, ubah nilai-nilai di atas sesuai environment Anda.

## ▶️ Menjalankan Server

### Development Mode
```bash
cd mcp-server
npm run dev
```
Server akan mendengarkan melalui stdio dan mencatat log ke stderr.

### Production Mode
```bash
cd mcp-server
npm start
```

Output:
```
✓ Database pool initialized
✓ KAIH MCP Server started and listening on stdio
```

## ▶️ Menjalankan Client

### Mode Interaktif (Development)
```bash
cd mcp-client
npm run dev
```

Client akan terhubung ke server dan menampilkan menu interaktif:
```
🎯 KAIH MCP Client - Interactive Mode

Pilih aksi:
1. Lihat daftar guru
2. Cari guru (nama/NIP)
3. Lihat detail guru
4. Tambah guru baru
5. Ubah data guru
6. Hapus guru
7. Lihat statistik guru

Pilihan (1-7):
```

### Mode Programmatic
```bash
cd mcp-client
npm start
```

## 🔧 Tools yang Tersedia

### 1. **list_guru**
Menampilkan daftar semua guru atau mencari guru berdasarkan nama/NIP.

**Parameters:**
- `search` (string, optional): Cari berdasarkan nama, NIP, jabatan, atau kelas
- `limit` (number, optional): Jumlah hasil (default: 20)

**Example:**
```json
{
  "search": "Rina",
  "limit": 10
}
```

**Response:**
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

---

### 2. **get_guru**
Mendapatkan detail guru berdasarkan ID.

**Parameters:**
- `id` (number, required): ID guru

**Example:**
```json
{
  "id": 1
}
```

**Response:**
```json
{
  "id": 1,
  "nip": "19810315 198703 2 002",
  "nama_guru": "Rina Wijaya",
  "jabatan": "Guru Bahasa Indonesia",
  "kelas": "Kelas 7A",
  "alamat": "Jl. Merdeka 123",
  "no_hp": "081234567890"
}
```

---

### 3. **create_guru**
Menambah guru baru ke database.

**Parameters:**
- `nip` (string, required): Nomor Induk Pegawai
- `nama_guru` (string, required): Nama guru
- `jabatan` (string, required): Jabatan (misal: Guru Bahasa Indonesia)
- `kelas` (string, optional): Kelas yang diampu (misal: Kelas 7A)
- `alamat` (string, optional): Alamat guru
- `no_hp` (string, optional): Nomor HP

**Example:**
```json
{
  "nip": "19850822 201001 1 004",
  "nama_guru": "Budi Santoso",
  "jabatan": "Guru Matematika",
  "kelas": "Kelas 8B",
  "alamat": "Jl. Ahmad Yani 45",
  "no_hp": "082234567890"
}
```

**Response:**
```json
{
  "id": 12,
  "message": "Guru Budi Santoso berhasil ditambahkan"
}
```

---

### 4. **update_guru**
Mengubah data guru yang sudah ada. Field yang tidak disebutkan tidak akan diubah.

**Parameters:**
- `id` (number, required): ID guru
- `nip` (string, optional): Nomor Induk Pegawai baru
- `nama_guru` (string, optional): Nama baru
- `jabatan` (string, optional): Jabatan baru
- `kelas` (string, optional): Kelas baru
- `alamat` (string, optional): Alamat baru
- `no_hp` (string, optional): Nomor HP baru

**Example:**
```json
{
  "id": 12,
  "jabatan": "Guru Matematika dan IPA",
  "kelas": "Kelas 9A"
}
```

**Response:**
```json
{
  "message": "Guru berhasil diperbarui"
}
```

---

### 5. **delete_guru**
Menghapus guru dari database. Siswa yang memiliki guru ini sebagai wali kelas akan di-reset.

**Parameters:**
- `id` (number, required): ID guru yang akan dihapus

**Example:**
```json
{
  "id": 12
}
```

**Response:**
```json
{
  "message": "Guru Budi Santoso dan data terkaitnya berhasil dihapus"
}
```

---

### 6. **guru_stats**
Mendapatkan statistik guru (total, per jabatan, per kelas, total wali kelas).

**Parameters:** (none)

**Example:**
```json
{}
```

**Response:**
```json
{
  "total_guru": 15,
  "guru_per_jabatan": [
    {
      "jabatan": "Guru Bahasa Indonesia",
      "jumlah": 3
    },
    {
      "jabatan": "Guru Matematika",
      "jumlah": 4
    }
  ],
  "guru_per_kelas": [
    {
      "kelas": "Kelas 7A",
      "jumlah": 1
    },
    {
      "kelas": "Kelas 8B",
      "jumlah": 1
    }
  ],
  "total_wali_kelas": 8
}
```

---

## 💡 Contoh Penggunaan

### Skenario 1: List Semua Guru
```bash
Pilihan (1-7): 1
```
✅ Menampilkan daftar 20 guru pertama

### Skenario 2: Cari Guru Bernama "Rina"
```bash
Pilihan (1-7): 2
Cari (nama/NIP): Rina
```
✅ Menampilkan semua guru dengan nama "Rina"

### Skenario 3: Tambah Guru Baru
```bash
Pilihan (1-7): 4
📝 Tambah Guru Baru:
NIP: 19900101 202001 1 001
Nama: Ahmad Putra
Jabatan: Guru IPA
Kelas: Kelas 7B
Alamat: Jl. Sudirman 10
No HP: 085678901234
```
✅ Guru Ahmad Putra berhasil ditambahkan dengan ID baru

### Skenario 4: Update Guru Menjadi Wali Kelas
```bash
Pilihan (1-7): 5
✏️ Ubah Data Guru:
ID guru: 1
Nama baru (kosongkan jika tidak): 
Jabatan baru (kosongkan jika tidak): 
Kelas baru (kosongkan jika tidak): Kelas 7C
```
✅ Guru dengan ID 1 sekarang menjadi wali Kelas 7C

### Skenario 5: Lihat Statistik
```bash
Pilihan (1-7): 7
```
✅ Menampilkan:
- Total guru: 16
- Guru per jabatan
- Guru per kelas (yang menjadi wali)
- Total guru yang menjadi wali kelas

---

## 🔄 Integrasi dengan Sistem KAIH

MCP Server ini dirancang untuk:

1. **Otomasi Data Guru**: Bot atau sistem eksternal dapat menambah/mengubah guru tanpa UI
2. **Sinkronisasi**: Sistem lain dapat mengakses data guru secara real-time
3. **Laporan Otomatis**: Generate laporan dari statistik guru
4. **Validasi Data**: AI dapat memvalidasi integritas data guru

---

## 📝 Catatan Teknis

### Database Schema
- **Table**: `guru`
- **Columns**: id, nip, nama_guru, jabatan, kelas, alamat, no_hp
- **Wali Assignment**: Guru menjadi wali kelas jika `kelas` field tidak kosong
- **Student Reference**: `siswa.wali_kelas_id` mereferensi `guru.id`

### Error Handling
- Invalid ID: Server mengembalikan `{ error: "..." }`
- Database connection error: Otomatis log dan error response
- Duplicate NIP: MySQL constraint akan menangani

### Performance
- Connection pool: Hingga 10 koneksi simultaneous
- Query dengan index pada `id`, `nip`, `nama_guru`
- LIMIT default 20 untuk mencegah query berat

---

## 🐛 Troubleshooting

### Server tidak mau start
```
Error: connect ECONNREFUSED 127.0.0.1:3306
```
→ Pastikan MySQL sudah berjalan dan database `kaih` ada

### Client tidak bisa connect ke server
```
Error: ENOENT ../mcp-server/dist/index.js
```
→ Pastikan sudah `npm run build` di mcp-server folder

### Query timeout
→ Naikkan pool size atau check MySQL performance

---

## 📞 Support

Untuk pertanyaan atau issue, cek:
- `mcp-server/src/index.ts` - Logika server
- `mcp-client/src/index.ts` - Contoh client
- Database config di `../config/database.php`

