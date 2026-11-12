# MANUAL SETUP — Panduan Instalasi & Pengujian Aplikasi SI-KDO BMD

Panduan ini menjelaskan langkah demi langkah untuk menyiapkan lingkungan server lokal (XAMPP), mengimpor database MySQL, menjalankan aplikasi web, serta menguji RESTful API menggunakan Postman.

---

## 1. Prasyarat Sistem

1. **XAMPP** (dengan **PHP >= 8.0** dan **MySQL / MariaDB**) terinstal pada direktori `C:\xampp`.
2. **Browser Modern** (Google Chrome, Microsoft Edge, atau Mozilla Firefox).
3. **Postman** (opsional, untuk pengujian endpoint RESTful API secara langsung).

---

## 2. Langkah Instalasi & Konfigurasi

### Langkah 1: Pastikan Direktori Proyek Berada di `htdocs`
Pastikan seluruh file proyek berada pada folder:
```text
C:\xampp\htdocs\kendaraan-dinas-operasional
```

### Langkah 2: Jalankan Layanan Apache dan MySQL
1. Buka **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache**.
3. Klik tombol **Start** pada modul **MySQL**.

### Langkah 3: Impor Database & Data Riil (`db_kendaraan_dinas_operasional`)

Anda dapat mengimpor database melalui **Terminal/PowerShell** atau melalui **phpMyAdmin**:

#### Opsi A — Menggunakan PowerShell / Command Prompt (Cepat)
Jalankan perintah berikut pada terminal:
```powershell
c:\xampp\mysql\bin\mysql.exe -u root -e "SOURCE c:/xampp/htdocs/kendaraan-dinas-operasional/database/db_kendaraan_dinas_operasional.sql;"
```

#### Opsi B — Menggunakan phpMyAdmin
1. Buka browser dan akses `http://localhost/phpmyadmin`.
2. Klik menu **Import** di bagian atas.
3. Klik **Choose File** dan pilih file:
   `C:\xampp\htdocs\kendaraan-dinas-operasional\database\db_kendaraan_dinas_operasional.sql`
4. Klik tombol **Import / Go** di bagian bawah halaman.
5. Database `db_kendaraan_dinas_operasional` beserta 4 tabel (`perangkat_daerah`, `pns`, `kdo`, `pemakaian_kdo`) dan data riil BMD akan otomatis terbuat.

### Langkah 4: Periksa Konfigurasi Koneksi Database (`config/db.php`)
Secara bawaan, `config/db.php` sudah dikonfigurasi untuk XAMPP standar:
```php
$host = 'localhost';
$db   = 'db_kendaraan_dinas_operasional';
$user = 'root';
$pass = '';
```
Jika MySQL Anda menggunakan password atau port khusus, sesuaikan variabel tersebut di `config/db.php`.

---

## 3. Menjalankan Aplikasi Web di Browser

1. Buka browser dan kunjungi alamat:
   ```text
   http://localhost/kendaraan-dinas-operasional/
   ```
2. Anda dapat langsung menggunakan seluruh halaman aplikasi:
   - **Dashboard**: Melihat ringkasan KPI aset KDO, grafik proporsi kondisi kendaraan, daftar kendaraan yang butuh servis, rekapitulasi KDO per Perangkat Daerah, dan log pemakaian terbaru.
   - **Data KDO**: Mencari, memfilter (berdasarkan Perangkat Daerah, Kondisi, Jenis Kendaraan), melihat Detail KDO beserta riwayat pemakaiannya, menambah KDO baru, mengubah kondisi/data KDO, dan menghapus KDO.
   - **Pemakaian KDO**: Mencatat Surat Tugas / SPPD pemakaian kendaraan dinas beserta PNS dan Perangkat Daerah, memperbarui kilometer akhir & menyelesaikan penugasan, serta menyinkronkan kondisi KDO.
   - **Pegawai (PNS)**: Mengelola data PNS (Nama, NIP 18 digit, Jabatan, Golongan, Unit Kerja PD) serta melihat daftar KDO yang dipegang oleh masing-masing PNS.
   - **Perangkat Daerah**: Mengelola data OPD/SKPD (Dinas Pendidikan, Dinas Kesehatan, Dinas PUPR, BPKAD, Dishub, DLH, Inspektorat) beserta rincian aset KDO dan PNS di dalamnya.

---

## 4. Panduan Pengujian RESTful API di Postman

1. Buka aplikasi **Postman**.
2. Klik tombol **Import** di pojok kiri atas.
3. Pilih file koleksi Postman yang telah disediakan di dalam proyek:
   ```text
   C:\xampp\htdocs\kendaraan-dinas-operasional\postman\SI_KDO_BMD.postman_collection.json
   ```
4. Setelah koleksi **SI-KDO BMD — RESTful API Pengelolaan Kendaraan Dinas Operasional** masuk ke daftar Collections:
   - Pastikan variabel koleksi `{{base_url}}` bernilai `http://localhost/kendaraan-dinas-operasional`.
   - Buka masing-masing folder (`1. Dashboard`, `2. Perangkat Daerah (PD)`, `3. Pegawai Negeri Sipil (PNS)`, `4. Kendaraan Dinas Operasional (KDO)`, `5. Pemakaian KDO`).
   - Klik **Send** pada setiap request untuk menguji operasi `GET` (List & Detail), `POST` (Create), `PUT` (Update), dan `DELETE` (Delete).
