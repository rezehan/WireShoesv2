# WireShoes dengan Firebase Authentication

Laravel menerima form Inertia, mengirim kredensial ke Firebase untuk login, memvalidasi ID token yang diterima, lalu membuka sesi Laravel. Role `admin`, `kasir`, dan `pelanggan` berasal dari tabel `users`. Pendaftaran lewat situs selalu membuat role `pelanggan`.

Pelanggan yang belum verifikasi tetap bisa masuk dan membuka beranda. Saat membuka layanan, Laravel mengarahkannya ke halaman verifikasi. Permintaan membuat pesanan juga diperiksa di server. Setelah membuka tautan dari Firebase, kembali ke WireShoes dan klik **Cek status verifikasi**. Status juga diperbarui saat login berikutnya.

## Salin file

1. Ekstrak ZIP. Untuk setiap file di folder `copas/`, buka file tersebut dan salin seluruh isinya. Tempel ke path yang sama di proyek WireShoes. Contoh, salin `copas/routes/auth.php` ke `routes/auth.php`. Buat file atau folder tujuan jika belum ada.
2. `copas/.env.example` adalah contoh konfigurasi. Tambahkan dua variabel Firebase di bawah ke `.env` yang sudah dipakai proyek. Pertahankan nilai `APP_KEY` dan pengaturan database yang sudah berfungsi.
3. File di `copas/tests/` memperbarui pengujian bawaan. Salin file tersebut juga jika kamu menjalankan `php artisan test`.

## Siapkan Firebase

Aktifkan provider **Email/Password** di Firebase Console pada menu **Authentication → Sign-in method**. Ambil `apiKey` dari konfigurasi aplikasi Web di **Project settings → General**. Service account JSON harus berasal dari proyek Firebase yang sama.

Arsip sumber berisi private key service account. Buat kunci pengganti, pasang kunci baru pada server, lalu cabut kunci lama di Google Cloud IAM. Simpan kunci baru di luar repositori. Jika file lama sudah di-track Git, setelah rotasi gunakan `git rm --cached -- <path-file-json>` untuk mengeluarkannya dari indeks Git. Untuk cara menangani kunci yang sudah dibagikan, lihat [panduan Google Cloud](https://docs.cloud.google.com/iam/docs/best-practices-for-managing-service-account-keys).

Isi `.env` di root proyek. Contoh path Windows menggunakan garis miring ke depan.

```dotenv
FIREBASE_API_KEY=
FIREBASE_CREDENTIALS="C:/path/ke/kunci-baru.json"
```

Verifikasi dan reset password membuka halaman tindakan email bawaan Firebase. Bila proyek Firebase memakai URL tindakan email khusus, pastikan URL tersebut menangani kode tindakan Firebase. Pengiriman ulang verifikasi memakai SDK di server.

## Jalankan proyek

Jalankan dari root proyek. Jika satu instalasi dependensi sudah lengkap, kamu bisa melewati perintah instalasinya.

```sh
composer install
npm install
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=WireShoesOrderStatusSeeder
npm run build
php artisan serve
```

Jika proyek baru dan `APP_KEY` kosong, jalankan `php artisan key:generate` sebelum menjalankan server. Jika memakai SQLite baru, buat file SQLite kosong sebelum `migrate`, aktifkan ekstensi PHP `pdo_sqlite`, dan isi `DB_DATABASE` dengan path absolut. Untuk SQLite yang sudah berisi data, pakai path file database lama. Paket ini menyesuaikan migrasi awal tabel `orders` agar validasi kode pelacakan dapat dijalankan di SQLite.

Perintah seeder membuat status awal **Dipesan** jika tabel status masih kosong. Form pesanan menampilkan layanan aktif dari tabel `services` dan memakai harga yang tercatat di database. Jika tabelnya masih kosong, masukkan layanan beserta harga toko. Contoh melalui `php artisan tinker`, sesuaikan nama dan harga sebelum menjalankan.

```php
App\Models\Service::firstOrCreate(
    ['nama' => 'Cuci Sepatu'],
    ['harga' => 25000, 'estimasi_hari' => 3, 'aktif' => true],
);
```

## Hubungkan akun staf dan akun Laravel lama

Buat atau impor akun di **Firebase Authentication → Users** terlebih dahulu. Cocokkan email dan pastikan akun Firebase tersebut benar-benar milik orang yang akan diberi akses.

Untuk admin atau kasir yang sudah ada di database Laravel, jalankan perintah berikut dengan email akun tersebut. Perintah mempertahankan ID dan role lokal. Cara ini juga berlaku untuk pelanggan lama.

```sh
php artisan firebase:link-user admin@example.com
```

Untuk menambahkan staf baru yang sudah ada di Firebase, gunakan `--role` dari terminal Laravel.

```sh
php artisan firebase:link-user admin-baru@example.com --role=admin --name="Admin Toko"
php artisan firebase:link-user kasir-baru@example.com --role=kasir --name="Kasir Toko"
```

Akun lama memakai password Firebase setelah ditautkan. Jika akunnya dibuat baru di Firebase, pengguna dapat mengatur password sendiri lewat **Lupa password?**. Sesi dari login lokal lama meminta pengguna masuk kembali. Dashboard staf memakai pemeriksaan email terverifikasi yang sudah dipakai proyek.

## Cek setelah konfigurasi

1. Daftar sebagai pelanggan, masuk, lalu buka **Layanan** sebelum verifikasi. WireShoes mengarahkan ke verifikasi email.
2. Klik tautan Firebase dari email, kembali ke WireShoes, lalu klik **Cek status verifikasi**. Kirim pesanan dan periksa bahwa nomor pesanan muncul.
3. Masuk dengan akun kasir atau admin yang sudah ditautkan. Periksa bahwa dashboard terbuka dan role di database sesuai. Uji juga **Lupa password?** dengan akun percobaan.

Build Vite dan pengujian Laravel memakai SQLite sudah lulus. Pengujian lokal memakai respons Firebase simulasi. Pengiriman email dan login ke proyek Firebase asli perlu diperiksa setelah kamu memasang konfigurasi baru.