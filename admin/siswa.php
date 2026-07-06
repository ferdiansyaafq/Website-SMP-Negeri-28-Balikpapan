<?php
require_once '../includes/admin_auth.php';
requireAdminLogin();
require_once '../config/database.php';
require_once '../includes/user_accounts.php';

$conn    = getConnection();
$success = '';
$error   = '';
$managedStudentDefaultPassword = getManagedStudentDefaultPassword();

/* ============================================================
   XLSX READER (No composer dependency)
   - Reads first worksheet (sheet1)
   - Supports shared strings + inline strings + numbers
   ============================================================ */
function xlsxColumnIndex(string $cellRef): int
{
  if (!preg_match('/^([A-Z]+)\d+$/', strtoupper($cellRef), $m)) {
    return -1;
  }
  $letters = $m[1];
  $n = 0;
  for ($i = 0; $i < strlen($letters); $i++) {
    $n = $n * 26 + (ord($letters[$i]) - 64);
  }
  return $n - 1;
}

function readXlsxFirstSheetRows(string $path): array
{
  if (!class_exists('ZipArchive')) {
    throw new RuntimeException('ZipArchive tidak tersedia. Aktifkan ekstensi ZIP di PHP.');
  }

  $zip = new ZipArchive();
  if ($zip->open($path) !== true) {
    throw new RuntimeException('File Excel tidak bisa dibuka.');
  }

  // Resolve first worksheet path dynamically (not always sheet1.xml)
  $sheetPath = 'xl/worksheets/sheet1.xml';
  try {
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml !== false && $relsXml !== false) {
      $wb = @simplexml_load_string($workbookXml);
      $rels = @simplexml_load_string($relsXml);
      if ($wb && $rels) {
        $wb->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $sheets = $wb->xpath('//a:sheets/a:sheet') ?: [];
        if (!empty($sheets)) {
          $first = $sheets[0];
          $rid = (string) ($first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
          if ($rid !== '') {
            // Find relationship target
            foreach ($rels->Relationship as $rel) {
              $attrs = $rel->attributes();
              if ((string) ($attrs['Id'] ?? '') === $rid) {
                $target = (string) ($attrs['Target'] ?? '');
                $target = ltrim($target, '/');
                // Usually "worksheets/sheet2.xml"
                if ($target !== '') {
                  $sheetPath = 'xl/' . $target;
                }
                break;
              }
            }
          }
        }
      }
    }
  } catch (Throwable) {
    // fallback to sheet1.xml
  }

  $sharedStrings = [];
  $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
  if ($sharedXml !== false) {
    $sx = @simplexml_load_string($sharedXml);
    if ($sx) {
      foreach ($sx->si as $si) {
        if (isset($si->t)) {
          $sharedStrings[] = (string) $si->t;
          continue;
        }
        $parts = [];
        if (isset($si->r)) {
          foreach ($si->r as $r) {
            if (isset($r->t)) {
              $parts[] = (string) $r->t;
            }
          }
        }
        $sharedStrings[] = implode('', $parts);
      }
    }
  }

  $sheetXml = $zip->getFromName($sheetPath);
  if ($sheetXml === false) {
    $zip->close();
    throw new RuntimeException('Worksheet tidak ditemukan dalam file Excel.');
  }

  $sheet = @simplexml_load_string($sheetXml);
  if (!$sheet) {
    $zip->close();
    throw new RuntimeException('Gagal membaca isi sheet Excel.');
  }

  $rows = [];
  $sheet->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
  $rowNodes = $sheet->xpath('//a:sheetData/a:row') ?: [];
  foreach ($rowNodes as $row) {
    $cells = [];
    foreach ($row->c as $c) {
      $ref = (string) $c['r'];
      $colIdx = xlsxColumnIndex($ref);
      if ($colIdx < 0) continue;

      $type = (string) ($c['t'] ?? '');
      $value = '';

      if ($type === 's') {
        $idx = (int) ($c->v ?? 0);
        $value = $sharedStrings[$idx] ?? '';
      } elseif ($type === 'inlineStr') {
        $value = (string) ($c->is->t ?? '');
      } else {
        $value = isset($c->v) ? (string) $c->v : '';
      }

      $cells[$colIdx] = trim($value);
    }

    if (!empty($cells)) {
      $max = max(array_keys($cells));
      $rowArr = array_fill(0, $max + 1, '');
      foreach ($cells as $i => $v) $rowArr[$i] = $v;
      $rows[] = $rowArr;
    } else {
      $rows[] = [];
    }
  }

  $zip->close();
  return $rows;
}

function ensureSiswaImportColumns(mysqli $conn): void
{
  $cols = [];
  $res = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'siswa'");
  if ($res) {
    while ($r = $res->fetch_assoc()) {
      $cols[strtolower((string) $r['COLUMN_NAME'])] = true;
    }
  }

  if (!isset($cols['nis'])) {
    $conn->query("ALTER TABLE siswa ADD COLUMN nis VARCHAR(30) NULL AFTER nisn");
  }
  if (!isset($cols['jenis_kelamin'])) {
    $conn->query("ALTER TABLE siswa ADD COLUMN jenis_kelamin CHAR(1) NULL AFTER nama_siswa");
  }
}

function normalizeKelasName(string $kelas): string
{
  $kelas = trim(preg_replace('/\s+/', ' ', $kelas));
  if ($kelas === '') return '';
  $abbr = preg_replace('/^Kelas\s+/i', '', $kelas);
  return 'Kelas ' . strtoupper(trim((string) $abbr));
}

function getOrCreateKelasId(mysqli $conn, string $namaKelas): int
{
  $namaKelas = normalizeKelasName($namaKelas);
  if ($namaKelas === '') {
    throw new RuntimeException('Nama kelas kosong.');
  }

  $stmt = $conn->prepare('SELECT id FROM kaih_kelas WHERE nama_kelas = ? LIMIT 1');
  if ($stmt) {
    $stmt->bind_param('s', $namaKelas);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) return (int) $row['id'];
  }

  $stmt2 = $conn->prepare('INSERT INTO kaih_kelas (nama_kelas, created_at, updated_at) VALUES (?, NOW(), NOW())');
  if (!$stmt2) {
    throw new RuntimeException('Gagal menambahkan kelas baru.');
  }
  $stmt2->bind_param('s', $namaKelas);
  if (!$stmt2->execute()) {
    $stmt2->close();
    throw new RuntimeException('Gagal menambahkan kelas baru.');
  }
  $id = (int) $stmt2->insert_id;
  $stmt2->close();
  return $id;
}

function normalizeHeaderKey(string $value): string
{
  $v = strtoupper(trim($value));
  $v = preg_replace('/\s+/', ' ', $v);
  $v = str_replace(['.', ':'], '', $v);
  return $v;
}

function padLeftDigits(string $digits, int $len): string
{
  $digits = preg_replace('/\D+/', '', $digits);
  if ($digits === '') return '';
  if (strlen($digits) >= $len) return $digits;
  return str_pad($digits, $len, '0', STR_PAD_LEFT);
}

function readCsvRows(string $path): array
{
  $content = @file_get_contents($path);
  if ($content === false) {
    throw new RuntimeException('File CSV tidak bisa dibaca.');
  }

  // Remove UTF-8 BOM if present
  $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

  $lines = preg_split("/\r\n|\n|\r/", $content);
  $lines = array_values(array_filter($lines, fn($l) => trim((string) $l) !== ''));
  if (empty($lines)) {
    return [];
  }

  $sample = implode("\n", array_slice($lines, 0, min(5, count($lines))));
  $delims = [',' => substr_count($sample, ','), ';' => substr_count($sample, ';'), "\t" => substr_count($sample, "\t")];
  arsort($delims);
  $delimiter = array_key_first($delims) ?: ',';

  $rows = [];
  $fh = fopen($path, 'rb');
  if (!$fh) {
    throw new RuntimeException('File CSV tidak bisa dibuka.');
  }
  while (($data = fgetcsv($fh, 0, $delimiter)) !== false) {
    if ($data === [null] || $data === false) continue;
    $row = array_map(fn($v) => trim((string) $v), $data);
    // skip fully empty row
    $nonEmpty = false;
    foreach ($row as $v) { if ($v !== '') { $nonEmpty = true; break; } }
    if ($nonEmpty) $rows[] = $row;
  }
  fclose($fh);
  return $rows;
}

function detectImportHeader(array $rows): array
{
  $headerRowIndex = -1;
  $colMap = ['nisn_nis' => -1, 'nama' => -1, 'lp' => -1, 'kelas' => -1];
  $scanMax = min(10, count($rows));
  for ($h = 0; $h < $scanMax; $h++) {
    $row = $rows[$h] ?? [];
    if (empty($row)) continue;
    $localMap = $colMap;
    foreach ($row as $ci => $cv) {
      $key = normalizeHeaderKey((string) $cv);
      if ($key === '') continue;
      if ($localMap['nisn_nis'] < 0 && str_contains($key, 'NISN') && str_contains($key, 'NIS')) {
        $localMap['nisn_nis'] = (int) $ci;
      }
      if ($localMap['nama'] < 0 && (str_contains($key, 'NAMA') && str_contains($key, 'LENGKAP'))) {
        $localMap['nama'] = (int) $ci;
      }
      if ($localMap['lp'] < 0 && (str_contains($key, 'L/P') || $key === 'LP' || $key === 'L P')) {
        $localMap['lp'] = (int) $ci;
      }
      if ($localMap['kelas'] < 0 && str_contains($key, 'KELAS')) {
        $localMap['kelas'] = (int) $ci;
      }
    }
    if ($localMap['nisn_nis'] >= 0 && $localMap['nama'] >= 0) {
      $headerRowIndex = $h;
      $colMap = $localMap;
      break;
    }
  }

  if ($headerRowIndex < 0) {
    $headerRowIndex = 0;
    $colMap = ['nisn_nis' => 0, 'nama' => 1, 'lp' => 2, 'kelas' => 3];
  }

  return [$headerRowIndex, $colMap];
}

function ensureLaporanHarianTableAdmin(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS laporan_harian (
        id INT AUTO_INCREMENT PRIMARY KEY,
        siswa_id INT NOT NULL,
        tanggal DATE NOT NULL,
        bangun TINYINT(1) NOT NULL DEFAULT 0,
        ibadah TINYINT(1) NOT NULL DEFAULT 0,
        ibadah_catatan VARCHAR(255) NULL,
        olahraga TINYINT(1) NOT NULL DEFAULT 0,
        olahraga_jenis VARCHAR(50) NULL,
        sarapan TINYINT(1) NOT NULL DEFAULT 0,
        sarapan_menu VARCHAR(50) NULL,
        membaca TINYINT(1) NOT NULL DEFAULT 0,
        membaca_judul VARCHAR(255) NULL,
        membaca_menit INT NULL,
        membantu TINYINT(1) NOT NULL DEFAULT 0,
        membantu_jenis VARCHAR(50) NULL,
        menabung TINYINT(1) NOT NULL DEFAULT 0,
        menabung_nominal INT NULL,
        orang_tua_validated_at DATETIME NULL,
        guru_validated_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_siswa_tanggal (siswa_id, tanggal),
        INDEX idx_tanggal (tanggal)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        throw new RuntimeException('Gagal menyiapkan tabel laporan harian.');
    }
}

// Sinkron ringan untuk data lama:
// - Pastikan siswa yang sudah punya wali_kelas_id memakai nama kelas yang sama dengan kaih_kelas.
// - Jika wali_kelas_id kosong tapi kolom kelas terisi, coba hubungkan berdasarkan nama kelas (toleransi 'Kelas 7A' vs '7A').
try {
  $conn->query(
    "UPDATE siswa s\n" .
    "JOIN kaih_kelas k ON k.id = s.wali_kelas_id\n" .
    "SET s.kelas = k.nama_kelas, s.updated_at = NOW()\n" .
    "WHERE s.wali_kelas_id IS NOT NULL AND s.wali_kelas_id != 0\n" .
    "  AND (s.kelas IS NULL OR s.kelas = '' OR s.kelas <> k.nama_kelas)"
  );

  $conn->query(
    "UPDATE siswa s\n" .
    "JOIN kaih_kelas k\n" .
    "  ON TRIM(REPLACE(LOWER(s.kelas), 'kelas ', '')) = TRIM(REPLACE(LOWER(k.nama_kelas), 'kelas ', ''))\n" .
    "SET s.wali_kelas_id = k.id, s.kelas = k.nama_kelas, s.updated_at = NOW()\n" .
    "WHERE (s.wali_kelas_id IS NULL OR s.wali_kelas_id = 0)\n" .
    "  AND s.kelas IS NOT NULL AND s.kelas <> ''"
  );
} catch (Throwable $e) {
  // Abaikan jika DB tidak mendukung query tertentu; halaman tetap bisa berjalan.
}

/* ============================================================
   HANDLE POST ACTIONS
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika ukuran POST melebihi batas php.ini, $_POST dan $_FILES bisa kosong (tanpa error tampil).
    if (empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH'])) {
        $contentLength = (int) $_SERVER['CONTENT_LENGTH'];
        $postMax = (string) ini_get('post_max_size');
        $uploadMax = (string) ini_get('upload_max_filesize');

        $toBytes = function (string $val): int {
            $val = trim($val);
            if ($val === '') return 0;
            $unit = strtolower(substr($val, -1));
            $num = (int) preg_replace('/\D+/', '', $val);
            return match ($unit) {
                'g' => $num * 1024 * 1024 * 1024,
                'm' => $num * 1024 * 1024,
                'k' => $num * 1024,
                default => (int) $val,
            };
        };

        $postMaxBytes = $toBytes($postMax);
        $uploadMaxBytes = $toBytes($uploadMax);

        if (($postMaxBytes > 0 && $contentLength > $postMaxBytes) || ($uploadMaxBytes > 0 && $contentLength > $uploadMaxBytes)) {
            $error = 'Gagal import: ukuran file terlalu besar. Batas server: post_max_size=' . $postMax . ', upload_max_filesize=' . $uploadMax . '.';
        }
    }

    $action = $_POST['action'] ?? '';
    // Guard: multipart POST tapi action tidak terbaca → tampilkan error agar tidak "diam".
    if ($action === '' && empty($error)) {
        $ct = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (stripos($ct, 'multipart/form-data') !== false) {
            $error = 'Import tidak diproses: data form tidak terbaca oleh server. Coba kecilkan ukuran file atau naikkan batas upload PHP (post_max_size / upload_max_filesize).';
        }
    }

    /* ---- TAMBAH & EDIT ---- */
    if ($action === 'simpan') {
      $id            = intval($_POST['id'] ?? 0);
      $nisn          = trim($_POST['nisn'] ?? '');
      $nama_siswa    = trim($_POST['nama_siswa'] ?? '');
      $kelas         = trim($_POST['kelas'] ?? '');
      $kelas_id      = intval($_POST['kelas_id'] ?? 0);
      $wali_kelas_id = intval($_POST['wali_kelas_id'] ?? 0) ?: null;

      if ($kelas_id <= 0 && $wali_kelas_id) {
        $kelas_id = (int) $wali_kelas_id;
      }

      if ($kelas_id > 0) {
        $kelasStmt = $conn->prepare('SELECT id, nama_kelas FROM kaih_kelas WHERE id = ? LIMIT 1');
        if (!$kelasStmt) {
          $error = 'Gagal membaca data kelas.';
        } else {
          $kelasStmt->bind_param('i', $kelas_id);
          $kelasStmt->execute();
          $kelasRow = $kelasStmt->get_result()->fetch_assoc();
          $kelasStmt->close();

          if (!$kelasRow) {
            $error = 'Kelas yang dipilih tidak valid.';
          } else {
            $kelas = trim((string) $kelasRow['nama_kelas']);
            $wali_kelas_id = (int) $kelasRow['id'];
          }
        }
      } elseif ($kelas !== '') {
        // Fallback untuk kompatibilitas request lama yang masih kirim nama kelas.
        $kelasStmt = $conn->prepare('SELECT id FROM kaih_kelas WHERE nama_kelas = ? LIMIT 1');
        if ($kelasStmt) {
          $kelasStmt->bind_param('s', $kelas);
          $kelasStmt->execute();
          $kelasRow = $kelasStmt->get_result()->fetch_assoc();
          $kelasStmt->close();
          if ($kelasRow) {
            $wali_kelas_id = (int) $kelasRow['id'];
          }
        }
      }

        if (empty($error) && (empty($nisn) || empty($nama_siswa) || empty($kelas))) {
            $error = 'NISN, Nama Siswa, dan Kelas wajib diisi.';
        }

        if (empty($error)) {
      $conn->begin_transaction();
      try {
        if ($id === 0) {
          $cek = $conn->prepare("SELECT id FROM siswa WHERE nisn = ?");
          if (!$cek) {
            throw new RuntimeException('Gagal memeriksa duplikasi NISN.');
          }
          $cek->bind_param('s', $nisn);
          $cek->execute();
          if ($cek->get_result()->num_rows > 0) {
            $cek->close();
            throw new RuntimeException('NISN sudah terdaftar. Gunakan NISN yang berbeda.');
          }
          $cek->close();

          $stmt = $conn->prepare("INSERT INTO siswa (nisn, nama_siswa, kelas, wali_kelas_id, created_at, updated_at) VALUES (?, ?, ?, NULLIF(?, 0), NOW(), NOW())");
          if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan penyimpanan data siswa.');
          }
          $waliKelasKey = $wali_kelas_id ?? 0;
          $stmt->bind_param('sssi', $nisn, $nama_siswa, $kelas, $waliKelasKey);
          if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Gagal menambahkan data siswa.');
          }
          $id = (int) $stmt->insert_id;
          $stmt->close();
          $success = 'Data siswa berhasil ditambahkan.';
        } else {
          $cek = $conn->prepare("SELECT id FROM siswa WHERE nisn = ? AND id != ?");
          if (!$cek) {
            throw new RuntimeException('Gagal memeriksa duplikasi NISN.');
          }
          $cek->bind_param('si', $nisn, $id);
          $cek->execute();
          if ($cek->get_result()->num_rows > 0) {
            $cek->close();
            throw new RuntimeException('NISN sudah digunakan siswa lain.');
          }
          $cek->close();

          $stmt = $conn->prepare("UPDATE siswa SET nisn=?, nama_siswa=?, kelas=?, wali_kelas_id=NULLIF(?, 0), updated_at=NOW() WHERE id=?");
          if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan pembaruan data siswa.');
          }
          $waliKelasKey = $wali_kelas_id ?? 0;
          $stmt->bind_param('sssii', $nisn, $nama_siswa, $kelas, $waliKelasKey, $id);
          if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Gagal memperbarui data siswa.');
          }
          $stmt->close();
          $success = 'Data siswa berhasil diperbarui.';
        }

        syncStudentAccounts($conn, $id, $nisn);
        $conn->commit();
      } catch (Throwable $e) {
        $conn->rollback();
        $error = $e->getMessage();
        $success = '';
            }
        }
    }

    /* ---- BULK HAPUS ---- */
    if ($action === 'bulk_hapus') {
        $rawIds = $_POST['ids'] ?? [];
        $ids = array_values(array_filter(array_map('intval', (array) $rawIds), fn($v) => $v > 0));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $conn->begin_transaction();
            try {
                foreach ($ids as $delId) {
                    deleteStudentAccounts($conn, $delId);
                }
                $stmt = $conn->prepare("DELETE FROM siswa WHERE id IN ($placeholders)");
                if (!$stmt) throw new RuntimeException('Gagal menyiapkan penghapusan massal.');
                $stmt->bind_param($types, ...$ids);
                if (!$stmt->execute()) { $stmt->close(); throw new RuntimeException('Gagal menghapus data siswa.'); }
                $stmt->close();
                $conn->commit();
                $success = count($ids) . ' data siswa berhasil dihapus.';
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- HAPUS ---- */
    if ($action === 'hapus') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
        $conn->begin_transaction();
        try {
          deleteStudentAccounts($conn, $id);

          $stmt = $conn->prepare("DELETE FROM siswa WHERE id = ?");
          if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan penghapusan data siswa.');
          }
          $stmt->bind_param('i', $id);
          if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Gagal menghapus data siswa.');
          }
          $stmt->close();

          $conn->commit();
          $success = 'Data siswa berhasil dihapus.';
        } catch (Throwable $e) {
          $conn->rollback();
          $error = $e->getMessage();
          $success = '';
        }
        }
    }

    /* ---- IMPORT EXCEL ---- */
    if ($action === 'import_excel' && empty($error)) {
        $kelasIdOverride = (int) ($_POST['import_kelas_id'] ?? 0);
        $file = $_FILES['import_file'] ?? null;

        if (!$file || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $error = 'File Excel wajib dipilih.';
        } else {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
          $error = 'Format file harus .xlsx atau .csv';
            } elseif ($ext === 'xlsx' && !class_exists('ZipArchive')) {
                $error = 'Import .xlsx tidak bisa diproses karena ZipArchive (PHP ZIP) belum aktif. Solusi cepat: simpan file sebagai .csv lalu import ulang.';
        }
        }

        if (empty($error)) {
            $tmpPath = (string) ($file['tmp_name'] ?? '');
        $workPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kaih_import_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($tmpPath, $workPath)) {
                $error = 'Gagal memproses upload file.';
            } else {
                try {
                    $conn->begin_transaction();
                    ensureSiswaImportColumns($conn);

            $rows = $ext === 'csv' ? readCsvRows($workPath) : readXlsxFirstSheetRows($workPath);
                    if (count($rows) < 2) {
                        throw new RuntimeException('File Excel kosong atau format tidak sesuai.');
                    }

                    $imported = 0;
                    $updated = 0;
                    $skipped = 0;

                    [$headerRowIndex, $colMap] = detectImportHeader($rows);

                    for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
                        $r = $rows[$i];
                        $col0 = trim((string) ($r[$colMap['nisn_nis']] ?? ''));
                        $nama = trim((string) ($r[$colMap['nama']] ?? ''));
                        $lp = strtoupper(trim((string) ($r[$colMap['lp']] ?? '')));
                        $kelasText = trim((string) ($r[$colMap['kelas']] ?? ''));

                        if ($col0 === '' && $nama === '') {
                            $skipped++;
                            continue;
                        }

                        $nisn = '';
                        $nis = '';
                        if (strpos($col0, '/') !== false) {
                            $parts = array_map('trim', explode('/', $col0));
                            $nisn = $parts[0] ?? '';
                            $nis = $parts[1] ?? '';
                        } else {
                            $nisn = $col0;
                        }

                        // Pastikan nol di depan tidak hilang untuk NISN.
                        // NISN biasanya 10 digit, NIS biasanya 3 digit (opsional).
                        $nisn = padLeftDigits($nisn, 10);
                        $nis = $nis !== '' ? padLeftDigits($nis, 3) : '';

                        if ($nisn === '' || $nama === '') {
                            $skipped++;
                            continue;
                        }

                        $jk = in_array($lp, ['L', 'P'], true) ? $lp : null;

                        $kelasId = 0;
                        $kelasNama = '';
                        if ($kelasIdOverride > 0) {
                            $kelasRow = fetchSingleRow($conn, 'SELECT id, nama_kelas FROM kaih_kelas WHERE id = ? LIMIT 1', 'i', [$kelasIdOverride]);
                            if (!$kelasRow) {
                                throw new RuntimeException('Kelas override tidak valid.');
                            }
                            $kelasId = (int) $kelasRow['id'];
                            $kelasNama = (string) $kelasRow['nama_kelas'];
                        } else {
                            if ($kelasText === '') {
                                $skipped++;
                                continue;
                            }
                            $kelasNama = normalizeKelasName($kelasText);
                            $kelasId = getOrCreateKelasId($conn, $kelasNama);
                        }

                        $existing = fetchSingleRow($conn, 'SELECT id FROM siswa WHERE nisn = ? LIMIT 1', 's', [$nisn]);
                        if ($existing) {
                            $sid = (int) $existing['id'];
                            $stmtU = $conn->prepare("UPDATE siswa SET nisn=?, nis=NULLIF(?, ''), nama_siswa=?, jenis_kelamin=?, kelas=?, wali_kelas_id=?, updated_at=NOW() WHERE id=?");
                            if (!$stmtU) {
                                throw new RuntimeException('Gagal menyiapkan update siswa.');
                            }
                            $jkVal = $jk ?? null;
                            $stmtU->bind_param('sssssii', $nisn, $nis, $nama, $jkVal, $kelasNama, $kelasId, $sid);
                            if (!$stmtU->execute()) {
                                $stmtU->close();
                                throw new RuntimeException('Gagal update siswa: ' . $nama);
                            }
                            $stmtU->close();
                            syncStudentAccounts($conn, $sid, $nisn); // login tetap pakai NISN
                            $updated++;
                        } else {
                            $stmtI = $conn->prepare("INSERT INTO siswa (nisn, nis, nama_siswa, jenis_kelamin, kelas, wali_kelas_id, created_at, updated_at) VALUES (?, NULLIF(?, ''), ?, ?, ?, ?, NOW(), NOW())");
                            if (!$stmtI) {
                                throw new RuntimeException('Gagal menyiapkan insert siswa.');
                            }
                            $jkVal = $jk ?? null;
                            $stmtI->bind_param('sssssi', $nisn, $nis, $nama, $jkVal, $kelasNama, $kelasId);
                            if (!$stmtI->execute()) {
                                $stmtI->close();
                                throw new RuntimeException('Gagal insert siswa: ' . $nama);
                            }
                            $sid = (int) $stmtI->insert_id;
                            $stmtI->close();
                            syncStudentAccounts($conn, $sid, $nisn); // login tetap pakai NISN
                            $imported++;
                        }
                    }

                    $conn->commit();
                    if (($imported + $updated) === 0) {
                        $error = 'Import tidak menambahkan data. Pastikan sheet pertama berisi data di bawah header: NISN /NIS, NAMA LENGKAP, L/P, KELAS.';
                        $success = '';
                    } else {
                        $success = "Import selesai. Baru: $imported, Update: $updated, Lewat: $skipped.";
                    }
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                } finally {
                    @unlink($workPath);
                }
            }
        }
    }

    /* ---- SET PASSWORD SISWA / ORANG TUA ---- */
    if ($action === 'set_password_siswa' && empty($error)) {
        $pwSiswaId   = (int) ($_POST['pw_siswa_id'] ?? 0);
        $pwNisn      = trim((string) ($_POST['pw_nisn'] ?? ''));
        $pwSiswa     = trim((string) ($_POST['pw_siswa'] ?? ''));
        $pwOrtu      = trim((string) ($_POST['pw_ortu'] ?? ''));
        $pwReset     = (string) ($_POST['pw_reset'] ?? '') === '1';

        if ($pwSiswaId <= 0 || $pwNisn === '') {
            $error = 'Data siswa tidak valid.';
        } else {
            try {
                $conn->begin_transaction();

                if ($pwReset) {
                    syncStudentAccounts($conn, $pwSiswaId, $pwNisn, true);
                  $success = 'Password siswa dan orang tua berhasil direset ke default (' . htmlspecialchars($managedStudentDefaultPassword) . ').';
                } else {
                    if ($pwSiswa !== '') {
                        upsertLinkedUser($conn, 'siswa', $pwNisn, $pwNisn, null, $pwSiswaId, $pwSiswa, false);
                    }
                    if ($pwOrtu !== '') {
                        $ortuUsername = buildParentUsername($pwNisn);
                        upsertLinkedUser($conn, 'orang_tua', $ortuUsername, $ortuUsername, null, $pwSiswaId, $pwOrtu, false);
                    }
                    if ($pwSiswa === '' && $pwOrtu === '') {
                        $error = 'Masukkan minimal satu password yang ingin diubah.';
                        $conn->rollback();
                    } else {
                        $success = 'Password berhasil diperbarui.';
                    }
                }

                if (empty($error)) {
                    $conn->commit();
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }

    /* ---- RESET LAPORAN HARIAN ---- */
    if ($action === 'reset_laporan' && empty($error)) {
        $scope = (string) ($_POST['reset_scope'] ?? 'all'); // all | month
        $month = trim((string) ($_POST['reset_month'] ?? '')); // YYYY-MM
        $confirm = (string) ($_POST['reset_confirm'] ?? '');
        if ($confirm !== 'RESET') {
            $error = 'Reset dibatalkan: ketik RESET untuk konfirmasi.';
        } else {
            try {
                $conn->begin_transaction();
                ensureLaporanHarianTableAdmin($conn);

                $affected = 0;
                if ($scope === 'month') {
                    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                        throw new RuntimeException('Bulan reset tidak valid.');
                    }
                    $start = $month . '-01';
                    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $start);
                    if (!$dt) {
                        throw new RuntimeException('Bulan reset tidak valid.');
                    }
                    $end = $dt->modify('last day of this month')->format('Y-m-d');

                    $stmt = $conn->prepare('DELETE FROM laporan_harian WHERE tanggal BETWEEN ? AND ?');
                    if (!$stmt) {
                        throw new RuntimeException('Gagal menyiapkan reset laporan.');
                    }
                    $stmt->bind_param('ss', $start, $end);
                    if (!$stmt->execute()) {
                        $stmt->close();
                        throw new RuntimeException('Gagal melakukan reset laporan.');
                    }
                    $affected = (int) $stmt->affected_rows;
                    $stmt->close();
                    $conn->commit();
                    $success = 'Reset laporan bulan ' . htmlspecialchars($month) . ' selesai. Baris terhapus: ' . $affected . '.';
                } else {
                    $stmt = $conn->prepare('DELETE FROM laporan_harian');
                    if (!$stmt) {
                        throw new RuntimeException('Gagal menyiapkan reset laporan.');
                    }
                    if (!$stmt->execute()) {
                        $stmt->close();
                        throw new RuntimeException('Gagal melakukan reset laporan.');
                    }
                    $affected = (int) $stmt->affected_rows;
                    $stmt->close();
                    $conn->commit();
                    $success = 'Reset semua laporan selesai. Baris terhapus: ' . $affected . '.';
                }
            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

/* ============================================================
   FETCH DATA
   ============================================================ */
// Search & Pagination
$search    = trim($_GET['q'] ?? '');
$per_page  = 10;
$page      = max(1, intval($_GET['page'] ?? 1));
$offset    = ($page - 1) * $per_page;

$where = '';
$params = [];
$types  = '';
if ($search !== '') {
    $like = "%$search%";
    $where = "WHERE s.nisn LIKE ? OR s.nama_siswa LIKE ? OR s.kelas LIKE ?";
    $params = [$like, $like, $like];
    $types  = 'sss';
}

// Total rows
$countSql = "SELECT COUNT(*) as total FROM siswa s $where";
$stmtC = $conn->prepare($countSql);
if ($types) { $stmtC->bind_param($types, ...$params); }
$stmtC->execute();
$total_rows = $stmtC->get_result()->fetch_assoc()['total'];
$stmtC->close();
$total_pages = ceil($total_rows / $per_page);

// Data siswa dengan join kelas
$sql = "SELECT s.*, k.nama_kelas,
  (
      SELECT GROUP_CONCAT(DISTINCT g.nama_guru ORDER BY g.nama_guru SEPARATOR ', ')
      FROM guru g
      WHERE TRIM(REPLACE(LOWER(g.kelas), 'kelas ', '')) = TRIM(REPLACE(LOWER(k.nama_kelas), 'kelas ', ''))
  ) AS wali_nama
  FROM siswa s
  LEFT JOIN kaih_kelas k ON s.wali_kelas_id = k.id
  $where
  ORDER BY s.id DESC
  LIMIT ? OFFSET ?";
$stmtD = $conn->prepare($sql);
$allParams = array_merge($params, [$per_page, $offset]);
$allTypes  = $types . 'ii';
$stmtD->bind_param($allTypes, ...$allParams);
$stmtD->execute();
$siswaList = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

// Fetch kelas + wali kelas untuk dropdown form siswa
$kelasResult = $conn->query("SELECT k.id, k.nama_kelas,
    GROUP_CONCAT(DISTINCT g.nama_guru ORDER BY g.nama_guru SEPARATOR ', ') AS nama_wali
    FROM kaih_kelas k
    LEFT JOIN guru g ON TRIM(REPLACE(LOWER(g.kelas), 'kelas ', '')) = TRIM(REPLACE(LOWER(k.nama_kelas), 'kelas ', ''))
    GROUP BY k.id, k.nama_kelas
    ORDER BY k.nama_kelas");
$kelasList = $kelasResult ? $kelasResult->fetch_all(MYSQLI_ASSOC) : [];

// Stats
$totalSiswa = $conn->query("SELECT COUNT(*) as c FROM siswa")->fetch_assoc()['c'];
$conn->close();

$adminName = htmlspecialchars($_SESSION['admin_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<script>(function(){try{var t=localStorage.getItem('kaih_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();function toggleTheme(){var h=document.documentElement,d=h.getAttribute('data-theme')==='dark';if(d)h.removeAttribute('data-theme');else h.setAttribute('data-theme','dark');try{localStorage.setItem('kaih_theme',d?'light':'dark');}catch(e){}}</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Siswa — KAIH Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard.css?v=20260316g">
<link rel="stylesheet" href="../assets/css/siswa.css?v=20260316g">
</head>
<body>
<div class="layout">

  <button class="sidebar-toggle" id="sidebarToggle" title="Menu">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 12h18M3 6h18M3 18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  </button>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <div class="logo-box">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="10" fill="url(#lg1)"/>
            <path d="M10 20L17 13L24 20L17 27L10 20Z" fill="white"/>
            <path d="M18 20L25 13L32 20L25 27L18 20Z" fill="white" fill-opacity=".6"/>
            <defs><linearGradient id="lg1" x1="0" y1="0" x2="40" y2="40"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
          </svg>
        </div>
        <div class="logo-text"><span class="logo-title">KAIH</span><span class="logo-sub">Admin Panel</span></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Menu Utama</div>
      <a href="dashboard.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/></svg>
        <span>Dashboard</span>
      </a>
      <a href="siswa.php" class="nav-item active">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Data Siswa</span>
      </a>
      <a href="guru.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
        <span>Data Guru</span>
      </a>
      <a href="kelas.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2"/><polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2"/></svg>
        <span>Data Kelas</span>
      </a>
      <a href="users.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Akun &amp; User</span>
      </a>
      <a href="foto.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/><path d="M21 15l-5-5L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Foto Slideshow</span>
      </a>
      <div class="nav-section-label">Laporan</div>
      <a href="laporan.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/><polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2"/><line x1="16" y1="13" x2="8" y2="13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><line x1="16" y1="17" x2="8" y2="17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span>Laporan Siswa</span>
      </a>
      <a href="laporan-guru.php" class="nav-item">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="2"/><rect x="9" y="3" width="6" height="4" rx="1" stroke="currentColor" stroke-width="2"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Laporan Guru</span>
      </a>
    </nav>
    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
        <div class="user-detail">
          <span class="user-name"><?= $adminName ?></span>
          <span class="user-role">Administrator</span>
        </div>
      </div>
      <a href="logout.php" class="btn-logout" title="Keluar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Data Siswa</h1>
        <p class="page-sub">Kelola data siswa &mdash; total <strong><?= $totalSiswa ?> siswa</strong> terdaftar</p>
      </div>
      <div class="topbar-right">
        <div class="topbar-date">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
          <?= date('d M Y') ?>
        </div>
        <div class="admin-chip"><div class="admin-dot"></div>Admin</div>
      </div>
    </header>

    <!-- CONTENT -->
    <div class="content-area">

      <!-- Alert Messages -->
      <?php if ($success): ?>
      <div class="alert-toast success" id="toastMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#22c55e" stroke-width="2"/><path d="M9 12l2 2 4-4" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <?= htmlspecialchars($success) ?>
        <button onclick="this.parentElement.remove()">×</button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert-toast error" id="toastMsg">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="#ef4444" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="#ef4444" stroke-width="2" stroke-linecap="round"/></svg>
        <?= htmlspecialchars($error) ?>
        <button onclick="this.parentElement.remove()">×</button>
      </div>
      <?php endif; ?>

      <!-- Toolbar -->
      <div class="toolbar">
        <form method="GET" action="" class="search-form" id="searchForm">
          <div class="search-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
              <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
              <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input type="text" name="q" id="searchInput" placeholder="Cari NISN, nama, atau kelas..." value="<?= htmlspecialchars($search) ?>" class="search-input" autocomplete="off">
            <?php if ($search): ?>
            <a href="siswa.php" class="search-clear" title="Hapus pencarian">×</a>
            <?php endif; ?>
          </div>
        </form>
        <button class="btn-primary" id="btnTambah" onclick="openModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
          Tambah Siswa
        </button>
        <a class="btn-primary" href="template-import-siswa.php?format=xlsx" style="background:#334155; text-decoration:none;" title="Download Template Import (.xlsx)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2"/>
            <path d="M14 2v6h6" stroke="currentColor" stroke-width="2"/>
            <path d="M12 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M9 14l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Template Import
        </a>
        <button class="btn-primary" type="button" style="background:#b91c1c;" onclick="openResetModal()" title="Reset laporan harian siswa">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M21 12a9 9 0 1 1-3.3-6.9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Reset Laporan
        </button>
        <button class="btn-primary" type="button" style="background:#0f766e;" onclick="openImportModal()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M8 11l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Import Excel
        </button>
      </div>

      <!-- Table -->
      <div class="table-card">
        <div class="table-header">
          <h3>Daftar Siswa</h3>
          <span class="table-count"><?= $total_rows ?> data ditemukan</span>
        </div>

        <!-- Bulk action toolbar -->
        <form method="POST" id="frmBulkSiswa" action="">
          <input type="hidden" name="action" value="bulk_hapus">
          <div id="bulkToolbarSiswa" style="display:none;align-items:center;gap:10px;padding:10px 16px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:8px;margin-bottom:10px;">
            <span id="bulkCountSiswa" style="font-size:.875rem;color:#dc2626;font-weight:600;">0 dipilih</span>
            <button type="submit" id="btnBulkDelSiswa"
              style="display:flex;align-items:center;gap:6px;padding:6px 14px;background:#dc2626;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.875rem;font-weight:600;"
              onclick="return confirm('Hapus ' + document.querySelectorAll(\'input[name=\"ids[]\"]\').length + ' data siswa yang dipilih? Tindakan ini tidak dapat dibatalkan.')">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
              Hapus Terpilih
            </button>
            <button type="button"
              style="padding:6px 12px;background:transparent;border:1px solid #94a3b8;border-radius:6px;cursor:pointer;font-size:.875rem;color:#64748b;"
              onclick="clearSelectionSiswa()">Batal Pilih</button>
          </div>

        <div class="table-responsive">
          <table class="data-table" id="tabelSiswa">
            <thead>
              <tr>
                <th style="width:36px;text-align:center;"><input type="checkbox" id="chkAllSiswa" title="Pilih Semua" style="cursor:pointer;width:16px;height:16px;"></th>
                <th style="width:50px">No</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Wali Kelas</th>
                <th>Tanggal Daftar</th>
                <th style="width:150px;text-align:center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($siswaList)): ?>
              <tr>
                <td colspan="8" class="empty-state">
                  <div class="empty-wrap">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <p><?= $search ? 'Tidak ada siswa yang cocok dengan pencarian.' : 'Belum ada data siswa. Klik "Tambah Siswa" untuk memulai.' ?></p>
                  </div>
                </td>
              </tr>
              <?php else: ?>
              <?php $no = $offset + 1; foreach ($siswaList as $s): ?>
              <tr class="table-row" data-id="<?= $s['id'] ?>">
                <td style="text-align:center;"><input type="checkbox" name="ids[]" value="<?= $s['id'] ?>" class="chk-siswa" style="cursor:pointer;width:16px;height:16px;"></td>
                <td class="td-no"><?= $no++ ?></td>
                <td><span class="nisn-badge"><?= htmlspecialchars($s['nisn']) ?></span></td>
                <td class="td-name">
                  <div class="name-wrap">
                    <div class="avatar-sm"><?= strtoupper(substr($s['nama_siswa'],0,1)) ?></div>
                    <span><?= htmlspecialchars($s['nama_siswa']) ?></span>
                  </div>
                </td>
                <td><span class="kelas-chip"><?= htmlspecialchars($s['kelas']) ?></span></td>
                <td class="td-wali">
                  <?php if (!empty($s['wali_nama'])): ?>
                    <?= htmlspecialchars($s['wali_nama']) ?>
                  <?php elseif (!empty($s['nama_kelas'])): ?>
                    <span class="td-muted">Belum ada wali kelas</span>
                  <?php else: ?>
                    <span class="td-none">—</span>
                  <?php endif; ?>
                </td>
                <td class="td-date"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                <td class="td-action">
                  <button type="button" class="btn-edit" title="Edit"
                    onclick="openEdit(<?= $s['id'] ?>, '<?= addslashes($s['nisn']) ?>', '<?= addslashes($s['nama_siswa']) ?>', '<?= addslashes($s['kelas']) ?>', '<?= $s['wali_kelas_id'] ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                  </button>
                  <button type="button" class="btn-delete" title="Hapus"
                    onclick="confirmDelete(<?= $s['id'] ?>, '<?= addslashes($s['nama_siswa']) ?>')">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
          <span class="page-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
          <div class="page-btns">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn">‹ Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
            <a href="?page=<?= $i ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn <?= $i==$page ? 'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page+1 ?><?= $search ? '&q='.urlencode($search) : '' ?>" class="page-btn">Next ›</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
        </form><!-- /frmBulkSiswa -->

    </div><!-- /content-area -->
  </main>
</div><!-- /layout -->

<!-- ============================================================
     MODAL RESET LAPORAN
     ============================================================ -->
<div class="modal-overlay" id="resetOverlay" onclick="closeResetModal(event)" style="display:none;">
  <div class="modal-box" style="max-width:560px;">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M21 12a9 9 0 1 1-3.3-6.9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div>
        <h3 class="modal-title">Reset Laporan Harian</h3>
        <p class="modal-sub">Menghapus laporan siswa yang sudah dikirim (termasuk status validasi)</p>
      </div>
      <button class="modal-close" type="button" onclick="closeResetModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="action" value="reset_laporan">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Scope Reset</label>
          <select name="reset_scope" class="form-select" id="resetScope" onchange="toggleResetMonth()">
            <option value="all">Semua laporan (hapus total)</option>
            <option value="month">Per bulan</option>
          </select>
          <p class="form-hint"><strong>Peringatan:</strong> data yang dihapus tidak bisa dikembalikan.</p>
        </div>
        <div class="form-group" id="resetMonthWrap" style="display:none;">
          <label class="form-label">Bulan</label>
          <input type="month" name="reset_month" class="form-input" value="<?= date('Y-m') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Ketik <strong>RESET</strong> untuk konfirmasi</label>
          <input type="text" name="reset_confirm" class="form-input" placeholder="RESET" required>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeResetModal()">Batal</button>
        <button type="submit" class="btn-danger">Ya, Reset</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL ATUR PASSWORD SISWA
     ============================================================ -->
<div class="modal-overlay" id="passwordOverlay" onclick="closePasswordModal(event)" style="display:none;">
  <div class="modal-box" style="max-width:500px;">
    <div class="modal-head">
      <div class="modal-icon" style="background:rgba(15,118,110,0.12);color:#0f766e;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Atur Password</h3>
        <p class="modal-sub" id="pwModalSub">Atur password siswa dan/atau orang tua</p>
      </div>
      <button class="modal-close" type="button" onclick="closePasswordModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formPassword">
      <input type="hidden" name="action" value="set_password_siswa">
      <input type="hidden" name="pw_siswa_id" id="pwSiswaId" value="">
      <input type="hidden" name="pw_nisn" id="pwNisn" value="">
      <input type="hidden" name="pw_reset" id="pwReset" value="0">
      <div class="modal-body">
        <p class="form-hint" style="margin-bottom:12px;">
          Kosongkan field yang tidak ingin diubah. Password default siswa dan orang tua = <strong><?= htmlspecialchars($managedStudentDefaultPassword) ?></strong>.
        </p>
        <div class="form-group">
          <label class="form-label">Password Baru — Siswa (Login: NISN)</label>
          <input type="password" name="pw_siswa" id="pwSiswaField" class="form-input" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
          <p class="form-hint">Biarkan kosong untuk tidak mengubah password siswa.</p>
        </div>
        <div class="form-group">
          <label class="form-label">Password Baru — Orang Tua (Login: ORT+NISN)</label>
          <input type="password" name="pw_ortu" id="pwOrtuField" class="form-input" placeholder="Kosongkan jika tidak diubah" autocomplete="new-password">
          <p class="form-hint">Biarkan kosong untuk tidak mengubah password orang tua.</p>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;color:#b91c1c;">
            <input type="checkbox" id="pwResetCheck" onchange="document.getElementById('pwReset').value=this.checked?'1':'0'; document.getElementById('pwSiswaField').disabled=this.checked; document.getElementById('pwOrtuField').disabled=this.checked;">
            Reset ke default (<?= htmlspecialchars($managedStudentDefaultPassword) ?>)
          </label>
          <p class="form-hint">Centang untuk mereset password siswa dan orang tua ke default yang sama.</p>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closePasswordModal()">Batal</button>
        <button type="submit" class="btn-save" style="background:linear-gradient(135deg,#0f766e,#14b8a6);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Simpan Password
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL TAMBAH / EDIT SISWA
     ============================================================ -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal-box" id="modalBox">
    <div class="modal-head">
      <div class="modal-icon" id="modalIcon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title" id="modalTitle">Tambah Siswa</h3>
        <p class="modal-sub" id="modalSub">Isi data siswa baru</p>
      </div>
      <button class="modal-close" onclick="closeModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" id="formSiswa">
      <input type="hidden" name="action" value="simpan">
      <input type="hidden" name="id" id="fieldId" value="0">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">NISN <span class="req">*</span></label>
            <input type="text" name="nisn" id="fieldNisn" class="form-input" placeholder="Nomor Induk Siswa Nasional" maxlength="30" required>
          </div>
          <div class="form-group">
            <label class="form-label">Kelas <span class="req">*</span></label>
            <select name="kelas_id" id="fieldKelasId" class="form-select" required>
              <option value="">— Pilih Kelas —</option>
              <?php foreach ($kelasList as $k): ?>
              <option value="<?= (int) $k['id'] ?>" data-nama="<?= htmlspecialchars($k['nama_kelas'], ENT_QUOTES) ?>" data-wali="<?= htmlspecialchars((string) ($k['nama_wali'] ?? ''), ENT_QUOTES) ?>">
                <?= htmlspecialchars($k['nama_kelas']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($kelasList)): ?>
            <p class="form-hint">⚠️ Belum ada kelas. Tambahkan kelas terlebih dahulu.</p>
            <?php endif; ?>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Lengkap Siswa <span class="req">*</span></label>
          <input type="text" name="nama_siswa" id="fieldNama" class="form-input" placeholder="Nama lengkap siswa" maxlength="100" required>
        </div>
        <div class="form-group">
          <label class="form-label">Wali Kelas Terhubung</label>
          <div class="wali-info" id="waliInfoBox">
            <span id="waliInfoValue">Pilih kelas untuk menghubungkan wali kelas otomatis.</span>
          </div>
        </div>
        <input type="hidden" name="kelas" id="fieldKelas" value="">
        <input type="hidden" name="wali_kelas_id" id="fieldWaliKelas" value="">
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
        <button type="submit" class="btn-save" id="btnSave">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/><polyline points="7 3 7 8 15 8" stroke="currentColor" stroke-width="2"/></svg>
          <span id="btnSaveText">Simpan Data</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL IMPORT EXCEL
     ============================================================ -->
<div class="modal-overlay" id="importOverlay" onclick="closeImportModal(event)" style="display:none;">
  <div class="modal-box" style="max-width:560px;">
    <div class="modal-head">
      <div class="modal-icon" style="background: rgba(15,118,110,0.12); color:#0f766e;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M12 3v12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          <path d="M8 11l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <div>
        <h3 class="modal-title">Import Data Siswa (Excel)</h3>
        <p class="modal-sub">Kolom: NISN/NIS, Nama Lengkap, L/P, Kelas</p>
      </div>
      <button class="modal-close" type="button" onclick="closeImportModal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <form method="POST" action="" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import_excel">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">File (.xlsx / .csv) <span class="req">*</span></label>
          <input type="file" name="import_file" accept=".xlsx,.csv" class="form-input" required>
          <p class="form-hint">
            Butuh format yang benar? Download:
            <a href="template-import-siswa.php?format=xlsx" style="font-weight:600;">Template .xlsx</a>
            &middot;
            <a href="template-import-siswa.php?format=csv" style="font-weight:600;">Template .csv</a><br>
            Akun login memakai <strong>NISN</strong>. NIS hanya disimpan sebagai data tambahan.
          </p>
        </div>
        <div class="form-group">
          <label class="form-label">Override Kelas (opsional)</label>
          <select name="import_kelas_id" class="form-select">
            <option value="0">— pakai kolom Kelas dari Excel —</option>
            <?php foreach ($kelasList as $k): ?>
              <option value="<?= (int) $k['id'] ?>"><?= htmlspecialchars($k['nama_kelas']) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="form-hint">Jika dipilih, semua baris import akan masuk ke kelas ini.</p>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeImportModal()">Batal</button>
        <button type="submit" class="btn-save" style="background:#0f766e;">Import Sekarang</button>
      </div>
    </form>
  </div>
</div>

<!-- ============================================================
     MODAL KONFIRMASI HAPUS
     ============================================================ -->
<div class="modal-overlay" id="deleteOverlay" onclick="closeDelete(event)">
  <div class="modal-box modal-sm" id="deleteBox">
    <div class="modal-head">
      <div class="modal-icon danger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2"/></svg>
      </div>
      <div>
        <h3 class="modal-title">Hapus Siswa</h3>
        <p class="modal-sub">Tindakan ini tidak dapat dibatalkan</p>
      </div>
      <button class="modal-close" onclick="closeDelete()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M18 6 6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="delete-confirm-msg">
        Apakah Anda yakin ingin menghapus data siswa:<br>
        <strong id="deleteName"></strong>?
      </div>
    </div>
    <form method="POST" action="" id="formDelete">
      <input type="hidden" name="action" value="hapus">
      <input type="hidden" name="id" id="deleteId" value="">
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeDelete()">Batal</button>
        <button type="submit" class="btn-danger">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><polyline points="3 6 5 6 21 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          Ya, Hapus
        </button>
      </div>
    </form>
  </div>
</div>

<script src="../assets/js/siswa.js?v=20260316g"></script>

<script>
(function(){
  var btn=document.getElementById('sidebarToggle'),sb=document.getElementById('sidebar'),bd=document.getElementById('sidebarBackdrop');
  if(!btn||!sb)return;
  function toggle(){sb.classList.toggle('open');bd.classList.toggle('active');}
  function close(){sb.classList.remove('open');bd.classList.remove('active');}
  btn.addEventListener('click',toggle);
  if(bd)bd.addEventListener('click',close);
  sb.querySelectorAll('.nav-item').forEach(function(a){a.addEventListener('click',close);});
})();
</script>

<script>
function openImportModal() {
  const ov = document.getElementById('importOverlay');
  if (!ov) return;
  ov.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeImportModal(event) {
  if (event && event.target && event.target.id !== 'importOverlay') return;
  const ov = document.getElementById('importOverlay');
  if (!ov) return;
  ov.style.display = 'none';
  document.body.style.overflow = 'auto';
}

function openResetModal() {
  const ov = document.getElementById('resetOverlay');
  if (!ov) return;
  ov.style.display = 'flex';
  document.body.style.overflow = 'hidden';
  toggleResetMonth();
}

function closeResetModal(event) {
  if (event && event.target && event.target.id !== 'resetOverlay') return;
  const ov = document.getElementById('resetOverlay');
  if (!ov) return;
  ov.style.display = 'none';
  document.body.style.overflow = 'auto';
}

function openPasswordModal(siswaId, nisn, nama) {
  const ov = document.getElementById('passwordOverlay');
  if (!ov) return;
  document.getElementById('pwSiswaId').value = siswaId;
  document.getElementById('pwNisn').value = nisn;
  document.getElementById('pwReset').value = '0';
  document.getElementById('pwResetCheck').checked = false;
  document.getElementById('pwSiswaField').disabled = false;
  document.getElementById('pwOrtuField').disabled = false;
  document.getElementById('pwSiswaField').value = '';
  document.getElementById('pwOrtuField').value = '';
  document.getElementById('pwModalSub').textContent = nama + ' — NISN: ' + nisn;
  ov.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closePasswordModal(event) {
  if (event && event.target && event.target.id !== 'passwordOverlay') return;
  const ov = document.getElementById('passwordOverlay');
  if (!ov) return;
  ov.style.display = 'none';
  document.body.style.overflow = 'auto';
}

function toggleResetMonth() {
  const scope = document.getElementById('resetScope');
  const wrap = document.getElementById('resetMonthWrap');
  if (!scope || !wrap) return;
  wrap.style.display = (scope.value === 'month') ? 'block' : 'none';
}

/* ---- Bulk select siswa ---- */
(function() {
  function getBoxes()   { return document.querySelectorAll('input.chk-siswa'); }
  function getChecked() { return document.querySelectorAll('input.chk-siswa:checked'); }
  var chkAll  = document.getElementById('chkAllSiswa');
  var toolbar = document.getElementById('bulkToolbarSiswa');
  var countEl = document.getElementById('bulkCountSiswa');

  function updateBulkUI() {
    var n = getChecked().length;
    if (countEl) countEl.textContent = n + ' dipilih';
    if (toolbar) toolbar.style.display = n > 0 ? 'flex' : 'none';
    if (chkAll) {
      var total = getBoxes().length;
      chkAll.checked = n > 0 && n === total;
      chkAll.indeterminate = n > 0 && n < total;
    }
  }

  if (chkAll) {
    chkAll.addEventListener('change', function() {
      getBoxes().forEach(function(b) { b.checked = chkAll.checked; });
      updateBulkUI();
    });
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('chk-siswa')) updateBulkUI();
  });

  window.clearSelectionSiswa = function() {
    getBoxes().forEach(function(b) { b.checked = false; });
    if (chkAll) { chkAll.checked = false; chkAll.indeterminate = false; }
    updateBulkUI();
  };
})();

</script>

<button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Ganti tema">
  <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="2"/><path d="M12 2v2M12 20v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2 12h2M20 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
  <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
</button>

</body>
</html>
