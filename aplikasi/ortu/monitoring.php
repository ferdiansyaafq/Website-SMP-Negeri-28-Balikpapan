<?php
// aplikasi/ortu/monitoring.php
require_once '../includes/header-kaih.php';
?>

<div class="card">
    <h3>👨‍👩‍👧 Monitoring Anak</h3>
    <div style="margin-bottom: 15px;">
        <label style="font-weight: 600; display: block; margin-bottom: 5px;">Masukkan NISN Anak:</label>
        <div style="display: flex; gap: 10px;">
            <input type="text" placeholder="Contoh: 1234567890" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #e2e8f0; flex: 1;">
            <button style="padding: 8px 16px; background: #0284c7; color: white; border: none; border-radius: 8px; cursor: pointer;">Cari</button>
        </div>
    </div>

    <div style="background: #f0f9ff; padding: 20px; border-radius: 12px; text-align: center; color: #94a3b8;">
        <div style="font-size: 48px;">👤</div>
        <p>Masukkan NISN anak untuk melihat data monitoring</p>
    </div>
</div>

<?php
?>