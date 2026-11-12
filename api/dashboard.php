<?php
/**
 * RESTful API Endpoint: Ringkasan Dashboard BMD Kendaraan Dinas Operasional
 * URL: /api/dashboard.php
 * Methods: GET
 */

require_once __DIR__ . '/../config/db.php';
api_headers('GET, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response([
        'status'  => 'error',
        'message' => 'Metode HTTP tidak diizinkan.'
    ], 405);
}

try {
    // 1. Ringkasan KPI Utama
    $summary = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM kdo) AS total_kdo,
            (SELECT COUNT(*) FROM kdo WHERE kondisi = 'Baik') AS kdo_baik,
            (SELECT COUNT(*) FROM kdo WHERE kondisi = 'Rusak Ringan') AS kdo_rusak_ringan,
            (SELECT COUNT(*) FROM kdo WHERE kondisi = 'Rusak Berat') AS kdo_rusak_berat,
            (SELECT COUNT(*) FROM pns) AS total_pns,
            (SELECT COUNT(*) FROM perangkat_daerah) AS total_pd,
            (SELECT COUNT(*) FROM pemakaian_kdo) AS total_pemakaian,
            (SELECT COUNT(*) FROM pemakaian_kdo WHERE status_pemakaian = 'Digunakan') AS pemakaian_aktif,
            (SELECT COUNT(*) FROM pemakaian_kdo WHERE status_pemakaian = 'Terjadwal') AS pemakaian_terjadwal,
            (SELECT COUNT(*) FROM pemakaian_kdo WHERE status_pemakaian = 'Selesai') AS pemakaian_selesai
    ")->fetch();

    // 2. Distribusi KDO per Perangkat Daerah
    $distribusiPd = $pdo->query("
        SELECT
            pd.id,
            pd.kode_perangkat,
            pd.nama_perangkat,
            pd.singkatan,
            COUNT(k.id) AS total_kdo,
            SUM(CASE WHEN k.kondisi = 'Baik' THEN 1 ELSE 0 END) AS baik,
            SUM(CASE WHEN k.kondisi = 'Rusak Ringan' THEN 1 ELSE 0 END) AS rusak_ringan,
            SUM(CASE WHEN k.kondisi = 'Rusak Berat' THEN 1 ELSE 0 END) AS rusak_berat,
            (SELECT COUNT(*) FROM pns WHERE pns.perangkat_id = pd.id) AS total_pns
        FROM perangkat_daerah pd
        LEFT JOIN kdo k ON k.perangkat_id = pd.id
        GROUP BY pd.id, pd.kode_perangkat, pd.nama_perangkat, pd.singkatan
        ORDER BY total_kdo DESC, pd.nama_perangkat ASC
    ")->fetchAll();

    // 3. Distribusi berdasarkan Jenis Kendaraan
    $distribusiJenis = $pdo->query("
        SELECT
            jenis_kendaraan,
            COUNT(*) AS jumlah
        FROM kdo
        GROUP BY jenis_kendaraan
        ORDER BY jumlah DESC
    ")->fetchAll();

    // 4. Pemakaian KDO Terbaru (6 aktivitas terakhir)
    $pemakaianTerbaru = $pdo->query("
        SELECT
            pk.*,
            k.nomor_polisi_kendaraan,
            k.merk_kendaraan,
            k.tipe_kendaraan,
            p.nama_pegawai,
            p.nip,
            pd.nama_perangkat,
            pd.singkatan AS singkatan_perangkat
        FROM pemakaian_kdo pk
        JOIN kdo k ON pk.kdo_id = k.id
        JOIN pns p ON pk.pegawai_id = p.id
        JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
        ORDER BY pk.tanggal_mulai DESC, pk.id DESC
        LIMIT 6
    ")->fetchAll();

    // 5. Daftar KDO yang Memerlukan Pemeliharaan (Rusak Ringan / Rusak Berat)
    $kdoPerhatian = $pdo->query("
        SELECT
            k.*,
            p.nama_pegawai,
            p.nip,
            p.jabatan,
            pd.nama_perangkat,
            pd.singkatan AS singkatan_perangkat
        FROM kdo k
        JOIN pns p ON k.pegawai_id = p.id
        JOIN perangkat_daerah pd ON k.perangkat_id = pd.id
        WHERE k.kondisi IN ('Rusak Ringan', 'Rusak Berat')
        ORDER BY FIELD(k.kondisi, 'Rusak Berat', 'Rusak Ringan'), k.id DESC
    ")->fetchAll();

    json_response([
        'status' => 'success',
        'data'   => [
            'summary'              => $summary,
            'distribusi_perangkat' => $distribusiPd,
            'distribusi_jenis'     => $distribusiJenis,
            'pemakaian_terbaru'    => $pemakaianTerbaru,
            'kdo_perlu_perhatian'  => $kdoPerhatian
        ]
    ]);
} catch (PDOException $e) {
    json_response([
        'status'  => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ], 500);
}
