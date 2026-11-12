<?php
/**
 * RESTful API Endpoint: Pegawai Negeri Sipil (PNS)
 * URL: /api/pns.php
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
        $id          = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $search      = clean_str($_GET['search'] ?? '');
        $perangkatId = isset($_GET['perangkat_id']) && $_GET['perangkat_id'] !== '' ? (int)$_GET['perangkat_id'] : 0;
        $golongan    = clean_str($_GET['golongan'] ?? '');

        if ($id > 0) {
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    pd.nama_perangkat,
                    pd.singkatan AS singkatan_perangkat,
                    pd.kode_perangkat,
                    (SELECT COUNT(*) FROM kdo WHERE kdo.pegawai_id = p.id) AS jumlah_kdo,
                    (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.pegawai_id = p.id) AS jumlah_pemakaian
                FROM pns p
                LEFT JOIN perangkat_daerah pd ON p.perangkat_id = pd.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch();

            if (!$row) {
                json_response([
                    'status'  => 'error',
                    'message' => 'Data Pegawai Negeri Sipil (PNS) tidak ditemukan.'
                ], 404);
            }

            // Daftar KDO di bawah tanggung jawab PNS ini
            $stmtKdo = $pdo->prepare("
                SELECT 
                    k.*,
                    pd.nama_perangkat,
                    pd.singkatan AS singkatan_perangkat
                FROM kdo k
                LEFT JOIN perangkat_daerah pd ON k.perangkat_id = pd.id
                WHERE k.pegawai_id = ?
                ORDER BY k.nomor_polisi_kendaraan ASC
            ");
            $stmtKdo->execute([$id]);
            $row['daftar_kdo'] = $stmtKdo->fetchAll();

            // Riwayat Pemakaian KDO oleh PNS ini
            $stmtPakai = $pdo->prepare("
                SELECT 
                    pk.*,
                    k.nomor_polisi_kendaraan,
                    k.merk_kendaraan,
                    k.tipe_kendaraan,
                    pd.nama_perangkat,
                    pd.singkatan AS singkatan_perangkat
                FROM pemakaian_kdo pk
                JOIN kdo k ON pk.kdo_id = k.id
                JOIN perangkat_daerah pd ON pk.perangkat_id = pd.id
                WHERE pk.pegawai_id = ?
                ORDER BY pk.tanggal_mulai DESC, pk.id DESC
            ");
            $stmtPakai->execute([$id]);
            $row['riwayat_pemakaian'] = $stmtPakai->fetchAll();

            json_response([
                'status' => 'success',
                'data'   => $row
            ]);
        }

        // GET List PNS
        $sql = "
            SELECT 
                p.*,
                pd.nama_perangkat,
                pd.singkatan AS singkatan_perangkat,
                pd.kode_perangkat,
                (SELECT COUNT(*) FROM kdo WHERE kdo.pegawai_id = p.id) AS jumlah_kdo,
                (SELECT COUNT(*) FROM pemakaian_kdo pk WHERE pk.pegawai_id = p.id) AS jumlah_pemakaian
            FROM pns p
            LEFT JOIN perangkat_daerah pd ON p.perangkat_id = pd.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (p.nama_pegawai LIKE ? OR p.nip LIKE ? OR p.jabatan LIKE ? OR pd.nama_perangkat LIKE ?)";
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like);
        }

        if ($perangkatId > 0) {
            $sql .= " AND p.perangkat_id = ?";
            $params[] = $perangkatId;
        }

        if ($golongan !== '') {
            $sql .= " AND p.golongan = ?";
            $params[] = $golongan;
        }

        $sql .= " ORDER BY p.nama_pegawai ASC";
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
        $nama        = clean_str($in['nama_pegawai'] ?? '');
        $nip         = preg_replace('/\s+/', '', clean_str($in['nip'] ?? ''));
        $jabatan     = clean_str($in['jabatan'] ?? '');
        $golongan    = clean_str($in['golongan'] ?? '');
        $noHp        = clean_str($in['no_hp'] ?? '');
        $perangkatId = isset($in['perangkat_id']) && $in['perangkat_id'] !== '' ? (int)$in['perangkat_id'] : null;

        $errors = [];

        if ($nama === '') {
            $errors['nama_pegawai'] = 'Nama Pegawai wajib diisi.';
        }

        if ($nip === '') {
            $errors['nip'] = 'NIP wajib diisi.';
        } elseif (!preg_match('/^\d{8,20}$/', $nip)) {
            $errors['nip'] = 'NIP harus berupa angka (standar 18 digit).';
        } else {
            $cekNip = $pdo->prepare("SELECT COUNT(*) FROM pns WHERE nip = ?");
            $cekNip->execute([$nip]);
            if ((int)$cekNip->fetchColumn() > 0) {
                $errors['nip'] = 'NIP sudah terdaftar pada pegawai lain.';
            }
        }

        if ($jabatan === '') {
            $errors['jabatan'] = 'Jabatan wajib diisi.';
        }

        if ($golongan === '') {
            $errors['golongan'] = 'Golongan wajib diisi (contoh: III/c, IV/a).';
        }

        if ($perangkatId !== null && $perangkatId > 0) {
            $cekPd = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE id = ?");
            $cekPd->execute([$perangkatId]);
            if ((int)$cekPd->fetchColumn() === 0) {
                $errors['perangkat_id'] = 'Perangkat Daerah yang dipilih tidak valid.';
            }
        } else {
            $perangkatId = null;
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $stmt = $pdo->prepare("
            INSERT INTO pns (perangkat_id, nama_pegawai, nip, jabatan, golongan, no_hp)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $perangkatId,
            $nama,
            $nip,
            $jabatan,
            $golongan,
            $noHp ?: null
        ]);

        $newId = (int)$pdo->lastInsertId();
        $detail = $pdo->prepare("
            SELECT p.*, pd.nama_perangkat, pd.singkatan AS singkatan_perangkat
            FROM pns p
            LEFT JOIN perangkat_daerah pd ON p.perangkat_id = pd.id
            WHERE p.id = ?
        ");
        $detail->execute([$newId]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Pegawai Negeri Sipil (PNS) berhasil ditambahkan.',
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
                'message' => 'Parameter ID PNS wajib disertakan.'
            ], 400);
        }

        $existingStmt = $pdo->prepare("SELECT * FROM pns WHERE id = ?");
        $existingStmt->execute([$id]);
        $existing = $existingStmt->fetch();

        if (!$existing) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Pegawai Negeri Sipil (PNS) tidak ditemukan.'
            ], 404);
        }

        $nama     = array_key_exists('nama_pegawai', $in) ? clean_str($in['nama_pegawai']) : $existing['nama_pegawai'];
        $nip      = array_key_exists('nip', $in) ? preg_replace('/\s+/', '', clean_str($in['nip'])) : $existing['nip'];
        $jabatan  = array_key_exists('jabatan', $in) ? clean_str($in['jabatan']) : $existing['jabatan'];
        $golongan = array_key_exists('golongan', $in) ? clean_str($in['golongan']) : $existing['golongan'];
        $noHp     = array_key_exists('no_hp', $in) ? clean_str($in['no_hp']) : ($existing['no_hp'] ?? '');

        if (array_key_exists('perangkat_id', $in)) {
            $perangkatId = ($in['perangkat_id'] !== '' && $in['perangkat_id'] !== null) ? (int)$in['perangkat_id'] : null;
        } else {
            $perangkatId = $existing['perangkat_id'] ? (int)$existing['perangkat_id'] : null;
        }

        $errors = [];
        if ($nama === '') {
            $errors['nama_pegawai'] = 'Nama Pegawai wajib diisi.';
        }

        if ($nip === '') {
            $errors['nip'] = 'NIP wajib diisi.';
        } elseif (!preg_match('/^\d{8,20}$/', $nip)) {
            $errors['nip'] = 'NIP harus berupa angka (standar 18 digit).';
        } else {
            $cekNip = $pdo->prepare("SELECT COUNT(*) FROM pns WHERE nip = ? AND id != ?");
            $cekNip->execute([$nip, $id]);
            if ((int)$cekNip->fetchColumn() > 0) {
                $errors['nip'] = 'NIP sudah digunakan oleh pegawai lain.';
            }
        }

        if ($jabatan === '') {
            $errors['jabatan'] = 'Jabatan wajib diisi.';
        }

        if ($golongan === '') {
            $errors['golongan'] = 'Golongan wajib diisi.';
        }

        if ($perangkatId !== null && $perangkatId > 0) {
            $cekPd = $pdo->prepare("SELECT COUNT(*) FROM perangkat_daerah WHERE id = ?");
            $cekPd->execute([$perangkatId]);
            if ((int)$cekPd->fetchColumn() === 0) {
                $errors['perangkat_id'] = 'Perangkat Daerah yang dipilih tidak ditemukan.';
            }
        } else {
            $perangkatId = null;
        }

        if (!empty($errors)) {
            json_response([
                'status'  => 'error',
                'message' => reset($errors),
                'errors'  => $errors
            ], 422);
        }

        $stmt = $pdo->prepare("
            UPDATE pns
            SET perangkat_id = ?, nama_pegawai = ?, nip = ?, jabatan = ?, golongan = ?, no_hp = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $perangkatId,
            $nama,
            $nip,
            $jabatan,
            $golongan,
            $noHp ?: null,
            $id
        ]);

        $updated = $pdo->prepare("
            SELECT p.*, pd.nama_perangkat, pd.singkatan AS singkatan_perangkat
            FROM pns p
            LEFT JOIN perangkat_daerah pd ON p.perangkat_id = pd.id
            WHERE p.id = ?
        ");
        $updated->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Pegawai Negeri Sipil (PNS) berhasil diperbarui.',
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
                'message' => 'ID PNS wajib disertakan untuk penghapusan.'
            ], 400);
        }

        $cek = $pdo->prepare("SELECT * FROM pns WHERE id = ?");
        $cek->execute([$id]);
        $pns = $cek->fetch();
        if (!$pns) {
            json_response([
                'status'  => 'error',
                'message' => 'Data Pegawai Negeri Sipil (PNS) tidak ditemukan.'
            ], 404);
        }

        // Cek apakah PNS masih menjadi penanggung jawab KDO
        $cekKdo = $pdo->prepare("SELECT COUNT(*) FROM kdo WHERE pegawai_id = ?");
        $cekKdo->execute([$id]);
        $jmlKdo = (int)$cekKdo->fetchColumn();

        if ($jmlKdo > 0) {
            json_response([
                'status'  => 'error',
                'message' => "Pegawai \"{$pns['nama_pegawai']}\" tidak dapat dihapus karena masih tercatat sebagai penanggung jawab {$jmlKdo} unit KDO. Silakan alihkan penanggung jawab KDO terlebih dahulu."
            ], 409);
        }

        // Cek apakah PNS masih memiliki riwayat pemakaian KDO
        $cekPakai = $pdo->prepare("SELECT COUNT(*) FROM pemakaian_kdo WHERE pegawai_id = ?");
        $cekPakai->execute([$id]);
        $jmlPakai = (int)$cekPakai->fetchColumn();

        if ($jmlPakai > 0) {
            json_response([
                'status'  => 'error',
                'message' => "Pegawai \"{$pns['nama_pegawai']}\" tidak dapat dihapus karena memiliki {$jmlPakai} riwayat pemakaian KDO."
            ], 409);
        }

        $del = $pdo->prepare("DELETE FROM pns WHERE id = ?");
        $del->execute([$id]);

        json_response([
            'status'  => 'success',
            'message' => 'Data Pegawai Negeri Sipil (PNS) berhasil dihapus.'
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
