<?php
// aplikasi/guru/monitoring.php
require_once '../includes/header-kaih.php';
?>

<div class="card">
    <h3>📋 Monitoring Siswa per Kelas</h3>
    <div style="margin-bottom: 15px;">
        <label style="font-weight: 600; display: block; margin-bottom: 5px;">Pilih Kelas:</label>
        <select style="padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; width: 200px;">
            <option value="">-- Pilih Kelas --</option>
            <option value="7A">Kelas 7A</option>
            <option value="7B">Kelas 7B</option>
            <option value="8A">Kelas 8A</option>
        </select>
        <button style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer; margin-left: 10px;">Tampilkan</button>
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
            <tr style="background: #f8fafc; text-align: left;">
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">NISN</th>
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Nama</th>
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Status KAIH</th>
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Silakan pilih kelas terlebih dahulu</td>
            </tr>
        </tbody>
    </table>
</div>

<?php
?>