(function () {
  function qs(id) {
    return document.getElementById(id);
  }

  const modalOverlay = qs('modalOverlay');
  const deleteOverlay = qs('deleteOverlay');

  const modalTitle = qs('modalTitle');
  const modalSub = qs('modalSub');
  const btnSaveText = qs('btnSaveText');

  const fieldId = qs('fieldId');
  const fieldNama = qs('fieldNamaKelas');

  const deleteId = qs('deleteId');
  const deleteName = qs('deleteName');
  const deleteWarning = qs('deleteWarning');
  const btnDeleteConfirm = qs('btnDeleteConfirm');

  function setOverlayActive(overlay, active) {
    if (!overlay) return;
    overlay.classList.toggle('active', !!active);
  }

  function resetForm() {
    if (fieldId) fieldId.value = '0';
    if (fieldNama) fieldNama.value = '';
  }

  window.openModal = function () {
    if (modalTitle) modalTitle.textContent = 'Tambah Kelas Baru';
    if (modalSub) modalSub.textContent = 'Masukkan nama kelas yang akan ditambahkan';
    if (btnSaveText) btnSaveText.textContent = 'Simpan Kelas';

    resetForm();
    setOverlayActive(modalOverlay, true);
    setTimeout(function () {
      if (fieldNama) fieldNama.focus();
    }, 0);
  };

  window.openEdit = function (id, namaKelas) {
    if (modalTitle) modalTitle.textContent = 'Edit Data Kelas';
    if (modalSub) modalSub.textContent = 'Perbarui nama kelas yang dipilih';
    if (btnSaveText) btnSaveText.textContent = 'Simpan Perubahan';

    if (fieldId) fieldId.value = String(id || '0');
    if (fieldNama) fieldNama.value = String(namaKelas || '');

    setOverlayActive(modalOverlay, true);
    setTimeout(function () {
      if (fieldNama) {
        fieldNama.focus();
        fieldNama.select();
      }
    }, 0);
  };

  window.closeModal = function (evt) {
    if (evt && modalOverlay && evt.target !== modalOverlay) return;
    setOverlayActive(modalOverlay, false);
  };

  window.setKelas = function (nama) {
    if (!fieldNama) return;
    fieldNama.value = String(nama || '').trim();
    fieldNama.focus();
  };

  window.confirmDelete = function (id, nama, jumlahSiswa) {
    if (deleteId) deleteId.value = String(id || '');
    if (deleteName) deleteName.textContent = String(nama || '');

    const j = Number(jumlahSiswa || 0);
    if (deleteWarning) {
      if (j > 0) {
        deleteWarning.style.display = 'block';
        deleteWarning.textContent = 'Kelas ini masih digunakan oleh ' + j + ' siswa. Hapus/ubah wali kelas siswa dulu.';
      } else {
        deleteWarning.style.display = 'none';
        deleteWarning.textContent = '';
      }
    }

    // disable delete button client-side when still used by siswa
    if (btnDeleteConfirm) {
      btnDeleteConfirm.disabled = j > 0;
      btnDeleteConfirm.style.opacity = j > 0 ? '0.6' : '1';
      btnDeleteConfirm.style.cursor = j > 0 ? 'not-allowed' : 'pointer';
      btnDeleteConfirm.title = j > 0 ? 'Tidak bisa menghapus: masih digunakan siswa' : '';
    }

    setOverlayActive(deleteOverlay, true);
  };

  window.closeDelete = function (evt) {
    if (evt && deleteOverlay && evt.target !== deleteOverlay) return;
    setOverlayActive(deleteOverlay, false);
  };

  // Auto hide toast
  const toast = qs('toastMsg');
  if (toast) {
    window.setTimeout(function () {
      if (toast && toast.parentNode) toast.remove();
    }, 3500);
  }

  // Prevent double submit
  const formKelas = qs('formKelas');
  const btnSave = qs('btnSave');
  if (formKelas && btnSave) {
    formKelas.addEventListener('submit', function () {
      btnSave.disabled = true;
      btnSave.style.opacity = '0.7';
    });
  }
})();
