# AGENTS.md — Panduan Arsitektur & Konvensi Pengembangan SI-KDO BMD

Dokumen ini menjadi acuan teknis bagi AI Agent maupun pengembang manusia saat memelihara, mengaudit, atau mengembangkan fitur baru pada **Sistem Aplikasi Pengelolaan Kendaraan Dinas Operasional (SI-KDO BMD)**.

---

## 1. Konteks Domain (Barang Milik Daerah / BMD)

Aplikasi ini mengelola aset **Kendaraan Dinas Operasional (KDO)** milik Pemerintah Daerah (Kartu Inventaris Barang / KIB B) dengan 4 entitas saling berelasi:
1. **Perangkat Daerah (`perangkat_daerah`)**: Organisasi Perangkat Daerah (OPD/SKPD) pengguna barang, antara lain Dinas Pendidikan, Dinas Kesehatan, Dinas Pekerjaan Umum dan Penataan Ruang (PUPR), BPKAD, Dinas Perhubungan, Dinas Lingkungan Hidup, dan Inspektorat Daerah.
2. **Pegawai Negeri Sipil (`pns`)**: Aparatur Sipil Negara (ASN) yang bertanggung jawab atas penguasaan maupun pemakaian kendaraan dinas (memiliki `nip` 18 digit standar BKN, `nama_pegawai`, `jabatan`, `golongan`, dan relasi ke `perangkat_id`).
3. **Kendaraan Dinas Operasional (`kdo`)**: Aset kendaraan dinas dengan atribut `nomor_polisi_kendaraan` (unik), `kode_barang`, `merk_kendaraan`, `tipe_kendaraan`, `jenis_kendaraan` (`Roda 4`, `Roda 2`, `Roda 6 / Khusus`), `tahun_produksi`, `warna_kendaraan`, `nomor_rangka`, `nomor_mesin`, `kondisi` (`Baik`, `Rusak Ringan`, `Rusak Berat`), serta foreign key `pegawai_id` dan `perangkat_id`.
4. **Pemakaian KDO (`pemakaian_kdo`)**: Pencatatan operasional penggunaan KDO berdasarkan Surat Tugas / SPPD / BAST yang menghubungkan `kdo_id`, `pegawai_id`, dan `perangkat_id`. Apabila `status_pemakaian` diselesaikan (`Selesai`), kolom `kondisi_setelah` secara otomatis menyinkronkan nilai `kondisi` pada tabel `kdo`.

---

## 2. Konvensi Backend & Keamanan RESTful API

Semua endpoint berada di direktori `api/` dan wajib me-require `config/db.php`:
- **Database Access**: Selalu gunakan `$pdo->prepare(...)` dan `$stmt->execute([...])` (`PDO::ATTR_EMULATE_PREPARES => false`). Dilarang menggabungkan variabel input pengguna secara langsung ke dalam string SQL.
- **Input Parsing**: Gunakan helper `get_request_input()` dari `config/db.php` agar endpoint mendukung payload JSON (`php://input`), form data (`$_POST`), maupun parameter URL (`$_GET`).
- **Sanitasi & Validasi**:
  - Gunakan `clean_str()` untuk membersihkan tag HTML dan spasi berlebih.
  - Kembalikan status HTTP yang tepat melalui `json_response($payload, $statusCode)`:
    - `200 OK`: Berhasil membaca, memperbarui, atau menghapus data.
    - `201 Created`: Berhasil membuat entitas baru (`POST`).
    - `400 Bad Request`: Parameter kunci (seperti `id` pada `PUT`/`DELETE`) tidak disertakan.
    - `404 Not Found`: Data dengan `id` yang diminta tidak ditemukan di database.
    - `405 Method Not Allowed`: Metode HTTP tidak didukung oleh endpoint.
    - `409 Conflict`: Penghapusan ditolak karena entitas masih direferensikan oleh data aktif (mis. menghapus PNS yang masih memegang KDO, atau menghapus KDO yang sedang berstatus `Digunakan`).
    - `422 Unprocessable Entity`: Validasi input gagal (mis. NIP bukan angka, Nomor Polisi duplikat, atau kolom wajib kosong). Sertakan objek `errors` per nama field.

---

## 3. Standar Frontend, Aksesibilitas, & Browser Support

Antarmuka utama (`index.php`, `assets/css/style.css`, `assets/js/app.js`) mengikuti standar **Modern Web Guidance**:

- **Browser Support Policy**:
  - Gunakan fitur **Baseline Widely Available** secara langsung, serta terapkan progressive enhancement / fallback ringan untuk fitur baru:
    - `<dialog closedby="any">`: Selalu buka modal dengan `dialog.showModal()`, sertakan CSS `dialog:modal { overscroll-behavior: contain; }` dan `dialog::backdrop { overflow: hidden; overscroll-behavior: contain; }`, reset `dialog.returnValue = ''` pada event `close`, dan pertahankan fallback pengecekan koordinat `getBoundingClientRect()` untuk browser yang belum mendukung `HTMLDialogElement.prototype.closedBy`.
    - Validasi Form Interaktif (`:user-invalid`): Gunakan `:is(:user-invalid, .user-invalid-fallback)` agar pesan error baru tampil setelah pengguna selesai berinteraksi dengan field (`blur` atau `submit`), tempatkan `.hint` di atas elemen `<input>`, dan jaga sinkronisasi `aria-invalid` serta `aria-describedby` (`data-error`) melalui helper di `assets/js/app.js`.
    - Tabel Responsif (`@container`): Bungkus `<table class="data-table">` di dalam `<div tabindex="0" role="region" aria-labelledby="..." class="table-wrapper">` dengan `container-type: inline-size` dan variabel CSS `--label-2` s/d `--label-7` untuk tampilan kartu pada layar sempit.
    - Elemen Pencarian: Bungkus form pencarian dan filter menggunakan elemen semantik `<search>`.
    - Keamanan DOM (XSS Prevention): Selalu gunakan helper `escapeHtml()` saat menyisipkan data dari API ke dalam template HTML.

---

## 4. Alur Verifikasi Perubahan

Setiap kali melakukan perubahan pada skema database atau kode PHP/JS:
1. Jalankan pemeriksaan sintaks PHP:
   ```powershell
   c:\xampp\php\php.exe -l index.php
   c:\xampp\php\php.exe -l config/db.php
   c:\xampp\php\php.exe -l api/kdo.php
   c:\xampp\php\php.exe -l api/pns.php
   c:\xampp\php\php.exe -l api/pd.php
   c:\xampp\php\php.exe -l api/pemakaian.php
   c:\xampp\php\php.exe -l api/dashboard.php
   ```
2. Pastikan file dump SQL `database/db_kendaraan_dinas_operasional.sql` selalu diperbarui jika ada perubahan struktur tabel atau data awal.
3. Uji operasi CRUD (`GET`, `POST`, `PUT`, `DELETE`) pada setiap endpoint untuk memastikan status HTTP dan struktur JSON tetap konsisten dengan `postman/SI_KDO_BMD.postman_collection.json`.
