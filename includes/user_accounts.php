<?php

function normalizeLoginIdentifier(string $value): string
{
    return preg_replace('/\s+/', '', trim($value));
}

function buildParentUsername(string $nisn): string
{
    return 'ORT' . normalizeLoginIdentifier($nisn);
}

function normalizeParentUsername(string $value): string
{
    $normalized = strtoupper(normalizeLoginIdentifier($value));
    if (strpos($normalized, 'ORT') === 0) {
        return $normalized;
    }

    return 'ORT' . $normalized;
}

function getManagedStudentDefaultPassword(): string
{
    return 'SMPN28BPP';
}

function executeOrFail(mysqli_stmt $stmt, string $message): void
{
    if (!$stmt->execute()) {
        throw new RuntimeException($message . ' (' . $stmt->error . ')');
    }
}

function fetchSingleRow(mysqli $conn, string $sql, string $types = '', array $params = []): ?array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan query database.');
    }

    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    executeOrFail($stmt, 'Gagal menjalankan query database.');
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function assertUsernameAvailable(mysqli $conn, string $username, ?int $ignoreUserId = null): void
{
    $existing = fetchSingleRow($conn, 'SELECT id FROM users WHERE username = ? LIMIT 1', 's', [$username]);
    if ($existing && (int) $existing['id'] !== (int) $ignoreUserId) {
        throw new RuntimeException('Username login ' . $username . ' sudah dipakai akun lain.');
    }
}

function upsertLinkedUser(
    mysqli $conn,
    string $role,
    string $username,
    string $defaultPassword,
    ?int $guruId = null,
    ?int $siswaId = null,
    ?string $newPassword = null,
    bool $forceResetPassword = false
): int {
    $username = normalizeLoginIdentifier($username);
    $defaultPassword = trim($defaultPassword);

    if ($username === '') {
        throw new RuntimeException('Username akun tidak boleh kosong.');
    }

    if ($defaultPassword === '') {
        throw new RuntimeException('Password default akun tidak boleh kosong.');
    }

    if ($guruId !== null) {
        $linkedUser = fetchSingleRow(
            $conn,
            'SELECT id, username, password FROM users WHERE role = ? AND guru_id = ? LIMIT 1',
            'si',
            [$role, $guruId]
        );
    } else {
        $linkedUser = fetchSingleRow(
            $conn,
            'SELECT id, username, password FROM users WHERE role = ? AND siswa_id = ? LIMIT 1',
            'si',
            [$role, $siswaId]
        );
    }

    $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);
    if ($newPassword !== null && trim($newPassword) !== '') {
        $passwordHash = password_hash(trim($newPassword), PASSWORD_BCRYPT);
    } elseif ($linkedUser && !$forceResetPassword) {
        $passwordHash = $linkedUser['password'];
    }

    if ($linkedUser) {
        $userId = (int) $linkedUser['id'];
        assertUsernameAvailable($conn, $username, $userId);
        $guruKey = $guruId ?? 0;
        $siswaKey = $siswaId ?? 0;
        $stmt = $conn->prepare('UPDATE users SET username = ?, password = ?, guru_id = NULLIF(?, 0), siswa_id = NULLIF(?, 0), updated_at = NOW() WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('Gagal menyiapkan pembaruan user.');
        }

        $stmt->bind_param('ssiii', $username, $passwordHash, $guruKey, $siswaKey, $userId);
        executeOrFail($stmt, 'Gagal memperbarui akun user.');
        $stmt->close();

        return $userId;
    }

    assertUsernameAvailable($conn, $username);
    $guruKey = $guruId ?? 0;
    $siswaKey = $siswaId ?? 0;
    $stmt = $conn->prepare('INSERT INTO users (username, password, role, guru_id, siswa_id, created_at, updated_at) VALUES (?, ?, ?, NULLIF(?, 0), NULLIF(?, 0), NOW(), NOW())');
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan pembuatan user.');
    }

    $stmt->bind_param('sssii', $username, $passwordHash, $role, $guruKey, $siswaKey);
    executeOrFail($stmt, 'Gagal membuat akun user.');
    $userId = (int) $stmt->insert_id;
    $stmt->close();

    return $userId;
}

function syncStudentAccounts(mysqli $conn, int $siswaId, string $nisn, bool $forceResetPassword = false): void
{
    $nisn = normalizeLoginIdentifier($nisn);
    $defaultPassword = getManagedStudentDefaultPassword();
    upsertLinkedUser($conn, 'siswa', $nisn, $defaultPassword, null, $siswaId, null, $forceResetPassword);
    upsertLinkedUser($conn, 'orang_tua', buildParentUsername($nisn), $defaultPassword, null, $siswaId, null, $forceResetPassword);
}

function deleteStudentAccounts(mysqli $conn, int $siswaId): void
{
    $stmt = $conn->prepare("DELETE FROM users WHERE siswa_id = ? AND role IN ('siswa', 'orang_tua')");
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan penghapusan akun siswa.');
    }

    $stmt->bind_param('i', $siswaId);
    executeOrFail($stmt, 'Gagal menghapus akun siswa.');
    $stmt->close();
}

function syncGuruAccount(mysqli $conn, int $guruId, string $nip, ?string $newPassword = null, bool $forceResetPassword = false): void
{
    $nip = normalizeLoginIdentifier($nip);
    upsertLinkedUser($conn, 'guru', $nip, $nip, $guruId, null, $newPassword, $forceResetPassword);
}

function deleteGuruAccount(mysqli $conn, int $guruId): void
{
    $stmt = $conn->prepare("DELETE FROM users WHERE guru_id = ? AND role = 'guru'");
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan penghapusan akun guru.');
    }

    $stmt->bind_param('i', $guruId);
    executeOrFail($stmt, 'Gagal menghapus akun guru.');
    $stmt->close();
}

function createAdminAccount(mysqli $conn, string $username, string $password): int
{
    $username = normalizeLoginIdentifier($username);
    $password = trim($password);

    if ($username === '' || $password === '') {
        throw new RuntimeException('Username dan password admin wajib diisi.');
    }

    assertUsernameAvailable($conn, $username);
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, created_at, updated_at) VALUES (?, ?, 'admin', NOW(), NOW())");
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan akun admin baru.');
    }

    $stmt->bind_param('ss', $username, $passwordHash);
    executeOrFail($stmt, 'Gagal membuat akun admin.');
    $userId = (int) $stmt->insert_id;
    $stmt->close();

    return $userId;
}

function updateAdminCredentials(mysqli $conn, int $userId, string $username, ?string $password = null): void
{
    $username = normalizeLoginIdentifier($username);
    if ($username === '') {
        throw new RuntimeException('Username admin wajib diisi.');
    }

    $user = fetchSingleRow($conn, "SELECT id, password FROM users WHERE id = ? AND role = 'admin' LIMIT 1", 'i', [$userId]);
    if (!$user) {
        throw new RuntimeException('Akun admin tidak ditemukan.');
    }

    assertUsernameAvailable($conn, $username, $userId);
    $passwordHash = $user['password'];
    if ($password !== null && trim($password) !== '') {
        $passwordHash = password_hash(trim($password), PASSWORD_BCRYPT);
    }

    $stmt = $conn->prepare('UPDATE users SET username = ?, password = ?, updated_at = NOW() WHERE id = ?');
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan perubahan akun admin.');
    }

    $stmt->bind_param('ssi', $username, $passwordHash, $userId);
    executeOrFail($stmt, 'Gagal memperbarui akun admin.');
    $stmt->close();
}

function syncAllManagedAccounts(mysqli $conn): array
{
    $syncedStudents = 0;
    $syncedParents = 0;
    $syncedTeachers = 0;

    $studentResult = $conn->query('SELECT id, nisn FROM siswa ORDER BY id');
    if (!$studentResult) {
        throw new RuntimeException('Gagal membaca data siswa.');
    }
    while ($student = $studentResult->fetch_assoc()) {
        syncStudentAccounts($conn, (int) $student['id'], $student['nisn']);
        $syncedStudents++;
        $syncedParents++;
    }

    $teacherResult = $conn->query('SELECT id, nip FROM guru ORDER BY id');
    if (!$teacherResult) {
        throw new RuntimeException('Gagal membaca data guru.');
    }
    while ($teacher = $teacherResult->fetch_assoc()) {
        syncGuruAccount($conn, (int) $teacher['id'], $teacher['nip']);
        $syncedTeachers++;
    }

    return [
        'siswa' => $syncedStudents,
        'orang_tua' => $syncedParents,
        'guru' => $syncedTeachers,
    ];
}

function findGuruByNip(mysqli $conn, string $nip): ?array
{
    return fetchSingleRow($conn, 'SELECT * FROM guru WHERE nip = ? LIMIT 1', 's', [normalizeLoginIdentifier($nip)]);
}

function findSiswaByNisn(mysqli $conn, string $nisn): ?array
{
    return fetchSingleRow($conn, 'SELECT * FROM siswa WHERE nisn = ? LIMIT 1', 's', [normalizeLoginIdentifier($nisn)]);
}

function findUserByRoleAndUsername(mysqli $conn, string $role, string $username): ?array
{
    return fetchSingleRow(
        $conn,
        'SELECT id, username, password, role, guru_id, siswa_id FROM users WHERE role = ? AND username = ? LIMIT 1',
        'ss',
        [$role, normalizeLoginIdentifier($username)]
    );
}

function fetchPortalProfileByUserId(mysqli $conn, int $userId): ?array
{
    $profile = fetchSingleRow(
        $conn,
        'SELECT u.id, u.username, u.role, u.guru_id, u.siswa_id,
                g.nip, g.nama_guru, g.jabatan, g.kelas AS guru_kelas, g.no_hp, g.alamat,
                s.nisn, s.nama_siswa, s.kelas AS siswa_kelas
         FROM users u
         LEFT JOIN guru g ON g.id = u.guru_id
         LEFT JOIN siswa s ON s.id = u.siswa_id
         WHERE u.id = ? LIMIT 1',
        'i',
        [$userId]
    );

    if (!$profile) {
        return null;
    }

    $profile['display_name'] = $profile['role'] === 'guru'
        ? ($profile['nama_guru'] ?: $profile['username'])
        : ($profile['nama_siswa'] ?: $profile['username']);

    return $profile;
}

function updateUserPasswordById(mysqli $conn, int $userId, string $newPassword): void
{
    $newPassword = trim($newPassword);
    if ($userId <= 0) {
        throw new RuntimeException('ID user tidak valid.');
    }
    if ($newPassword === '') {
        throw new RuntimeException('Password baru wajib diisi.');
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('Gagal menyiapkan perubahan password.');
    }

    $stmt->bind_param('si', $passwordHash, $userId);
    executeOrFail($stmt, 'Gagal menyimpan password baru.');
    if ($stmt->affected_rows < 1) {
        $stmt->close();
        throw new RuntimeException('Akun tidak ditemukan atau password tidak berubah.');
    }
    $stmt->close();
}

function changePortalUserPassword(mysqli $conn, int $userId, string $currentPassword, string $newPassword, array $allowedRoles = ['siswa', 'orang_tua']): void
{
    $currentPassword = trim($currentPassword);
    $newPassword = trim($newPassword);

    if ($currentPassword === '') {
        throw new RuntimeException('Password saat ini wajib diisi.');
    }
    if ($newPassword === '') {
        throw new RuntimeException('Password baru wajib diisi.');
    }
    if (strlen($newPassword) < 6) {
        throw new RuntimeException('Password baru minimal 6 karakter.');
    }
    if (hash_equals($currentPassword, $newPassword)) {
        throw new RuntimeException('Password baru harus berbeda dari password saat ini.');
    }

    $user = fetchSingleRow($conn, 'SELECT id, role, password FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
    if (!$user) {
        throw new RuntimeException('Akun tidak ditemukan.');
    }
    if (!in_array((string) $user['role'], $allowedRoles, true)) {
        throw new RuntimeException('Akun ini tidak diizinkan mengubah password di halaman ini.');
    }
    if (!password_verify($currentPassword, (string) $user['password'])) {
        throw new RuntimeException('Password saat ini tidak sesuai.');
    }

    updateUserPasswordById($conn, $userId, $newPassword);
}

function attemptPortalLogin(mysqli $conn, string $role, string $identifier, string $password = ''): array
{
    $role = trim($role);
    $identifier = trim($identifier);
    $password = trim($password);

    if (!in_array($role, ['siswa', 'orang_tua', 'guru'], true)) {
        return ['success' => false, 'message' => 'Kategori login tidak valid.'];
    }

    if ($role === 'guru') {
        if ($identifier === '' || $password === '') {
            return ['success' => false, 'message' => 'NIP dan password guru wajib diisi.'];
        }

        $teacher = findGuruByNip($conn, $identifier);
        if (!$teacher) {
            return ['success' => false, 'message' => 'NIP guru tidak terdaftar.'];
        }

        $conn->begin_transaction();
        try {
            syncGuruAccount($conn, (int) $teacher['id'], $teacher['nip']);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        $user = findUserByRoleAndUsername($conn, 'guru', $teacher['nip']);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'NIP atau password salah.'];
        }

        $profile = fetchPortalProfileByUserId($conn, (int) $user['id']);

        return ['success' => true, 'user' => $profile];
    }

    if ($identifier === '') {
        if ($role === 'orang_tua') {
            return ['success' => false, 'message' => 'Kode login orang tua wajib diisi (format: ORT+NISN).'];
        }
        return ['success' => false, 'message' => 'NISN wajib diisi.'];
    }

    if ($role === 'orang_tua') {
        if ($password === '') {
            return ['success' => false, 'message' => 'Password orang tua wajib diisi.'];
        }

        $parentUsername = normalizeParentUsername($identifier);
        $nisn = substr($parentUsername, 3);
        $student = findSiswaByNisn($conn, $nisn);
        if (!$student) {
            return ['success' => false, 'message' => 'Kode ORT+NISN tidak terdaftar. Contoh: ORT1234567890'];
        }

        $conn->begin_transaction();
        try {
            // false = jangan reset password yang sudah ada
            syncStudentAccounts($conn, (int) $student['id'], $student['nisn'], false);
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }

        $user = findUserByRoleAndUsername($conn, 'orang_tua', $parentUsername);
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Kode ORT+NISN atau password salah.'];
        }

        $profile = fetchPortalProfileByUserId($conn, (int) $user['id']);
        return $profile
            ? ['success' => true, 'user' => $profile]
            : ['success' => false, 'message' => 'Data akun orang tua tidak ditemukan.'];
    }

    if ($password === '') {
        return ['success' => false, 'message' => 'Password siswa wajib diisi.'];
    }

    $nisn = normalizeLoginIdentifier($identifier);
    $student = findSiswaByNisn($conn, $nisn);
    if (!$student) {
        return ['success' => false, 'message' => 'NISN siswa tidak terdaftar.'];
    }

    $conn->begin_transaction();
    try {
        // false = jangan reset password yang sudah ada
        syncStudentAccounts($conn, (int) $student['id'], $student['nisn'], false);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }

    $user = findUserByRoleAndUsername($conn, 'siswa', $student['nisn']);
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'NISN atau password salah.'];
    }

    $profile = fetchPortalProfileByUserId($conn, (int) $user['id']);
    return $profile
        ? ['success' => true, 'user' => $profile]
        : ['success' => false, 'message' => 'Data akun siswa tidak ditemukan.'];
}