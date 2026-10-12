/**
 * Buku Tamu Digital - SMP Negeri 28 Balikpapan
 * Form handling and helper scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    const tanggalInput = document.getElementById('tanggal_kunjungan');
    if (tanggalInput && !tanggalInput.value) {
        const today = new Date().toISOString().split('T')[0];
        tanggalInput.value = today;
    }

    const jamInput = document.getElementById('jam_kunjungan');
    if (jamInput && !jamInput.value) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        jamInput.value = `${hours}:${minutes}`;
    }

    const form = document.querySelector('form.guest-book-form');
    if (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('.btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Menyimpan Laporan Tamu...';
            }
        });
    }
});
