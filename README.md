# E-Shop — Fashion & Accessories Store

Aplikasi toko online berbasis **Laravel 10** untuk menampilkan dan menjual produk fashion serta aksesori. Proyek ini dibuat sebagai aplikasi pemrograman web dengan halaman pelanggan dan panel administrasi.

![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?logo=mysql&logoColor=white)
![Frontend](https://img.shields.io/badge/Frontend-Bootstrap%205-7952B3?logo=bootstrap&logoColor=white)

## Preview aplikasi

<p align="center">
  <img src="https://raw.githubusercontent.com/Arayandra/fashion-ecommerce-store/main/docs/images/storefront-preview.png" alt="Preview antarmuka E-Shop Fashion Store" width="100%">
</p>

> Gambar merupakan ilustrasi antarmuka toko untuk README, bukan screenshot hasil menjalankan aplikasi.

## Fitur

### Halaman pelanggan
- Beranda dengan banner promosi dan produk pilihan.
- Katalog produk dalam tampilan grid maupun list.
- Pencarian, filter kategori, subkategori, merek, harga, dan pengurutan produk.
- Halaman detail produk.
- Keranjang belanja dan wishlist untuk pengguna.
- Registrasi, login, dan halaman akun pelanggan.
- Checkout dengan pilihan pengiriman.
- Riwayat dan pelacakan pesanan.
- Ulasan produk.
- Blog, kategori/tag artikel, dan komentar.
- Formulir kontak serta langganan newsletter.
- Alur pembayaran Cash on Delivery (COD) dan integrasi PayPal yang memerlukan konfigurasi kredensial.

### Panel administrator
- Dashboard dan ringkasan pesanan.
- Pengelolaan produk, kategori, merek, dan banner.
- Pengelolaan pengguna.
- Pengelolaan pesanan dan pengiriman.
- Pengelolaan kupon.
- Pengelolaan artikel, kategori blog, dan tag.
- Pengelolaan pesan masuk dan pengaturan toko.
- Notifikasi serta file manager.
- Laporan pendapatan.

## Teknologi

| Komponen | Teknologi |
|---|---|
| Backend | PHP 8.1+ dan Laravel 10 |
| Database | MySQL / MariaDB |
| Frontend | Blade, HTML, CSS, JavaScript |
| UI framework | Bootstrap 5 |
| Asset bundler | Laravel Mix dan Webpack |
| Library tambahan | jQuery, Vue, Laravel Socialite |
| Fitur tambahan | DomPDF, Pusher, PayPal package |

## Persyaratan

Pastikan perangkat sudah memiliki:
- PHP **8.1 atau lebih baru** beserta ekstensi yang dibutuhkan Laravel dan package proyek.
- Composer.
- MySQL atau MariaDB (misalnya melalui XAMPP atau Laragon).
- Node.js dan npm.
- Git.

## Instalasi di Windows

### 1. Clone repository

Setelah nama repository diubah menjadi **fashion-ecommerce-store**, jalankan:

~~~cmd
git clone https://github.com/Arayandra/fashion-ecommerce-store.git
cd fashion-ecommerce-store
~~~

### 2. Instal dependency

~~~cmd
composer install
npm install
~~~

### 3. Siapkan file environment

~~~cmd
copy .env.example .env
php artisan key:generate
~~~

### 4. Buat database

1. Jalankan Apache dan MySQL melalui XAMPP atau Laragon.
2. Buka phpMyAdmin.
3. Buat database kosong, misalnya **fashion_store**.
4. Pilih database tersebut, lalu buka tab **Import**.
5. Pilih file **e-com.sql** yang tersedia di root proyek dan jalankan proses import.

File **e-com.sql** berisi struktur tabel dan data contoh aplikasi. Dump ini juga menyertakan tabel **migrations**, sehingga untuk instalasi menggunakan dump tersebut **jangan menjalankan php artisan migrate:fresh** karena perintah itu akan menghapus tabel dan data yang sudah diimpor.

### 5. Sesuaikan konfigurasi database

Buka file **.env**, lalu sesuaikan nilainya dengan konfigurasi MySQL lokal:

~~~env
APP_NAME="E-Shop Fashion Store"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fashion_store
DB_USERNAME=root
DB_PASSWORD=
~~~

Gunakan nama database yang sama dengan database tempat file SQL diimpor. Jika MySQL Anda memakai password, isi **DB_PASSWORD** sesuai konfigurasi lokal.

### 6. Siapkan storage dan asset

~~~cmd
php artisan storage:link
npm run production
~~~

### 7. Jalankan aplikasi

~~~cmd
php artisan serve
~~~

Buka alamat berikut di browser:

~~~text
http://127.0.0.1:8000
~~~

## Catatan konfigurasi

- Fitur PayPal membutuhkan kredensial dan konfigurasi provider yang sesuai. Uji alur pembayaran menggunakan sandbox sebelum mengaktifkannya untuk transaksi sungguhan.
- Pengiriman email, newsletter, dan broadcast/Pusher membutuhkan konfigurasi layanan masing-masing jika fitur tersebut digunakan.
- File SQL berisi data contoh dari pengembangan. Tinjau dan ganti akun, data pengguna, kontak, produk, serta informasi toko sebelum dipakai di lingkungan lain.
- Buat file **.env** lokal sendiri. Jangan mengunggah **.env**, API key, password, token, atau kredensial layanan ke GitHub.
- Untuk deployment, gunakan **APP_ENV=production**, **APP_DEBUG=false**, HTTPS, kredensial database khusus, dan konfigurasi server yang aman.

## Struktur direktori

~~~text
app/
  Http/Controllers/    # Controller frontend, admin, cart, order, dan lainnya
  Models/              # Model Eloquent
  Notifications/       # Notifikasi aplikasi
config/                # Konfigurasi Laravel dan package
database/
  migrations/          # Database migrations
public/                # Asset publik, CSS, JavaScript, dan gambar
resources/
  views/
    frontend/          # Halaman toko untuk pelanggan
    backend/           # Halaman panel admin
routes/
  web.php              # Definisi route web
tests/                 # Pengujian aplikasi
e-com.sql              # Dump database contoh
~~~

## Pengujian dan status proyek

Jalankan pengujian otomatis yang tersedia dengan:

~~~cmd
php artisan test
~~~

Hasil perintah tersebut menjadi acuan kondisi pengujian pada mesin lokal. Integrasi eksternal seperti PayPal, email, newsletter, dan Pusher tetap perlu diuji dengan konfigurasi layanan yang valid.

Proyek ini merupakan aplikasi pembelajaran/UAS dan belum dinyatakan siap produksi tanpa audit keamanan, pengujian alur transaksi, serta penyesuaian konfigurasi deployment.

## Lisensi

Lihat file [LICENSE](LICENSE) untuk informasi lisensi proyek.
