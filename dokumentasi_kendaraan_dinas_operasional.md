# Dokumentasi Teknis & Spesifikasi RESTful API — SI-KDO BMD

Dokumen ini memuat spesifikasi lengkap endpoint RESTful API, format Request/Response JSON, validasi & error handling, serta skenario pengujian untuk **Sistem Aplikasi Pengelolaan Kendaraan Dinas Operasional (KDO)** milik Perangkat Daerah (Barang Milik Daerah / BMD).

---

## 1. Spesifikasi Endpoint API

### A. Endpoint Kendaraan Dinas Operasional (`/api/kdo.php`)

#### 1. `GET /api/kdo.php` (Daftar KDO)
- **Query Params Opsional**:
  - `search`: kata kunci pencarian (Nomor Polisi, Merk, Tipe, Warna, Nama PNS, Nama PD)
  - `kondisi`: `Baik` | `Rusak Ringan` | `Rusak Berat`
  - `jenis_kendaraan`: `Roda 4` | `Roda 2` | `Roda 6 / Khusus`
  - `perangkat_id`: ID Perangkat Daerah
  - `pegawai_id`: ID PNS Penanggung Jawab
- **Response `200 OK`**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "pegawai_id": 1,
      "perangkat_id": 1,
      "kode_barang": "1.3.2.02.01.01.002",
      "nomor_polisi_kendaraan": "B 1024 PQN",
      "merk_kendaraan": "Toyota",
      "tipe_kendaraan": "Kijang Innova Zenix 2.0 G CVT",
      "jenis_kendaraan": "Roda 4",
      "tahun_produksi": 2023,
      "warna_kendaraan": "Hitam Metalik",
      "kondisi": "Baik",
      "nama_pegawai": "Dr. H. Bambang Sutrisno, M.Pd.",
      "nip": "197205141998031004",
      "nama_perangkat": "Dinas Pendidikan"
    }
  ]
}
```

#### 2. `GET /api/kdo.php?id={id}` (Detail KDO + Riwayat Pemakaian)
- **Response `200 OK`**: Mengembalikan objek detail KDO beserta array `riwayat_pemakaian`.
- **Response `404 Not Found`**: Jika ID tidak ditemukan.

#### 3. `POST /api/kdo.php` (Tambah KDO Baru)
- **Request Body (`application/json`)**:
```json
{
  "nomor_polisi_kendaraan": "B 1590 PQN",
  "kode_barang": "1.3.2.02.01.01.003",
  "merk_kendaraan": "Toyota",
  "tipe_kendaraan": "Rush 1.5 S AT GR Sport",
  "jenis_kendaraan": "Roda 4",
  "tahun_produksi": 2024,
  "warna_kendaraan": "Putih",
  "nomor_rangka": "MHKF8FA2JPK209184",
  "nomor_mesin": "2NR-VE8841209",
  "kondisi": "Baik",
  "pegawai_id": 2,
  "perangkat_id": 1,
  "keterangan": "Kendaraan Operasional Pengawasan Pembangunan Gedung Sekolah"
}
```
- **Response `201 Created`**: Berhasil menyimpan data KDO baru.
- **Response `422 Unprocessable Entity`**: Validasi gagal (misal Nomor Polisi duplikat atau format tidak valid).

#### 4. `PUT /api/kdo.php` atau `PATCH /api/kdo.php` (Ubah Data KDO)
- **Request Body (`application/json`)**: Sertakan `"id"` beserta atribut yang ingin diperbarui.
- **Response `200 OK`**: Data KDO berhasil diperbarui.

#### 5. `DELETE /api/kdo.php` (Hapus Data KDO)
- **Request Body / Query**: `{"id": 13}` atau `?id=13`.
- **Response `200 OK`**: Data KDO berhasil dihapus.
- **Response `409 Conflict`**: Ditolak bila KDO sedang dalam status pemakaian aktif (`Digunakan`).

---

### B. Endpoint Pegawai Negeri Sipil (`/api/pns.php`)

- **`GET /api/pns.php`**: Menampilkan daftar seluruh PNS beserta nama Perangkat Daerah dan jumlah KDO yang dipegang.
- **`GET /api/pns.php?id={id}`**: Menampilkan detail PNS beserta `daftar_kdo` dan `riwayat_pemakaian`.
- **`POST /api/pns.php`**:
```json
{
  "perangkat_id": 3,
  "nama_pegawai": "Rizky Pratama Wijaya, S.T.",
  "nip": "199103152015031004",
  "jabatan": "Pengawas Jalan dan Jembatan Ahli Muda",
  "golongan": "III/c",
  "no_hp": "081299881122"
}
```
- **`PUT /api/pns.php`**: Memperbarui data PNS berdasarkan `id`.
- **`DELETE /api/pns.php`**: Menghapus data PNS (mengembalikan `409 Conflict` bila PNS masih memegang KDO aktif).

---

### C. Endpoint Perangkat Daerah (`/api/pd.php`)

- **`GET /api/pd.php`**: Menampilkan daftar Perangkat Daerah beserta agregasi jumlah PNS dan rincian kondisi KDO (`kdo_baik`, `kdo_rusak_ringan`, `kdo_rusak_berat`).
- **`GET /api/pd.php?id={id}`**: Menampilkan detail Perangkat Daerah beserta `daftar_pns`, `daftar_kdo`, dan `riwayat_pemakaian`.
- **`POST /api/pd.php`**:
```json
{
  "kode_perangkat": "2.16.01",
  "nama_perangkat": "Dinas Komunikasi, Informatika dan Statistik",
  "singkatan": "DISKOMINFO",
  "alamat": "Jl. Medan Merdeka Selatan No. 8-9",
  "telepon": "(021) 3823252"
}
```
- **`PUT /api/pd.php`**: Memperbarui data Perangkat Daerah.
- **`DELETE /api/pd.php`**: Menghapus Perangkat Daerah (mengembalikan `409 Conflict` bila masih memiliki unit KDO).

---

### D. Endpoint Pemakaian KDO (`/api/pemakaian.php`)

- **`GET /api/pemakaian.php`**: Menampilkan daftar pemakaian KDO lengkap dengan informasi KDO, PNS pengguna, dan Perangkat Daerah.
- **`GET /api/pemakaian.php?id={id}`**: Menampilkan detail pemakaian KDO.
- **`POST /api/pemakaian.php`**:
```json
{
  "kdo_id": 1,
  "pegawai_id": 1,
  "perangkat_id": 1,
  "nomor_surat_tugas": "800/ST-155/DISDIK/XI/2026",
  "keperluan": "Rapat Koordinasi Nasional Evaluasi BOS dan BOSP",
  "tujuan": "Kementerian Pendidikan Dasar dan Menengah",
  "tanggal_mulai": "2026-11-12",
  "km_awal": 18200,
  "status_pemakaian": "Digunakan",
  "kondisi_setelah": "Baik",
  "catatan": "Penugasan resmi Kepala Dinas Pendidikan"
}
```
- **`PUT /api/pemakaian.php`**: Memperbarui atau menyelesaikan pemakaian KDO (ketika `status_pemakaian` diubah menjadi `Selesai`, nilai `kondisi_setelah` otomatis memperbarui kondisi pada tabel `kdo`).
- **`DELETE /api/pemakaian.php`**: Menghapus catatan pemakaian KDO.
