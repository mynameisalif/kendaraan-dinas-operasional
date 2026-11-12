<?php
/**
 * RESTful API Endpoint: Kendaraan Dinas Operasional (KDO)
 * URL: /api/kdo.php
 * Methods: GET, POST, PUT, PATCH, DELETE
 */

require_once __DIR__ . '/../config/db.php';
api_headers('GET, POST, PUT, PATCH, DELETE, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$validKondisi = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
$validJenis   = ['Roda 4', 'Roda 2', 'Roda 6 / Khusus'];

try {
    // ========================================================================
    // 1. READ (GET List & Detail)
    // ========================================================================
    if ($method === 'GET') {
        $id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $search      = clean_str($_GET['search'] ?? '');
        $kondisi     = clean_str($_GET['kondisi'] ?? '');
        $jenis       = clean_str($_GET['jenis_kendaraan'] ?? '');
        $perangkatId = isset($_GET['perangkat_id']) && $_GET['perangkat_id'] !== '' ? (int)$_GET['perangkat_id'] : 0;
        $pegawaiId   = isset($_GET['pegawai_id']) && $_GET['pegawai_id'] !== '' ? (int)$_GET['pegawai_id'] : 0;

        if ($id > 0) {
            $stmt = $pdo->prepare("
                SELECT 
                    k.*,
                    p.nama_pegawai,
                    p.nip,
                    p.jabatan,
                    p.golongan,
                    p.no_hp AS no_hp_pegawai,
                    pd.nama_perangkat,
                    pd.singkatan AS singkatan_perangkat,
                    pd.kode_perangkat,
                    pd.alamat AS alamat_perangkat,
                    (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.kdo_id = k.id) AS total_pemakaian,
                    (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.kdo_id = k.id AND pk.status_pemakaian = 'Digunakan') AS sedang_digunakan
                FROM kdo k
                JOIN pns p ON k.pegawai_id = p.id
                JOIN perangkat_daerah pd ON k.perangkat_id = pd.id
                WHERE k.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                json_response([
                    'status'  => 'error',
                    'message' => 'Data Kendaraan Dinas Operasional (KDO) tidak ditemukan.'
                ], 404);
            }

            // Riwayat Pemakaian KDO ini
            $stmtPakai = $pdo->prepare("
                SELECT 
                    pk.*,
                    p.nama_pegawai,
                    p.nip,
                    p.jabatan,
                    pd.nama_perangkat,
                    pd.singkatan AS singkatan_perangkat
                FROM pemakaian_kdo pk
                JOIN pns p ON pk.pegawai_id = p.id
                JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
                WHERE pk.kdo_id = ?
                ORDER BY pk.tanggal_mulai DESC, pk.id DESC
            ");
            $stmtPakai->execute([$id]);
            $row['riwayat_pemakaian'] = $stmtPakai->fetchAll();

            json_response([
                'status' => 'success',
                'data'   => $row
            ]);
        }

        // GET List KDO
        $sql = "
            SELECT 
                k.*,
                p.nama_pegawai,
                p.nip,
                p.jabatan,
                p.golongan,
                pd.nama_perangkat,
                pd.singkatan AS singkatan_perangkat,
                pd.kode_perangkat,
                (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.kdo_id = k.id) AS total_pemakaian,
                (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.kdo_id = k.id AND pk.status_pemakaian = 'Digunakan') AS sedang_digunakan
            FROM kdo k
            JOIN pns p ON k.pegawai_id = p.id
            JOIN perangkat_daerah pd ON k.perangkat_id = pd.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                k.nomor_polisi_kendaraan LIKE ? OR
                k.merk_kendaraan LIKE ? OR
                k.tipe_kendaraan LIKE ? OR
                k.warna_kendaraan LIKE ? OR
                k.nomor_rangka LIKE ? OR
                p.nama_pegawai LIKE ? OR
                pd.nama_perangkat LIKE ?
            )";
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }

        if ($kondisi !== '') {
            $sql .= " AND k.kondisi = ?";
            $params[] = $kondisi;
        }

        if ($jenis !== '') {
            $sql .= " AND k.jenis_kendaraan = ?";
            $params[] = $jenis;
        }

        if ($perangkatId > 0) {
            $sql .= " AND k.perangkat_id = ?";
            $params[] = $perangkatId;
        }

        if ($pegawaiId > 0) {
            $sql .= " AND k.pegawai_id = ?";
            $params[] = $pegawaiId;
        }

        $sql .= " ORDER BY k.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        json_response([
            'status' => 'success',
            'data'   => $stmt->fetchAll()
        ]);
    }

    // ========================================================================
    // 2. CREATE (POST)
    // ========================================================================
    if ($method === 'POST') {
        $in          = get_request_input();
        $nopol       = strtoupper(preg_replace('/\s+/', ' ', clean_str($in['nomor_polisi_kendaraan'] ?? '')));
        $merk        = clean_str($in['merk_kendaraan'] ?? '');
        $tipe        = clean_str($in['tipe_kendaraan'] ?? '');
        $jenis       = clean_str($in['jenis_kendaraan'] ?? 'Roda 4');
        $tahun       = isset($in['tahun_produksi']) ? (int)$in['tahun_produksi'] : 0;
        $warna       = clean_str($in['warna_kendaraan'] ?? '');
        $kondisi     = clean_str($in['kondisi'] ?? 'Baik');
        $pegawaiId   = isset($in['pegawai_id']) ? (int)$in['pegawai_id'] : 0;
        $perangkatId = isset($in['perangkat_id']) ? (int)$in['perangkat_id'] : 0;
        $kodeBarang  = clean_str($in['kode_barang'] ?? '1.3.2.02.01.01.001');
        $noRangka    = strtoupper(clean_str($in['nomor_rangka'] ?? ''));
        $noMesin     = strtoupper(clean_str($in['nomor_mesin'] ?? ''));
        $keterangan  = clean_str($in['keterangan'] ?? '');

        $errors = [];

        if ($nopol === '') {
            $errors['nomor_polisi_kendaraan'] = 'Nomor Polisi Kendaraan wajib diisi.';
        } elseif (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,4}$/', $nopol)) {
            $errors['nomor_polisi_kendaraan'] = 'Format Nomor Polisi tidak valid (contoh: B 1024 PQN).';
        } else {
            $cekNopol = $pdo->prepare("SELECT COUNT(*) FROM kdo WHERE REPLACE(UPPER(nomor_polisi_kendaraan), ' ', '') = REPLACE(UPPER(?), ' ', '')");
            $cekNopol->execute([$nopol]);
            if ((int)$cekNopol->fetchColumn() > 0) {
                $errors['nomor_polisi_kendaraan'] = "Nomor Polisi \"{$nopol}\" sudah terdaftar pada sistem.";
            }
        }

        if ($merk === '') {
            $errors['merk_kendaraan'] = 'Merk Kendaraan wajib diisi.';
        }

        if ($tipe === '') {
            $errors['tipe_kendaraan'] = 'Tipe Kendaraan wajib diisi.';
        }

        if (!in_array($jenis, $validJenis, true)) {
            $jenis = 'Roda 4';
        }

        $maxYear = (int)date('Y') + 1;
        if ($tahun < 1980 || $tahun > $maxYear) {
            $errors['tahun_produksi'] = "Tahun Produksi harus berada di antara 1980 dan {$maxYear}.";
        }

        if ($warna === '') {
            $errors['warna_kendaraan'] = 'Warna Kendaraan wajib diisi.';
        }

        if (!in_array($kondisi, $validKondisi, true)) {
            $errors['kondisi'] = 'Kondisi harus bernilai Baik, Rusak Ringan, atau Rusak Berat.';
        }

        if ($pegawaiId <= 0) {
            $errors['pegawai_id'] = 'Pegawai Penanggung Jawab (PNS) wajib dipilih.';
        } else {
            $cekPns = $pdo->prepare("SELECT COUNT(*) FROM pns WHERE id = ?");
            $cekPns->execute([$pegawaiId]);
            if ((int)$cekPns->fetchColumn() === 0) {
                $errors['pegawai_id'] = 'Data PNS yang dipilih tidak ditemukan.';
            }
        }

        if ($perangkatId <= 0) {
            $errors['perangkat_id'] = 'Perangkat Daerah wajib dipilih.';
        } else {
            $cekPd = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE id = ?");
            $cekPd->execute([$perangkatId]);
            if ((int)$cekPd->fetchColumn() === 0) {
                $errors['perangkat_id'] = 'Data Perangkat Daerah yang dipilih tidak ditemukan.';
            }
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $stmt = $pdo->prepare("
            INSERT INTO kdo (
                pegawai_id, perangkat_id, kode_barang, nomor_polisi_kendaraan,
                merk_kendaraan, tipe_kendaraan, jenis_kendaraan, tahun_produksi,
                warna_kendaraan, nomor_rangka, nomor_mesin, kondisi, keterangan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pegawaiId,
            $perangkatId,
            $kodeBarang ?: '1.3.2.02.01.01.001',
            $nopol,
            $merk,
            $tipe,
            $jenis,
            $tahun,
            $warna,
            $noRangka ?: null,
            $noMesin ?: null,
            $kondisi,
            $keterangan ?: null
        ]);

        $newId = (int)$pdo->lastInsertId();
        $detail = $pdo->prepare("
            SELECT k.*, p.nama_pegawai, p.nip, p.jabatan, pd.nama_perangkat, pd.singkatan AS singkatan_perangkat
            FROM kdo k
            JOIN pns p ON k.pegawai_id = p.id
            JOIN perangkat_daerah pd ON k.perangkat_id = pd.id
            WHERE k.id = ?
        ");
        $detail->execute([$newId]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Kendaraan Dinas Operasional (KDO) berhasil ditambahkan.',
            'data'    => $detail->fetch()
        ], 201);
    }

    // ========================================================================
    // 3. UPDATE (PUT / PATCH)
    // ========================================================================
    if ($method === 'PUT' || $method === 'PATCH') {
        $in = get_request_input();
        $id = isset($in['id']) ? (int)$in['id'] : 0;

        if ($id <= 0) {
            json_response([
                'status'  => 'error',
                'message' => 'Parameter ID KDO wajib disertakan.'
            ], 400);
        }

        $existingStmt = $pdo->prepare("SELECT * FROM kdo WHERE id = ?");
        $existingStmt->execute([$id]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Kendaraan Dinas Operasional (KDO) tidak ditemukan.'
            ], 404);
        }

        $nopol       = array_key_exists('nomor_polisi_kendaraan', $in) ? strtoupper(preg_replace('/\s+/', ' ', clean_str($in['nomor_polisi_kendaraan']))) : $existing['nomor_polisi_kendaraan'];
        $merk        = array_key_exists('merk_kendaraan', $in) ? clean_str($in['merk_kendaraan']) : $existing['merk_kendaraan'];
        $tipe        = array_key_exists('tipe_kendaraan', $in) ? clean_str($in['tipe_kendaraan']) : $existing['tipe_kendaraan'];
        $jenis       = array_key_exists('jenis_kendaraan', $in) ? clean_str($in['jenis_kendaraan']) : ($existing['jenis_kendaraan'] ?? 'Roda 4');
        $tahun       = array_key_exists('tahun_produksi', $in) ? (int)$in['tahun_produksi'] : (int)$existing['tahun_produksi'];
        $warna       = array_key_exists('warna_kendaraan', $in) ? clean_str($in['warna_kendaraan']) : $existing['warna_kendaraan'];
        $kondisi     = array_key_exists('kondisi', $in) ? clean_str($in['kondisi']) : $existing['kondisi'];
        $pegawaiId   = array_key_exists('pegawai_id', $in) ? (int)$in['pegawai_id'] : (int)$existing['pegawai_id'];
        $perangkatId = array_key_exists('perangkat_id', $in) ? (int)$in['perangkat_id'] : (int)$existing['perangkat_id'];
        $kodeBarang  = array_key_exists('kode_barang', $in) ? clean_str($in['kode_barang']) : ($existing['kode_barang'] ?? '1.3.2.02.01.01.001');
        $noRangka    = array_key_exists('nomor_rangka', $in) ? strtoupper(clean_str($in['nomor_rangka'])) : ($existing['nomor_rangka'] ?? '');
        $noMesin     = array_key_exists('nomor_mesin', $in) ? strtoupper(clean_str($in['nomor_mesin'])) : ($existing['nomor_mesin'] ?? '');
        $keterangan  = array_key_exists('keterangan', $in) ? clean_str($in['keterangan']) : ($existing['keterangan'] ?? '');

        $errors = [];

        if ($nopol === '') {
            $errors['nomor_polisi_kendaraan'] = 'Nomor Polisi Kendaraan wajib diisi.';
        } elseif (!preg_match('/^[A-Z]{1,2}\s?\d{1,4}\s?[A-Z]{1,4}$/', $nopol)) {
            $errors['nomor_polisi_kendaraan'] = 'Format Nomor Polisi tidak valid (contoh: B 1024 PQN).';
        } else {
            $cekNopol = $pdo->prepare("SELECT COUNT(*) FROM kdo WHERE REPLACE(UPPER(nomor_polisi_kendaraan), ' ', '') = REPLACE(UPPER(?), ' ', '') AND id != ?");
            $cekNopol->execute([$nopol, $id]);
            if ((int)$cekNopol->fetchColumn() > 0) {
                $errors['nomor_polisi_kendaraan'] = "Nomor Polisi \"{$nopol}\" sudah digunakan pada KDO lain.";
            }
        }

        if ($merk === '') {
            $errors['merk_kendaraan'] = 'Merk Kendaraan wajib diisi.';
        }

        if ($tipe === '') {
            $errors['tipe_kendaraan'] = 'Tipe Kendaraan wajib diisi.';
        }

        if (!in_array($jenis, $validJenis, true)) {
            $jenis = 'Roda 4';
        }

        $maxYear = (int)date('Y') + 1;
        if ($tahun < 1980 || $tahun > $maxYear) {
            $errors['tahun_produksi'] = "Tahun Produksi harus berada di antara 1980 dan {$maxYear}.";
        }

        if ($warna === '') {
            $errors['warna_kendaraan'] = 'Warna Kendaraan wajib diisi.';
        }

        if (!in_array($kondisi, $validKondisi, true)) {
            $errors['kondisi'] = 'Kondisi harus bernilai Baik, Rusak Ringan, atau Rusak Berat.';
        }

        if ($pegawaiId <= 0) {
            $errors['pegawai_id'] = 'Pegawai Penanggung Jawab (PNS) wajib dipilih.';
        } else {
            $cekPns = $pdo->prepare("SELECT COUNT(*) FROM pns WHERE id = ?");
            $cekPns->execute([$pegawaiId]);
            if ((int)$cekPns->fetchColumn() === 0) {
                $errors['pegawai_id'] = 'Data PNS yang dipilih tidak ditemukan.';
            }
        }

        if ($perangkatId <= 0) {
            $errors['perangkat_id'] = 'Perangkat Daerah wajib dipilih.';
        } else {
            $cekPd = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE id = ?");
            $cekPd->execute([$perangkatId]);
            if ((int)$cekPd->fetchColumn() === 0) {
                $errors['perangkat_id'] = 'Data Perangkat Daerah yang dipilih tidak ditemukan.';
            }
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $stmt = $pdo->prepare("
            UPDATE kdo
            SET pegawai_id = ?, perangkat_id = ?, kode_barang = ?, nomor_polisi_kendaraan = ?,
                merk_kendaraan = ?, tipe_kendaraan = ?, jenis_kendaraan = ?, tahun_produksi = ?,
                warna_kendaraan = ?, nomor_rangka = ?, nomor_mesin = ?, kondisi = ?, keterangan = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $pegawaiId,
            $perangkatId,
            $kodeBarang ?: '1.3.2.02.01.01.001',
            $nopol,
            $merk,
            $tipe,
            $jenis,
            $tahun,
            $warna,
            $noRangka ?: null,
            $noMesin ?: null,
            $kondisi,
            $keterangan ?: null,
            $id
        ]);

        $updated = $pdo->prepare("
            SELECT k.*, p.nama_pegawai, p.nip, p.jabatan, pd.nama_perangkat, pd.singkatan AS singkatan_perangkat
            FROM kdo k
            JOIN pns p ON k.pegawai_id = p.id
            JOIN perangkat_daerah pd ON k.perangkat_id = pd.id
            WHERE k.id = ?
        ");
        $updated->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Kendaraan Dinas Operasional (KDO) berhasil diperbarui.',
            'data'    => $updated->fetch()
        ]);
    }

    // ========================================================================
    // 4. DELETE (DELETE)
    // ========================================================================
    if ($method === 'DELETE') {
        $in = get_request_input();
        $id = isset($in['id']) ? (int)$in['id'] : 0;

        if ($id <= 0) {
            json_response([
                'status'  => 'error',
                'message' => 'ID KDO wajib disertakan untuk penghapusan.'
            ], 400);
        }

        $cek = $pdo->prepare("SELECT * FROM kdo WHERE id = ?");
        $cek->execute([$id]);
        $kdo = $cek->fetch();
        if (!$kdo) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Kendaraan Dinas Operasional (KDO) tidak ditemukan.'
            ], 404);
        }

        // Cek apakah sedang digunakan aktif
        $cekAktif = $pdo->prepare("SELECT COUNT(*) FROM pemakaian_kdo WHERE kdo_id = ? AND status_pemakaian = 'Digunakan'");
        $cekAktif->execute([$id]);
        if ((int)$cekAktif->fetchColumn() > 0) {
            json_response([
                'status'  => 'error',
                'message' => "Kendaraan \"{$kdo['nomor_polisi_kendaraan']}\" sedang dalam status pemakaian aktif. Selesaikan pemakaian terlebih dahulu sebelum menghapus."
            ], 409);
        }

        $del = $pdo->prepare("DELETE FROM kdo WHERE id = ?");
        $del->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Kendaraan Dinas Operasional (KDO) berhasil dihapus.'
        ]);
    }

    json_response([
        'status'  => 'error',
        'message' => 'Metode HTTP tidak diizinkan.'
    ], 405);
} catch (PDOException $e) {
    json_response([
        'status'  => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ], 500);
}
