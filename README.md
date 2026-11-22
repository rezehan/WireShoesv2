# WireShoes

WireShoes adalah aplikasi operasional toko cuci dan reparasi sepatu. Pelanggan dapat membuat pesanan online dan memantau statusnya. Kasir mencatat pesanan yang masuk di toko, menerima pesanan, dan menetapkan harga final setelah memeriksa kondisi sepatu.

Aplikasi menggunakan **Laravel 13**, **Inertia.js 2**, **Vue 3**, **Tailwind CSS 4**, dan **Vite**. Firebase Authentication menangani login, verifikasi email, dan reset kata sandi. Laravel menyimpan sesi aplikasi, hak akses, dan data operasional.

## Fitur dan hak akses

| Role | Fitur |
| --- | --- |
| **Pelanggan** | Pesan layanan secara online, lihat estimasi dan harga final, pantau status pesanan, dan cek jadwal pengambilan atau pengantaran di **Pesanan Saya**. |
| **Kasir** | Akses **Ringkasan** dan **Pesanan**. Buat pesanan toko, terima pesanan, tentukan estimasi dan harga final per item, tugaskan teknisi atau kurir, serta catat serah terima. |
| **Admin** | Akses semua fitur kasir, kelola layanan, data pelanggan, voucher kompensasi, tim dan absensi, serta laporan operasional. |

### Alur pesanan

1. Pelanggan membuat pesanan online melalui **Layanan**, atau kasir mencatatnya melalui **Pesanan → Hari Ini → Buat pesanan toko**. Sistem menerbitkan nomor pesanan 8 digit.
2. Kasir membuka detail pesanan dan mengisi **Harga final**, estimasi selesai, serta metode serah terima **Ambil di toko** atau **Diantar kurir**. Pesanan berstatus **Diproses** setelah diterima.
3. Saat pengerjaan selesai, kasir memperbarui status menjadi **Selesai**, lalu mencatat waktu pengambilan atau pengantaran. Pelanggan online dapat mengikuti pembaruan di **Pesanan Saya**.

Tab **Hari Ini** dan **Riwayat Pesanan** memakai tanggal masuk dalam WITA. Riwayat dapat difilter berdasarkan rentang tanggal, nomor pesanan, dan sumber pesanan. Ringkasan menampilkan grafik pesanan 7 hari terakhir dari kanal Online dan Toko.

### Tarif awal layanan

| Layanan | Tarif awal |
| --- | ---: |
| Cuci | Rp20.000 |
| Reparasi | Rp35.000 |
| Cuci dan Reparasi | Rp50.000 |

Tarif awal tampil saat pelanggan memilih layanan. Kasir menentukan harga final setiap item sesuai kondisi sepatu dan batas minimum layanan. Sistem menyimpan harga per item pada pesanan.

### Voucher kompensasi

Pesanan yang selesai melewati estimasi masuk ke antrean **Perlu diterbitkan**. Admin menerbitkan voucher dengan nominal dan masa berlaku yang ditentukan saat penerbitan. Voucher yang berstatus **Terbit** dapat dipakai sekali pada pesanan lain milik pelanggan yang sama, setelah pengerjaan selesai dan sebelum serah terima. Total yang sudah dipotong tampil pada pesanan yang menggunakan voucher.

## Prasyarat

- PHP 8.3–8.5 dan Composer 2.
- Node.js 22.12 atau lebih baru dan npm.
- SQLite dengan ekstensi PHP `pdo_sqlite`, atau MySQL 8.0.16 atau lebih baru dengan `pdo_mysql`.
- Proyek Firebase dengan provider **Email/Password** aktif, API key aplikasi Web, dan service account JSON untuk server.

## Menjalankan proyek secara lokal

Jalankan perintah dari root repositori.

### 1. Pasang dependensi dan siapkan konfigurasi

```bash
composer install
npm ci
```

Untuk instalasi baru, buat `.env` dan kunci aplikasi.

```bash
cp .env.example .env
php artisan key:generate
```

### 2. Konfigurasi database dan Firebase

Contoh konfigurasi lokal dengan SQLite. Buat file database dengan `touch database/database.sqlite`, lalu sesuaikan path absolutnya di `.env`.

```dotenv
APP_NAME=WireShoes
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/path/ke/WireShoes/database/database.sqlite

FIREBASE_API_KEY=isi_api_key_aplikasi_web
FIREBASE_CREDENTIALS="/path/di-luar-repositori/firebase-service-account.json"
```

Untuk MySQL, buat database terlebih dahulu lalu gunakan `DB_CONNECTION=mysql` dan isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, serta `DB_PASSWORD`.

Ambil API key dari konfigurasi aplikasi Web di pengaturan proyek Firebase. Gunakan service account dari proyek Firebase yang sama. Simpan `.env` dan service account JSON sebagai konfigurasi lokal di luar GitHub.

### 3. Buat tabel dan isi data awal

```bash
php artisan migrate
php artisan db:seed --class=WireShoesOrderStatusSeeder
php artisan db:seed --class=WireShoesTarifAwalSeeder
```

`WireShoesTarifAwalSeeder` menetapkan tarif katalog ketiga layanan sesuai tabel di atas.

### 4. Jalankan Laravel dan Vite

Di terminal pertama, jalankan `php artisan serve`. Di terminal kedua, jalankan `npm run dev`. Buka [http://localhost:8000](http://localhost:8000). Untuk membangun aset statis, jalankan `npm run build`.

## Menyiapkan akun

**Pelanggan** mendaftar dari situs, membuka tautan verifikasi yang dikirim Firebase, lalu kembali ke WireShoes dan klik **Cek status verifikasi**. Email terverifikasi diperlukan untuk membuat pesanan dan membuka **Pesanan Saya**.

**Admin dan kasir** dibuat terlebih dahulu di Firebase Authentication. Jalankan perintah berikut dengan email akun masing-masing untuk menautkannya ke Laravel.

```bash
php artisan firebase:link-user admin@example.com --role=admin
php artisan firebase:link-user kasir@example.com --role=kasir
```

Untuk akun Laravel yang sudah ada dan ingin mempertahankan role-nya, jalankan perintah tanpa `--role`. Staf dapat membuka `/dashboard` setelah login dan verifikasi email.

## Rute utama

| Rute | Keterangan |
| --- | --- |
| `/` | Beranda WireShoes |
| `/layanan` | Form pesanan online |
| `/pesanan-saya` | Status, harga, dan serah terima pesanan pelanggan |
| `/dashboard` | Dashboard admin dan kasir |

## Pengujian

```bash
php artisan test
npm run build
```