# GlowSkin Laravel 13 Conversion

Project ini adalah hasil konversi dari front-end Next.js/React (`cosmeticlaravel.zip`) menjadi struktur Laravel 13 + Blade, dengan design visual tetap mengikuti konsep awal: dark luxury cosmetic brand, hero slider, product tabs, sale carousel, beauty articles, skin analyzer section, skin type section, connect section, product detail, articles page, dan admin dashboard preview.

## Cara Jalankan

1. Ekstrak ZIP ini.
2. Masuk ke folder project:
   ```bash
   cd glowskin_laravel13
   ```
3. Install dependency Laravel:
   ```bash
   composer install
   ```
4. Buat `.env` dari contoh jika belum ada:
   ```bash
   copy .env.example .env
   ```
5. Generate key:
   ```bash
   php artisan key:generate
   ```
6. Buat database MySQL bernama `glowskin_db` di phpMyAdmin.
7. Pilih salah satu:
   - Import langsung `glowskin_db.sql` lewat phpMyAdmin, atau
   - Jalankan migration + seeder:
     ```bash
     php artisan migrate --seed
     ```
8. Jalankan:
   ```bash
   php artisan serve
   ```
9. Buka:
   - Landing page: `http://127.0.0.1:8000`
   - Articles: `http://127.0.0.1:8000/articles`
   - Product detail: `http://127.0.0.1:8000/product-detail`
   - Admin preview: `http://127.0.0.1:8000/admin-dashboard`

## Catatan Penting

- Design tidak diganti konsepnya; struktur Next.js diubah menjadi Blade + CSS + JS ringan.
- Gambar produk/hero sebagian masih memakai URL eksternal dari project awal. Jadi saat offline, beberapa gambar eksternal bisa tidak tampil.
- Folder `vendor` tidak disertakan. Jalankan `composer install` setelah ekstrak.
