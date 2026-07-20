<?php
// aplikasi/ortu/index.php
require_once '../includes/header-kaih.php';
?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="card" style="text-align: center; border-top: 4px solid #0284c7;">
        <div style="font-size: 32px; font-weight: 800; color: #0284c7;">0</div>
        <div style="color: #64748b; font-size: 14px;">Total Kegiatan Anak</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #10b981;">
        <div style="font-size: 32px; font-weight: 800; color: #10b981;">0</div>
        <div style="color: #64748b; font-size: 14px;">Telah Divalidasi</div>
    </div>
    <div class="card" style="text-align: center; border-top: 4px solid #f59e0b;">
        <div style="font-size: 32px; font-weight: 800; color: #f59e0b;">0</div>
        <div style="color: #64748b; font-size: 14px;">Menunggu Validasi</div>
    </div>
</div>

<div class="card">
    <h3>📋 Kegiatan Anak</h3>
    <p style="color: #64748b;">Belum ada kegiatan anak yang tercatat.</p>
</div>

<?php
?>