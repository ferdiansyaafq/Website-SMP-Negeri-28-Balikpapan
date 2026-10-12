/**
 * Ruang Peduli - SMP Negeri 28 Balikpapan
 * Form handling and helper scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    const tanggalInput = document.querySelector('input[name="tanggal_kejadian"]');
    if (tanggalInput && !tanggalInput.value) {
        const today = new Date().toISOString().split('T')[0];
        tanggalInput.setAttribute('max', today);
    }

    const form = document.querySelector('form[action="proses_bullying.php"]');
    if (form) {
        form.addEventListener('submit', function (e) {
            const btn = form.querySelector('.btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Mengirim Laporan...';
            }
        });
    }
});
