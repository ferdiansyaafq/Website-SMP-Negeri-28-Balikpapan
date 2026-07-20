<?php
// aplikasi/admin/siswa.php
require_once '../includes/header-kaih.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;">👨‍🎓 Data Siswa</h3>
        <button onclick="alert('Fitur tambah siswa akan segera hadir!')" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer;">
            + Tambah Siswa
        </button>
    </div>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <thead>
            <tr style="background: #f8fafc; text-align: left;">
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">NISN</th>
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Nama</th>
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Kelas</th>
                <th style="padding: 10px; border-bottom: 2px solid #e2e8f0;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">Belum ada data siswa</td>
            </tr>
        </tbody>
    </table>
</div>

<?php
?>