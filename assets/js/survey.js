/**
 * Survei Pelayanan - SMP Negeri 28 Balikpapan
 * Emoji satisfaction rating script
 */

document.addEventListener('DOMContentLoaded', function () {
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const feedbackIndicator = document.getElementById('selectedRatingFeedback');

    const descriptions = {
        '1': '😡 Sangat Tidak Puas - Kami mohon maaf atas ketidaknyamanan Anda.',
        '2': '🙁 Tidak Puas - Kami akan berupaya meningkatkan kualitas layanan.',
        '3': '😐 Cukup - Terima kasih atas masukan obyektif Anda.',
        '4': '🙂 Puas - Terima kasih, senang bisa melayani Anda dengan baik.',
        '5': '🤩 Sangat Puas - Luar biasa! Terima kasih atas apresiasi Anda.'
    };

    function updateFeedback(value) {
        if (!feedbackIndicator) return;
        if (descriptions[value]) {
            feedbackIndicator.textContent = descriptions[value];
            feedbackIndicator.style.opacity = '1';
        } else {
            feedbackIndicator.textContent = 'Pilih salah satu ekspresi kepuasan di atas.';
            feedbackIndicator.style.opacity = '0.7';
        }
    }

    ratingInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            updateFeedback(this.value);
        });

        if (input.checked) {
            updateFeedback(input.value);
        }
    });

    const form = document.querySelector('form.survey-form-tag');
    if (form) {
        form.addEventListener('submit', function (e) {
            const hasCheckedRating = document.querySelector('input[name="rating"]:checked');
            if (!hasCheckedRating) {
                e.preventDefault();
                alert('Silakan pilih salah satu ekspresi kepuasan pelayanan terlebih dahulu.');
                return;
            }

            const btn = form.querySelector('.btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '⏳ Mengirim Survei & Masukan...';
            }
        });
    }
});
