'use strict';

(function () {
  const cssEscape = (window.CSS && typeof window.CSS.escape === 'function')
    ? window.CSS.escape.bind(window.CSS)
    : function (value) {
        return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
      };

  const modalOverlay = document.getElementById('modalOverlay');
  const modalBox = document.getElementById('modalBox');
  const deleteOverlay = document.getElementById('deleteOverlay');

  const fieldId = document.getElementById('fieldId');
  const fieldNisn = document.getElementById('fieldNisn');
  const fieldNama = document.getElementById('fieldNama');
  const fieldKelasId = document.getElementById('fieldKelasId');
  const fieldKelas = document.getElementById('fieldKelas');
  const fieldWaliKelas = document.getElementById('fieldWaliKelas');

  const waliInfoValue = document.getElementById('waliInfoValue');

  const modalTitle = document.getElementById('modalTitle');
  const modalSub = document.getElementById('modalSub');
  const btnSaveText = document.getElementById('btnSaveText');

  const deleteId = document.getElementById('deleteId');
  const deleteName = document.getElementById('deleteName');

  function setOverlayActive(overlay, isActive) {
    if (!overlay) return;
    overlay.classList.toggle('active', !!isActive);
  }

  function getSelectedKelasOption() {
    if (!fieldKelasId) return null;
    return fieldKelasId.options[fieldKelasId.selectedIndex] || null;
  }

  function updateConnectedWali() {
    const option = getSelectedKelasOption();
    if (!option) return;

    const kelasNama = option.getAttribute('data-nama') || '';
    const waliNama = option.getAttribute('data-wali') || '';
    const kelasId = option.value || '';

    if (fieldKelas) fieldKelas.value = kelasNama;
    if (fieldWaliKelas) fieldWaliKelas.value = kelasId;

    if (waliInfoValue) {
      if (!kelasId) {
        waliInfoValue.textContent = 'Pilih kelas untuk menghubungkan wali kelas otomatis.';
      } else if (waliNama.trim()) {
        waliInfoValue.textContent = waliNama;
      } else {
        waliInfoValue.textContent = 'Belum ada wali kelas untuk kelas ini.';
      }
    }
  }

  window.openModal = function openModal() {
    if (fieldId) fieldId.value = '0';
    if (fieldNisn) fieldNisn.value = '';
    if (fieldNama) fieldNama.value = '';
    if (fieldKelasId) fieldKelasId.value = '';
    if (fieldKelas) fieldKelas.value = '';
    if (fieldWaliKelas) fieldWaliKelas.value = '';

    if (modalTitle) modalTitle.textContent = 'Tambah Siswa';
    if (modalSub) modalSub.textContent = 'Isi data siswa baru';
    if (btnSaveText) btnSaveText.textContent = 'Simpan Data';

    updateConnectedWali();
    setOverlayActive(modalOverlay, true);

    setTimeout(() => {
      if (fieldNisn) fieldNisn.focus();
    }, 0);
  };

  window.openEdit = function openEdit(id, nisn, nama, kelasNama, waliKelasId) {
    if (fieldId) fieldId.value = String(id || 0);
    if (fieldNisn) fieldNisn.value = nisn || '';
    if (fieldNama) fieldNama.value = nama || '';

    if (modalTitle) modalTitle.textContent = 'Edit Siswa';
    if (modalSub) modalSub.textContent = 'Perbarui data siswa';
    if (btnSaveText) btnSaveText.textContent = 'Simpan Perubahan';

    if (fieldKelasId) {
      const idValue = String(waliKelasId || '').trim();
      if (idValue && fieldKelasId.querySelector(`option[value="${cssEscape(idValue)}"]`)) {
        fieldKelasId.value = idValue;
      } else {
        // Fallback by name
        const options = Array.from(fieldKelasId.options);
        const match = options.find(o => (o.getAttribute('data-nama') || '') === (kelasNama || ''));
        fieldKelasId.value = match ? match.value : '';
      }
    }

    updateConnectedWali();
    setOverlayActive(modalOverlay, true);

    setTimeout(() => {
      if (fieldNisn) fieldNisn.focus();
    }, 0);
  };

  window.closeModal = function closeModal(e) {
    if (e && modalBox && e.target && modalBox.contains(e.target)) {
      return;
    }
    setOverlayActive(modalOverlay, false);
  };

  window.confirmDelete = function confirmDelete(id, nama) {
    if (deleteId) deleteId.value = String(id || '');
    if (deleteName) deleteName.textContent = nama || '';
    setOverlayActive(deleteOverlay, true);
  };

  window.closeDelete = function closeDelete(e) {
    const deleteBox = document.getElementById('deleteBox');
    if (e && deleteBox && e.target && deleteBox.contains(e.target)) {
      return;
    }
    setOverlayActive(deleteOverlay, false);
  };

  // Events
  if (fieldKelasId) {
    fieldKelasId.addEventListener('change', updateConnectedWali);
  }

  // Auto-hide toast
  const toast = document.getElementById('toastMsg');
  if (toast) {
    setTimeout(() => {
      if (toast && toast.parentElement) toast.remove();
    }, 4000);
  }
})();
