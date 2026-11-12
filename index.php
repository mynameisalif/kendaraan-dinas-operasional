<?php
/**
 * Halaman Utama Aplikasi Pengelolaan Kendaraan Dinas Operasional (SI-KDO BMD)
 * Barang Milik Daerah (BMD) Pemerintah Daerah
 */
require_once __DIR__ . '/config/db.php';

// Ambil hitungan awal untuk badge navigasi saat halaman pertama kali dimuat
$initCounts = [
    'kdo'       => (int)$pdo->query("SELECT COUNT(*) FROM kdo")->fetchColumn(),
    'pemakaian' => (int)$pdo->query("SELECT COUNT(*) FROM pemakaian_kdo")->fetchColumn(),
    'pns'       => (int)$pdo->query("SELECT COUNT(*) FROM pns")->fetchColumn(),
    'pd'        => (int)$pdo->query("SELECT COUNT(*) FROM perangkat_daerah")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SI-KDO BMD — Sistem Pengelolaan Kendaraan Dinas Operasional</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <!-- Header & Navigasi Utama -->
  <header class="app-header">
    <div class="header-topbar">
      <div class="header-topbar-inner">
        <span class="topbar-tag">
          <span class="topbar-dot" aria-hidden="true"></span>
          Tata Kelola Barang Milik Daerah (BMD) — Kartu Inventaris Barang (KIB B) Perangkat Daerah
        </span>
        <span id="live-clock-label">Kamis, 12 November 2026 • Waktu Indonesia Barat</span>
      </div>
    </div>

    <div class="header-inner">
      <div class="brand" data-nav="dashboard" role="button" tabindex="0" aria-label="Kembali ke Dashboard Utama">
        <div class="brand-badge" aria-hidden="true">KDO</div>
        <div class="brand-text">
          <h1>SI-KDO Perangkat Daerah</h1>
          <p>Sistem Informasi Pengelolaan Kendaraan Dinas Operasional &amp; BMD</p>
        </div>
      </div>

      <nav class="main-nav" aria-label="Navigasi Utama Aplikasi">
        <button type="button" class="nav-btn" data-nav="dashboard" aria-current="page">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="3" width="7" height="9" rx="1"></rect>
            <rect x="14" y="3" width="7" height="5" rx="1"></rect>
            <rect x="14" y="12" width="7" height="9" rx="1"></rect>
            <rect x="3" y="16" width="7" height="5" rx="1"></rect>
          </svg>
          <span>Dashboard</span>
        </button>
        <button type="button" class="nav-btn" data-nav="kdo">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"></path>
            <circle cx="6.5" cy="16.5" r="2.5"></circle>
            <circle cx="16.5" cy="16.5" r="2.5"></circle>
          </svg>
          <span>Data KDO</span>
          <span class="nav-count" id="nav-count-kdo"><?= $initCounts['kdo'] ?></span>
        </button>
        <button type="button" class="nav-btn" data-nav="pemakaian">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M9 11l3 3L22 4"></path>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
          </svg>
          <span>Pemakaian KDO</span>
          <span class="nav-count" id="nav-count-pemakaian"><?= $initCounts['pemakaian'] ?></span>
        </button>
        <button type="button" class="nav-btn" data-nav="pns">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
          <span>Pegawai (PNS)</span>
          <span class="nav-count" id="nav-count-pns"><?= $initCounts['pns'] ?></span>
        </button>
        <button type="button" class="nav-btn" data-nav="pd">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M3 21h18"></path>
            <path d="M5 21V7l8-4v18"></path>
            <path d="M19 21V11l-6-4"></path>
            <path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"></path>
          </svg>
          <span>Perangkat Daerah</span>
          <span class="nav-count" id="nav-count-pd"><?= $initCounts['pd'] ?></span>
        </button>
      </nav>
    </div>
  </header>

  <main class="app-main" id="main-content">
    <!-- Visually Hidden Status Region for Sortable Tables (modern-web-guidance sortable-table) -->
    <div id="table-sort-status" role="status" class="visually-hidden"></div>

    <!-- ====================================================================
         1. HALAMAN DASHBOARD
         ==================================================================== -->
    <section id="view-dashboard" class="view-section" aria-labelledby="heading-dashboard">
      <!-- Executive Hero Banner -->
      <div class="hero-banner">
        <div class="hero-content">
          <span class="hero-eyebrow">★ Portal Kendali Aset Kendaraan Dinas (KIB B)</span>
          <h2 id="heading-dashboard" tabindex="-1">Dashboard Pengelolaan Kendaraan Dinas Operasional</h2>
          <p>Pantau kesiapan operasional armada dinas, kondisi teknis kendaraan, penanggung jawab ASN/PNS, serta penugasan Surat Tugas secara terpusat di seluruh Perangkat Daerah.</p>
        </div>
        <div class="hero-actions">
          <button type="button" class="btn btn-gold" data-action="open-form" data-entity="kdo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah KDO
          </button>
          <button type="button" class="btn btn-success" data-action="open-form" data-entity="pemakaian">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Catat Pemakaian KDO
          </button>
          <button type="button" class="btn btn-glass" data-action="open-form" data-entity="pns">
            + PNS Baru
          </button>
          <button type="button" class="btn btn-glass" data-action="open-form" data-entity="pd">
            + Perangkat Daerah
          </button>
        </div>
      </div>

      <!-- KPI Cards (Clickable to filter) -->
      <div class="kpi-grid" id="dashboard-kpi-grid">
        <div class="kpi-card" data-clickable="true" data-action="quick-filter-kdo" data-kondisi="">
          <div class="kpi-top">
            <div>
              <span class="kpi-label">Total Aset KDO</span>
              <div class="kpi-value" id="kpi-total-kdo">0</div>
            </div>
            <div class="kpi-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"></path>
                <circle cx="6.5" cy="16.5" r="2.5"></circle>
                <circle cx="16.5" cy="16.5" r="2.5"></circle>
              </svg>
            </div>
          </div>
          <div class="kpi-sub">
            <span class="status-badge badge-neutral" id="kpi-jenis-summary">Memuat...</span>
            <span id="kpi-kondisi-breakdown" style="font-size:0.78rem;color:var(--text-muted);"></span>
          </div>
        </div>

        <div class="kpi-card kpi-baik" data-clickable="true" data-action="quick-filter-kdo" data-kondisi="Baik">
          <div class="kpi-top">
            <div>
              <span class="kpi-label">Kondisi Baik (Laik Jalan)</span>
              <div class="kpi-value" id="kpi-kdo-baik">0</div>
            </div>
            <div class="kpi-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              </svg>
            </div>
          </div>
          <div class="kpi-sub">
            <span class="status-badge badge-baik">✔ Siap Operasional</span>
            <span id="kpi-persen-baik">0% armada</span>
          </div>
        </div>

        <div class="kpi-card kpi-perhatian" data-clickable="true" data-action="quick-filter-kdo" data-kondisi="Rusak Ringan">
          <div class="kpi-top">
            <div>
              <span class="kpi-label">Perlu Pemeliharaan</span>
              <div class="kpi-value" id="kpi-kdo-rusak">0</div>
            </div>
            <div class="kpi-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
              </svg>
            </div>
          </div>
          <div class="kpi-sub">
            <span class="status-badge badge-rusak-ringan">⚠ Perhatian Servis</span>
            <span id="kpi-rusak-detail">0 RR • 0 RB</span>
          </div>
        </div>

        <div class="kpi-card kpi-aktif" data-clickable="true" data-nav="pemakaian">
          <div class="kpi-top">
            <div>
              <span class="kpi-label">Operasional &amp; Penanggung Jawab</span>
              <div class="kpi-value" id="kpi-total-pns">0 PNS</div>
            </div>
            <div class="kpi-icon-box" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
          </div>
          <div class="kpi-sub">
            <span class="status-badge badge-digunakan" id="kpi-pakai-aktif">0 Aktif Jalan</span>
            <span id="kpi-total-pd">0 OPD</span>
          </div>
        </div>
      </div>

      <!-- Grid Grafik Kondisi & Distribusi Perangkat Daerah -->
      <div class="dashboard-grid">
        <div class="card col-span-5">
          <div class="card-header">
            <h3> Komposisi &amp; Kesehatan Fisik KDO</h3>
          </div>
          <div class="card-body">
            <div class="segmented-meter" id="dashboard-segmented-meter" aria-hidden="true"></div>
            <div class="condition-bars" id="dashboard-condition-bars">
              <!-- Diisi oleh JS -->
            </div>
            <div class="category-pills" id="dashboard-category-pills">
              <!-- Diisi oleh JS -->
            </div>
          </div>
        </div>

        <div class="card col-span-7">
          <div class="card-header">
            <h3>⚠ Daftar Kendaraan Memerlukan Pemeliharaan / Servis</h3>
            <button type="button" class="btn btn-sm btn-secondary" data-nav="kdo">Lihat Semua KDO</button>
          </div>
          <div class="card-body" id="dashboard-perhatian-list">
            <!-- Diisi oleh JS -->
          </div>
        </div>

        <!-- Tabel Distribusi KDO per Perangkat Daerah -->
        <div class="card col-span-12">
          <div class="card-header">
            <h3>Rekapitulasi Penguasaan KDO per Perangkat Daerah (OPD / SKPD)</h3>
            <div class="row-actions">
              <button type="button" class="btn btn-sm btn-secondary" data-action="print-page">🖨 Cetak Ringkasan</button>
              <button type="button" class="btn btn-sm btn-primary" data-nav="pd">Kelola Perangkat Daerah</button>
            </div>
          </div>
          <div tabindex="0" role="region" aria-labelledby="caption-rekap-pd" class="table-wrapper" style="border:none;border-radius:0;">
            <table id="table-rekap-pd" class="data-table" style="--label-2:'Singkatan';--label-3:'Jumlah PNS';--label-4:'Total KDO';--label-5:'Kondisi Baik';--label-6:'Rusak Ringan/Berat';--label-7:'Aksi';">
              <caption id="caption-rekap-pd" class="visually-hidden">Rekapitulasi jumlah KDO dan kondisi per Perangkat Daerah</caption>
              <thead>
                <tr>
                  <th scope="col" data-type="text">
                    <button type="button" class="sort-btn" data-action="sort-table">
                      <span>Perangkat Daerah</span>
                      <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                    </button>
                  </th>
                  <th scope="col">Singkatan</th>
                  <th scope="col" data-type="number">
                    <button type="button" class="sort-btn" data-action="sort-table">
                      <span>PNS Tercatat</span>
                      <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                    </button>
                  </th>
                  <th scope="col" data-type="number">
                    <button type="button" class="sort-btn" data-action="sort-table">
                      <span>Total KDO</span>
                      <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                    </button>
                  </th>
                  <th scope="col">Kondisi Baik</th>
                  <th scope="col">Rusak Ringan / Berat</th>
                  <th scope="col">Aksi</th>
                </tr>
              </thead>
              <tbody id="dashboard-pd-tbody">
                <!-- Diisi oleh JS -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pemakaian Terbaru -->
        <div class="card col-span-12">
          <div class="card-header">
            <h3>Log Pemakaian Operasional &amp; Surat Tugas Terbaru</h3>
            <button type="button" class="btn btn-sm btn-primary" data-nav="pemakaian">Lihat Seluruh Pemakaian</button>
          </div>
          <div tabindex="0" role="region" aria-labelledby="caption-pakai-terbaru" class="table-wrapper" style="border:none;border-radius:0;">
            <table class="data-table" style="--label-2:'Kendaraan (KDO)';--label-3:'PNS Pengguna';--label-4:'Perangkat Daerah';--label-5:'Keperluan & Tujuan';--label-6:'Status';--label-7:'Aksi';">
              <caption id="caption-pakai-terbaru" class="visually-hidden">Daftar 6 aktivitas pemakaian KDO terbaru</caption>
              <thead>
                <tr>
                  <th scope="col">No. Surat Tugas &amp; Tanggal</th>
                  <th scope="col">Kendaraan (KDO)</th>
                  <th scope="col">PNS Pengguna</th>
                  <th scope="col">Perangkat Daerah</th>
                  <th scope="col">Keperluan &amp; Tujuan</th>
                  <th scope="col">Status</th>
                  <th scope="col">Aksi</th>
                </tr>
              </thead>
              <tbody id="dashboard-pakai-tbody">
                <!-- Diisi oleh JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <!-- ====================================================================
         2. HALAMAN LIST: KENDARAAN DINAS OPERASIONAL (KDO)
         ==================================================================== -->
    <section id="view-kdo" class="view-section" hidden aria-labelledby="heading-kdo">
      <div class="page-header">
        <div class="page-title-group">
          <h2 id="heading-kdo" tabindex="-1">Inventaris Kendaraan Dinas Operasional (KDO)</h2>
          <p>Data Barang Milik Daerah (KIB B) kendaraan bermotor dinas beserta kondisi fisik dan PNS penanggung jawab.</p>
        </div>
        <div class="action-group">
          <button type="button" class="btn btn-secondary" data-action="print-page">🖨 Cetak Daftar</button>
          <button type="button" class="btn btn-primary" data-action="open-form" data-entity="kdo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Kendaraan Dinas (KDO)
          </button>
        </div>
      </div>

      <search class="filter-bar" aria-label="Pencarian dan Filter Data KDO">
        <form id="filter-form-kdo" class="filter-form" action="api/kdo.php" method="GET">
          <div class="filter-field">
            <label for="filter-kdo-search">Cari Nopol / Merk / Tipe / PNS</label>
            <input type="search" id="filter-kdo-search" name="search" placeholder="Contoh: B 1024 PQN atau Innova" autocorrect="off" autocapitalize="none" spellcheck="false">
          </div>
          <div class="filter-field">
            <label for="filter-kdo-pd">Perangkat Daerah</label>
            <select id="filter-kdo-pd" name="perangkat_id">
              <option value="">Semua Perangkat Daerah</option>
            </select>
          </div>
          <div class="filter-field">
            <label for="filter-kdo-kondisi">Kondisi Kendaraan</label>
            <select id="filter-kdo-kondisi" name="kondisi">
              <option value="">Semua Kondisi</option>
              <option value="Baik">Baik</option>
              <option value="Rusak Ringan">Rusak Ringan</option>
              <option value="Rusak Berat">Rusak Berat</option>
            </select>
          </div>
          <div class="filter-field">
            <label for="filter-kdo-jenis">Jenis Kendaraan</label>
            <select id="filter-kdo-jenis" name="jenis_kendaraan">
              <option value="">Semua Jenis</option>
              <option value="Roda 4">Roda 4</option>
              <option value="Roda 2">Roda 2</option>
              <option value="Roda 6 / Khusus">Roda 6 / Khusus</option>
            </select>
          </div>
          <div class="filter-field">
            <button type="button" class="btn btn-secondary" id="btn-reset-kdo">Reset Filter</button>
          </div>
        </form>
      </search>

      <!-- Quick Filter Chips & View Mode Switcher (Tabel vs Kartu Armada) -->
      <div class="quick-chips-bar">
        <div class="chip-group" role="group" aria-label="Filter Cepat Kondisi KDO">
          <button type="button" class="filter-chip" data-action="chip-kdo-kondisi" data-value="" aria-pressed="true">Semua KDO</button>
          <button type="button" class="filter-chip" data-action="chip-kdo-kondisi" data-value="Baik" aria-pressed="false">✔ Kondisi Baik</button>
          <button type="button" class="filter-chip" data-action="chip-kdo-kondisi" data-value="Rusak Ringan" aria-pressed="false">⚠ Rusak Ringan</button>
          <button type="button" class="filter-chip" data-action="chip-kdo-kondisi" data-value="Rusak Berat" aria-pressed="false">✖ Rusak Berat</button>
        </div>

        <div class="view-mode-toggle" role="group" aria-label="Mode Tampilan Daftar KDO">
          <button type="button" class="view-mode-btn" data-action="toggle-kdo-mode" data-mode="table" aria-pressed="true">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
            Tabel Data
          </button>
          <button type="button" class="view-mode-btn" data-action="toggle-kdo-mode" data-mode="cards" aria-pressed="false">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
            Kartu Armada
          </button>
        </div>
      </div>

      <!-- Mode 1: Sortable & Responsive Table -->
      <div id="kdo-table-wrapper" tabindex="0" role="region" aria-labelledby="caption-table-kdo" class="table-wrapper">
        <table id="table-kdo" class="data-table" style="--label-2:'Merk & Tipe';--label-3:'Tahun & Warna';--label-4:'Kondisi';--label-5:'Penanggung Jawab (PNS)';--label-6:'Perangkat Daerah';--label-7:'Aksi';">
          <caption id="caption-table-kdo">Daftar Kendaraan Dinas Operasional (KDO) Terdaftar — Klik judul kolom untuk mengurutkan</caption>
          <thead>
            <tr>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Nomor Polisi</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Merk &amp; Tipe Kendaraan</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="number">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Tahun &amp; Warna</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Kondisi</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Penanggung Jawab (PNS)</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Perangkat Daerah</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-kdo">
            <!-- Diisi oleh JS -->
          </tbody>
        </table>
      </div>

      <!-- Mode 2: Katalog Kartu Armada KDO -->
      <div id="kdo-card-grid" class="kdo-card-grid" hidden>
        <!-- Diisi oleh JS -->
      </div>
    </section>

    <!-- ====================================================================
         3. HALAMAN LIST: PEMAKAIAN KDO
         ==================================================================== -->
    <section id="view-pemakaian" class="view-section" hidden aria-labelledby="heading-pemakaian">
      <div class="page-header">
        <div class="page-title-group">
          <h2 id="heading-pemakaian" tabindex="-1">Daftar Pemakaian Kendaraan Dinas Operasional</h2>
          <p>Pencatatan operasional penggunaan KDO berdasarkan Surat Tugas / SPPD beserta PNS pengguna dan Perangkat Daerah.</p>
        </div>
        <div class="action-group">
          <button type="button" class="btn btn-success" data-action="open-form" data-entity="pemakaian">
            + Input Pemakaian KDO Baru
          </button>
        </div>
      </div>

      <search class="filter-bar" aria-label="Pencarian dan Filter Pemakaian KDO">
        <form id="filter-form-pemakaian" class="filter-form" action="api/pemakaian.php" method="GET">
          <div class="filter-field">
            <label for="filter-pakai-search">Cari No. Surat / Nopol / Keperluan / PNS</label>
            <input type="search" id="filter-pakai-search" name="search" placeholder="Contoh: SPPD, Monitoring, B 1482 PQN" autocorrect="off" autocapitalize="none" spellcheck="false">
          </div>
          <div class="filter-field">
            <label for="filter-pakai-pd">Perangkat Daerah</label>
            <select id="filter-pakai-pd" name="perangkat_id">
              <option value="">Semua Perangkat Daerah</option>
            </select>
          </div>
          <div class="filter-field">
            <label for="filter-pakai-status">Status Pemakaian</label>
            <select id="filter-pakai-status" name="status_pemakaian">
              <option value="">Semua Status</option>
              <option value="Digunakan">Sedang Digunakan</option>
              <option value="Terjadwal">Terjadwal</option>
              <option value="Selesai">Selesai</option>
            </select>
          </div>
          <div class="filter-field">
            <button type="button" class="btn btn-secondary" id="btn-reset-pemakaian">Reset Filter</button>
          </div>
        </form>
      </search>

      <!-- Quick Status Filter Chips -->
      <div class="quick-chips-bar">
        <div class="chip-group" role="group" aria-label="Filter Cepat Status Pemakaian">
          <button type="button" class="filter-chip" data-action="chip-pakai-status" data-value="" aria-pressed="true">Semua Log</button>
          <button type="button" class="filter-chip" data-action="chip-pakai-status" data-value="Digunakan" aria-pressed="false">🚗 Sedang Digunakan</button>
          <button type="button" class="filter-chip" data-action="chip-pakai-status" data-value="Terjadwal" aria-pressed="false">📅 Terjadwal</button>
          <button type="button" class="filter-chip" data-action="chip-pakai-status" data-value="Selesai" aria-pressed="false">✔ Selesai</button>
        </div>
      </div>

      <div tabindex="0" role="region" aria-labelledby="caption-table-pemakaian" class="table-wrapper">
        <table id="table-pemakaian" class="data-table" style="--label-2:'Kendaraan (KDO)';--label-3:'Pegawai (PNS) & PD';--label-4:'Keperluan & Tujuan';--label-5:'Periode & Odometer';--label-6:'Status & Kondisi';--label-7:'Aksi';">
          <caption id="caption-table-pemakaian">Daftar Penugasan &amp; Riwayat Pemakaian KDO — Klik judul kolom untuk mengurutkan</caption>
          <thead>
            <tr>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>No. Surat Tugas</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Kendaraan (KDO)</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Pegawai (PNS) &amp; PD</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Keperluan &amp; Tujuan</th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Periode &amp; Odometer</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Status &amp; Kondisi Akhir</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-pemakaian">
            <!-- Diisi oleh JS -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- ====================================================================
         4. HALAMAN LIST: PEGAWAI NEGERI SIPIL (PNS)
         ==================================================================== -->
    <section id="view-pns" class="view-section" hidden aria-labelledby="heading-pns">
      <div class="page-header">
        <div class="page-title-group">
          <h2 id="heading-pns" tabindex="-1">Direktori Pegawai Negeri Sipil (PNS)</h2>
          <p>Data ASN / PNS yang bertanggung jawab sebagai pemegang dan pengguna Kendaraan Dinas Operasional.</p>
        </div>
        <div class="action-group">
          <button type="button" class="btn btn-primary" data-action="open-form" data-entity="pns">
            + Tambah Data PNS
          </button>
        </div>
      </div>

      <search class="filter-bar" aria-label="Pencarian dan Filter Data PNS">
        <form id="filter-form-pns" class="filter-form" action="api/pns.php" method="GET">
          <div class="filter-field">
            <label for="filter-pns-search">Cari Nama / NIP / Jabatan</label>
            <input type="search" id="filter-pns-search" name="search" placeholder="Ketik Nama atau 18 digit NIP..." autocorrect="off" autocapitalize="none" spellcheck="false">
          </div>
          <div class="filter-field">
            <label for="filter-pns-pd">Unit Kerja (Perangkat Daerah)</label>
            <select id="filter-pns-pd" name="perangkat_id">
              <option value="">Semua Perangkat Daerah</option>
            </select>
          </div>
          <div class="filter-field">
            <label for="filter-pns-golongan">Golongan Ruang</label>
            <select id="filter-pns-golongan" name="golongan">
              <option value="">Semua Golongan</option>
              <option value="IV/d">IV/d</option>
              <option value="IV/c">IV/c</option>
              <option value="IV/b">IV/b</option>
              <option value="IV/a">IV/a</option>
              <option value="III/d">III/d</option>
              <option value="III/c">III/c</option>
              <option value="III/b">III/b</option>
              <option value="III/a">III/a</option>
              <option value="II/d">II/d</option>
              <option value="II/c">II/c</option>
            </select>
          </div>
          <div class="filter-field">
            <button type="button" class="btn btn-secondary" id="btn-reset-pns">Reset Filter</button>
          </div>
        </form>
      </search>

      <div tabindex="0" role="region" aria-labelledby="caption-table-pns" class="table-wrapper">
        <table id="table-pns" class="data-table" style="--label-2:'NIP';--label-3:'Jabatan';--label-4:'Golongan';--label-5:'Perangkat Daerah';--label-6:'KDO Dipegang';--label-7:'Aksi';">
          <caption id="caption-table-pns">Direktori Pegawai Negeri Sipil (PNS) Penanggung Jawab KDO</caption>
          <thead>
            <tr>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Nama Pegawai</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>NIP (18 Digit)</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Jabatan</th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Golongan</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Perangkat Daerah</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="number">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>KDO Dipegang</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-pns">
            <!-- Diisi oleh JS -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- ====================================================================
         5. HALAMAN LIST: PERANGKAT DAERAH (PD)
         ==================================================================== -->
    <section id="view-pd" class="view-section" hidden aria-labelledby="heading-pd">
      <div class="page-header">
        <div class="page-title-group">
          <h2 id="heading-pd" tabindex="-1">Daftar Perangkat Daerah (PD)</h2>
          <p>Organisasi Perangkat Daerah (OPD / SKPD) Pengguna Barang Milik Daerah pada Pemerintah Daerah.</p>
        </div>
        <div class="action-group">
          <button type="button" class="btn btn-primary" data-action="open-form" data-entity="pd">
            + Tambah Perangkat Daerah
          </button>
        </div>
      </div>

      <search class="filter-bar" aria-label="Pencarian Data Perangkat Daerah">
        <form id="filter-form-pd" class="filter-form" action="api/pd.php" method="GET">
          <div class="filter-field">
            <label for="filter-pd-search">Cari Nama / Singkatan / Kode SKPD / Alamat</label>
            <input type="search" id="filter-pd-search" name="search" placeholder="Contoh: Dinas Pendidikan, PUPR, Kesehatan..." autocorrect="off" autocapitalize="none" spellcheck="false">
          </div>
          <div class="filter-field">
            <button type="button" class="btn btn-secondary" id="btn-reset-pd">Reset Pencarian</button>
          </div>
        </form>
      </search>

      <div tabindex="0" role="region" aria-labelledby="caption-table-pd" class="table-wrapper">
        <table id="table-pd" class="data-table" style="--label-2:'Kode & Singkatan';--label-3:'Alamat Kantor';--label-4:'Jumlah PNS';--label-5:'Jumlah KDO';--label-6:'Rincian Kondisi KDO';--label-7:'Aksi';">
          <caption id="caption-table-pd">Daftar Organisasi Perangkat Daerah (PD)</caption>
          <thead>
            <tr>
              <th scope="col" data-type="text">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Nama Perangkat Daerah</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Kode &amp; Singkatan</th>
              <th scope="col">Alamat &amp; Telepon</th>
              <th scope="col" data-type="number">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Jumlah PNS</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col" data-type="number">
                <button type="button" class="sort-btn" data-action="sort-table">
                  <span>Total KDO</span>
                  <svg class="sort-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 15l5 5 5-5M7 9l5-5 5 5"/></svg>
                </button>
              </th>
              <th scope="col">Rincian Kondisi KDO</th>
              <th scope="col">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-pd">
            <!-- Diisi oleh JS -->
          </tbody>
        </table>
      </div>
    </section>

    <!-- ====================================================================
         6. HALAMAN DETAIL ENTITAS (KDO / PEMAKAIAN / PNS / PD)
         ==================================================================== -->
    <section id="view-detail" class="view-section" hidden aria-labelledby="detail-heading">
      <nav class="breadcrumb-bar" aria-label="Breadcrumb" id="detail-breadcrumb"></nav>
      <div class="page-header">
        <div class="page-title-group">
          <h2 id="detail-heading" tabindex="-1">Detail Data</h2>
          <p id="detail-subheading">Rincian informasi lengkap dan relasi data terkait.</p>
        </div>
        <div class="action-group" id="detail-header-actions">
          <!-- Diisi oleh JS -->
        </div>
      </div>
      <div id="detail-content-container">
        <!-- Diisi oleh JS -->
      </div>
    </section>

    <!-- ====================================================================
         7. HALAMAN FORM (CREATE & UPDATE UNTUK KDO, PEMAKAIAN, PNS, PD)
         ==================================================================== -->
    <section id="view-form" class="view-section" hidden aria-labelledby="form-page-heading">
      <nav class="breadcrumb-bar" aria-label="Breadcrumb" id="form-breadcrumb"></nav>
      <div class="page-header">
        <div class="page-title-group">
          <h2 id="form-page-heading" tabindex="-1">Formulir Data</h2>
          <p id="form-page-subheading">Lengkapi seluruh kolom bertanda bintang (*) sebelum menyimpan data.</p>
        </div>
        <div class="action-group">
          <button type="button" class="btn btn-secondary" id="btn-form-back">← Kembali</button>
        </div>
      </div>

      <!-- 7A. FORM KENDARAAN DINAS OPERASIONAL (KDO) -->
      <div class="form-card" id="form-card-kdo" hidden>
        <div class="card-header">
          <h3 id="form-title-kdo">Formulir Kendaraan Dinas Operasional (KDO)</h3>
        </div>
        <div class="card-body">
          <form id="entity-form-kdo" action="api/kdo.php" method="POST" aria-labelledby="form-title-kdo" novalidate>
            <input type="hidden" id="kdo-id" name="id" value="">

            <div class="form-grid">
              <div class="field">
                <label for="kdo-nopol">Nomor Polisi Kendaraan <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-nopol-hint" class="hint">Format plat dinas: Huruf Seri - Angka - Kode Belakang (contoh: B 1024 PQN)</span>
                <input
                  type="text"
                  id="kdo-nopol"
                  name="nomor_polisi_kendaraan"
                  required
                  maxlength="20"
                  pattern="[A-Za-z]{1,2}\s?\d{1,4}\s?[A-Za-z]{1,4}"
                  autocorrect="off"
                  autocapitalize="characters"
                  spellcheck="false"
                  aria-describedby="kdo-nopol-hint"
                  data-error="kdo-nopol-error"
                >
                <div id="kdo-nopol-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Masukkan Nomor Polisi yang valid dengan format seperti B 1024 PQN.
                </div>
              </div>

              <div class="field">
                <label for="kdo-kode-barang">Kode Barang BMD (KIB B)</label>
                <span id="kdo-kode-hint" class="hint">Standar Permendagri (contoh: 1.3.2.02.01.01.001)</span>
                <input
                  type="text"
                  id="kdo-kode-barang"
                  name="kode_barang"
                  value="1.3.2.02.01.01.001"
                  maxlength="40"
                  autocorrect="off"
                  autocapitalize="none"
                  spellcheck="false"
                  aria-describedby="kdo-kode-hint"
                >
              </div>

              <div class="field">
                <label for="kdo-merk">Merk Kendaraan <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-merk-hint" class="hint">Contoh: Toyota, Mitsubishi, Honda, Isuzu, Suzuki</span>
                <input
                  type="text"
                  id="kdo-merk"
                  name="merk_kendaraan"
                  required
                  maxlength="100"
                  aria-describedby="kdo-merk-hint"
                  data-error="kdo-merk-error"
                >
                <div id="kdo-merk-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Merk kendaraan wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="kdo-tipe">Tipe Kendaraan <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-tipe-hint" class="hint">Contoh: Kijang Innova Zenix 2.0 G CVT, Hilux Double Cabin 4x4</span>
                <input
                  type="text"
                  id="kdo-tipe"
                  name="tipe_kendaraan"
                  required
                  maxlength="100"
                  aria-describedby="kdo-tipe-hint"
                  data-error="kdo-tipe-error"
                >
                <div id="kdo-tipe-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Tipe kendaraan wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="kdo-tahun">Tahun Produksi <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-tahun-hint" class="hint">Tahun pembuatan kendaraan (4 digit, contoh: 2023)</span>
                <input
                  type="text"
                  id="kdo-tahun"
                  name="tahun_produksi"
                  inputmode="numeric"
                  required
                  pattern="(19[89]\d|20[0-3]\d)"
                  maxlength="4"
                  aria-describedby="kdo-tahun-hint"
                  data-error="kdo-tahun-error"
                >
                <div id="kdo-tahun-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Masukkan tahun produksi 4 digit yang valid (1980–2027).
                </div>
              </div>

              <div class="field">
                <label for="kdo-warna">Warna Kendaraan <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-warna-hint" class="hint">Warna sesuai STNK (contoh: Hitam Metalik, Putih, Silver)</span>
                <input
                  type="text"
                  id="kdo-warna"
                  name="warna_kendaraan"
                  required
                  maxlength="50"
                  aria-describedby="kdo-warna-hint"
                  data-error="kdo-warna-error"
                >
                <div id="kdo-warna-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Warna kendaraan wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="kdo-rangka">Nomor Rangka (VIN)</label>
                <span id="kdo-rangka-hint" class="hint">Nomor rangka pada STNK/BPKB (opsional)</span>
                <input
                  type="text"
                  id="kdo-rangka"
                  name="nomor_rangka"
                  maxlength="50"
                  autocorrect="off"
                  autocapitalize="characters"
                  spellcheck="false"
                  aria-describedby="kdo-rangka-hint"
                >
              </div>

              <div class="field">
                <label for="kdo-mesin">Nomor Mesin</label>
                <span id="kdo-mesin-hint" class="hint">Nomor mesin pada STNK/BPKB (opsional)</span>
                <input
                  type="text"
                  id="kdo-mesin"
                  name="nomor_mesin"
                  maxlength="50"
                  autocorrect="off"
                  autocapitalize="characters"
                  spellcheck="false"
                  aria-describedby="kdo-mesin-hint"
                >
              </div>
            </div>

            <!-- Jenis Kendaraan (3 opsi -> Radio buttons sesuai Decision Matrix) -->
            <fieldset class="radio-fieldset">
              <legend>Jenis Kendaraan <span class="req-star" aria-hidden="true">*</span></legend>
              <div class="radio-group">
                <div class="radio-option">
                  <input type="radio" id="kdo-jenis-r4" name="jenis_kendaraan" value="Roda 4" checked>
                  <label for="kdo-jenis-r4">🚗 Roda 4 (Mobil Operasional)</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="kdo-jenis-r2" name="jenis_kendaraan" value="Roda 2">
                  <label for="kdo-jenis-r2">🏍 Roda 2 (Sepeda Motor Dinas)</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="kdo-jenis-r6" name="jenis_kendaraan" value="Roda 6 / Khusus">
                  <label for="kdo-jenis-r6">🚛 Roda 6 / Kendaraan Khusus</label>
                </div>
              </div>
            </fieldset>

            <!-- Kondisi Kendaraan (3 opsi -> Radio buttons sesuai Decision Matrix) -->
            <fieldset class="radio-fieldset">
              <legend>Kondisi Fisik Kendaraan <span class="req-star" aria-hidden="true">*</span></legend>
              <div class="radio-group">
                <div class="radio-option">
                  <input type="radio" id="kdo-kondisi-baik" name="kondisi" value="Baik" checked>
                  <label for="kdo-kondisi-baik">✔ Baik (Laik Jalan)</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="kdo-kondisi-ringan" name="kondisi" value="Rusak Ringan">
                  <label for="kdo-kondisi-ringan">⚠ Rusak Ringan (Perlu Servis Berkala)</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="kdo-kondisi-berat" name="kondisi" value="Rusak Berat">
                  <label for="kdo-kondisi-berat">✖ Rusak Berat (Tidak Beroperasi)</label>
                </div>
              </div>
            </fieldset>

            <div class="form-grid">
              <div class="field">
                <label for="kdo-perangkat">Perangkat Daerah (Pengguna Barang) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-pd-hint" class="hint">Pilih OPD / SKPD tempat kendaraan ini tercatat</span>
                <select
                  id="kdo-perangkat"
                  name="perangkat_id"
                  required
                  aria-describedby="kdo-pd-hint"
                  data-error="kdo-pd-error"
                >
                  <option value="">-- Pilih Perangkat Daerah --</option>
                </select>
                <div id="kdo-pd-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Silakan pilih Perangkat Daerah pemilik KDO.
                </div>
              </div>

              <div class="field">
                <label for="kdo-pegawai">Pegawai Penanggung Jawab (PNS) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="kdo-pns-hint" class="hint">Pilih PNS pemegang BAST kendaraan dinas</span>
                <select
                  id="kdo-pegawai"
                  name="pegawai_id"
                  required
                  aria-describedby="kdo-pns-hint"
                  data-error="kdo-pns-error"
                >
                  <option value="">-- Pilih Pegawai (PNS) --</option>
                </select>
                <div id="kdo-pns-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Silakan pilih Pegawai Negeri Sipil (PNS) penanggung jawab.
                </div>
              </div>

              <div class="field field-full">
                <label for="kdo-keterangan">Keterangan Penggunaan / Catatan Kondisi</label>
                <span id="kdo-ket-hint" class="hint">Informasi peruntukan kendaraan atau catatan pemeliharaan</span>
                <textarea id="kdo-keterangan" name="keterangan" maxlength="500" aria-describedby="kdo-ket-hint"></textarea>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" data-action="cancel-form">Batal</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-kdo">Simpan Data KDO</button>
            </div>
          </form>
        </div>
      </div>

      <!-- 7B. FORM PEMAKAIAN KDO -->
      <div class="form-card" id="form-card-pemakaian" hidden>
        <div class="card-header">
          <h3 id="form-title-pemakaian">Formulir Pencatatan Pemakaian KDO</h3>
        </div>
        <div class="card-body">
          <form id="entity-form-pemakaian" action="api/pemakaian.php" method="POST" aria-labelledby="form-title-pemakaian" novalidate>
            <input type="hidden" id="pakai-id" name="id" value="">

            <div class="form-grid">
              <div class="field">
                <label for="pakai-kdo">Kendaraan Dinas Operasional (KDO) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-kdo-hint" class="hint">Pilih unit kendaraan dinas yang digunakan</span>
                <select
                  id="pakai-kdo"
                  name="kdo_id"
                  required
                  aria-describedby="pakai-kdo-hint"
                  data-error="pakai-kdo-error"
                >
                  <option value="">-- Pilih Kendaraan (KDO) --</option>
                </select>
                <div id="pakai-kdo-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Pilih kendaraan dinas yang akan digunakan.
                </div>
              </div>

              <div class="field">
                <label for="pakai-surat">Nomor Surat Tugas / SPPD / BAST <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-surat-hint" class="hint">Contoh: 800/ST-145/DISDIK/XI/2026</span>
                <input
                  type="text"
                  id="pakai-surat"
                  name="nomor_surat_tugas"
                  required
                  maxlength="80"
                  autocorrect="off"
                  autocapitalize="characters"
                  spellcheck="false"
                  aria-describedby="pakai-surat-hint"
                  data-error="pakai-surat-error"
                >
                <div id="pakai-surat-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Nomor Surat Tugas / BAST wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="pakai-perangkat">Perangkat Daerah Pengguna <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-pd-hint" class="hint">Otomatis terisi saat memilih KDO, atau sesuaikan bila lintas unit</span>
                <select
                  id="pakai-perangkat"
                  name="perangkat_id"
                  required
                  aria-describedby="pakai-pd-hint"
                  data-error="pakai-pd-error"
                >
                  <option value="">-- Pilih Perangkat Daerah --</option>
                </select>
                <div id="pakai-pd-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Perangkat Daerah pengguna wajib dipilih.
                </div>
              </div>

              <div class="field">
                <label for="pakai-pegawai">Pegawai Negeri Sipil (PNS) Pengguna <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-pns-hint" class="hint">Pilih PNS yang ditugaskan memakai kendaraan</span>
                <select
                  id="pakai-pegawai"
                  name="pegawai_id"
                  required
                  aria-describedby="pakai-pns-hint"
                  data-error="pakai-pns-error"
                >
                  <option value="">-- Pilih Pegawai (PNS) --</option>
                </select>
                <div id="pakai-pns-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Pegawai (PNS) pengguna wajib dipilih.
                </div>
              </div>

              <div class="field field-full">
                <label for="pakai-keperluan">Keperluan Dinas / Kegiatan Operasional <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-keperluan-hint" class="hint">Uraikan agenda kegiatan dinas secara jelas</span>
                <input
                  type="text"
                  id="pakai-keperluan"
                  name="keperluan"
                  required
                  maxlength="255"
                  aria-describedby="pakai-keperluan-hint"
                  data-error="pakai-keperluan-error"
                >
                <div id="pakai-keperluan-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Keperluan penggunaan kendaraan wajib diisi.
                </div>
              </div>

              <div class="field field-full">
                <label for="pakai-tujuan">Lokasi Tujuan Dinas <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-tujuan-hint" class="hint">Instansi atau lokasi lapangan yang dituju</span>
                <input
                  type="text"
                  id="pakai-tujuan"
                  name="tujuan"
                  required
                  maxlength="150"
                  aria-describedby="pakai-tujuan-hint"
                  data-error="pakai-tujuan-error"
                >
                <div id="pakai-tujuan-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Lokasi tujuan dinas wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="pakai-tgl-mulai">Tanggal Mulai Pemakaian <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-mulai-hint" class="hint">Tanggal keberangkatan / serah terima</span>
                <input
                  type="date"
                  id="pakai-tgl-mulai"
                  name="tanggal_mulai"
                  required
                  aria-describedby="pakai-mulai-hint"
                  data-error="pakai-mulai-error"
                >
                <div id="pakai-mulai-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Tanggal mulai pemakaian wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="pakai-tgl-selesai">Tanggal Selesai / Kembali</label>
                <span id="pakai-selesai-hint" class="hint">Kosongkan bila kendaraan masih sedang digunakan</span>
                <input
                  type="date"
                  id="pakai-tgl-selesai"
                  name="tanggal_selesai"
                  aria-describedby="pakai-selesai-hint"
                >
              </div>

              <div class="field">
                <label for="pakai-km-awal">Odometer Awal (KM) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pakai-kmawal-hint" class="hint">Angka kilometer pada speedometer saat berangkat</span>
                <input
                  type="text"
                  id="pakai-km-awal"
                  name="km_awal"
                  inputmode="numeric"
                  required
                  pattern="\d+"
                  value="0"
                  aria-describedby="pakai-kmawal-hint"
                  data-error="pakai-kmawal-error"
                >
                <div id="pakai-kmawal-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Masukkan angka KM awal yang valid.
                </div>
              </div>

              <div class="field">
                <label for="pakai-km-akhir">Odometer Akhir (KM)</label>
                <span id="pakai-kmakhir-hint" class="hint">Angka kilometer saat selesai bertugas (opsional)</span>
                <input
                  type="text"
                  id="pakai-km-akhir"
                  name="km_akhir"
                  inputmode="numeric"
                  pattern="\d*"
                  aria-describedby="pakai-kmakhir-hint"
                >
              </div>
            </div>

            <!-- Status Pemakaian (3 opsi -> Radio buttons) -->
            <fieldset class="radio-fieldset">
              <legend>Status Pemakaian <span class="req-star" aria-hidden="true">*</span></legend>
              <div class="radio-group">
                <div class="radio-option">
                  <input type="radio" id="pakai-status-digunakan" name="status_pemakaian" value="Digunakan" checked>
                  <label for="pakai-status-digunakan">🚗 Sedang Digunakan</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="pakai-status-terjadwal" name="status_pemakaian" value="Terjadwal">
                  <label for="pakai-status-terjadwal">📅 Terjadwal</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="pakai-status-selesai" name="status_pemakaian" value="Selesai">
                  <label for="pakai-status-selesai">✔ Selesai (Kembali)</label>
                </div>
              </div>
            </fieldset>

            <!-- Kondisi Setelah Pemakaian (3 opsi -> Radio buttons) -->
            <fieldset class="radio-fieldset">
              <legend>Kondisi Kendaraan Saat / Setelah Digunakan</legend>
              <span class="hint">Jika status pemakaian "Selesai", pilihan ini otomatis memperbarui status kondisi pada master KDO.</span>
              <div class="radio-group">
                <div class="radio-option">
                  <input type="radio" id="pakai-kondisi-baik" name="kondisi_setelah" value="Baik" checked>
                  <label for="pakai-kondisi-baik">✔ Baik</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="pakai-kondisi-ringan" name="kondisi_setelah" value="Rusak Ringan">
                  <label for="pakai-kondisi-ringan">⚠ Rusak Ringan</label>
                </div>
                <div class="radio-option">
                  <input type="radio" id="pakai-kondisi-berat" name="kondisi_setelah" value="Rusak Berat">
                  <label for="pakai-kondisi-berat">✖ Rusak Berat</label>
                </div>
              </div>
            </fieldset>

            <div class="field field-full">
              <label for="pakai-catatan">Catatan Operasional / Laporan Kondisi</label>
              <span id="pakai-catatan-hint" class="hint">Catatan BBM, tol, atau temuan teknis kendaraan selama perjalanan dinas</span>
              <textarea id="pakai-catatan" name="catatan" maxlength="500" aria-describedby="pakai-catatan-hint"></textarea>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" data-action="cancel-form">Batal</button>
              <button type="submit" class="btn btn-success" id="btn-submit-pemakaian">Simpan Data Pemakaian</button>
            </div>
          </form>
        </div>
      </div>

      <!-- 7C. FORM PEGAWAI NEGERI SIPIL (PNS) -->
      <div class="form-card" id="form-card-pns" hidden>
        <div class="card-header">
          <h3 id="form-title-pns">Formulir Pegawai Negeri Sipil (PNS)</h3>
        </div>
        <div class="card-body">
          <form id="entity-form-pns" action="api/pns.php" method="POST" aria-labelledby="form-title-pns" novalidate>
            <input type="hidden" id="pns-id" name="id" value="">

            <div class="form-grid">
              <div class="field">
                <label for="pns-nama">Nama Lengkap Pegawai (Beserta Gelar) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pns-nama-hint" class="hint">Contoh: Dr. H. Bambang Sutrisno, M.Pd.</span>
                <input
                  type="text"
                  id="pns-nama"
                  name="nama_pegawai"
                  required
                  maxlength="250"
                  autocomplete="name"
                  aria-describedby="pns-nama-hint"
                  data-error="pns-nama-error"
                >
                <div id="pns-nama-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Nama lengkap pegawai wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="pns-nip">Nomor Induk Pegawai (NIP) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pns-nip-hint" class="hint">18 digit angka tanpa spasi (contoh: 198504122010011008)</span>
                <input
                  type="text"
                  id="pns-nip"
                  name="nip"
                  inputmode="numeric"
                  required
                  pattern="\d{8,20}"
                  maxlength="20"
                  autocorrect="off"
                  autocapitalize="none"
                  spellcheck="false"
                  aria-describedby="pns-nip-hint"
                  data-error="pns-nip-error"
                >
                <div id="pns-nip-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Masukkan NIP berupa angka 8–20 digit (standar BKN 18 digit) tanpa spasi.
                </div>
              </div>

              <div class="field">
                <label for="pns-jabatan">Jabatan Struktural / Fungsional <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pns-jabatan-hint" class="hint">Contoh: Kepala Bidang Bina Marga / Pengurus Barang Pengguna</span>
                <input
                  type="text"
                  id="pns-jabatan"
                  name="jabatan"
                  required
                  maxlength="150"
                  autocomplete="organization-title"
                  aria-describedby="pns-jabatan-hint"
                  data-error="pns-jabatan-error"
                >
                <div id="pns-jabatan-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Jabatan pegawai wajib diisi.
                </div>
              </div>

              <div class="field">
                <label for="pns-golongan">Golongan Ruang <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pns-gol-hint" class="hint">Pilih pangkat/golongan ruang PNS</span>
                <select
                  id="pns-golongan"
                  name="golongan"
                  required
                  aria-describedby="pns-gol-hint"
                  data-error="pns-gol-error"
                >
                  <option value="">-- Pilih Golongan --</option>
                  <option value="IV/e">IV/e (Pembina Utama)</option>
                  <option value="IV/d">IV/d (Pembina Utama Madya)</option>
                  <option value="IV/c">IV/c (Pembina Utama Muda)</option>
                  <option value="IV/b">IV/b (Pembina Tingkat I)</option>
                  <option value="IV/a">IV/a (Pembina)</option>
                  <option value="III/d">III/d (Penata Tingkat I)</option>
                  <option value="III/c">III/c (Penata)</option>
                  <option value="III/b">III/b (Penata Muda Tingkat I)</option>
                  <option value="III/a">III/a (Penata Muda)</option>
                  <option value="II/d">II/d (Pengatur Tingkat I)</option>
                  <option value="II/c">II/c (Pengatur)</option>
                  <option value="II/b">II/b (Pengatur Muda Tingkat I)</option>
                  <option value="II/a">II/a (Pengatur Muda)</option>
                </select>
                <div id="pns-gol-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Silakan pilih Golongan PNS.
                </div>
              </div>

              <div class="field">
                <label for="pns-perangkat">Unit Kerja Perangkat Daerah</label>
                <span id="pns-pd-hint" class="hint">OPD tempat pegawai bertugas</span>
                <select id="pns-perangkat" name="perangkat_id" aria-describedby="pns-pd-hint">
                  <option value="">-- Pilih Perangkat Daerah --</option>
                </select>
              </div>

              <div class="field">
                <label for="pns-hp">Nomor Telepon / WhatsApp Kedinasan</label>
                <span id="pns-hp-hint" class="hint">Nomor kontak aktif (contoh: 081280114401)</span>
                <input
                  type="tel"
                  id="pns-hp"
                  name="no_hp"
                  inputmode="tel"
                  autocomplete="tel"
                  maxlength="25"
                  aria-describedby="pns-hp-hint"
                >
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" data-action="cancel-form">Batal</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-pns">Simpan Data PNS</button>
            </div>
          </form>
        </div>
      </div>

      <!-- 7D. FORM PERANGKAT DAERAH (PD) -->
      <div class="form-card" id="form-card-pd" hidden>
        <div class="card-header">
          <h3 id="form-title-pd">Formulir Perangkat Daerah (PD)</h3>
        </div>
        <div class="card-body">
          <form id="entity-form-pd" action="api/pd.php" method="POST" aria-labelledby="form-title-pd" novalidate>
            <input type="hidden" id="pd-id" name="id" value="">

            <div class="form-grid">
              <div class="field field-full">
                <label for="pd-nama">Nama Perangkat Daerah (SKPD / OPD) <span class="req-star" aria-hidden="true">*</span></label>
                <span id="pd-nama-hint" class="hint">Contoh: Dinas Pendidikan, Dinas Kesehatan, Dinas Pekerjaan Umum dan Penataan Ruang (PUPR)</span>
                <input
                  type="text"
                  id="pd-nama"
                  name="nama_perangkat"
                  required
                  minlength="3"
                  maxlength="150"
                  aria-describedby="pd-nama-hint"
                  data-error="pd-nama-error"
                >
                <div id="pd-nama-error" class="error-msg">
                  <span role="img" aria-label="Error">⚠️</span> Nama Perangkat Daerah wajib diisi (minimal 3 karakter).
                </div>
              </div>

              <div class="field">
                <label for="pd-kode">Kode Urusan / Kode SKPD</label>
                <span id="pd-kode-hint" class="hint">Contoh: 1.01.01 (otomatis dibuat bila dikosongkan)</span>
                <input
                  type="text"
                  id="pd-kode"
                  name="kode_perangkat"
                  maxlength="30"
                  autocorrect="off"
                  autocapitalize="none"
                  spellcheck="false"
                  aria-describedby="pd-kode-hint"
                >
              </div>

              <div class="field">
                <label for="pd-singkatan">Singkatan / Akronim OPD</label>
                <span id="pd-singkatan-hint" class="hint">Contoh: DISDIK, DINKES, DPUPR, BPKAD</span>
                <input
                  type="text"
                  id="pd-singkatan"
                  name="singkatan"
                  maxlength="30"
                  autocorrect="off"
                  autocapitalize="characters"
                  spellcheck="false"
                  aria-describedby="pd-singkatan-hint"
                >
              </div>

              <div class="field">
                <label for="pd-telepon">Nomor Telepon Kantor</label>
                <span id="pd-tel-hint" class="hint">Contoh: (021) 3845825</span>
                <input
                  type="tel"
                  id="pd-telepon"
                  name="telepon"
                  maxlength="30"
                  aria-describedby="pd-tel-hint"
                >
              </div>

              <div class="field field-full">
                <label for="pd-alamat">Alamat Lengkap Kantor Perangkat Daerah</label>
                <span id="pd-alamat-hint" class="hint">Alamat gedung/kantor kedinasan</span>
                <textarea id="pd-alamat" name="alamat" maxlength="255" aria-describedby="pd-alamat-hint"></textarea>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="btn btn-secondary" data-action="cancel-form">Batal</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-pd">Simpan Perangkat Daerah</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>

  <!-- Modal 1: Konfirmasi Hapus (Native <dialog> dengan closedby="any" sesuai modern-web-guidance) -->
  <dialog id="delete-dialog" closedby="any" aria-labelledby="delete-dialog-title">
    <div class="dialog-inner">
      <div class="dialog-header-strip">
        <h2 id="delete-dialog-title">Konfirmasi Penghapusan Data</h2>
      </div>
      <p id="delete-dialog-message">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
      <form method="dialog" class="dialog-actions">
        <button type="submit" value="cancel" class="btn btn-secondary" autofocus>Batal</button>
        <button type="submit" value="delete" class="btn btn-danger" id="btn-confirm-delete">Ya, Hapus Data</button>
      </form>
    </div>
  </dialog>

  <!-- Modal 2: Selesaikan Pemakaian Cepat (<dialog> dengan closedby="any") -->
  <dialog id="complete-pakai-dialog" closedby="any" aria-labelledby="complete-dialog-title">
    <div class="dialog-inner">
      <div class="dialog-header-strip">
        <h2 id="complete-dialog-title">Selesaikan Penugasan / Pengembalian KDO</h2>
      </div>
      <p id="complete-dialog-subtitle">Catat odometer akhir dan kondisi fisik kendaraan saat dikembalikan ke pool Perangkat Daerah.</p>
      <form id="complete-pakai-form" action="api/pemakaian.php" method="POST" novalidate>
        <input type="hidden" id="complete-pakai-id" name="id" value="">

        <div class="field">
          <label for="complete-tgl-selesai">Tanggal Kembali / Selesai <span class="req-star" aria-hidden="true">*</span></label>
          <input type="date" id="complete-tgl-selesai" name="tanggal_selesai" required>
        </div>

        <div class="field">
          <label for="complete-km-akhir">Odometer Akhir (KM) <span class="req-star" aria-hidden="true">*</span></label>
          <span id="complete-km-hint" class="hint">KM Awal tercatat: <strong id="complete-km-awal-label">0</strong> KM</span>
          <input
            type="text"
            id="complete-km-akhir"
            name="km_akhir"
            inputmode="numeric"
            required
            pattern="\d+"
            aria-describedby="complete-km-hint"
            data-error="complete-km-error"
          >
          <div id="complete-km-error" class="error-msg">
            <span role="img" aria-label="Error">⚠️</span> Masukkan angka KM akhir yang valid (tidak kurang dari KM awal).
          </div>
        </div>

        <fieldset class="radio-fieldset">
          <legend>Kondisi Fisik Kendaraan Setelah Digunakan <span class="req-star" aria-hidden="true">*</span></legend>
          <span class="hint">Pilihan ini otomatis menyinkronkan status kondisi pada master KDO.</span>
          <div class="radio-group">
            <div class="radio-option">
              <input type="radio" id="comp-kond-baik" name="kondisi_setelah" value="Baik" checked>
              <label for="comp-kond-baik">✔ Baik</label>
            </div>
            <div class="radio-option">
              <input type="radio" id="comp-kond-ringan" name="kondisi_setelah" value="Rusak Ringan">
              <label for="comp-kond-ringan">⚠ Rusak Ringan</label>
            </div>
            <div class="radio-option">
              <input type="radio" id="comp-kond-berat" name="kondisi_setelah" value="Rusak Berat">
              <label for="comp-kond-berat">✖ Rusak Berat</label>
            </div>
          </div>
        </fieldset>

        <div class="field">
          <label for="complete-catatan">Catatan Pengembalian / Temuan Teknis</label>
          <textarea id="complete-catatan" name="catatan" maxlength="400"></textarea>
        </div>

        <div class="dialog-actions">
          <button type="button" class="btn btn-secondary" data-action="close-complete-dialog">Batal</button>
          <button type="submit" class="btn btn-success" id="btn-submit-complete">✔ Konfirmasi Selesai</button>
        </div>
      </form>
    </div>
  </dialog>

  <!-- Notifikasi Status Aksi (AJAX Live Region) -->
  <div id="toast-banner" class="toast-banner" role="status" aria-live="polite" hidden></div>

  <footer class="app-footer">
    <span>SI-KDO BMD &copy; <?= date('Y') ?> — Sistem Pengelolaan Kendaraan Dinas Operasional Milik Perangkat Daerah (Barang Milik Daerah)</span>
  </footer>

  <script src="assets/js/app.js"></script>
</body>
</html>
