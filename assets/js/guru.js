(function () {
  function qs(id) {
    return document.getElementById(id);
  }

  function normalizeKelasName(v) {
    return String(v || '')
      .trim()
      .toLowerCase()
      .replace(/^kelas\s+/i, '');
  }

  const cssEscape = (function () {
    if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape;
    return function (value) {
      return String(value).replace(/[^a-zA-Z0-9_\u00A0-\uFFFF-]/g, function (ch) {
        return '\\' + ch;
      });
    };
  })();

  const modalOverlay = qs('modalOverlay');
  const deleteOverlay = qs('deleteOverlay');

  const modalTitle = qs('modalTitle');
  const modalSub = qs('modalSub');
  const btnSaveText = qs('btnSaveText');

  const fieldId = qs('fieldId');
  const fieldNip = qs('fieldNip');
  const fieldNama = qs('fieldNama');
  const fieldJabatan = qs('fieldJabatan');
  const fieldKelasId = qs('fieldKelasId');
  const fieldKelas = qs('fieldKelas');
  const fieldAlamat = qs('fieldAlamat');
  const fieldHp = qs('fieldHp');

  const kelasHint = qs('kelasHint');
  const kelasReqMark = qs('kelasReqMark');

  function setOverlayActive(overlay, active) {
    if (!overlay) return;
    overlay.classList.toggle('active', !!active);
  }

  function syncKelasHiddenFromSelect() {
    if (!fieldKelasId || !fieldKelas) return;
    const opt = fieldKelasId.options[fieldKelasId.selectedIndex];
    const nama = opt && opt.getAttribute ? (opt.getAttribute('data-nama') || '') : '';
    fieldKelas.value = String(nama || '').trim();

    if (kelasHint) {
      kelasHint.textContent = fieldKelas.value ? 'Terhubung ke data kelas' : 'Kosongkan jika bukan wali kelas';
      kelasHint.classList.toggle('form-hint-ok', !!fieldKelas.value);
    }
  }

  function updateWaliRuleUI() {
    if (!fieldJabatan) return;
    const jab = String(fieldJabatan.value || '').toLowerCase();
    const isWali = jab.includes('wali');
    if (kelasReqMark) kelasReqMark.style.display = isWali ? 'inline' : 'none';
    if (fieldKelasId) fieldKelasId.required = false;
  }

  function resetForm() {
    if (fieldId) fieldId.value = '0';
    if (fieldNip) fieldNip.value = '';
    if (fieldNama) fieldNama.value = '';
    if (fieldJabatan) fieldJabatan.value = '';
    if (fieldAlamat) fieldAlamat.value = '';
    if (fieldHp) fieldHp.value = '';

    if (fieldKelasId) fieldKelasId.value = '';
    if (fieldKelas) fieldKelas.value = '';

    updateWaliRuleUI();
    syncKelasHiddenFromSelect();
  }

  window.openModal = function () {
    if (modalTitle) modalTitle.textContent = 'Tambah Guru Baru';
    if (modalSub) modalSub.textContent = 'Isi data guru baru dengan lengkap';
    if (btnSaveText) btnSaveText.textContent = 'Simpan Data';

    resetForm();
    setOverlayActive(modalOverlay, true);
    setTimeout(function () {
      if (fieldNip) fieldNip.focus();
    }, 0);
  };

  window.closeModal = function (evt) {
    if (evt && modalOverlay && evt.target !== modalOverlay) return;
    setOverlayActive(modalOverlay, false);
  };

  window.openEdit = function (id, nip, nama, jabatan, kelas, alamat, nohp) {
    if (modalTitle) modalTitle.textContent = 'Edit Data Guru';
    if (modalSub) modalSub.textContent = 'Perbarui data guru yang dipilih';
    if (btnSaveText) btnSaveText.textContent = 'Simpan Perubahan';

    if (fieldId) fieldId.value = String(id || '0');
    if (fieldNip) fieldNip.value = String(nip || '');
    if (fieldNama) fieldNama.value = String(nama || '');
    if (fieldJabatan) fieldJabatan.value = String(jabatan || '');
    if (fieldAlamat) fieldAlamat.value = String(alamat || '');

    if (fieldHp) {
      let hp = String(nohp || '').trim();
      hp = hp.replace(/^\+?62\s*/i, '');
      fieldHp.value = hp;
    }

    // set kelas select by matching data-nama/text
    const kelasText = String(kelas || '').trim();
    if (fieldKelasId) {
      fieldKelasId.value = '';
      if (kelasText) {
        const target = normalizeKelasName(kelasText);
        for (let i = 0; i < fieldKelasId.options.length; i++) {
          const opt = fieldKelasId.options[i];
          const nama = opt.getAttribute('data-nama') || opt.textContent || '';
          if (normalizeKelasName(nama) === target) {
            fieldKelasId.value = opt.value;
            break;
          }
        }
      }
    }

    updateWaliRuleUI();
    syncKelasHiddenFromSelect();
    setOverlayActive(modalOverlay, true);
  };

  window.confirmDelete = function (id, name) {
    const deleteId = qs('deleteId');
    const deleteName = qs('deleteName');
    if (deleteId) deleteId.value = String(id || '');
    if (deleteName) deleteName.textContent = String(name || '');
    setOverlayActive(deleteOverlay, true);
  };

  window.closeDelete = function (evt) {
    if (evt && deleteOverlay && evt.target !== deleteOverlay) return;
    setOverlayActive(deleteOverlay, false);
  };

  // Wire events
  if (fieldKelasId) {
    fieldKelasId.addEventListener('change', syncKelasHiddenFromSelect);
  }

  if (fieldJabatan) {
    fieldJabatan.addEventListener('input', updateWaliRuleUI);
  }

  // Auto hide toast
  const toast = qs('toastMsg');
  if (toast) {
    window.setTimeout(function () {
      if (toast && toast.parentNode) toast.remove();
    }, 3500);
  }

  // Prevent form submit double-click
  const formGuru = qs('formGuru');
  const btnSave = qs('btnSave');
  if (formGuru && btnSave) {
    formGuru.addEventListener('submit', function () {
      btnSave.disabled = true;
      btnSave.style.opacity = '0.7';
    });
  }

  // If querystring contains ?edit=..., not used here, noop
  void cssEscape;
})();
