/**
 * SI-KDO BMD — Aplikasi Pengelolaan Kendaraan Dinas Operasional
 * Client-Side Application Controller (Vanilla ES6+)
 */

(() => {
  'use strict';

  // ==========================================================================
  // 1. STATE & CACHES
  // ==========================================================================
  const state = {
    currentView: 'dashboard',
    previousView: 'dashboard',
    kdoViewMode: 'table', // 'table' | 'cards'
    masterPd: [],
    masterPns: [],
    masterKdo: [],
    pendingDelete: null, // { entity, id, label }
    activeCompletePakai: null, // { id, km_awal, nomor_surat_tugas, nopol }
  };

  const viewHeadings = {
    dashboard: 'heading-dashboard',
    kdo: 'heading-kdo',
    pemakaian: 'heading-pemakaian',
    pns: 'heading-pns',
    pd: 'heading-pd',
    detail: 'detail-heading',
    form: 'form-page-heading',
  };

  const entityNames = {
    kdo: 'Data KDO',
    pemakaian: 'Pemakaian KDO',
    pns: 'Pegawai (PNS)',
    pd: 'Perangkat Daerah',
  };

  // ==========================================================================
  // 2. HELPERS: SANITIZATION & FORMATTING
  // ==========================================================================
  function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getInitials(name) {
    if (!name) return 'ASN';
    const cleaned = String(name)
      .replace(/\b(Dr|Dra|Drs|Ir|H|Hj|apt|S\.Pd|M\.Pd|M\.Si|S\.AP|M\.Kes|S\.Farm|M\.K\.M|S\.T|M\.T|M\.Eng|A\.Md|Ak|M\.M|S\.Si\.T|M\.Ling|S\.E|M\.Ak)\b\.?,?/gi, ' ')
      .trim();
    const parts = cleaned.split(/\s+/).filter(Boolean);
    if (!parts.length) return 'ASN';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }

  function formatDateId(dateStr) {
    if (!dateStr) return 'Masih Berlangsung';
    const parts = String(dateStr).split('-');
    if (parts.length !== 3) return escapeHtml(dateStr);
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
      'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    const m = parseInt(parts[1], 10) - 1;
    return `${parts[2]} ${months[m] || parts[1]} ${parts[0]}`;
  }

  function formatNumber(num) {
    if (num === null || num === undefined || num === '') return '-';
    return Number(num).toLocaleString('id-ID');
  }

  function renderKondisiBadge(kondisi) {
    const k = String(kondisi || 'Baik');
    if (k === 'Baik') {
      return '<span class="status-badge badge-baik"><span aria-hidden="true">✔</span> Baik</span>';
    }
    if (k === 'Rusak Ringan') {
      return '<span class="status-badge badge-rusak-ringan"><span aria-hidden="true">⚠</span> Rusak Ringan</span>';
    }
    return '<span class="status-badge badge-rusak-berat"><span aria-hidden="true">✖</span> Rusak Berat</span>';
  }

  function renderStatusPakaiBadge(status) {
    const s = String(status || 'Digunakan');
    if (s === 'Selesai') {
      return '<span class="status-badge badge-selesai"><span aria-hidden="true">✔</span> Selesai</span>';
    }
    if (s === 'Terjadwal') {
      return '<span class="status-badge badge-terjadwal"><span aria-hidden="true">📅</span> Terjadwal</span>';
    }
    return '<span class="status-badge badge-digunakan"><span aria-hidden="true">🚗</span> Digunakan</span>';
  }

  let toastTimer = null;
  function showToast(message, type = 'success') {
    const banner = document.getElementById('toast-banner');
    if (!banner) return;
    clearTimeout(toastTimer);
    banner.className = `toast-banner ${type === 'error' ? 'toast-error' : 'toast-success'}`;
    banner.textContent = (type === 'error' ? '⚠️ ' : '✔ ') + message;
    banner.hidden = false;
    toastTimer = setTimeout(() => {
      banner.hidden = true;
    }, 4500);
  }

  function initLiveClock() {
    const el = document.getElementById('live-clock-label');
    if (!el) return;
    const updateClock = () => {
      const now = new Date();
      try {
        const datePart = new Intl.DateTimeFormat('id-ID', {
          weekday: 'long',
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        }).format(now);
        const timePart = new Intl.DateTimeFormat('id-ID', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: false
        }).format(now);
        el.textContent = `${datePart} • ${timePart} WIB`;
      } catch (_) {
        // Fallback static text
      }
    };
    updateClock();
    setInterval(updateClock, 1000);
  }

  // ==========================================================================
  // 3. API CLIENT
  // ==========================================================================
  async function apiRequest(endpoint, options = {}) {
    const config = {
      method: options.method || 'GET',
      headers: {
        'Accept': 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {})
      },
      ...(options.body ? { body: JSON.stringify(options.body) } : {})
    };

    const response = await fetch(endpoint, config);
    let payload = {};
    try {
      payload = await response.json();
    } catch (_) {
      payload = { status: 'error', message: 'Respons server tidak valid.' };
    }

    if (!response.ok) {
      const err = new Error(payload.message || `HTTP Error ${response.status}`);
      err.status = response.status;
      err.errors = payload.errors || {};
      throw err;
    }
    return payload;
  }

  // ==========================================================================
  // 4. MODERN WEB GUIDANCE: FORM VALIDATION & ARIA SYNC
  // ==========================================================================
  const linkError = (el, shown) => {
    const errorId = el.dataset?.error;
    if (!errorId) return;
    const ids = (el.getAttribute('aria-describedby') || '')
      .split(/\s+/)
      .filter((id) => id && id !== errorId);
    if (shown) ids.push(errorId);
    if (ids.length) el.setAttribute('aria-describedby', ids.join(' '));
    else el.removeAttribute('aria-describedby');
  };

  const announcer = document.createElement('div');
  announcer.setAttribute('aria-live', 'polite');
  announcer.classList.add('visually-hidden');
  document.body.append(announcer);
  let announceTimer;
  let announcedField;

  const spokenText = (node) => {
    const copy = node.cloneNode(true);
    copy.querySelectorAll('[aria-hidden="true"]').forEach((n) => n.remove());
    copy.querySelectorAll('[role="img"][aria-label]').forEach((n) => n.replaceWith(`${n.getAttribute('aria-label')}: `));
    return copy.textContent.replace(/\s+/g, ' ').trim();
  };

  const announceError = (el) => {
    const error = document.getElementById(el.dataset.error);
    if (!error) return;
    const label = el.labels?.[0] ? `${spokenText(el.labels[0])}: ` : '';
    clearTimeout(announceTimer);
    announcer.textContent = '';
    announcedField = el;
    announceTimer = setTimeout(() => {
      announcer.textContent = label + spokenText(error);
    }, 500);
  };

  const flagged = new WeakSet();
  const announced = new WeakSet();

  const syncAria = (el, announce = false) => {
    if (!el.matches?.('input, select, textarea')) return;
    let invalid = false;
    try {
      invalid = el.matches(':user-invalid') || el.classList.contains('user-invalid-fallback');
    } catch (_) {
      invalid = el.classList.contains('user-invalid-fallback');
    }

    if (invalid) {
      if (announce && !announced.has(el)) {
        announceError(el);
        announced.add(el);
      }
      el.setAttribute('aria-invalid', 'true');
      linkError(el, true);
      flagged.add(el);
    } else if (flagged.has(el) || el.getAttribute('aria-invalid') !== 'true') {
      el.setAttribute('aria-invalid', 'false');
      linkError(el, false);
      flagged.delete(el);
      announced.delete(el);
      if (announcedField === el) {
        clearTimeout(announceTimer);
        announcer.textContent = '';
      }
    }
  };

  const UserInvalidFallback = (() => {
    const dirtyState = new WeakMap();

    const updateState = (input) => {
      const isValid = input.validity.valid;
      input.classList.toggle('user-invalid-fallback', !isValid);
      input.classList.toggle('user-valid-fallback', isValid);
      input.setAttribute('aria-invalid', isValid ? 'false' : 'true');
      linkError(input, !isValid);
    };

    const handleEvent = (event) => {
      const input = event.target;
      if (event.type === 'reset') {
        const controls = input.elements || [];
        for (const control of controls) {
          dirtyState.delete(control);
          control.classList.remove('user-invalid-fallback', 'user-valid-fallback');
          if (control.matches?.('input, select, textarea')) control.setAttribute('aria-invalid', 'false');
          if (control.dataset?.error) linkError(control, false);
        }
        return;
      }
      if (!input.checkValidity) return;
      if (event.type === 'invalid') {
        dirtyState.set(input, { hasInteracted: true, hasBlurred: true });
        updateState(input);
        return;
      }
      if (event.type === 'input' || event.type === 'change') {
        const st = dirtyState.get(input) || { hasInteracted: false, hasBlurred: false };
        st.hasInteracted = true;
        dirtyState.set(input, st);
        if (st.hasBlurred) updateState(input);
      } else if (event.type === 'blur') {
        const st = dirtyState.get(input) || { hasInteracted: false, hasBlurred: false };
        st.hasBlurred = true;
        dirtyState.set(input, st);
        if (st.hasInteracted) updateState(input);
      }
    };

    const init = (root = document) => {
      if (CSS.supports('selector(:user-invalid)')) return;
      root.querySelectorAll('input, select, textarea').forEach((el) => {
        if (!el.hasAttribute('aria-invalid')) el.setAttribute('aria-invalid', 'false');
      });
      root.addEventListener('blur', handleEvent, true);
      root.addEventListener('input', handleEvent);
      root.addEventListener('change', handleEvent);
      root.addEventListener('invalid', handleEvent, true);
      root.addEventListener('reset', handleEvent, true);
    };

    return { init };
  })();

  function initFormValidationAccessibility() {
    document.querySelectorAll('input, select, textarea').forEach((el) => syncAria(el));
    document.addEventListener('blur', (e) => syncAria(e.target, true), true);
    document.addEventListener('input', (e) => {
      if (e.target.matches?.('input, select, textarea')) {
        e.target.setCustomValidity('');
        e.target.classList.remove('user-invalid-fallback');
        if (e.target.hasAttribute('aria-invalid')) syncAria(e.target);
      }
    });
    document.addEventListener('change', (e) => {
      if (e.target.matches?.('input, select, textarea')) {
        e.target.setCustomValidity('');
        e.target.classList.remove('user-invalid-fallback');
        syncAria(e.target, true);
      }
    }, true);
    document.addEventListener('invalid', (e) => {
      announced.add(e.target);
      e.target.classList.add('user-invalid-fallback');
      syncAria(e.target);
    }, true);
    document.addEventListener('reset', (e) => {
      setTimeout(() => [...(e.target.elements || [])].forEach((el) => {
        el.classList.remove('user-invalid-fallback');
        syncAria(el);
      }));
    }, true);
    UserInvalidFallback.init(document);
  }

  // ==========================================================================
  // 5. MODERN WEB GUIDANCE: TOP-LAYER DIALOG ANIMATION & LIGHT-DISMISS
  // ==========================================================================
  let supportsDisplayTransition;
  function canTransitionDisplay() {
    if (supportsDisplayTransition !== undefined) return supportsDisplayTransition;
    if (!window.CSS?.supports?.('transition-behavior', 'allow-discrete') || !document.body) {
      return false;
    }
    const probe = document.createElement('div');
    probe.style.cssText = 'transition: display 1s allow-discrete !important; display: block;';
    document.body.appendChild(probe);
    getComputedStyle(probe).display;
    probe.style.display = 'none';
    supportsDisplayTransition = getComputedStyle(probe).display === 'block';
    probe.remove();
    return supportsDisplayTransition;
  }

  function supportsTopLayerExit() {
    return window.CSS?.supports?.('overlay', 'auto') && canTransitionDisplay();
  }

  async function closeTopLayer(dialog, returnValue = '') {
    if (!dialog || !dialog.open) return;
    if (!supportsTopLayerExit()) {
      dialog.setAttribute('data-closing', '');
      const animations = dialog.getAnimations({ subtree: true });
      if (animations.length > 0) {
        await Promise.race([
          Promise.allSettled(animations.map((a) => a.finished)),
          new Promise((r) => setTimeout(r, 1000)),
        ]);
      }
      if (!dialog.hasAttribute('data-closing')) return;
      dialog.removeAttribute('data-closing');
    }
    dialog.close(returnValue);
  }

  function attachDialogEnhancements(dialog) {
    if (!dialog) return;

    // Fallback for browsers without closedby support (from light-dismiss-a-dialog guide)
    if (!('closedBy' in HTMLDialogElement.prototype)) {
      dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const rect = dialog.getBoundingClientRect();
        const isDialogContent = (
          rect.top <= event.clientY &&
          event.clientY <= rect.top + rect.height &&
          rect.left <= event.clientX &&
          event.clientX <= rect.left + rect.width
        );
        if (isDialogContent) return;
        closeTopLayer(dialog, 'cancel');
      });
    }

    // Route native close requests (Esc) through closeTopLayer so exit animates
    dialog.addEventListener('cancel', (event) => {
      event.preventDefault();
      closeTopLayer(dialog, 'cancel');
    });
  }

  function initDialogs() {
    const deleteDialog = document.getElementById('delete-dialog');
    const completeDialog = document.getElementById('complete-pakai-dialog');

    attachDialogEnhancements(deleteDialog);
    attachDialogEnhancements(completeDialog);

    if (deleteDialog) {
      deleteDialog.addEventListener('close', async () => {
        const action = deleteDialog.returnValue;
        deleteDialog.returnValue = '';
        if (action === 'delete' && state.pendingDelete) {
          const target = { ...state.pendingDelete };
          state.pendingDelete = null;
          await executeDelete(target.entity, target.id);
        } else {
          state.pendingDelete = null;
        }
      });
    }

    if (completeDialog) {
      completeDialog.addEventListener('close', () => {
        completeDialog.returnValue = '';
        state.activeCompletePakai = null;
      });
    }
  }

  function promptDelete(entity, id, label) {
    const dialog = document.getElementById('delete-dialog');
    const msg = document.getElementById('delete-dialog-message');
    if (!dialog || !msg) return;

    const entityLabels = {
      kdo: 'Kendaraan Dinas Operasional (KDO)',
      pemakaian: 'Catatan Pemakaian KDO',
      pns: 'Pegawai Negeri Sipil (PNS)',
      pd: 'Perangkat Daerah (PD)'
    };

    state.pendingDelete = { entity, id };
    msg.textContent = `Apakah Anda yakin ingin menghapus ${entityLabels[entity] || 'data'} "${label}"? Tindakan ini tidak dapat dibatalkan.`;
    dialog.showModal();
  }

  async function executeDelete(entity, id) {
    const endpoints = {
      kdo: 'api/kdo.php',
      pemakaian: 'api/pemakaian.php',
      pns: 'api/pns.php',
      pd: 'api/pd.php'
    };
    const endpoint = endpoints[entity];
    if (!endpoint) return;

    try {
      const res = await apiRequest(`${endpoint}?id=${encodeURIComponent(id)}`, {
        method: 'DELETE',
        body: { id }
      });
      showToast(res.message || 'Data berhasil dihapus.', 'success');
      await refreshMasterData();

      if (state.currentView === 'detail') {
        switchView(entity);
      } else {
        refreshCurrentView();
      }
    } catch (err) {
      showToast(err.message || 'Gagal menghapus data.', 'error');
    }
  }

  // Quick Complete Pemakaian Modal
  function openCompletePakaiDialog(id, kmAwal, noSurat, nopol) {
    const dialog = document.getElementById('complete-pakai-dialog');
    const form = document.getElementById('complete-pakai-form');
    if (!dialog || !form) return;

    form.reset();
    state.activeCompletePakai = { id, km_awal: Number(kmAwal || 0) };
    document.getElementById('complete-pakai-id').value = id;
    document.getElementById('complete-tgl-selesai').value = new Date().toISOString().slice(0, 10);
    document.getElementById('complete-km-awal-label').textContent = formatNumber(kmAwal || 0);
    document.getElementById('complete-km-akhir').value = Number(kmAwal || 0) + 25;
    document.getElementById('complete-dialog-subtitle').textContent =
      `Surat Tugas ${noSurat} — Kendaraan [${nopol}]. Catat KM akhir dan kondisi fisik kendaraan saat kembali.`;

    dialog.showModal();
  }

  async function handleCompletePakaiSubmit(e) {
    e.preventDefault();
    const form = e.currentTarget;
    const dialog = document.getElementById('complete-pakai-dialog');
    const kmAkhirInput = document.getElementById('complete-km-akhir');
    const kmAwal = state.activeCompletePakai?.km_awal || 0;
    const kmAkhir = Number(kmAkhirInput.value || 0);

    if (kmAkhir < kmAwal) {
      kmAkhirInput.setCustomValidity(`KM Akhir tidak boleh kurang dari KM Awal (${kmAwal} KM).`);
    } else {
      kmAkhirInput.setCustomValidity('');
    }

    if (!form.checkValidity()) {
      const firstInvalid = form.querySelector(':invalid');
      if (firstInvalid) {
        firstInvalid.classList.add('user-invalid-fallback');
        syncAria(firstInvalid, true);
        firstInvalid.focus();
      }
      return;
    }

    const submitBtn = document.getElementById('btn-submit-complete');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(form);
    const payload = { status_pemakaian: 'Selesai' };
    formData.forEach((v, k) => { payload[k] = v; });

    try {
      const res = await apiRequest('api/pemakaian.php', {
        method: 'PUT',
        body: payload
      });
      await closeTopLayer(dialog, 'saved');
      showToast(res.message || 'Penugasan KDO telah diselesaikan & kondisi diperbarui.', 'success');
      await refreshMasterData();
      if (state.currentView === 'detail') {
        await openDetail('pemakaian', payload.id);
      } else {
        refreshCurrentView();
      }
    } catch (err) {
      showToast(err.message || 'Gagal menyelesaikan pemakaian.', 'error');
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  // ==========================================================================
  // 6. MODERN WEB GUIDANCE: SORTABLE DATA TABLES (sortable-table)
  // ==========================================================================
  const collator = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });

  function sortTableByHeaderButton(button) {
    const th = button.closest('th');
    const table = button.closest('table');
    const tbody = table?.tBodies?.[0];
    if (!th || !table || !tbody) return;

    const rows = [...tbody.rows];
    if (rows.length <= 1 && rows[0]?.querySelector('.empty-state')) return;

    const direction = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';
    for (const header of table.tHead.rows[0].cells) {
      header.removeAttribute('aria-sort');
    }
    th.setAttribute('aria-sort', direction);

    const colIndex = th.cellIndex;
    const type = th.dataset.type || 'text';
    const factor = direction === 'ascending' ? 1 : -1;

    const decorated = rows.map((row) => {
      const cell = row.cells[colIndex];
      const raw = cell?.dataset?.value ?? cell?.textContent?.trim() ?? '';
      return {
        row,
        val: type === 'number' ? Number(raw) || 0 : raw
      };
    });

    decorated.sort((a, b) =>
      factor * (type === 'number' ? a.val - b.val : collator.compare(a.val, b.val))
    );

    const reorderDom = () => {
      for (const item of decorated) {
        if (typeof tbody.moveBefore === 'function') {
          tbody.moveBefore(item.row, null);
        } else {
          tbody.appendChild(item.row);
        }
      }
    };

    reorderDom();

    const statusEl = document.getElementById('table-sort-status');
    if (statusEl) {
      const dirLabel = direction === 'ascending' ? 'naik (A-Z)' : 'turun (Z-A)';
      statusEl.textContent = `Diurutkan berdasarkan ${button.textContent.trim()}, ${dirLabel}, ${rows.length} baris`;
    }
  }

  // ==========================================================================
  // 7. VIEW ROUTING WITH SAME-DOCUMENT VIEW TRANSITIONS
  // ==========================================================================
  function switchView(viewName, skipLoad = false) {
    const sections = {
      dashboard: document.getElementById('view-dashboard'),
      kdo: document.getElementById('view-kdo'),
      pemakaian: document.getElementById('view-pemakaian'),
      pns: document.getElementById('view-pns'),
      pd: document.getElementById('view-pd'),
      detail: document.getElementById('view-detail'),
      form: document.getElementById('view-form'),
    };

    if (state.currentView !== viewName && !['detail', 'form'].includes(state.currentView)) {
      state.previousView = state.currentView;
    }
    state.currentView = viewName;

    const applyDomUpdate = () => {
      Object.entries(sections).forEach(([key, sec]) => {
        if (sec) sec.hidden = key !== viewName;
      });

      document.querySelectorAll('.nav-btn[data-nav]').forEach((btn) => {
        if (btn.dataset.nav === viewName) {
          btn.setAttribute('aria-current', 'page');
        } else {
          btn.removeAttribute('aria-current');
        }
      });
    };

    const routeHeadingFocus = () => {
      if (viewName === 'form') return; // Form view focuses its first input
      const headingId = viewHeadings[viewName];
      const heading = headingId ? document.getElementById(headingId) : null;
      if (heading) {
        heading.focus({ preventScroll: true });
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    if (!document.startViewTransition) {
      applyDomUpdate();
      if (!skipLoad) refreshCurrentView();
      routeHeadingFocus();
      return;
    }

    const transition = document.startViewTransition(() => {
      applyDomUpdate();
    });
    if (!skipLoad) refreshCurrentView();
    transition.finished.finally(() => {
      routeHeadingFocus();
    });
  }

  function refreshCurrentView() {
    switch (state.currentView) {
      case 'dashboard':
        loadDashboard();
        break;
      case 'kdo':
        loadKdoList();
        break;
      case 'pemakaian':
        loadPemakaianList();
        break;
      case 'pns':
        loadPnsList();
        break;
      case 'pd':
        loadPdList();
        break;
    }
  }

  async function refreshMasterData() {
    try {
      const [pdRes, pnsRes, kdoRes, pakaiRes] = await Promise.all([
        apiRequest('api/pd.php'),
        apiRequest('api/pns.php'),
        apiRequest('api/kdo.php'),
        apiRequest('api/pemakaian.php')
      ]);

      state.masterPd = pdRes.data || [];
      state.masterPns = pnsRes.data || [];
      state.masterKdo = kdoRes.data || [];

      const setCount = (id, count) => {
        const el = document.getElementById(id);
        if (el) el.textContent = count;
      };
      setCount('nav-count-pd', state.masterPd.length);
      setCount('nav-count-pns', state.masterPns.length);
      setCount('nav-count-kdo', state.masterKdo.length);
      setCount('nav-count-pemakaian', (pakaiRes.data || []).length);

      populateDropdowns();
    } catch (err) {
      console.error('Gagal memuat master data:', err);
    }
  }

  function populateDropdowns() {
    const filterPdIds = ['filter-kdo-pd', 'filter-pakai-pd', 'filter-pns-pd'];
    filterPdIds.forEach((selectId) => {
      const sel = document.getElementById(selectId);
      if (!sel) return;
      const currentVal = sel.value;
      sel.innerHTML = '<option value="">Semua Perangkat Daerah</option>' +
        state.masterPd.map((pd) =>
          `<option value="${pd.id}">${escapeHtml(pd.nama_perangkat)}${pd.singkatan ? ` (${escapeHtml(pd.singkatan)})` : ''}</option>`
        ).join('');
      sel.value = currentVal;
    });

    const formPdIds = ['kdo-perangkat', 'pakai-perangkat', 'pns-perangkat'];
    formPdIds.forEach((selectId) => {
      const sel = document.getElementById(selectId);
      if (!sel) return;
      const currentVal = sel.value;
      sel.innerHTML = '<option value="">-- Pilih Perangkat Daerah --</option>' +
        state.masterPd.map((pd) =>
          `<option value="${pd.id}">${escapeHtml(pd.kode_perangkat ? pd.kode_perangkat + ' - ' : '')}${escapeHtml(pd.nama_perangkat)}</option>`
        ).join('');
      sel.value = currentVal;
    });

    const formPnsIds = ['kdo-pegawai', 'pakai-pegawai'];
    formPnsIds.forEach((selectId) => {
      const sel = document.getElementById(selectId);
      if (!sel) return;
      const currentVal = sel.value;
      sel.innerHTML = '<option value="">-- Pilih Pegawai (PNS) --</option>' +
        state.masterPns.map((p) =>
          `<option value="${p.id}" data-pd="${p.perangkat_id || ''}">${escapeHtml(p.nama_pegawai)} — NIP ${escapeHtml(p.nip)} (${escapeHtml(p.golongan)})</option>`
        ).join('');
      sel.value = currentVal;
    });

    const pakaiKdoSel = document.getElementById('pakai-kdo');
    if (pakaiKdoSel) {
      const currentVal = pakaiKdoSel.value;
      pakaiKdoSel.innerHTML = '<option value="">-- Pilih Kendaraan (KDO) --</option>' +
        state.masterKdo.map((k) =>
          `<option value="${k.id}" data-pd="${k.perangkat_id}" data-pns="${k.pegawai_id}" data-kondisi="${escapeHtml(k.kondisi)}">[${escapeHtml(k.nomor_polisi_kendaraan)}] ${escapeHtml(k.merk_kendaraan)} ${escapeHtml(k.tipe_kendaraan)} — ${escapeHtml(k.kondisi)}</option>`
        ).join('');
      pakaiKdoSel.value = currentVal;
    }
  }

  // ==========================================================================
  // 8. DASHBOARD VIEW
  // ==========================================================================
  async function loadDashboard() {
    try {
      const res = await apiRequest('api/dashboard.php');
      const d = res.data || {};
      const s = d.summary || {};

      const totalKdo = Number(s.total_kdo || 0);
      const kdoBaik = Number(s.kdo_baik || 0);
      const kdoRingan = Number(s.kdo_rusak_ringan || 0);
      const kdoBerat = Number(s.kdo_rusak_berat || 0);
      const pctBaik = totalKdo > 0 ? Math.round((kdoBaik / totalKdo) * 100) : 0;
      const pctRingan = totalKdo > 0 ? Math.round((kdoRingan / totalKdo) * 100) : 0;
      const pctBerat = totalKdo > 0 ? Math.round((kdoBerat / totalKdo) * 100) : 0;

      document.getElementById('kpi-total-kdo').textContent = `${totalKdo} Unit`;
      const jenisList = (d.distribusi_jenis || []).map((j) => `${j.jumlah} ${j.jenis_kendaraan}`).join(' • ');
      document.getElementById('kpi-jenis-summary').textContent = jenisList || 'BMD KIB B';
      document.getElementById('kpi-kondisi-breakdown').textContent = `${kdoBaik} Baik • ${kdoRingan} RR • ${kdoBerat} RB`;

      document.getElementById('kpi-kdo-baik').textContent = `${kdoBaik} Unit`;
      document.getElementById('kpi-persen-baik').textContent = `${pctBaik}% dari total armada`;

      document.getElementById('kpi-kdo-rusak').textContent = `${kdoRingan + kdoBerat} Unit`;
      document.getElementById('kpi-rusak-detail').textContent = `${kdoRingan} Rusak Ringan • ${kdoBerat} Rusak Berat`;

      document.getElementById('kpi-pakai-aktif').textContent = `${s.pemakaian_aktif || 0} Aktif • ${s.pemakaian_terjadwal || 0} Terjadwal`;
      document.getElementById('kpi-total-pns').textContent = `${s.total_pns || 0} PNS`;
      document.getElementById('kpi-total-pd').textContent = `pada ${s.total_pd || 0} OPD`;

      // Segmented Fleet Health Meter
      const segEl = document.getElementById('dashboard-segmented-meter');
      if (segEl) {
        segEl.innerHTML = `
          <div class="seg-slice seg-baik" style="inline-size:${pctBaik}%" title="Baik: ${kdoBaik} Unit (${pctBaik}%)"></div>
          <div class="seg-slice seg-ringan" style="inline-size:${pctRingan}%" title="Rusak Ringan: ${kdoRingan} Unit (${pctRingan}%)"></div>
          <div class="seg-slice seg-berat" style="inline-size:${pctBerat}%" title="Rusak Berat: ${kdoBerat} Unit (${pctBerat}%)"></div>
        `;
      }

      // Condition proportion bars
      const barsEl = document.getElementById('dashboard-condition-bars');
      if (barsEl) {
        barsEl.innerHTML = `
          <div class="bar-row">
            <div class="bar-label-line">
              <span>✔ Kondisi Baik (Laik Jalan)</span>
              <span>${kdoBaik} Unit (${pctBaik}%)</span>
            </div>
            <div class="bar-track"><div class="bar-fill fill-baik" style="inline-size:${pctBaik}%"></div></div>
          </div>
          <div class="bar-row">
            <div class="bar-label-line">
              <span>⚠ Kondisi Rusak Ringan (Perlu Servis)</span>
              <span>${kdoRingan} Unit (${pctRingan}%)</span>
            </div>
            <div class="bar-track"><div class="bar-fill fill-ringan" style="inline-size:${pctRingan}%"></div></div>
          </div>
          <div class="bar-row">
            <div class="bar-label-line">
              <span>✖ Kondisi Rusak Berat (Tidak Beroperasi)</span>
              <span>${kdoBerat} Unit (${pctBerat}%)</span>
            </div>
            <div class="bar-track"><div class="bar-fill fill-berat" style="inline-size:${pctBerat}%"></div></div>
          </div>
        `;
      }

      // Category pills
      const catEl = document.getElementById('dashboard-category-pills');
      if (catEl) {
        const mapJenis = { 'Roda 4': 0, 'Roda 2': 0, 'Roda 6 / Khusus': 0 };
        (d.distribusi_jenis || []).forEach((j) => {
          mapJenis[j.jenis_kendaraan] = Number(j.jumlah || 0);
        });
        catEl.innerHTML = `
          <div class="cat-pill-card">
            <strong>🚗 ${mapJenis['Roda 4'] || 0}</strong>
            <span>Roda 4 (Mobil)</span>
          </div>
          <div class="cat-pill-card">
            <strong>🏍 ${mapJenis['Roda 2'] || 0}</strong>
            <span>Roda 2 (Motor)</span>
          </div>
          <div class="cat-pill-card">
            <strong>🚛 ${mapJenis['Roda 6 / Khusus'] || 0}</strong>
            <span>Roda 6 / Khusus</span>
          </div>
        `;
      }

      // Vehicles needing attention
      const perhatianEl = document.getElementById('dashboard-perhatian-list');
      const perhatianList = d.kdo_perlu_perhatian || [];
      if (perhatianEl) {
        if (!perhatianList.length) {
          perhatianEl.innerHTML = '<div class="empty-state">Seluruh kendaraan dinas dalam kondisi Baik.</div>';
        } else {
          perhatianEl.innerHTML = `
            <div style="display:flex;flex-direction:column;gap:0.75rem;">
              ${perhatianList.map((k) => `
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:0.75rem;padding:0.85rem 1rem;border:1px solid var(--border-color);border-radius:var(--radius-sm);background:var(--bg-subtle);">
                  <div>
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                      <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${k.id}">${escapeHtml(k.nomor_polisi_kendaraan)}</button>
                      <strong>${escapeHtml(k.merk_kendaraan)} ${escapeHtml(k.tipe_kendaraan)}</strong>
                      ${renderKondisiBadge(k.kondisi)}
                    </div>
                    <span class="cell-sub">Penanggung Jawab: <strong>${escapeHtml(k.nama_pegawai)}</strong> (${escapeHtml(k.nama_perangkat)}) — ${escapeHtml(k.keterangan || '-')}</span>
                  </div>
                  <div class="row-actions">
                    <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="kdo" data-id="${k.id}">Detail</button>
                    <button type="button" class="btn btn-sm btn-primary" data-action="edit-entity" data-entity="kdo" data-id="${k.id}">Update Kondisi</button>
                  </div>
                </div>
              `).join('')}
            </div>
          `;
        }
      }

      // Rekap Perangkat Daerah Table
      const pdTbody = document.getElementById('dashboard-pd-tbody');
      if (pdTbody) {
        const pdRows = d.distribusi_perangkat || [];
        pdTbody.innerHTML = pdRows.map((pd) => `
          <tr>
            <th scope="row" data-value="${escapeHtml(pd.nama_perangkat)}">
              <button type="button" class="link-btn" data-action="view-detail" data-entity="pd" data-id="${pd.id}">
                ${escapeHtml(pd.nama_perangkat)}
              </button>
              <span class="cell-sub">Kode SKPD: ${escapeHtml(pd.kode_perangkat || '-')}</span>
            </th>
            <td><span class="status-badge badge-neutral">${escapeHtml(pd.singkatan || '-')}</span></td>
            <td data-value="${pd.total_pns || 0}"><strong>${pd.total_pns || 0}</strong> Pegawai</td>
            <td data-value="${pd.total_kdo || 0}"><strong>${pd.total_kdo || 0}</strong> Unit</td>
            <td><span class="status-badge badge-baik">✔ ${pd.baik || 0} Baik</span></td>
            <td>
              <span class="status-badge badge-rusak-ringan">⚠ ${pd.rusak_ringan || 0} RR</span>
              <span class="status-badge badge-rusak-berat">✖ ${pd.rusak_berat || 0} RB</span>
            </td>
            <td>
              <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pd" data-id="${pd.id}">Detail PD</button>
            </td>
          </tr>
        `).join('');
      }

      // Pemakaian Terbaru Table
      const pakaiTbody = document.getElementById('dashboard-pakai-tbody');
      if (pakaiTbody) {
        const pakaiRows = d.pemakaian_terbaru || [];
        if (!pakaiRows.length) {
          pakaiTbody.innerHTML = '<tr><td colspan="7" class="empty-state">Belum ada catatan pemakaian KDO.</td></tr>';
        } else {
          pakaiTbody.innerHTML = pakaiRows.map((pk) => `
            <tr>
              <th scope="row">
                <button type="button" class="link-btn" data-action="view-detail" data-entity="pemakaian" data-id="${pk.id}">
                  ${escapeHtml(pk.nomor_surat_tugas)}
                </button>
                <span class="cell-sub">${formatDateId(pk.tanggal_mulai)} s/d ${formatDateId(pk.tanggal_selesai)}</span>
              </th>
              <td>
                <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${pk.kdo_id}">
                  ${escapeHtml(pk.nomor_polisi_kendaraan)}
                </button>
                <span class="cell-sub">${escapeHtml(pk.merk_kendaraan)} ${escapeHtml(pk.tipe_kendaraan)}</span>
              </td>
              <td>
                <button type="button" class="link-btn" data-action="view-detail" data-entity="pns" data-id="${pk.pegawai_id}">
                  ${escapeHtml(pk.nama_pegawai)}
                </button>
                <span class="cell-sub">NIP: ${escapeHtml(pk.nip)}</span>
              </td>
              <td>${escapeHtml(pk.nama_perangkat)}</td>
              <td>
                <strong>${escapeHtml(pk.keperluan)}</strong>
                <span class="cell-sub">Tujuan: ${escapeHtml(pk.tujuan)}</span>
              </td>
              <td>${renderStatusPakaiBadge(pk.status_pemakaian)}</td>
              <td>
                <div class="row-actions">
                  ${pk.status_pemakaian !== 'Selesai'
                    ? `<button type="button" class="btn btn-sm btn-success" data-action="complete-pakai" data-id="${pk.id}" data-kmawal="${pk.km_awal || 0}" data-surat="${escapeHtml(pk.nomor_surat_tugas)}" data-nopol="${escapeHtml(pk.nomor_polisi_kendaraan)}">✔ Selesai</button>`
                    : ''}
                  <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pemakaian" data-id="${pk.id}">Detail</button>
                </div>
              </td>
            </tr>
          `).join('');
        }
      }
    } catch (err) {
      showToast('Gagal memuat ringkasan dashboard: ' + err.message, 'error');
    }
  }

  // ==========================================================================
  // 9. LIST VIEWS (KDO TABLE & CARD GRID, PEMAKAIAN, PNS, PD)
  // ==========================================================================
  function syncKdoConditionChips(kondisiValue) {
    document.querySelectorAll('[data-action="chip-kdo-kondisi"]').forEach((chip) => {
      chip.setAttribute('aria-pressed', chip.dataset.value === kondisiValue ? 'true' : 'false');
    });
  }

  function syncPakaiStatusChips(statusValue) {
    document.querySelectorAll('[data-action="chip-pakai-status"]').forEach((chip) => {
      chip.setAttribute('aria-pressed', chip.dataset.value === statusValue ? 'true' : 'false');
    });
  }

  async function loadKdoList() {
    const search = document.getElementById('filter-kdo-search')?.value || '';
    const pdId = document.getElementById('filter-kdo-pd')?.value || '';
    const kondisi = document.getElementById('filter-kdo-kondisi')?.value || '';
    const jenis = document.getElementById('filter-kdo-jenis')?.value || '';

    syncKdoConditionChips(kondisi);

    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (pdId) params.set('perangkat_id', pdId);
    if (kondisi) params.set('kondisi', kondisi);
    if (jenis) params.set('jenis_kendaraan', jenis);

    try {
      const res = await apiRequest(`api/kdo.php?${params.toString()}`);
      const rows = res.data || [];
      const tbody = document.getElementById('tbody-kdo');
      const cardGrid = document.getElementById('kdo-card-grid');

      if (tbody) {
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Tidak ada data Kendaraan Dinas Operasional (KDO) yang sesuai filter.</td></tr>';
        } else {
          tbody.innerHTML = rows.map((k) => `
            <tr>
              <th scope="row" data-value="${escapeHtml(k.nomor_polisi_kendaraan)}">
                <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${k.id}">
                  ${escapeHtml(k.nomor_polisi_kendaraan)}
                </button>
                <span class="cell-sub">${escapeHtml(k.jenis_kendaraan || 'Roda 4')} • ${Number(k.sedang_digunakan) > 0 ? '🚗 Sedang Jalan' : 'Standby di OPD'}</span>
              </th>
              <td data-value="${escapeHtml(k.merk_kendaraan)} ${escapeHtml(k.tipe_kendaraan)}">
                <strong>${escapeHtml(k.merk_kendaraan)}</strong> — ${escapeHtml(k.tipe_kendaraan)}
                <span class="cell-sub">Kode BMD: ${escapeHtml(k.kode_barang || '-')}</span>
              </td>
              <td data-value="${k.tahun_produksi}">
                <strong>${escapeHtml(k.tahun_produksi)}</strong>
                <span class="cell-sub">Warna: ${escapeHtml(k.warna_kendaraan)}</span>
              </td>
              <td data-value="${escapeHtml(k.kondisi)}">${renderKondisiBadge(k.kondisi)}</td>
              <td data-value="${escapeHtml(k.nama_pegawai)}">
                <div style="display:flex;align-items:center;gap:0.55rem;">
                  <span class="avatar-circle" aria-hidden="true">${escapeHtml(getInitials(k.nama_pegawai))}</span>
                  <div>
                    <button type="button" class="link-btn" data-action="view-detail" data-entity="pns" data-id="${k.pegawai_id}">
                      ${escapeHtml(k.nama_pegawai)}
                    </button>
                    <span class="cell-sub">NIP: ${escapeHtml(k.nip)} (${escapeHtml(k.golongan)})</span>
                  </div>
                </div>
              </td>
              <td data-value="${escapeHtml(k.nama_perangkat)}">
                <button type="button" class="link-btn" data-action="view-detail" data-entity="pd" data-id="${k.perangkat_id}">
                  ${escapeHtml(k.nama_perangkat)}
                </button>
              </td>
              <td>
                <div class="row-actions">
                  <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="kdo" data-id="${k.id}">Detail</button>
                  <button type="button" class="btn btn-sm btn-primary" data-action="edit-entity" data-entity="kdo" data-id="${k.id}">Edit</button>
                  <button type="button" class="btn btn-sm btn-danger" data-action="delete-entity" data-entity="kdo" data-id="${k.id}" data-label="${escapeHtml(k.nomor_polisi_kendaraan)} (${escapeHtml(k.merk_kendaraan)} ${escapeHtml(k.tipe_kendaraan)})">Hapus</button>
                </div>
              </td>
            </tr>
          `).join('');
        }
      }

      if (cardGrid) {
        if (!rows.length) {
          cardGrid.innerHTML = '<div class="card" style="grid-column:1/-1;"><div class="empty-state">Tidak ada unit KDO yang sesuai filter pencarian.</div></div>';
        } else {
          cardGrid.innerHTML = rows.map((k) => `
            <article class="fleet-card">
              <div>
                <div class="fleet-card-top">
                  <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${k.id}">
                    ${escapeHtml(k.nomor_polisi_kendaraan)}
                  </button>
                  ${renderKondisiBadge(k.kondisi)}
                </div>
                <div class="fleet-card-body">
                  <div>
                    <h3 class="fleet-title">${escapeHtml(k.merk_kendaraan)} ${escapeHtml(k.tipe_kendaraan)}</h3>
                    <div class="fleet-meta">
                      <span>${escapeHtml(k.jenis_kendaraan || 'Roda 4')}</span>
                      <span>• Tahun ${escapeHtml(k.tahun_produksi)}</span>
                      <span>• ${escapeHtml(k.warna_kendaraan)}</span>
                    </div>
                  </div>
                  <div class="fleet-holder">
                    <span class="avatar-circle" aria-hidden="true">${escapeHtml(getInitials(k.nama_pegawai))}</span>
                    <div style="min-inline-size:0;">
                      <button type="button" class="link-btn" style="font-size:0.85rem;" data-action="view-detail" data-entity="pns" data-id="${k.pegawai_id}">
                        ${escapeHtml(k.nama_pegawai)}
                      </button>
                      <span class="cell-sub">${escapeHtml(k.nama_perangkat)}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="fleet-card-footer">
                <span class="status-badge badge-neutral">${Number(k.sedang_digunakan) > 0 ? '🚗 Sedang Jalan' : 'Standby Pool'}</span>
                <div class="row-actions">
                  <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="kdo" data-id="${k.id}">Detail</button>
                  <button type="button" class="btn btn-sm btn-primary" data-action="edit-entity" data-entity="kdo" data-id="${k.id}">Edit</button>
                  <button type="button" class="btn btn-sm btn-danger" data-action="delete-entity" data-entity="kdo" data-id="${k.id}" data-label="${escapeHtml(k.nomor_polisi_kendaraan)}">Hapus</button>
                </div>
              </div>
            </article>
          `).join('');
        }
      }
    } catch (err) {
      showToast('Gagal memuat daftar KDO: ' + err.message, 'error');
    }
  }

  function setKdoViewMode(mode) {
    state.kdoViewMode = mode === 'cards' ? 'cards' : 'table';
    const tableWrap = document.getElementById('kdo-table-wrapper');
    const cardGrid = document.getElementById('kdo-card-grid');
    if (tableWrap) tableWrap.hidden = state.kdoViewMode !== 'table';
    if (cardGrid) cardGrid.hidden = state.kdoViewMode !== 'cards';

    document.querySelectorAll('[data-action="toggle-kdo-mode"]').forEach((btn) => {
      btn.setAttribute('aria-pressed', btn.dataset.mode === state.kdoViewMode ? 'true' : 'false');
    });
  }

  async function loadPemakaianList() {
    const search = document.getElementById('filter-pakai-search')?.value || '';
    const pdId = document.getElementById('filter-pakai-pd')?.value || '';
    const status = document.getElementById('filter-pakai-status')?.value || '';

    syncPakaiStatusChips(status);

    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (pdId) params.set('perangkat_id', pdId);
    if (status) params.set('status_pemakaian', status);

    try {
      const res = await apiRequest(`api/pemakaian.php?${params.toString()}`);
      const rows = res.data || [];
      const tbody = document.getElementById('tbody-pemakaian');
      if (!tbody) return;

      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Tidak ada data Pemakaian KDO yang ditemukan.</td></tr>';
        return;
      }

      tbody.innerHTML = rows.map((pk) => `
        <tr>
          <th scope="row" data-value="${escapeHtml(pk.nomor_surat_tugas)}">
            <button type="button" class="link-btn" data-action="view-detail" data-entity="pemakaian" data-id="${pk.id}">
              ${escapeHtml(pk.nomor_surat_tugas)}
            </button>
            <span class="cell-sub">ID Log #${pk.id}</span>
          </th>
          <td data-value="${escapeHtml(pk.nomor_polisi_kendaraan)}">
            <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${pk.kdo_id}">
              ${escapeHtml(pk.nomor_polisi_kendaraan)}
            </button>
            <span class="cell-sub">${escapeHtml(pk.merk_kendaraan)} ${escapeHtml(pk.tipe_kendaraan)}</span>
          </td>
          <td data-value="${escapeHtml(pk.nama_pegawai)}">
            <button type="button" class="link-btn" data-action="view-detail" data-entity="pns" data-id="${pk.pegawai_id}">
              ${escapeHtml(pk.nama_pegawai)}
            </button>
            <span class="cell-sub">${escapeHtml(pk.nama_perangkat)}</span>
          </td>
          <td>
            <strong>${escapeHtml(pk.keperluan)}</strong>
            <span class="cell-sub">Tujuan: ${escapeHtml(pk.tujuan)}</span>
          </td>
          <td data-value="${escapeHtml(pk.tanggal_mulai)}">
            <strong>${formatDateId(pk.tanggal_mulai)}</strong>
            <span class="cell-sub">s/d ${formatDateId(pk.tanggal_selesai)} • KM: ${formatNumber(pk.km_awal)} → ${pk.km_akhir ? formatNumber(pk.km_akhir) : '...'}</span>
          </td>
          <td data-value="${escapeHtml(pk.status_pemakaian)}">
            <div style="display:flex;flex-direction:column;gap:0.25rem;align-items:flex-start;">
              ${renderStatusPakaiBadge(pk.status_pemakaian)}
              <span class="cell-sub">Kondisi: ${escapeHtml(pk.kondisi_setelah || 'Baik')}</span>
            </div>
          </td>
          <td>
            <div class="row-actions">
              ${pk.status_pemakaian !== 'Selesai'
                ? `<button type="button" class="btn btn-sm btn-success" data-action="complete-pakai" data-id="${pk.id}" data-kmawal="${pk.km_awal || 0}" data-surat="${escapeHtml(pk.nomor_surat_tugas)}" data-nopol="${escapeHtml(pk.nomor_polisi_kendaraan)}">✔ Selesaikan</button>`
                : ''}
              <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pemakaian" data-id="${pk.id}">Detail</button>
              <button type="button" class="btn btn-sm btn-primary" data-action="edit-entity" data-entity="pemakaian" data-id="${pk.id}">Edit</button>
              <button type="button" class="btn btn-sm btn-danger" data-action="delete-entity" data-entity="pemakaian" data-id="${pk.id}" data-label="${escapeHtml(pk.nomor_surat_tugas)}">Hapus</button>
            </div>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      showToast('Gagal memuat daftar Pemakaian KDO: ' + err.message, 'error');
    }
  }

  async function loadPnsList() {
    const search = document.getElementById('filter-pns-search')?.value || '';
    const pdId = document.getElementById('filter-pns-pd')?.value || '';
    const golongan = document.getElementById('filter-pns-golongan')?.value || '';

    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (pdId) params.set('perangkat_id', pdId);
    if (golongan) params.set('golongan', golongan);

    try {
      const res = await apiRequest(`api/pns.php?${params.toString()}`);
      const rows = res.data || [];
      const tbody = document.getElementById('tbody-pns');
      if (!tbody) return;

      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Tidak ada data Pegawai Negeri Sipil (PNS) yang sesuai filter.</td></tr>';
        return;
      }

      tbody.innerHTML = rows.map((p) => `
        <tr>
          <th scope="row" data-value="${escapeHtml(p.nama_pegawai)}">
            <div style="display:flex;align-items:center;gap:0.6rem;">
              <span class="avatar-circle" aria-hidden="true">${escapeHtml(getInitials(p.nama_pegawai))}</span>
              <div>
                <button type="button" class="link-btn" data-action="view-detail" data-entity="pns" data-id="${p.id}">
                  ${escapeHtml(p.nama_pegawai)}
                </button>
                <span class="cell-sub">Kontak: ${escapeHtml(p.no_hp || '-')}</span>
              </div>
            </div>
          </th>
          <td data-value="${escapeHtml(p.nip)}"><code>${escapeHtml(p.nip)}</code></td>
          <td>${escapeHtml(p.jabatan)}</td>
          <td data-value="${escapeHtml(p.golongan)}"><span class="status-badge badge-neutral">${escapeHtml(p.golongan)}</span></td>
          <td data-value="${escapeHtml(p.nama_perangkat || '')}">
            ${p.perangkat_id
              ? `<button type="button" class="link-btn" data-action="view-detail" data-entity="pd" data-id="${p.perangkat_id}">${escapeHtml(p.nama_perangkat)}</button>`
              : '<span class="cell-sub">Belum ditentukan</span>'}
          </td>
          <td data-value="${p.jumlah_kdo || 0}">
            <strong>${p.jumlah_kdo || 0} Unit KDO</strong>
            <span class="cell-sub">${p.jumlah_pemakaian || 0}x Riwayat Pakai</span>
          </td>
          <td>
            <div class="row-actions">
              <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pns" data-id="${p.id}">Detail</button>
              <button type="button" class="btn btn-sm btn-primary" data-action="edit-entity" data-entity="pns" data-id="${p.id}">Edit</button>
              <button type="button" class="btn btn-sm btn-danger" data-action="delete-entity" data-entity="pns" data-id="${p.id}" data-label="${escapeHtml(p.nama_pegawai)}">Hapus</button>
            </div>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      showToast('Gagal memuat daftar PNS: ' + err.message, 'error');
    }
  }

  async function loadPdList() {
    const search = document.getElementById('filter-pd-search')?.value || '';
    const params = new URLSearchParams();
    if (search) params.set('search', search);

    try {
      const res = await apiRequest(`api/pd.php?${params.toString()}`);
      const rows = res.data || [];
      const tbody = document.getElementById('tbody-pd');
      if (!tbody) return;

      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty-state">Tidak ada data Perangkat Daerah yang ditemukan.</td></tr>';
        return;
      }

      tbody.innerHTML = rows.map((pd) => `
        <tr>
          <th scope="row" data-value="${escapeHtml(pd.nama_perangkat)}">
            <button type="button" class="link-btn" data-action="view-detail" data-entity="pd" data-id="${pd.id}">
              ${escapeHtml(pd.nama_perangkat)}
            </button>
          </th>
          <td>
            <span class="status-badge badge-neutral">${escapeHtml(pd.singkatan || 'OPD')}</span>
            <span class="cell-sub">Kode: ${escapeHtml(pd.kode_perangkat || '-')}</span>
          </td>
          <td>
            <span>${escapeHtml(pd.alamat || '-')}</span>
            <span class="cell-sub">Telp: ${escapeHtml(pd.telepon || '-')}</span>
          </td>
          <td data-value="${pd.jumlah_pns || 0}"><strong>${pd.jumlah_pns || 0}</strong> Pegawai</td>
          <td data-value="${pd.jumlah_kdo || 0}"><strong>${pd.jumlah_kdo || 0}</strong> Unit KDO</td>
          <td>
            <span class="status-badge badge-baik">✔ ${pd.kdo_baik || 0}</span>
            <span class="status-badge badge-rusak-ringan">⚠ ${pd.kdo_rusak_ringan || 0}</span>
            <span class="status-badge badge-rusak-berat">✖ ${pd.kdo_rusak_berat || 0}</span>
          </td>
          <td>
            <div class="row-actions">
              <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pd" data-id="${pd.id}">Detail</button>
              <button type="button" class="btn btn-sm btn-primary" data-action="edit-entity" data-entity="pd" data-id="${pd.id}">Edit</button>
              <button type="button" class="btn btn-sm btn-danger" data-action="delete-entity" data-entity="pd" data-id="${pd.id}" data-label="${escapeHtml(pd.nama_perangkat)}">Hapus</button>
            </div>
          </td>
        </tr>
      `).join('');
    } catch (err) {
      showToast('Gagal memuat daftar Perangkat Daerah: ' + err.message, 'error');
    }
  }

  // ==========================================================================
  // 10. HALAMAN DETAIL (READ DETAIL VIEW)
  // ==========================================================================
  function renderBreadcrumb(targetId, entity, currentTitle) {
    const el = document.getElementById(targetId);
    if (!el) return;
    el.innerHTML = `
      <button type="button" data-nav="dashboard">Dashboard</button>
      <span aria-hidden="true">/</span>
      <button type="button" data-nav="${escapeHtml(entity)}">${escapeHtml(entityNames[entity] || 'Daftar')}</button>
      <span aria-hidden="true">/</span>
      <span aria-current="page"><strong>${escapeHtml(currentTitle)}</strong></span>
    `;
  }

  async function openDetail(entity, id) {
    const endpoints = {
      kdo: 'api/kdo.php',
      pemakaian: 'api/pemakaian.php',
      pns: 'api/pns.php',
      pd: 'api/pd.php'
    };
    const endpoint = endpoints[entity];
    if (!endpoint) return;

    try {
      const res = await apiRequest(`${endpoint}?id=${encodeURIComponent(id)}`);
      const d = res.data;
      if (!d) return;

      switchView('detail', true);

      const heading = document.getElementById('detail-heading');
      const subheading = document.getElementById('detail-subheading');
      const actions = document.getElementById('detail-header-actions');
      const container = document.getElementById('detail-content-container');

      if (entity === 'kdo') {
        renderBreadcrumb('detail-breadcrumb', 'kdo', d.nomor_polisi_kendaraan);
        heading.textContent = `Detail KDO: ${d.nomor_polisi_kendaraan}`;
        subheading.textContent = `${d.merk_kendaraan} ${d.tipe_kendaraan} (${d.tahun_produksi}) — Milik ${d.nama_perangkat}`;
        actions.innerHTML = `
          <button type="button" class="btn btn-secondary" data-nav="kdo">← Daftar KDO</button>
          <button type="button" class="btn btn-secondary" data-action="print-page">🖨 Cetak Kartu KDO</button>
          <button type="button" class="btn btn-success" data-action="open-form" data-entity="pemakaian" data-prefill-kdo="${d.id}">+ Catat Pemakaian KDO Ini</button>
          <button type="button" class="btn btn-primary" data-action="edit-entity" data-entity="kdo" data-id="${d.id}">Edit KDO</button>
          <button type="button" class="btn btn-danger" data-action="delete-entity" data-entity="kdo" data-id="${d.id}" data-label="${escapeHtml(d.nomor_polisi_kendaraan)}">Hapus</button>
        `;

        const riwayat = d.riwayat_pemakaian || [];
        container.innerHTML = `
          <div class="detail-grid">
            <div class="card">
              <div class="card-header">
                <h3>Spesifikasi Fisik &amp; Identitas BMD (KIB B)</h3>
                <span class="nopol-badge">${escapeHtml(d.nomor_polisi_kendaraan)}</span>
              </div>
              <div class="card-body">
                <dl class="dl-list">
                  <div class="dl-item"><dt>Nomor Polisi Kendaraan</dt><dd>${escapeHtml(d.nomor_polisi_kendaraan)}</dd></div>
                  <div class="dl-item"><dt>Merk &amp; Tipe Kendaraan</dt><dd>${escapeHtml(d.merk_kendaraan)} — ${escapeHtml(d.tipe_kendaraan)}</dd></div>
                  <div class="dl-item"><dt>Jenis Kendaraan</dt><dd>${escapeHtml(d.jenis_kendaraan || 'Roda 4')}</dd></div>
                  <div class="dl-item"><dt>Tahun Produksi &amp; Warna</dt><dd>Tahun ${escapeHtml(d.tahun_produksi)} • Warna ${escapeHtml(d.warna_kendaraan)}</dd></div>
                  <div class="dl-item"><dt>Kondisi Kendaraan Saat Ini</dt><dd>${renderKondisiBadge(d.kondisi)}</dd></div>
                  <div class="dl-item"><dt>Kode Barang BMD</dt><dd><code>${escapeHtml(d.kode_barang || '-')}</code></dd></div>
                  <div class="dl-item"><dt>Nomor Rangka (VIN) / Mesin</dt><dd>Rangka: <code>${escapeHtml(d.nomor_rangka || '-')}</code> • Mesin: <code>${escapeHtml(d.nomor_mesin || '-')}</code></dd></div>
                  <div class="dl-item"><dt>Keterangan / Catatan</dt><dd>${escapeHtml(d.keterangan || '-')}</dd></div>
                </dl>
              </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:1.25rem;">
              <div class="card">
                <div class="card-header">
                  <h3>Penanggung Jawab Kendaraan (PNS)</h3>
                  <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pns" data-id="${d.pegawai_id}">Lihat Profil PNS</button>
                </div>
                <div class="card-body">
                  <dl class="dl-list">
                    <div class="dl-item"><dt>Nama Pegawai</dt><dd>${escapeHtml(d.nama_pegawai)}</dd></div>
                    <div class="dl-item"><dt>NIP</dt><dd><code>${escapeHtml(d.nip)}</code></dd></div>
                    <div class="dl-item"><dt>Jabatan &amp; Golongan</dt><dd>${escapeHtml(d.jabatan)} (${escapeHtml(d.golongan)})</dd></div>
                    <div class="dl-item"><dt>No. Telepon Kedinasan</dt><dd>${escapeHtml(d.no_hp_pegawai || '-')}</dd></div>
                  </dl>
                </div>
              </div>

              <div class="card">
                <div class="card-header">
                  <h3>Unit Pengelola (Perangkat Daerah)</h3>
                  <button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pd" data-id="${d.perangkat_id}">Lihat Detail PD</button>
                </div>
                <div class="card-body">
                  <dl class="dl-list">
                    <div class="dl-item"><dt>Nama Perangkat Daerah</dt><dd>${escapeHtml(d.nama_perangkat)} (${escapeHtml(d.singkatan_perangkat || 'OPD')})</dd></div>
                    <div class="dl-item"><dt>Kode SKPD</dt><dd><code>${escapeHtml(d.kode_perangkat || '-')}</code></dd></div>
                    <div class="dl-item"><dt>Alamat Kantor</dt><dd>${escapeHtml(d.alamat_perangkat || '-')}</dd></div>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Riwayat Pemakaian Kendaraan (${riwayat.length} Catatan)</h3>
              <button type="button" class="btn btn-sm btn-success" data-action="open-form" data-entity="pemakaian" data-prefill-kdo="${d.id}">+ Tambah Pemakaian</button>
            </div>
            <div tabindex="0" role="region" aria-label="Riwayat Pemakaian KDO" class="table-wrapper" style="border:none;border-radius:0;">
              <table class="data-table" style="--label-2:'PNS Pengguna';--label-3:'Keperluan & Tujuan';--label-4:'Tanggal';--label-5:'Odometer';--label-6:'Status';--label-7:'Aksi';">
                <thead>
                  <tr>
                    <th scope="col">No. Surat Tugas</th>
                    <th scope="col">PNS Pengguna</th>
                    <th scope="col">Keperluan &amp; Tujuan</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Odometer (KM)</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  ${riwayat.length ? riwayat.map((pk) => `
                    <tr>
                      <th scope="row">${escapeHtml(pk.nomor_surat_tugas)}</th>
                      <td>${escapeHtml(pk.nama_pegawai)}</td>
                      <td><strong>${escapeHtml(pk.keperluan)}</strong><span class="cell-sub">${escapeHtml(pk.tujuan)}</span></td>
                      <td>${formatDateId(pk.tanggal_mulai)} s/d ${formatDateId(pk.tanggal_selesai)}</td>
                      <td>${formatNumber(pk.km_awal)} → ${pk.km_akhir ? formatNumber(pk.km_akhir) : '...'}</td>
                      <td>${renderStatusPakaiBadge(pk.status_pemakaian)}</td>
                      <td><button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pemakaian" data-id="${pk.id}">Detail</button></td>
                    </tr>
                  `).join('') : '<tr><td colspan="7" class="empty-state">Belum ada riwayat pemakaian untuk kendaraan ini.</td></tr>'}
                </tbody>
              </table>
            </div>
          </div>
        `;
      } else if (entity === 'pemakaian') {
        renderBreadcrumb('detail-breadcrumb', 'pemakaian', d.nomor_surat_tugas);
        heading.textContent = `Detail Pemakaian KDO: ${d.nomor_surat_tugas}`;
        subheading.textContent = `Kendaraan ${d.nomor_polisi_kendaraan} (${d.merk_kendaraan} ${d.tipe_kendaraan}) — Digunakan oleh ${d.nama_pegawai}`;
        const selisihKm = (d.km_akhir !== null && d.km_akhir >= d.km_awal)
          ? `${formatNumber(d.km_akhir - d.km_awal)} KM`
          : 'Belum selesai / menunggu input KM akhir';

        actions.innerHTML = `
          <button type="button" class="btn btn-secondary" data-nav="pemakaian">← Daftar Pemakaian</button>
          <button type="button" class="btn btn-secondary" data-action="print-page">🖨 Cetak Surat Jalan</button>
          ${d.status_pemakaian !== 'Selesai'
            ? `<button type="button" class="btn btn-success" data-action="complete-pakai" data-id="${d.id}" data-kmawal="${d.km_awal || 0}" data-surat="${escapeHtml(d.nomor_surat_tugas)}" data-nopol="${escapeHtml(d.nomor_polisi_kendaraan)}">✔ Tandai Selesai</button>`
            : ''}
          <button type="button" class="btn btn-primary" data-action="edit-entity" data-entity="pemakaian" data-id="${d.id}">Edit Data</button>
          <button type="button" class="btn btn-danger" data-action="delete-entity" data-entity="pemakaian" data-id="${d.id}" data-label="${escapeHtml(d.nomor_surat_tugas)}">Hapus</button>
        `;

        container.innerHTML = `
          <div class="detail-grid">
            <div class="card">
              <div class="card-header">
                <h3>Rincian Surat Tugas &amp; Operasional</h3>
                ${renderStatusPakaiBadge(d.status_pemakaian)}
              </div>
              <div class="card-body">
                <dl class="dl-list">
                  <div class="dl-item"><dt>Nomor Surat Tugas / SPPD</dt><dd><code>${escapeHtml(d.nomor_surat_tugas)}</code></dd></div>
                  <div class="dl-item"><dt>Keperluan Dinas</dt><dd>${escapeHtml(d.keperluan)}</dd></div>
                  <div class="dl-item"><dt>Lokasi Tujuan</dt><dd>${escapeHtml(d.tujuan)}</dd></div>
                  <div class="dl-item"><dt>Tanggal Mulai s/d Selesai</dt><dd>${formatDateId(d.tanggal_mulai)} s/d ${formatDateId(d.tanggal_selesai)}</dd></div>
                  <div class="dl-item"><dt>Odometer (KM Awal → KM Akhir)</dt><dd>${formatNumber(d.km_awal)} KM → ${d.km_akhir !== null ? formatNumber(d.km_akhir) + ' KM' : 'Masih digunakan'}</dd></div>
                  <div class="dl-item"><dt>Total Jarak Tempuh</dt><dd>${selisihKm}</dd></div>
                  <div class="dl-item"><dt>Kondisi Kendaraan Setelah Pakai</dt><dd>${renderKondisiBadge(d.kondisi_setelah || 'Baik')}</dd></div>
                  <div class="dl-item"><dt>Catatan Operasional</dt><dd>${escapeHtml(d.catatan || '-')}</dd></div>
                </dl>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>KDO, Pegawai (PNS), &amp; Perangkat Daerah Terkait</h3>
              </div>
              <div class="card-body">
                <dl class="dl-list">
                  <div class="dl-item">
                    <dt>Kendaraan Dinas Operasional (KDO)</dt>
                    <dd>
                      <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${d.kdo_id}">${escapeHtml(d.nomor_polisi_kendaraan)}</button>
                      <div style="margin-top:0.35rem;">${escapeHtml(d.merk_kendaraan)} ${escapeHtml(d.tipe_kendaraan)} (${escapeHtml(d.tahun_produksi)}, ${escapeHtml(d.warna_kendaraan)})</div>
                    </dd>
                  </div>
                  <div class="dl-item">
                    <dt>Pegawai Negeri Sipil (PNS) Pengguna</dt>
                    <dd>
                      <button type="button" class="link-btn" data-action="view-detail" data-entity="pns" data-id="${d.pegawai_id}">${escapeHtml(d.nama_pegawai)}</button>
                      <span class="cell-sub">NIP: ${escapeHtml(d.nip)} • ${escapeHtml(d.jabatan)} (${escapeHtml(d.golongan)})</span>
                    </dd>
                  </div>
                  <div class="dl-item">
                    <dt>Perangkat Daerah Pengguna</dt>
                    <dd>
                      <button type="button" class="link-btn" data-action="view-detail" data-entity="pd" data-id="${d.perangkat_id}">${escapeHtml(d.nama_perangkat)}</button>
                      <span class="cell-sub">${escapeHtml(d.alamat_perangkat || '-')}</span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        `;
      } else if (entity === 'pns') {
        renderBreadcrumb('detail-breadcrumb', 'pns', d.nama_pegawai);
        heading.textContent = `Detail PNS: ${d.nama_pegawai}`;
        subheading.textContent = `NIP ${d.nip} • ${d.jabatan} (${d.golongan})`;
        actions.innerHTML = `
          <button type="button" class="btn btn-secondary" data-nav="pns">← Daftar PNS</button>
          <button type="button" class="btn btn-primary" data-action="edit-entity" data-entity="pns" data-id="${d.id}">Edit Data PNS</button>
          <button type="button" class="btn btn-danger" data-action="delete-entity" data-entity="pns" data-id="${d.id}" data-label="${escapeHtml(d.nama_pegawai)}">Hapus</button>
        `;

        const daftarKdo = d.daftar_kdo || [];
        const riwayatPakai = d.riwayat_pemakaian || [];

        container.innerHTML = `
          <div class="detail-grid">
            <div class="card">
              <div class="card-header">
                <h3>Profil Pegawai Negeri Sipil (ASN)</h3>
                <span class="status-badge badge-neutral">Golongan ${escapeHtml(d.golongan)}</span>
              </div>
              <div class="card-body">
                <dl class="dl-list">
                  <div class="dl-item"><dt>Nama Lengkap Pegawai</dt><dd>${escapeHtml(d.nama_pegawai)}</dd></div>
                  <div class="dl-item"><dt>Nomor Induk Pegawai (NIP)</dt><dd><code>${escapeHtml(d.nip)}</code></dd></div>
                  <div class="dl-item"><dt>Jabatan</dt><dd>${escapeHtml(d.jabatan)}</dd></div>
                  <div class="dl-item"><dt>Golongan Ruang</dt><dd>${escapeHtml(d.golongan)}</dd></div>
                  <div class="dl-item"><dt>Unit Kerja (Perangkat Daerah)</dt><dd>${d.perangkat_id ? `<button type="button" class="link-btn" data-action="view-detail" data-entity="pd" data-id="${d.perangkat_id}">${escapeHtml(d.nama_perangkat)}</button>` : '-'}</dd></div>
                  <div class="dl-item"><dt>Nomor Telepon / HP</dt><dd>${escapeHtml(d.no_hp || '-')}</dd></div>
                </dl>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>Kendaraan Dinas di Bawah Tanggung Jawab (${daftarKdo.length} Unit)</h3>
              </div>
              <div class="card-body">
                ${daftarKdo.length ? `
                  <div style="display:flex;flex-direction:column;gap:0.75rem;">
                    ${daftarKdo.map((k) => `
                      <div style="display:flex;justify-content:space-between;align-items:center;padding:0.75rem;border:1px solid var(--border-color);border-radius:var(--radius-sm);">
                        <div>
                          <button type="button" class="nopol-badge" style="cursor:pointer;" data-action="view-detail" data-entity="kdo" data-id="${k.id}">${escapeHtml(k.nomor_polisi_kendaraan)}</button>
                          <strong style="margin-left:0.5rem;">${escapeHtml(k.merk_kendaraan)} ${escapeHtml(k.tipe_kendaraan)}</strong>
                          <span class="cell-sub">Tahun ${escapeHtml(k.tahun_produksi)} • Warna ${escapeHtml(k.warna_kendaraan)}</span>
                        </div>
                        ${renderKondisiBadge(k.kondisi)}
                      </div>
                    `).join('')}
                  </div>
                ` : '<div class="empty-state">Pegawai ini belum memegang unit KDO tetap.</div>'}
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Riwayat Pemakaian KDO oleh ${escapeHtml(d.nama_pegawai)} (${riwayatPakai.length} Catatan)</h3>
            </div>
            <div tabindex="0" role="region" aria-label="Riwayat Pemakaian KDO oleh PNS" class="table-wrapper" style="border:none;border-radius:0;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th scope="col">No. Surat Tugas</th>
                    <th scope="col">Kendaraan (KDO)</th>
                    <th scope="col">Keperluan &amp; Tujuan</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  ${riwayatPakai.length ? riwayatPakai.map((pk) => `
                    <tr>
                      <th scope="row">${escapeHtml(pk.nomor_surat_tugas)}</th>
                      <td><span class="nopol-badge">${escapeHtml(pk.nomor_polisi_kendaraan)}</span> ${escapeHtml(pk.merk_kendaraan)} ${escapeHtml(pk.tipe_kendaraan)}</td>
                      <td><strong>${escapeHtml(pk.keperluan)}</strong><span class="cell-sub">${escapeHtml(pk.tujuan)}</span></td>
                      <td>${formatDateId(pk.tanggal_mulai)} s/d ${formatDateId(pk.tanggal_selesai)}</td>
                      <td>${renderStatusPakaiBadge(pk.status_pemakaian)}</td>
                      <td><button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="pemakaian" data-id="${pk.id}">Detail</button></td>
                    </tr>
                  `).join('') : '<tr><td colspan="6" class="empty-state">Belum ada riwayat pemakaian KDO.</td></tr>'}
                </tbody>
              </table>
            </div>
          </div>
        `;
      } else if (entity === 'pd') {
        renderBreadcrumb('detail-breadcrumb', 'pd', d.nama_perangkat);
        heading.textContent = `Detail Perangkat Daerah: ${d.nama_perangkat}`;
        subheading.textContent = `Kode SKPD ${d.kode_perangkat || '-'} (${d.singkatan || 'OPD'}) • ${d.jumlah_kdo || 0} Unit KDO & ${d.jumlah_pns || 0} PNS`;
        actions.innerHTML = `
          <button type="button" class="btn btn-secondary" data-nav="pd">← Daftar Perangkat Daerah</button>
          <button type="button" class="btn btn-primary" data-action="edit-entity" data-entity="pd" data-id="${d.id}">Edit Perangkat Daerah</button>
          <button type="button" class="btn btn-danger" data-action="delete-entity" data-entity="pd" data-id="${d.id}" data-label="${escapeHtml(d.nama_perangkat)}">Hapus</button>
        `;

        const daftarKdo = d.daftar_kdo || [];
        const daftarPns = d.daftar_pns || [];

        container.innerHTML = `
          <div class="detail-grid">
            <div class="card">
              <div class="card-header">
                <h3>Identitas Organisasi Perangkat Daerah (OPD)</h3>
                <span class="status-badge badge-neutral">${escapeHtml(d.singkatan || 'OPD')}</span>
              </div>
              <div class="card-body">
                <dl class="dl-list">
                  <div class="dl-item"><dt>Nama Perangkat Daerah</dt><dd>${escapeHtml(d.nama_perangkat)}</dd></div>
                  <div class="dl-item"><dt>Kode SKPD &amp; Singkatan</dt><dd><code>${escapeHtml(d.kode_perangkat || '-')}</code> (${escapeHtml(d.singkatan || '-')})</dd></div>
                  <div class="dl-item"><dt>Alamat Kantor</dt><dd>${escapeHtml(d.alamat || '-')}</dd></div>
                  <div class="dl-item"><dt>Telepon</dt><dd>${escapeHtml(d.telepon || '-')}</dd></div>
                  <div class="dl-item">
                    <dt>Ringkasan Kondisi Armada KDO</dt>
                    <dd style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.25rem;">
                      <span class="status-badge badge-baik">✔ ${d.kdo_baik || 0} Baik</span>
                      <span class="status-badge badge-rusak-ringan">⚠ ${d.kdo_rusak_ringan || 0} Rusak Ringan</span>
                      <span class="status-badge badge-rusak-berat">✖ ${d.kdo_rusak_berat || 0} Rusak Berat</span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <div class="card">
              <div class="card-header">
                <h3>Daftar PNS pada ${escapeHtml(d.singkatan || d.nama_perangkat)} (${daftarPns.length} Orang)</h3>
              </div>
              <div class="card-body">
                ${daftarPns.length ? `
                  <div style="display:flex;flex-direction:column;gap:0.65rem;">
                    ${daftarPns.map((p) => `
                      <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;border:1px solid var(--border-color);border-radius:var(--radius-sm);">
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                          <span class="avatar-circle" aria-hidden="true">${escapeHtml(getInitials(p.nama_pegawai))}</span>
                          <div>
                            <button type="button" class="link-btn" data-action="view-detail" data-entity="pns" data-id="${p.id}">${escapeHtml(p.nama_pegawai)}</button>
                            <span class="cell-sub">NIP: ${escapeHtml(p.nip)} • ${escapeHtml(p.jabatan)}</span>
                          </div>
                        </div>
                        <span class="status-badge badge-neutral">${escapeHtml(p.golongan)}</span>
                      </div>
                    `).join('')}
                  </div>
                ` : '<div class="empty-state">Belum ada PNS terdaftar pada Perangkat Daerah ini.</div>'}
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header">
              <h3>Daftar Kendaraan Dinas Operasional (KDO) Milik ${escapeHtml(d.nama_perangkat)} (${daftarKdo.length} Unit)</h3>
            </div>
            <div tabindex="0" role="region" aria-label="Daftar KDO milik Perangkat Daerah" class="table-wrapper" style="border:none;border-radius:0;">
              <table class="data-table">
                <thead>
                  <tr>
                    <th scope="col">Nomor Polisi</th>
                    <th scope="col">Merk &amp; Tipe</th>
                    <th scope="col">Tahun &amp; Warna</th>
                    <th scope="col">Kondisi</th>
                    <th scope="col">Penanggung Jawab (PNS)</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  ${daftarKdo.length ? daftarKdo.map((k) => `
                    <tr>
                      <th scope="row"><span class="nopol-badge">${escapeHtml(k.nomor_polisi_kendaraan)}</span></th>
                      <td><strong>${escapeHtml(k.merk_kendaraan)}</strong> — ${escapeHtml(k.tipe_kendaraan)}</td>
                      <td>${escapeHtml(k.tahun_produksi)} • ${escapeHtml(k.warna_kendaraan)}</td>
                      <td>${renderKondisiBadge(k.kondisi)}</td>
                      <td>${escapeHtml(k.nama_pegawai || '-')}</td>
                      <td><button type="button" class="btn btn-sm btn-secondary" data-action="view-detail" data-entity="kdo" data-id="${k.id}">Detail KDO</button></td>
                    </tr>
                  `).join('') : '<tr><td colspan="6" class="empty-state">Belum ada aset KDO tercatat pada Perangkat Daerah ini.</td></tr>'}
                </tbody>
              </table>
            </div>
          </div>
        `;
      }
    } catch (err) {
      showToast('Gagal memuat detail data: ' + err.message, 'error');
    }
  }

  // ==========================================================================
  // 11. HALAMAN FORM (CREATE & UPDATE)
  // ==========================================================================
  async function openForm(entity, editId = null, prefill = {}) {
    await refreshMasterData();
    switchView('form', true);

    const cards = {
      kdo: document.getElementById('form-card-kdo'),
      pemakaian: document.getElementById('form-card-pemakaian'),
      pns: document.getElementById('form-card-pns'),
      pd: document.getElementById('form-card-pd'),
    };
    Object.entries(cards).forEach(([k, card]) => {
      if (card) card.hidden = k !== entity;
    });

    const pageHeading = document.getElementById('form-page-heading');
    const isEdit = editId !== null && editId !== undefined && Number(editId) > 0;
    renderBreadcrumb('form-breadcrumb', entity, isEdit ? 'Edit Data' : 'Tambah Data Baru');

    if (entity === 'kdo') {
      const form = document.getElementById('entity-form-kdo');
      form.reset();
      document.getElementById('kdo-id').value = isEdit ? editId : '';
      pageHeading.textContent = isEdit
        ? 'Edit Data Kendaraan Dinas Operasional (KDO)'
        : 'Tambah Kendaraan Dinas Operasional (KDO) Baru';
      document.getElementById('btn-submit-kdo').textContent = isEdit ? 'Simpan Perubahan KDO' : 'Simpan Data KDO';

      if (isEdit) {
        const res = await apiRequest(`api/kdo.php?id=${encodeURIComponent(editId)}`);
        const d = res.data;
        document.getElementById('kdo-nopol').value = d.nomor_polisi_kendaraan || '';
        document.getElementById('kdo-kode-barang').value = d.kode_barang || '1.3.2.02.01.01.001';
        document.getElementById('kdo-merk').value = d.merk_kendaraan || '';
        document.getElementById('kdo-tipe').value = d.tipe_kendaraan || '';
        document.getElementById('kdo-tahun').value = d.tahun_produksi || '';
        document.getElementById('kdo-warna').value = d.warna_kendaraan || '';
        document.getElementById('kdo-rangka').value = d.nomor_rangka || '';
        document.getElementById('kdo-mesin').value = d.nomor_mesin || '';
        document.getElementById('kdo-perangkat').value = d.perangkat_id || '';
        document.getElementById('kdo-pegawai').value = d.pegawai_id || '';
        document.getElementById('kdo-keterangan').value = d.keterangan || '';

        const jenisRadio = form.querySelector(`input[name="jenis_kendaraan"][value="${d.jenis_kendaraan}"]`);
        if (jenisRadio) jenisRadio.checked = true;
        const kondisiRadio = form.querySelector(`input[name="kondisi"][value="${d.kondisi}"]`);
        if (kondisiRadio) kondisiRadio.checked = true;
      }
      document.getElementById('kdo-nopol').focus();
    } else if (entity === 'pemakaian') {
      const form = document.getElementById('entity-form-pemakaian');
      form.reset();
      document.getElementById('pakai-id').value = isEdit ? editId : '';
      document.getElementById('pakai-tgl-mulai').value = new Date().toISOString().slice(0, 10);
      pageHeading.textContent = isEdit
        ? 'Edit / Selesaikan Pemakaian KDO'
        : 'Input Pencatatan Pemakaian KDO Baru';
      document.getElementById('btn-submit-pemakaian').textContent = isEdit ? 'Perbarui Data Pemakaian' : 'Simpan Data Pemakaian';

      if (isEdit) {
        const res = await apiRequest(`api/pemakaian.php?id=${encodeURIComponent(editId)}`);
        const d = res.data;
        document.getElementById('pakai-kdo').value = d.kdo_id || '';
        document.getElementById('pakai-surat').value = d.nomor_surat_tugas || '';
        document.getElementById('pakai-perangkat').value = d.perangkat_id || '';
        document.getElementById('pakai-pegawai').value = d.pegawai_id || '';
        document.getElementById('pakai-keperluan').value = d.keperluan || '';
        document.getElementById('pakai-tujuan').value = d.tujuan || '';
        document.getElementById('pakai-tgl-mulai').value = d.tanggal_mulai || '';
        document.getElementById('pakai-tgl-selesai').value = d.tanggal_selesai || '';
        document.getElementById('pakai-km-awal').value = d.km_awal ?? 0;
        document.getElementById('pakai-km-akhir').value = d.km_akhir ?? '';
        document.getElementById('pakai-catatan').value = d.catatan || '';

        const stRadio = form.querySelector(`input[name="status_pemakaian"][value="${d.status_pemakaian}"]`);
        if (stRadio) stRadio.checked = true;
        const kondRadio = form.querySelector(`input[name="kondisi_setelah"][value="${d.kondisi_setelah || 'Baik'}"]`);
        if (kondRadio) kondRadio.checked = true;
      } else if (prefill.kdoId) {
        const kdoSel = document.getElementById('pakai-kdo');
        kdoSel.value = prefill.kdoId;
        kdoSel.dispatchEvent(new Event('change', { bubbles: true }));
      }
      document.getElementById('pakai-kdo').focus();
    } else if (entity === 'pns') {
      const form = document.getElementById('entity-form-pns');
      form.reset();
      document.getElementById('pns-id').value = isEdit ? editId : '';
      pageHeading.textContent = isEdit
        ? 'Edit Data Pegawai Negeri Sipil (PNS)'
        : 'Tambah Pegawai Negeri Sipil (PNS) Baru';
      document.getElementById('btn-submit-pns').textContent = isEdit ? 'Simpan Perubahan PNS' : 'Simpan Data PNS';

      if (isEdit) {
        const res = await apiRequest(`api/pns.php?id=${encodeURIComponent(editId)}`);
        const d = res.data;
        document.getElementById('pns-nama').value = d.nama_pegawai || '';
        document.getElementById('pns-nip').value = d.nip || '';
        document.getElementById('pns-jabatan').value = d.jabatan || '';
        document.getElementById('pns-golongan').value = d.golongan || '';
        document.getElementById('pns-perangkat').value = d.perangkat_id || '';
        document.getElementById('pns-hp').value = d.no_hp || '';
      }
      document.getElementById('pns-nama').focus();
    } else if (entity === 'pd') {
      const form = document.getElementById('entity-form-pd');
      form.reset();
      document.getElementById('pd-id').value = isEdit ? editId : '';
      pageHeading.textContent = isEdit
        ? 'Edit Data Perangkat Daerah (PD)'
        : 'Tambah Perangkat Daerah (PD) Baru';
      document.getElementById('btn-submit-pd').textContent = isEdit ? 'Simpan Perubahan PD' : 'Simpan Perangkat Daerah';

      if (isEdit) {
        const res = await apiRequest(`api/pd.php?id=${encodeURIComponent(editId)}`);
        const d = res.data;
        document.getElementById('pd-nama').value = d.nama_perangkat || '';
        document.getElementById('pd-kode').value = d.kode_perangkat || '';
        document.getElementById('pd-singkatan').value = d.singkatan || '';
        document.getElementById('pd-telepon').value = d.telepon || '';
        document.getElementById('pd-alamat').value = d.alamat || '';
      }
      document.getElementById('pd-nama').focus();
    }
  }

  async function handleFormSubmit(event, entity, endpoint) {
    event.preventDefault();
    const form = event.currentTarget;

    if (!form.checkValidity()) {
      const firstInvalid = form.querySelector(':invalid');
      if (firstInvalid) {
        firstInvalid.classList.add('user-invalid-fallback');
        syncAria(firstInvalid, true);
        firstInvalid.focus();
      }
      return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    const formData = new FormData(form);
    const payload = {};
    formData.forEach((value, key) => {
      payload[key] = value;
    });

    const isUpdate = payload.id && String(payload.id).trim() !== '';

    try {
      const res = await apiRequest(endpoint, {
        method: isUpdate ? 'PUT' : 'POST',
        body: payload
      });

      showToast(res.message || 'Data berhasil disimpan.', 'success');
      await refreshMasterData();
      switchView(entity);
    } catch (err) {
      showToast(err.message || 'Terjadi kesalahan saat menyimpan data.', 'error');

      if (err.errors && typeof err.errors === 'object') {
        const firstKey = Object.keys(err.errors)[0];
        const field = form.querySelector(`[name="${firstKey}"]`);
        if (field) {
          field.setCustomValidity(err.errors[firstKey]);
          field.classList.add('user-invalid-fallback');
          syncAria(field, true);
          field.focus();
        }
      }
    } finally {
      if (submitBtn) submitBtn.disabled = false;
    }
  }

  // ==========================================================================
  // 12. EVENT WIRING & INITIALIZATION
  // ==========================================================================
  function bindEvents() {
    document.addEventListener('click', (e) => {
      const navBtn = e.target.closest('[data-nav]');
      if (navBtn) {
        e.preventDefault();
        switchView(navBtn.dataset.nav);
        return;
      }

      const actionBtn = e.target.closest('[data-action]');
      if (!actionBtn) return;

      const action = actionBtn.dataset.action;
      const entity = actionBtn.dataset.entity;
      const id = actionBtn.dataset.id;

      if (action === 'open-form') {
        const prefill = {};
        if (actionBtn.dataset.prefillKdo) {
          prefill.kdoId = actionBtn.dataset.prefillKdo;
        }
        openForm(entity, null, prefill);
      } else if (action === 'edit-entity') {
        openForm(entity, id);
      } else if (action === 'view-detail') {
        openDetail(entity, id);
      } else if (action === 'delete-entity') {
        promptDelete(entity, id, actionBtn.dataset.label || `#${id}`);
      } else if (action === 'cancel-form') {
        switchView(state.previousView || 'dashboard');
      } else if (action === 'sort-table') {
        sortTableByHeaderButton(actionBtn);
      } else if (action === 'toggle-kdo-mode') {
        setKdoViewMode(actionBtn.dataset.mode);
      } else if (action === 'chip-kdo-kondisi') {
        const sel = document.getElementById('filter-kdo-kondisi');
        if (sel) {
          sel.value = actionBtn.dataset.value || '';
          loadKdoList();
        }
      } else if (action === 'chip-pakai-status') {
        const sel = document.getElementById('filter-pakai-status');
        if (sel) {
          sel.value = actionBtn.dataset.value || '';
          loadPemakaianList();
        }
      } else if (action === 'quick-filter-kdo') {
        const sel = document.getElementById('filter-kdo-kondisi');
        if (sel) {
          sel.value = actionBtn.dataset.kondisi || '';
        }
        switchView('kdo');
      } else if (action === 'complete-pakai') {
        openCompletePakaiDialog(
          actionBtn.dataset.id,
          actionBtn.dataset.kmawal,
          actionBtn.dataset.surat,
          actionBtn.dataset.nopol
        );
      } else if (action === 'close-complete-dialog') {
        closeTopLayer(document.getElementById('complete-pakai-dialog'), 'cancel');
      } else if (action === 'print-page') {
        window.print();
      }
    });

    const backBtn = document.getElementById('btn-form-back');
    if (backBtn) {
      backBtn.addEventListener('click', () => {
        switchView(state.previousView || 'dashboard');
      });
    }

    const pakaiKdoSel = document.getElementById('pakai-kdo');
    if (pakaiKdoSel) {
      pakaiKdoSel.addEventListener('change', () => {
        const opt = pakaiKdoSel.selectedOptions[0];
        if (!opt) return;
        const pdId = opt.dataset.pd;
        const pnsId = opt.dataset.pns;
        const pdSel = document.getElementById('pakai-perangkat');
        const pnsSel = document.getElementById('pakai-pegawai');
        if (pdId && pdSel && !pdSel.value) pdSel.value = pdId;
        if (pnsId && pnsSel && !pnsSel.value) pnsSel.value = pnsId;
      });
    }

    let searchTimer = null;
    const debouncedRun = (fn) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(fn, 200);
    };

    // KDO Filters
    ['filter-kdo-search', 'filter-kdo-pd', 'filter-kdo-kondisi', 'filter-kdo-jenis'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => debouncedRun(loadKdoList));
    });
    document.getElementById('filter-form-kdo')?.addEventListener('submit', (e) => {
      e.preventDefault();
      loadKdoList();
    });
    document.getElementById('btn-reset-kdo')?.addEventListener('click', () => {
      document.getElementById('filter-form-kdo')?.reset();
      loadKdoList();
    });

    // Pemakaian Filters
    ['filter-pakai-search', 'filter-pakai-pd', 'filter-pakai-status'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => debouncedRun(loadPemakaianList));
    });
    document.getElementById('filter-form-pemakaian')?.addEventListener('submit', (e) => {
      e.preventDefault();
      loadPemakaianList();
    });
    document.getElementById('btn-reset-pemakaian')?.addEventListener('click', () => {
      document.getElementById('filter-form-pemakaian')?.reset();
      loadPemakaianList();
    });

    // PNS Filters
    ['filter-pns-search', 'filter-pns-pd', 'filter-pns-golongan'].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', () => debouncedRun(loadPnsList));
    });
    document.getElementById('filter-form-pns')?.addEventListener('submit', (e) => {
      e.preventDefault();
      loadPnsList();
    });
    document.getElementById('btn-reset-pns')?.addEventListener('click', () => {
      document.getElementById('filter-form-pns')?.reset();
      loadPnsList();
    });

    // PD Filters
    document.getElementById('filter-pd-search')?.addEventListener('input', () => debouncedRun(loadPdList));
    document.getElementById('filter-form-pd')?.addEventListener('submit', (e) => {
      e.preventDefault();
      loadPdList();
    });
    document.getElementById('btn-reset-pd')?.addEventListener('click', () => {
      document.getElementById('filter-form-pd')?.reset();
      loadPdList();
    });

    // Entity Form Submissions
    document.getElementById('entity-form-kdo')?.addEventListener('submit', (e) => handleFormSubmit(e, 'kdo', 'api/kdo.php'));
    document.getElementById('entity-form-pemakaian')?.addEventListener('submit', (e) => handleFormSubmit(e, 'pemakaian', 'api/pemakaian.php'));
    document.getElementById('entity-form-pns')?.addEventListener('submit', (e) => handleFormSubmit(e, 'pns', 'api/pns.php'));
    document.getElementById('entity-form-pd')?.addEventListener('submit', (e) => handleFormSubmit(e, 'pd', 'api/pd.php'));
    document.getElementById('complete-pakai-form')?.addEventListener('submit', handleCompletePakaiSubmit);
  }

  document.addEventListener('DOMContentLoaded', async () => {
    initLiveClock();
    initFormValidationAccessibility();
    initDialogs();
    bindEvents();
    await refreshMasterData();
    await loadDashboard();
  });
})();
