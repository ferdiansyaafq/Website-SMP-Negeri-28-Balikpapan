(function () {
  function qs(id) {
    return document.getElementById(id);
  }

  function normalizeLogin(value) {
    return String(value || '').trim().replace(/\s+/g, '');
  }

  const managedStudentDefaultPassword =
    document.body && document.body.dataset && document.body.dataset.managedStudentDefaultPassword
      ? String(document.body.dataset.managedStudentDefaultPassword)
      : 'SMPN28BPP';

  function setOverlayActive(overlay, active) {
    if (!overlay) return;
    overlay.classList.toggle('active', !!active);
  }

  function setCredentialPreview(secretEl, button, visible) {
    if (!secretEl) return;
    const value = secretEl.dataset.passwordValue || managedStudentDefaultPassword;
    const isVisible = !!visible;
    secretEl.dataset.visible = isVisible ? 'true' : 'false';
    secretEl.textContent = isVisible ? value : '••••••••';
    if (button) {
      button.textContent = isVisible ? 'Sembunyikan' : 'Lihat';
    }
  }

  window.toggleCredentialPassword = function (button) {
    if (!button) return;
    const wrapper = button.closest('.credential-preview');
    if (!wrapper) return;
    const secretEl = wrapper.querySelector('.credential-secret');
    const isVisible = secretEl && secretEl.dataset.visible === 'true';
    setCredentialPreview(secretEl, button, !isVisible);
  };

  window.togglePasswordInput = function (inputId, button) {
    const input = qs(inputId);
    if (!input) return;
    const shouldShow = input.type === 'password';
    input.type = shouldShow ? 'text' : 'password';
    if (button) {
      button.textContent = shouldShow ? 'Sembunyikan' : 'Lihat';
    }
  };

  // -----------------------------
  // Tabs
  // -----------------------------
  const TAB_META = {
    admin: {
      title: 'Daftar Admin',
      badgeEl: 'role-count-admin',
      thead: 'thead-admin',
      tbody: 'tbody-admin',
      toolbar: 'toolbar-admin',
      pagination: 'pagination-wrap',
    },
    guru: {
      title: 'Akun Login Guru',
      badgeEl: 'role-count-guru',
      thead: 'thead-guru',
      tbody: 'tbody-guru',
      toolbar: 'toolbar-guru',
      pagination: null,
    },
    siswa: {
      title: 'Akun Login Siswa & Orang Tua',
      badgeEl: 'role-count-siswa',
      thead: 'thead-siswa',
      tbody: 'tbody-siswa',
      toolbar: 'toolbar-siswa',
      pagination: null,
    },
  };

  function updateUrlTab(tab) {
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', tab);
      if (tab !== 'admin') {
        url.searchParams.delete('page');
        url.searchParams.delete('q');
      }
      window.history.replaceState({}, '', url.toString());
    } catch (e) {
      // ignore
    }
  }

  window.switchTab = function (tab) {
    const key = TAB_META[tab] ? tab : 'admin';

    // Toggle role tab button
    document.querySelectorAll('.role-tab').forEach(function (btn) {
      btn.classList.toggle('active', btn.getAttribute('data-tab') === key);
    });

    // Toggle toolbars, theads, tbodys
    Object.keys(TAB_META).forEach(function (k) {
      const meta = TAB_META[k];
      const thead = qs(meta.thead);
      const tbody = qs(meta.tbody);
      const toolbar = qs(meta.toolbar);
      if (thead) thead.style.display = k === key ? '' : 'none';
      if (tbody) tbody.style.display = k === key ? '' : 'none';
      if (toolbar) toolbar.style.display = k === key ? '' : 'none';
    });

    // Admin pagination only
    const pagination = qs('pagination-wrap');
    if (pagination) {
      pagination.style.display = key === 'admin' ? '' : 'none';
    }

    // Update title and count badge (uses existing badge content)
    const tableTitle = qs('tableTitle');
    if (tableTitle) tableTitle.textContent = TAB_META[key].title;

    const tableCountBadge = qs('tableCountBadge');
    if (tableCountBadge) {
      const roleCountEl = qs(TAB_META[key].badgeEl);
      const raw = roleCountEl ? roleCountEl.textContent : '';
      tableCountBadge.textContent = (raw || '0').trim() + ' data';
    }

    updateUrlTab(key);
  };

  function initTabFromUrl() {
    try {
      const url = new URL(window.location.href);
      const tab = url.searchParams.get('tab') || 'admin';
      window.switchTab(tab);
    } catch (e) {
      window.switchTab('admin');
    }
  }

  // -----------------------------
  // Admin modal
  // -----------------------------
  const adminModal = qs('adminModal');
  const adminModalAction = qs('adminModalAction');
  const adminModalId = qs('adminModalId');
  const adminModalTitle = qs('adminModalTitle');
  const adminModalSub = qs('adminModalSub');
  const adminModalUsername = qs('adminModalUsername');
  const adminModalPassword = qs('adminModalPassword');
  const adminPwdReq = qs('adminPwdReq');
  const adminPwdHint = qs('adminPwdHint');
  const adminModalSaveTxt = qs('adminModalSaveTxt');

  window.openAdminModal = function () {
    if (adminModalAction) adminModalAction.value = 'create_admin';
    if (adminModalId) adminModalId.value = '0';
    if (adminModalTitle) adminModalTitle.textContent = 'Tambah Admin Baru';
    if (adminModalSub) adminModalSub.textContent = 'Isi username dan password akun admin baru';
    if (adminModalSaveTxt) adminModalSaveTxt.textContent = 'Simpan Admin';
    if (adminPwdReq) adminPwdReq.style.display = '';
    if (adminPwdHint) adminPwdHint.style.display = 'none';

    if (adminModalUsername) adminModalUsername.value = '';
    if (adminModalPassword) {
      adminModalPassword.value = '';
      adminModalPassword.required = true;
      adminModalPassword.placeholder = 'Password admin';
    }

    setOverlayActive(adminModal, true);
    setTimeout(function () {
      if (adminModalUsername) adminModalUsername.focus();
    }, 0);
  };

  window.editAdmin = function (id, username) {
    if (adminModalAction) adminModalAction.value = 'update_admin';
    if (adminModalId) adminModalId.value = String(id || '0');
    if (adminModalTitle) adminModalTitle.textContent = 'Edit Admin';
    if (adminModalSub) adminModalSub.textContent = 'Ubah username dan/atau password admin';
    if (adminModalSaveTxt) adminModalSaveTxt.textContent = 'Simpan Perubahan';
    if (adminPwdReq) adminPwdReq.style.display = 'none';
    if (adminPwdHint) adminPwdHint.style.display = '';

    if (adminModalUsername) adminModalUsername.value = String(username || '');
    if (adminModalPassword) {
      adminModalPassword.value = '';
      adminModalPassword.required = false;
      adminModalPassword.placeholder = 'Kosongkan jika tidak ingin mengganti password';
    }

    setOverlayActive(adminModal, true);
    setTimeout(function () {
      if (adminModalUsername) {
        adminModalUsername.focus();
        adminModalUsername.select();
      }
    }, 0);
  };

  window.closeAdminModal = function (evt) {
    if (evt && adminModal && evt.target !== adminModal) return;
    setOverlayActive(adminModal, false);
  };

  // Delete admin
  const deleteAdminModal = qs('deleteAdminModal');
  const deleteAdminId = qs('deleteAdminId');
  const deleteAdminName = qs('deleteAdminName');

  window.confirmDeleteAdmin = function (id, username) {
    if (deleteAdminId) deleteAdminId.value = String(id || '');
    if (deleteAdminName) deleteAdminName.textContent = String(username || '');
    setOverlayActive(deleteAdminModal, true);
  };

  window.closeDeleteAdmin = function (evt) {
    if (evt && deleteAdminModal && evt.target !== deleteAdminModal) return;
    setOverlayActive(deleteAdminModal, false);
  };

  // -----------------------------
  // Guru modal
  // -----------------------------
  const guruModal = qs('guruModal');
  const guruModalAction = qs('guruModalAction');
  const guruModalId = qs('guruModalId');
  const guruModalSub = qs('guruModalSub');
  const guruModalPwd = qs('guruModalPwd');
  const guruModalSaveTxt = qs('guruModalSaveTxt');
  const guruPwdReqSpan = qs('guruPwdReqSpan');
  const guruPwdHint = qs('guruPwdHint');
  const guruResetNipBtn = qs('guruResetNipBtn');
  const formGuru = qs('formGuru');
  const resetAllPasswordsModal = qs('resetAllPasswordsModal');

  window.buatAkunGuru = function (guruId, nama, nip) {
    if (guruModalAction) guruModalAction.value = 'create_guru_account';
    if (guruModalId) guruModalId.value = String(guruId || '');
    if (guruModalSub) guruModalSub.textContent = String(nama || '') + ' (NIP: ' + String(nip || '') + ')';
    if (guruModalSaveTxt) guruModalSaveTxt.textContent = 'Buat Akun';

    if (guruPwdReqSpan) guruPwdReqSpan.style.display = 'none';
    if (guruPwdHint) guruPwdHint.style.display = '';
    if (guruResetNipBtn) guruResetNipBtn.style.display = 'none';

    if (guruModalPwd) {
      guruModalPwd.value = '';
      guruModalPwd.required = false;
      guruModalPwd.placeholder = 'Password baru (kosongkan = pakai NIP)';
    }

    setOverlayActive(guruModal, true);
    setTimeout(function () {
      if (guruModalPwd) guruModalPwd.focus();
    }, 0);
  };

  window.editGuruPwd = function (guruId, nama, nip) {
    if (guruModalAction) guruModalAction.value = 'update_guru_password';
    if (guruModalId) guruModalId.value = String(guruId || '');
    if (guruModalSub) guruModalSub.textContent = String(nama || '') + ' (Username: ' + String(nip || '') + ')';
    if (guruModalSaveTxt) guruModalSaveTxt.textContent = 'Simpan Password';

    if (guruPwdReqSpan) guruPwdReqSpan.style.display = '';
    if (guruPwdHint) guruPwdHint.style.display = 'none';
    if (guruResetNipBtn) guruResetNipBtn.style.display = '';

    if (guruModalPwd) {
      guruModalPwd.value = '';
      guruModalPwd.required = true;
      guruModalPwd.placeholder = 'Password baru wajib diisi';
    }

    setOverlayActive(guruModal, true);
    setTimeout(function () {
      if (guruModalPwd) guruModalPwd.focus();
    }, 0);
  };

  window.submitGuruResetToNip = function () {
    if (!formGuru) return;
    if (guruModalAction) guruModalAction.value = 'reset_guru_to_nip';
    if (guruModalPwd) guruModalPwd.value = '';
    formGuru.submit();
  };

  window.closeGuruModal = function (evt) {
    if (evt && guruModal && evt.target !== guruModal) return;
    if (guruModalPwd) {
      guruModalPwd.type = 'password';
    }
    const guruToggleBtn = guruModal ? guruModal.querySelector('.password-input-toggle') : null;
    if (guruToggleBtn) {
      guruToggleBtn.textContent = 'Lihat';
    }
    setOverlayActive(guruModal, false);
  };

  window.openResetAllPasswordsModal = function () {
    setOverlayActive(resetAllPasswordsModal, true);
  };

  window.closeResetAllPasswordsModal = function (evt) {
    if (evt && resetAllPasswordsModal && evt.target !== resetAllPasswordsModal) return;
    setOverlayActive(resetAllPasswordsModal, false);
  };

  // Delete guru
  const deleteGuruModal = qs('deleteGuruModal');
  const deleteGuruId = qs('deleteGuruId');
  const deleteGuruName = qs('deleteGuruName');

  window.confirmDeleteGuru = function (guruId, nama) {
    if (deleteGuruId) deleteGuruId.value = String(guruId || '');
    if (deleteGuruName) deleteGuruName.textContent = String(nama || '');
    setOverlayActive(deleteGuruModal, true);
  };

  window.closeDeleteGuru = function (evt) {
    if (evt && deleteGuruModal && evt.target !== deleteGuruModal) return;
    setOverlayActive(deleteGuruModal, false);
  };

  // -----------------------------
  // Siswa modal
  // -----------------------------
  const siswaModal = qs('siswaModal');
  const siswaModalAction = qs('siswaModalAction');
  const siswaModalId = qs('siswaModalId');
  const siswaModalTitle = qs('siswaModalTitle');
  const siswaModalSub = qs('siswaModalSub');
  const siswaModalSaveTxt = qs('siswaModalSaveTxt');

  const siswaCreateInfo = qs('siswaCreateInfo');
  const siswaEditForm = qs('siswaEditForm');

  const siswaBuatNisn = qs('siswaBuatNisn');
  const siswaBuatPwdSiswa = qs('siswaBuatPwdSiswa');
  const siswaBuatOrtUser = qs('siswaBuatOrtUser');
  const siswaBuatPwdOrt = qs('siswaBuatPwdOrt');

  const siswaPwdSiswaInput = qs('siswaPwdSiswaInput');
  const siswaPwdOrtInput = qs('siswaPwdOrtInput');

  window.buatAkunSiswa = function (siswaId, nama, nisn) {
    const clean = normalizeLogin(nisn);
    const ortUser = 'ORT' + clean.toUpperCase();

    if (siswaModalAction) siswaModalAction.value = 'create_siswa_account';
    if (siswaModalId) siswaModalId.value = String(siswaId || '');

    if (siswaModalTitle) siswaModalTitle.textContent = 'Buat Akun Login Siswa';
    if (siswaModalSub) siswaModalSub.textContent = String(nama || '');
    if (siswaModalSaveTxt) siswaModalSaveTxt.textContent = 'Buat Akun';

    if (siswaCreateInfo) siswaCreateInfo.style.display = '';
    if (siswaEditForm) siswaEditForm.style.display = 'none';

    if (siswaBuatNisn) siswaBuatNisn.textContent = clean;
    if (siswaBuatPwdSiswa) siswaBuatPwdSiswa.textContent = managedStudentDefaultPassword;
    if (siswaBuatOrtUser) siswaBuatOrtUser.textContent = ortUser;
    if (siswaBuatPwdOrt) siswaBuatPwdOrt.textContent = managedStudentDefaultPassword;

    if (siswaPwdSiswaInput) siswaPwdSiswaInput.value = '';
    if (siswaPwdOrtInput) siswaPwdOrtInput.value = '';

    setOverlayActive(siswaModal, true);
  };

  window.editSiswaPwd = function (siswaId, nama, nisn) {
    const clean = normalizeLogin(nisn);

    if (siswaModalAction) siswaModalAction.value = 'update_siswa_password';
    if (siswaModalId) siswaModalId.value = String(siswaId || '');

    if (siswaModalTitle) siswaModalTitle.textContent = 'Edit Password Siswa & Orang Tua';
    if (siswaModalSub) siswaModalSub.textContent = String(nama || '') + ' (NISN: ' + clean + ')';
    if (siswaModalSaveTxt) siswaModalSaveTxt.textContent = 'Simpan Password';

    if (siswaCreateInfo) siswaCreateInfo.style.display = 'none';
    if (siswaEditForm) siswaEditForm.style.display = '';

    if (siswaPwdSiswaInput) siswaPwdSiswaInput.value = '';
    if (siswaPwdOrtInput) siswaPwdOrtInput.value = '';

    setOverlayActive(siswaModal, true);
    setTimeout(function () {
      if (siswaPwdSiswaInput) siswaPwdSiswaInput.focus();
    }, 0);
  };

  window.closeSiswaModal = function (evt) {
    if (evt && siswaModal && evt.target !== siswaModal) return;
    if (siswaPwdSiswaInput) {
      siswaPwdSiswaInput.type = 'password';
    }
    if (siswaPwdOrtInput) {
      siswaPwdOrtInput.type = 'password';
    }
    const toggles = siswaModal ? siswaModal.querySelectorAll('.password-input-toggle') : [];
    toggles.forEach(function (toggleBtn) {
      toggleBtn.textContent = 'Lihat';
    });
    setOverlayActive(siswaModal, false);
  };

  // Delete siswa
  const deleteSiswaModal = qs('deleteSiswaModal');
  const deleteSiswaId = qs('deleteSiswaId');
  const deleteSiswaName = qs('deleteSiswaName');

  window.confirmDeleteSiswa = function (siswaId, nama) {
    if (deleteSiswaId) deleteSiswaId.value = String(siswaId || '');
    if (deleteSiswaName) deleteSiswaName.textContent = String(nama || '');
    setOverlayActive(deleteSiswaModal, true);
  };

  window.closeDeleteSiswa = function (evt) {
    if (evt && deleteSiswaModal && evt.target !== deleteSiswaModal) return;
    setOverlayActive(deleteSiswaModal, false);
  };

  // -----------------------------
  // Search filters (client-side)
  // -----------------------------
  function bindRowFilter(inputEl, rowSelector) {
    if (!inputEl) return;
    inputEl.addEventListener('input', function () {
      const q = String(inputEl.value || '').toLowerCase().trim();
      const rows = document.querySelectorAll(rowSelector);
      rows.forEach(function (row) {
        const txt = (row.textContent || '').toLowerCase();
        row.style.display = q === '' || txt.indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }

  bindRowFilter(qs('guruSearch'), 'tr[data-guru-row]');
  bindRowFilter(qs('siswaSearch'), 'tr[data-siswa-row]');

  // -----------------------------
  // Toast auto-hide
  // -----------------------------
  const toast = qs('toastMsg');
  if (toast) {
    window.setTimeout(function () {
      if (toast && toast.parentNode) toast.remove();
    }, 3500);
  }

  // Prevent double-submit on modal forms
  ['formAdmin', 'formGuru', 'formSiswa'].forEach(function (fid) {
    const form = qs(fid);
    if (!form) return;
    form.addEventListener('submit', function () {
      const btn = form.querySelector('button[type="submit"]');
      if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.75';
      }
    });
  });

  // init
  initTabFromUrl();
})();
