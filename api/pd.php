<?php
/**
 * RESTful API Endpoint: Perangkat Daerah (PD)
 * URL: /api/pd.php
 * Methods: GET, POST, PUT, PATCH, DELETE
 */

require_once __DIR__ . '/../config/db.php';
api_headers('GET, POST, PUT, PATCH, DELETE, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    // ========================================================================
    // 1. READ (GET List & Detail)
    // ========================================================================
    if ($method === 'GET') {
        $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $search = clean_str($_GET['search'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("
                SELECT 
                    pd.*,
                    (SELECT COUNT(*) FROM pns WHERE pns.perangkat_id = pd.id) AS jumlah_pns,
                    (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id) AS jumlah_kdo,
                    (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id AND kdo.kondisi = 'Baik') AS kdo_baik,
                    (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id AND kdo.kondisi = 'Rusak Ringan') AS kdo_rusak_ringan,
                    (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id AND kdo.kondisi = 'Rusak Berat') AS kdo_rusak_berat,
                    (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.perangkat_id = pd.id) AS jumlah_pemakaian
                FROM perangkat_daerah pd
                WHERE pd.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                json_response([
                    'status'  => 'error',
                    'message' => 'Data Perangkat Daerah tidak ditemukan.'
                ], 404);
            }

            // Daftar PNS di Perangkat Daerah ini
            $stmtPns = $pdo->prepare("
                SELECT id, nama_pegawai, nip, jabatan, golongan, no_hp
                FROM pns
                WHERE perangkat_id = ?
                ORDER BY golongan DESC, nama_pegawai ASC
            ");
            $stmtPns->execute([$id]);
            $row['daftar_pns'] = $stmtPns->fetchAll();

            // Daftar KDO milik Perangkat Daerah ini
            $stmtKdo = $pdo->prepare("
                SELECT 
                    k.*,
                    p.nama_pegawai,
                    p.nip,
                    p.jabatan
                FROM kdo k
                LEFT JOIN pns p ON k.pegawai_id = p.id
                WHERE k.perangkat_id = ?
                ORDER BY k.nomor_polisi_kendaraan ASC
            ");
            $stmtKdo->execute([$id]);
            $row['daftar_kdo'] = $stmtKdo->fetchAll();

            // Riwayat Pemakaian KDO pada Perangkat Daerah ini
            $stmtPakai = $pdo->prepare("
                SELECT 
                    pk.*,
                    k.nomor_polisi_kendaraan,
                    k.merk_kendaraan,
                    k.tipe_kendaraan,
                    p.nama_pegawai,
                    p.nip
                FROM pemakaian_kdo pk
                JOIN kdo k ON pk.kdo_id = k.id
                JOIN pns p ON pk.pegawai_id = p.id
                WHERE pk.perangkat_id = ?
                ORDER BY pk.tanggal_mulai DESC, pk.id DESC
            ");
            $stmtPakai->execute([$id]);
            $row['riwayat_pemakaian'] = $stmtPakai->fetchAll();

            json_response([
                'status' => 'success',
                'data'   => $row
            ]);
        }

        // GET List Perangkat Daerah
        $sql = "
            SELECT 
                pd.*,
                (SELECT COUNT(*) FROM pns WHERE pns.perangkat_id = pd.id) AS jumlah_pns,
                (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id) AS jumlah_kdo,
                (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id AND kdo.kondisi = 'Baik') AS kdo_baik,
                (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id AND kdo.kondisi = 'Rusak Ringan') AS kdo_rusak_ringan,
                (SELECT COUNT(*) FROM kdo WHERE kdo.perangkat_id = pd.id AND kdo.kondisi = 'Rusak Berat') AS kdo_rusak_berat,
                (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.perangkat_id = pd.id) AS jumlah_pemakaian
            FROM perangkat_daerah pd
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (pd.nama_perangkat LIKE ? OR pd.singkatan LIKE ? OR pd.kode_perangkat LIKE ? OR pd.alamat LIKE ?)";
            $like = "%{$search}%";
            $params = [$like, $like, $like, $like];
        }

        $sql .= " ORDER BY pd.nama_perangkat ASC";
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
        $in       = get_request_input();
        $nama     = clean_str($in['nama_perangkat'] ?? '');
        $kode     = clean_str($in['kode_perangkat'] ?? '');
        $singkat  = strtoupper(clean_str($in['singkatan'] ?? ''));
        $alamat   = clean_str($in['alamat'] ?? '');
        $telepon  = clean_str($in['telepon'] ?? '');

        $errors = [];
        if ($nama === '') {
            $errors['nama_perangkat'] = 'Nama Perangkat Daerah wajib diisi.';
        } elseif (mb_strlen($nama) < 3) {
            $errors['nama_perangkat'] = 'Nama Perangkat Daerah minimal 3 karakter.';
        }

        if ($nama !== '') {
            $cekNama = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE LOWER(nama_perangkat) = LOWER(?)");
            $cekNama->execute([$nama]);
            if ((int)$cekNama->fetchColumn() > 0) {
                $errors['nama_perangkat'] = 'Nama Perangkat Daerah sudah terdaftar.';
            }
        }

        if ($kode !== '') {
            $cekKode = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE LOWER(kode_perangkat) = LOWER(?)");
            $cekKode->execute([$kode]);
            if ((int)$cekKode->fetchColumn() > 0) {
                $errors['kode_perangkat'] = 'Kode Perangkat Daerah sudah digunakan.';
            }
        } else {
            // Generate kode otomatis jika tidak dikirim
            $maxId = (int)$pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM perangkat_daerah")->fetchColumn();
            $kode  = sprintf('1.00.%02d', $maxId);
        }

        if ($singkat === '' && $nama !== '') {
            // Buat singkatan otomatis dari huruf awal kata
            $words = preg_split('/\s+/', preg_replace('/[^a-zA-Z\s]/', '', $nama));
            $acr = '';
            foreach ($words as $w) {
                if ($w !== '') {
                    $acr .= strtoupper($w[0]);
                }
            }
            $singkat = substr($acr ?: 'OPD', 0, 15);
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $stmt = $pdo->prepare("
            INSERT INTO perangkat_daerah (kode_perangkat, nama_perangkat, singkatan, alamat, telepon)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $kode ?: null,
            $nama,
            $singkat ?: null,
            $alamat ?: null,
            $telepon ?: null
        ]);

        $newId = (int)$pdo->lastInsertId();
        $detail = $pdo->prepare("SELECT * FROM perangkat_daerah WHERE id = ?");
        $detail->execute([$newId]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Perangkat Daerah berhasil ditambahkan.',
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
                'message' => 'Parameter ID Perangkat Daerah wajib disertakan.'
            ], 400);
        }

        $existingStmt = $pdo->prepare("SELECT * FROM perangkat_daerah WHERE id = ?");
        $existingStmt->execute([$id]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Perangkat Daerah tidak ditemukan.'
            ], 404);
        }

        $nama    = array_key_exists('nama_perangkat', $in) ? clean_str($in['nama_perangkat']) : $existing['nama_perangkat'];
        $kode    = array_key_exists('kode_perangkat', $in) ? clean_str($in['kode_perangkat']) : ($existing['kode_perangkat'] ?? '');
        $singkat = array_key_exists('singkatan', $in) ? strtoupper(clean_str($in['singkatan'])) : ($existing['singkatan'] ?? '');
        $alamat  = array_key_exists('alamat', $in) ? clean_str($in['alamat']) : ($existing['alamat'] ?? '');
        $telepon = array_key_exists('telepon', $in) ? clean_str($in['telepon']) : ($existing['telepon'] ?? '');

        $errors = [];
        if ($nama === '') {
            $errors['nama_perangkat'] = 'Nama Perangkat Daerah wajib diisi.';
        } else {
            $cekNama = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE LOWER(nama_perangkat) = LOWER(?) AND id != ?");
            $cekNama->execute([$nama, $id]);
            if ((int)$cekNama->fetchColumn() > 0) {
                $errors['nama_perangkat'] = 'Nama Perangkat Daerah sudah digunakan oleh unit lain.';
            }
        }

        if ($kode !== '') {
            $cekKode = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE LOWER(kode_perangkat) = LOWER(?) AND id != ?");
            $cekKode->execute([$kode, $id]);
            if ((int)$cekKode->fetchColumn() > 0) {
                $errors['kode_perangkat'] = 'Kode Perangkat Daerah sudah digunakan oleh unit lain.';
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
            UPDATE perangkat_daerah
            SET kode_perangkat = ?, nama_perangkat = ?, singkatan = ?, alamat = ?, telepon = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $kode ?: null,
            $nama,
            $singkat ?: null,
            $alamat ?: null,
            $telepon ?: null,
            $id
        ]);

        $updated = $pdo->prepare("SELECT * FROM perangkat_daerah WHERE id = ?");
        $updated->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Perangkat Daerah berhasil diperbarui.',
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
                'message' => 'ID Perangkat Daerah wajib disertakan untuk penghapusan.'
            ], 400);
        }

        $cek = $pdo->prepare("SELECT * FROM perangkat_daerah WHERE id = ?");
        $cek->execute([$id]);
        $pd = $cek->fetch();
        if (!$pd) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Perangkat Daerah tidak ditemukan.'
            ], 404);
        }

        // Cek relasi ke KDO & Pemakaian
        $cekKdo = $pdo->prepare("SELECT COUNT(*) FROM kdo WHERE perangkat_id = ?");
        $cekKdo->execute([$id]);
        $jmlKdo = (int)$cekKdo->fetchColumn();

        if ($jmlKdo > 0) {
            json_response([
                'status'  => 'error',
                'message' => "Perangkat Daerah \"{$pd['nama_perangkat']}\" tidak dapat dihapus karena masih memiliki {$jmlKdo} unit Kendaraan Dinas Operasional (KDO) aktif."
            ], 409);
        }

        $cekPakai = $pdo->prepare("SELECT COUNT(*) FROM pemakaian_kdo WHERE perangkat_id = ?");
        $cekPakai->execute([$id]);
        $jmlPakai = (int)$cekPakai->fetchColumn();

        if ($jmlPakai > 0) {
            json_response([
                'status'  => 'error',
                'message' => "Perangkat Daerah \"{$pd['nama_perangkat']}\" tidak dapat dihapus karena tercatat pada {$jmlPakai} riwayat pemakaian KDO."
            ], 409);
        }

        $del = $pdo->prepare("DELETE FROM perangkat_daerah WHERE id = ?");
        $del->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Perangkat Daerah berhasil dihapus.'
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
