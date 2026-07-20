<?php
// aplikasi/admin/kelas.php
require_once '../includes/header-kaih.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 style="margin: 0;">🏫 Data Kelas</h3>
        <button onclick="alert('Fitur tambah kelas akan segera hadir!')" style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer;">
            + Tambah Kelas
        </button>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <div style="background: #f0f9ff; padding: 20px; border-radius: 12px; border: 1px solid #e0f2fe; text-align: center;">
            <div style="font-size: 18px; font-weight: 700; color: #0284c7;">Kelas 7A</div>
            <div style="color: #64748b; font-size: 14px;">0 Siswa</div>
        </div>
        <div style="background: #f0f9ff; padding: 20px; border-radius: 12px; border: 1px solid #e0f2fe; text-align: center;">
            <div style="font-size: 18px; font-weight: 700; color: #0284c7;">Kelas 7B</div>
            <div style="color: #64748b; font-size: 14px;">0 Siswa</div>
        </div>
    </div>
</div>

<?php
?>