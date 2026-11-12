<?php
/**
 * RESTful API Endpoint: Pemakaian Kendaraan Dinas Operasional (KDO)
 * URL: /api/pemakaian.php
 * Methods: GET, POST, PUT, PATCH, DELETE
 */

require_once __DIR__ . '/../config/db.php';
api_headers('GET, POST, PUT, PATCH, DELETE, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$validStatus  = ['Digunakan', 'Selesai', 'Terjadwal'];
$validKondisi = ['Baik', 'Rusak Ringan', 'Rusak Berat'];

try {
    // ========================================================================
    // 1. READ (GET List & Detail)
    // ========================================================================
    if ($method === 'GET') {
        $id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $search      = clean_str($_GET['search'] ?? '');
        $status      = clean_str($_GET['status_pemakaian'] ?? '');
        $perangkatId = isset($_GET['perangkat_id']) && $_GET['perangkat_id'] !== '' ? (int)$_GET['perangkat_id'] : 0;
        $kdoId       = isset($_GET['kdo_id']) && $_GET['kdo_id'] !== '' ? (int)$_GET['kdo_id'] : 0;
        $pegawaiId   = isset($_GET['pegawai_id']) && $_GET['pegawai_id'] !== '' ? (int)$_GET['pegawai_id'] : 0;

        if ($id > 0) {
            $stmt = $pdo->prepare("
                SELECT 
                    pk.*,
                    k.nomor_polisi_kendaraan,
                    k.merk_kendaraan,
                    k.tipe_kendaraan,
                    k.jenis_kendaraan,
                    k.tahun_produksi,
                    k.warna_kendaraan,
                    k.kondisi AS kondisi_kdo_saat_ini,
                    k.kode_barang,
                    p.nama_pegawai,
                    p.nip,
                    p.jabatan,
                    p.golongan,
                    p.no_hp AS no_hp_pegawai,
                    pd.nama_perangkat,
                    pd.singkatan AS singkatan_perangkat,
                    pd.kode_perangkat,
                    pd.alamat AS alamat_perangkat
                FROM pemakaian_kdo pk
                JOIN kdo k ON pk.kdo_id = k.id
                JOIN pns p ON pk.pegawai_id = p.id
                JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
                WHERE pk.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                json_response([
                    'status'  => 'error',
                    'message' => 'Data Pemakaian KDO tidak ditemukan.'
                ], 404);
            }

            json_response([
                'status' => 'success',
                'data'   => $row
            ]);
        }

        // GET List Pemakaian KDO
        $sql = "
            SELECT 
                pk.*,
                k.nomor_polisi_kendaraan,
                k.merk_kendaraan,
                k.tipe_kendaraan,
                k.jenis_kendaraan,
                k.warna_kendaraan,
                k.kondisi AS kondisi_kdo_saat_ini,
                p.nama_pegawai,
                p.nip,
                p.jabatan,
                p.golongan,
                pd.nama_perangkat,
                pd.singkatan AS singkatan_perangkat
            FROM pemakaian_kdo pk
            JOIN kdo k ON pk.kdo_id = k.id
            JOIN pns p ON pk.pegawai_id = p.id
            JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                pk.nomor_surat_tugas LIKE ? OR
                pk.keperluan LIKE ? OR
                pk.tujuan LIKE ? OR
                k.nomor_polisi_kendaraan LIKE ? OR
                k.merk_kendaraan LIKE ? OR
                p.nama_pegawai LIKE ? OR
                pd.nama_perangkat LIKE ?
            )";
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }

        if ($status !== '') {
            $sql .= " AND pk.status_pemakaian = ?";
            $params[] = $status;
        }

        if ($perangkatId > 0) {
            $sql .= " AND pk.perangkat_id = ?";
            $params[] = $perangkatId;
        }

        if ($kdoId > 0) {
            $sql .= " AND pk.kdo_id = ?";
            $params[] = $kdoId;
        }

        if ($pegawaiId > 0) {
            $sql .= " AND pk.pegawai_id = ?";
            $params[] = $pegawaiId;
        }

        $sql .= " ORDER BY pk.tanggal_mulai DESC, pk.id DESC";
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
        $in             = get_request_input();
        $kdoId          = isset($in['kdo_id']) ? (int)$in['kdo_id'] : 0;
        $pegawaiId      = isset($in['pegawai_id']) ? (int)$in['pegawai_id'] : 0;
        $perangkatId    = isset($in['perangkat_id']) ? (int)$in['perangkat_id'] : 0;
        $noSurat        = clean_str($in['nomor_surat_tugas'] ?? '');
        $keperluan      = clean_str($in['keperluan'] ?? '');
        $tujuan         = clean_str($in['tujuan'] ?? '');
        $tglMulai       = clean_str($in['tanggal_mulai'] ?? '');
        $tglSelesai     = clean_str($in['tanggal_selesai'] ?? '');
        $kmAwal         = isset($in['km_awal']) && $in['km_awal'] !== '' ? (int)$in['km_awal'] : 0;
        $kmAkhir        = isset($in['km_akhir']) && $in['km_akhir'] !== '' ? (int)$in['km_akhir'] : null;
        $statusPakai    = clean_str($in['status_pemakaian'] ?? 'Digunakan');
        $kondisiSetelah = clean_str($in['kondisi_setelah'] ?? 'Baik');
        $catatan        = clean_str($in['catatan'] ?? '');

        $errors = [];

        if ($kdoId <= 0) {
            $errors['kdo_id'] = 'Kendaraan Dinas Operasional (KDO) wajib dipilih.';
        } else {
            $cekKdo = $pdo->prepare("SELECT * FROM kdo WHERE id = ?");
            $cekKdo->execute([$kdoId]);
            $kdoRow = $cekKdo->fetch();
            if (!$kdoRow) {
                $errors['kdo_id'] = 'Data KDO yang dipilih tidak ditemukan.';
            } elseif ($kdoRow['kondisi'] === 'Rusak Berat' && $statusPakai === 'Digunakan') {
                $errors['kdo_id'] = "Kendaraan {$kdoRow['nomor_polisi_kendaraan']} berstatus Rusak Berat sehingga tidak dapat langsung digunakan.";
            }
        }

        if ($pegawaiId <= 0) {
            $errors['pegawai_id'] = 'Pegawai (PNS) pengguna wajib dipilih.';
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
                $errors['perangkat_id'] = 'Data Perangkat Daerah tidak ditemukan.';
            }
        }

        if ($noSurat === '') {
            $errors['nomor_surat_tugas'] = 'Nomor Surat Tugas / BAST wajib diisi.';
        }

        if ($keperluan === '') {
            $errors['keperluan'] = 'Keperluan penggunaan KDO wajib diisi.';
        }

        if ($tujuan === '') {
            $errors['tujuan'] = 'Lokasi tujuan dinas wajib diisi.';
        }

        if ($tglMulai === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMulai)) {
            $errors['tanggal_mulai'] = 'Tanggal mulai wajib diisi dengan format YYYY-MM-DD.';
        }

        if ($tglSelesai !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglSelesai)) {
                $errors['tanggal_selesai'] = 'Format tanggal selesai harus YYYY-MM-DD.';
            } elseif ($tglMulai !== '' && $tglSelesai < $tglMulai) {
                $errors['tanggal_selesai'] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.';
            }
        } else {
            $tglSelesai = null;
        }

        if ($kmAwal < 0) {
            $errors['km_awal'] = 'Odometer (KM Awal) tidak boleh bernilai negatif.';
        }

        if ($kmAkhir !== null && $kmAkhir < $kmAwal) {
            $errors['km_akhir'] = 'KM Akhir tidak boleh lebih kecil dari KM Awal.';
        }

        if (!in_array($statusPakai, $validStatus, true)) {
            $errors['status_pemakaian'] = 'Status pemakaian harus Digunakan, Selesai, atau Terjadwal.';
        }

        if (!in_array($kondisiSetelah, $validKondisi, true)) {
            $kondisiSetelah = 'Baik';
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO pemakaian_kdo (
                kdo_id, pegawai_id, perangkat_id, nomor_surat_tugas,
                keperluan, tujuan, tanggal_mulai, tanggal_selesai,
                km_awal, km_akhir, status_pemakaian, kondisi_setelah, catatan
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $kdoId,
            $pegawaiId,
            $perangkatId,
            $noSurat,
            $keperluan,
            $tujuan,
            $tglMulai,
            $tglSelesai,
            $kmAwal,
            $kmAkhir,
            $statusPakai,
            $kondisiSetelah,
            $catatan ?: null
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Sinkronisasi kondisi kendaraan apabila pemakaian selesai dan kondisi dilaporkan berubah
        if ($statusPakai === 'Selesai' && in_array($kondisiSetelah, $validKondisi, true)) {
            $updKdo = $pdo->prepare("UPDATE kdo SET kondisi = ? WHERE id = ?");
            $updKdo->execute([$kondisiSetelah, $kdoId]);
        }

        $pdo->commit();

        $detail = $pdo->prepare("
            SELECT 
                pk.*,
                k.nomor_polisi_kendaraan, k.merk_kendaraan, k.tipe_kendaraan,
                p.nama_pegawai, p.nip,
                pd.nama_perangkat, pd.singkatan AS singkatan_perangkat
            FROM pemakaian_kdo pk
            JOIN kdo k ON pk.kdo_id = k.id
            JOIN pns p ON pk.pegawai_id = p.id
            JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
            WHERE pk.id = ?
        ");
        $detail->execute([$newId]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Pemakaian KDO berhasil dicatat.',
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
                'message' => 'Parameter ID Pemakaian KDO wajib disertakan.'
            ], 400);
        }

        $existingStmt = $pdo->prepare("SELECT * FROM pemakaian_kdo WHERE id = ?");
        $existingStmt->execute([$id]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Pemakaian KDO tidak ditemukan.'
            ], 404);
        }

        $kdoId          = array_key_exists('kdo_id', $in) ? (int)$in['kdo_id'] : (int)$existing['kdo_id'];
        $pegawaiId      = array_key_exists('pegawai_id', $in) ? (int)$in['pegawai_id'] : (int)$existing['pegawai_id'];
        $perangkatId    = array_key_exists('perangkat_id', $in) ? (int)$in['perangkat_id'] : (int)$existing['perangkat_id'];
        $noSurat        = array_key_exists('nomor_surat_tugas', $in) ? clean_str($in['nomor_surat_tugas']) : $existing['nomor_surat_tugas'];
        $keperluan      = array_key_exists('keperluan', $in) ? clean_str($in['keperluan']) : $existing['keperluan'];
        $tujuan         = array_key_exists('tujuan', $in) ? clean_str($in['tujuan']) : $existing['tujuan'];
        $tglMulai       = array_key_exists('tanggal_mulai', $in) ? clean_str($in['tanggal_mulai']) : $existing['tanggal_mulai'];
        $tglSelesai     = array_key_exists('tanggal_selesai', $in) ? clean_str($in['tanggal_selesai']) : ($existing['tanggal_selesai'] ?? '');
        $kmAwal         = array_key_exists('km_awal', $in) && $in['km_awal'] !== '' ? (int)$in['km_awal'] : (int)$existing['km_awal'];
        $kmAkhir        = array_key_exists('km_akhir', $in) ? ($in['km_akhir'] !== '' && $in['km_akhir'] !== null ? (int)$in['km_akhir'] : null) : ($existing['km_akhir'] !== null ? (int)$existing['km_akhir'] : null);
        $statusPakai    = array_key_exists('status_pemakaian', $in) ? clean_str($in['status_pemakaian']) : $existing['status_pemakaian'];
        $kondisiSetelah = array_key_exists('kondisi_setelah', $in) ? clean_str($in['kondisi_setelah']) : ($existing['kondisi_setelah'] ?? 'Baik');
        $catatan        = array_key_exists('catatan', $in) ? clean_str($in['catatan']) : ($existing['catatan'] ?? '');

        $errors = [];

        if ($kdoId <= 0) {
            $errors['kdo_id'] = 'KDO wajib dipilih.';
        }
        if ($pegawaiId <= 0) {
            $errors['pegawai_id'] = 'Pegawai (PNS) wajib dipilih.';
        }
        if ($perangkatId <= 0) {
            $errors['perangkat_id'] = 'Perangkat Daerah wajib dipilih.';
        }
        if ($noSurat === '') {
            $errors['nomor_surat_tugas'] = 'Nomor Surat Tugas wajib diisi.';
        }
        if ($keperluan === '') {
            $errors['keperluan'] = 'Keperluan wajib diisi.';
        }
        if ($tujuan === '') {
            $errors['tujuan'] = 'Lokasi tujuan wajib diisi.';
        }
        if ($tglMulai === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglMulai)) {
            $errors['tanggal_mulai'] = 'Tanggal mulai wajib berformat YYYY-MM-DD.';
        }
        if ($tglSelesai !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglSelesai)) {
                $errors['tanggal_selesai'] = 'Format tanggal selesai harus YYYY-MM-DD.';
            } elseif ($tglSelesai < $tglMulai) {
                $errors['tanggal_selesai'] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
            }
        } else {
            $tglSelesai = null;
        }

        if ($kmAkhir !== null && $kmAkhir < $kmAwal) {
            $errors['km_akhir'] = 'KM Akhir tidak boleh lebih kecil dari KM Awal.';
        }

        if (!in_array($statusPakai, $validStatus, true)) {
            $errors['status_pemakaian'] = 'Status pemakaian tidak valid.';
        }

        if (!in_array($kondisiSetelah, $validKondisi, true)) {
            $kondisiSetelah = 'Baik';
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE pemakaian_kdo
            SET kdo_id = ?, pegawai_id = ?, perangkat_id = ?, nomor_surat_tugas = ?,
                keperluan = ?, tujuan = ?, tanggal_mulai = ?, tanggal_selesai = ?,
                km_awal = ?, km_akhir = ?, status_pemakaian = ?, kondisi_setelah = ?, catatan = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $kdoId,
            $pegawaiId,
            $perangkatId,
            $noSurat,
            $keperluan,
            $tujuan,
            $tglMulai,
            $tglSelesai,
            $kmAwal,
            $kmAkhir,
            $statusPakai,
            $kondisiSetelah,
            $catatan ?: null,
            $id
        ]);

        // Apabila status pemakaian diubah menjadi Selesai, perbarui juga kondisi pada tabel KDO
        if ($statusPakai === 'Selesai' && in_array($kondisiSetelah, $validKondisi, true)) {
            $updKdo = $pdo->prepare("UPDATE kdo SET kondisi = ? WHERE id = ?");
            $updKdo->execute([$kondisiSetelah, $kdoId]);
        }

        $pdo->commit();

        $updated = $pdo->prepare("
            SELECT 
                pk.*,
                k.nomor_polisi_kendaraan, k.merk_kendaraan, k.tipe_kendaraan,
                p.nama_pegawai, p.nip,
                pd.nama_perangkat, pd.singkatan AS singkatan_perangkat
            FROM pemakaian_kdo pk
            JOIN kdo k ON pk.kdo_id = k.id
            JOIN pns p ON pk.pegawai_id = p.id
            JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
            WHERE pk.id = ?
        ");
        $updated->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Pemakaian KDO berhasil diperbarui.',
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
                'message' => 'ID Pemakaian KDO wajib disertakan.'
            ], 400);
        }

        $cek = $pdo->prepare("SELECT id FROM pemakaian_kdo WHERE id = ?");
        $cek->execute([$id]);
        if (!$cek->fetch()) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Pemakaian KDO tidak ditemukan.'
            ], 404);
        }

        $del = $pdo->prepare("DELETE FROM pemakaian_kdo WHERE id = ?");
        $del->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Pemakaian KDO berhasil dihapus.'
        ]);
    }

    json_response([
        'status'  => 'error',
        'message' => 'Metode HTTP tidak diizinkan.'
    ], 405);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response([
        'status'  => 'error',
        'message' => 'Kesalahan database: ' . $e->getMessage()
    ], 500);
}
