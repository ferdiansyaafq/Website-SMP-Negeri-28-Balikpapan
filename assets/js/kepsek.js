/**
 * assets/js/kepsek.js
 * Logika Interaktif & Visualisasi Grafik Observasi & Supervisi Guru Kepala Sekolah
 */

document.addEventListener('DOMContentLoaded', function () {
    initRubrikCalculator();
});

/**
 * Inisialisasi Kalkulator Rubrik Real-time
 */
function initRubrikCalculator() {
    const rubricForm = document.getElementById('formRubrikSupervisi');
    if (!rubricForm) return;

    const radioInputs = rubricForm.querySelectorAll('input[type="radio"][data-skor]');
    const liveScoreEl = document.getElementById('liveScoreValue');
    const livePredikatEl = document.getElementById('livePredikat');
    const liveAnsweredCountEl = document.getElementById('liveAnsweredCount');
    const totalQuestionsEl = document.getElementById('liveTotalQuestions');

    // Kumpulkan grup nama radio unik
    const questionGroups = new Set();
    radioInputs.forEach(input => questionGroups.add(input.name));
    const totalQuestions = questionGroups.size;

    if (totalQuestionsEl) {
        totalQuestionsEl.textContent = totalQuestions;
    }

    function calculateScore() {
        let totalSkor = 0;
        let answered = 0;

        questionGroups.forEach(groupName => {
            const checked = rubricForm.querySelector(`input[name="${groupName}"]:checked`);
            if (checked) {
                totalSkor += parseFloat(checked.getAttribute('data-skor') || 0);
                answered++;
            }
        });

        const rataRata = answered > 0 ? (totalSkor / totalQuestions).toFixed(1) : '0.0';
        
        if (liveScoreEl) {
            liveScoreEl.textContent = rataRata + '%';
        }

        if (liveAnsweredCountEl) {
            liveAnsweredCountEl.textContent = answered;
        }

        if (livePredikatEl) {
            const num = parseFloat(rataRata);
            let predikat = 'Belum Dinilai';
            let predClass = '';

            if (answered === totalQuestions) {
                if (num >= 86) {
                    predikat = 'Sangat Baik (A)';
                    predClass = 'predikat-sangat-baik';
                } else if (num >= 71) {
                    predikat = 'Baik (B)';
                    predClass = 'predikat-baik';
                } else if (num >= 56) {
                    predikat = 'Cukup (C)';
                    predClass = 'predikat-cukup';
                } else {
                    predikat = 'Perlu Peningkatan (D)';
                    predClass = 'predikat-kurang';
                }
            } else {
                predikat = `${answered} dari ${totalQuestions} terisi`;
                predClass = 'predikat-cukup';
            }

            livePredikatEl.textContent = predikat;
            livePredikatEl.className = 'predikat-badge ' + predClass;
        }
    }

    // Event listener untuk setiap radio button
    radioInputs.forEach(input => {
        input.addEventListener('change', function () {
            // Hapus kelas selected dari semua saudara di grup pertanyaan ini
            const groupInputs = rubricForm.querySelectorAll(`input[name="${this.name}"]`);
            groupInputs.forEach(inp => {
                const parentLabel = inp.closest('.rubrik-option-label');
                if (parentLabel) parentLabel.classList.remove('selected');
            });

            // Beri kelas selected ke label yang dipilih
            const myLabel = this.closest('.rubrik-option-label');
            if (myLabel) myLabel.classList.add('selected');

            calculateScore();
        });

        // Cek jika sudah tercentang awal (edit mode)
        if (input.checked) {
            const myLabel = input.closest('.rubrik-option-label');
            if (myLabel) myLabel.classList.add('selected');
        }
    });

    // Hitung pertama kali saat load
    calculateScore();

    // Validasi form saat submit agar semua soal dijawab
    rubricForm.addEventListener('submit', function (e) {
        let unansweredGroup = null;
        for (const groupName of questionGroups) {
            const checked = rubricForm.querySelector(`input[name="${groupName}"]:checked`);
            if (!checked) {
                unansweredGroup = groupName;
                break;
            }
        }

        if (unansweredGroup) {
            e.preventDefault();
            const firstUnanswered = rubricForm.querySelector(`input[name="${unansweredGroup}"]`);
            if (firstUnanswered) {
                const itemContainer = firstUnanswered.closest('.rubrik-item');
                if (itemContainer) {
                    itemContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    itemContainer.style.border = '2px solid #ef4444';
                    setTimeout(() => {
                        itemContainer.style.border = '';
                    }, 2500);
                }
            }
            alert('Harap isi semua poin rubrik penilaian sebelum menyimpan hasil observasi!');
        }
    });
}

/**
 * Render Grafik Radar Kompetensi Guru (Aspek Supervisi)
 * Menampilkan keseimbangan kompetensi guru di setiap dimensi
 */
function renderRadarKompetensi(canvasId, labels, dataScores) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: 'radar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Capaian Skor (%)',
                data: dataScores,
                backgroundColor: 'rgba(2, 132, 199, 0.25)',
                borderColor: '#0284c7',
                borderWidth: 3,
                pointBackgroundColor: dataScores.map(score => score < 60 ? '#ef4444' : (score < 75 ? '#f59e0b' : '#10b981')),
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: {
                        color: 'rgba(0, 0, 0, 0.08)'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)'
                    },
                    pointLabels: {
                        font: {
                            family: "'Segoe UI', sans-serif",
                            size: 12,
                            weight: '600'
                        },
                        color: '#334155'
                    },
                    suggestedMin: 0,
                    suggestedMax: 100,
                    ticks: {
                        stepSize: 25,
                        backdropColor: 'transparent',
                        callback: function (val) {
                            return val + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const val = context.raw;
                            let status = val >= 75 ? ' (Kekuatan)' : (val < 60 ? ' (Sangat Lemah / Kritis)' : ' (Perlu Bimbingan)');
                            return ` Skor: ${val}%${status}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Render Grafik Bar Indikator Rinci
 * Warna bar otomatis menunjukkan indikator lemah (Merah/Kuning) dan kuat (Hijau/Biru)
 */
function renderBarIndikator(canvasId, labels, dataScores) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    // Warna bar adaptif sesuai skor
    const barColors = dataScores.map(val => {
        if (val < 50) return '#ef4444'; // Merah - Kritis
        if (val < 75) return '#f59e0b'; // Kuning - Cukup / Lemah
        if (val < 90) return '#0284c7'; // Biru - Baik
        return '#10b981';              // Hijau - Sangat Baik
    });

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Skor Capaian (%)',
                data: dataScores,
                backgroundColor: barColors,
                borderRadius: 6,
                borderWidth: 0,
                maxBarThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y', // Bar horizontal agar nama indikator terbaca jelas
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    ticks: {
                        stepSize: 25,
                        callback: function (val) { return val + '%'; }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Segoe UI', sans-serif",
                            size: 11
                        },
                        color: '#1e293b'
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const val = ctx.raw;
                            let catatan = 'Baik';
                            if (val >= 90) catatan = 'Sangat Baik (100%)';
                            else if (val >= 75) catatan = 'Baik (75%)';
                            else if (val >= 50) catatan = 'Cukup / Perlu Perhatian (50%)';
                            else if (val >= 25) catatan = 'Kurang / Lemah (25%)';
                            else catatan = 'Sangat Kurang / Belum Ada (0%)';
                            return ` Skor: ${val}% - ${catatan}`;
                        }
                    }
                }
            }
        }
    });
}
