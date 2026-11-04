# 🍃 Kinar Resto — Sistem Manajemen Restoran

Aplikasi manajemen restoran berbasis **PHP & MySQL** dengan tampilan **3D clay UI + glassmorphism**.
Terintegrasi: website publik + CMS, pemesanan via **QR Code meja**, manajemen meja, **kasir (POS)**, **dapur & bar**, **pembayaran**, **printer thermal LAN**, dan **dashboard admin**.

## 🎨 Palet Warna
| Peran | Warna |
|---|---|
| Forest Botanical Green (utama) | `#2D5A27` |
| Burgundy (aksen) | `#6A1B4D` |
| Ivory Warm (latar) | `#FBF9F5` |

## ✨ Fitur & Modul
| Modul | Fitur utama |
|---|---|
| **Pemesanan QR Code** | Menu digital, input nama tamu, keranjang + catatan per item, dine-in & takeaway |
| **Manajemen Meja** | QR per meja (`qr_token`), sesi meja (`table_session_id`), penggabungan tagihan meja, status meja |
| **Kasir & POS** | Konfirmasi pesanan, tiket otomatis ke dapur, pembayaran (tunai/QRIS/transfer/kartu), struk, tutup sesi |
| **Dapur & Bar** | Antrean pesanan, status item (antrian → diproses → siap), auto-refresh |
| **Printer Thermal LAN** | Setting IP/port/lokasi, antrian cetak, cetak ulang. Mode: *semua printer* atau *makanan→dapur & minuman→bar* |
| **Dashboard Admin** | KPI (menu aktif, artikel, reservasi, pesanan hari ini, status buka), grafik penjualan 7 hari, aktivitas |
| **Manajemen Menu (CMS)** | Kategori, harga, foto (upload, maks 2MB, rasio 4:3), deskripsi, multi-tag, status *tersedia/habis* (operasional) vs *tayang/sembunyi* (web) |
| **Warta Kuliner (CMS)** | Artikel dengan kategori (Filosofi Rempah, Cerita Bahan Lokal, Tips Kuliner), cover, author, draf/terbit |
| **Branding & Web** | Logo, tagline, hero banner, SEO meta, jam operasional, WhatsApp, alamat & Google Maps |
| **Reservasi & Kontak** | Form reservasi dari web, konfirmasi/penolakan, riwayat |
| **Website Publik** | 4 halaman: Beranda, Menu Makanan, Menu Minuman, Warta Kuliner |
| **Keamanan & Audit** | Hak akses (admin/kasir/pelayan/dapur), activity log, antrian sinkronisasi ke web publik |

## 📁 Struktur Folder
```
config/       koneksi database & konstanta
includes/     auth, helper, generator QR (murni PHP), driver printer ESC/POS, layout
database/     kinar_resto.sql (skema + data awal)
admin/        login, dashboard, pos, kitchen, tables, menu, printers
order/        halaman pemesanan QR pelanggan (/order/?token=...)
public/       website publik: index, menu-makanan, menu-minuman, warta
assets/       css, js, gambar
uploads/      foto menu / artikel / branding (di-upload via CMS)
```

## 🚀 Panduan Deploy (Server Lokal)
1. **Import database**
   ```bash
   mysql -u root -p < database/kinar_resto.sql
   ```
2. **Konfigurasi** — edit `config/database.php` (DB_USER/DB_PASS) dan `BASE_URL` di `config/config.php`.
3. **Letakkan folder** di document root server web (mis. `htdocs/kinar`), aktifkan ekstensi PHP `pdo_mysql` dan `mbstring`.
4. **Login admin:** `http://localhost/kinar/admin/login.php` — default `admin` / `password` (ganti segera).
5. **Printer:** pastikan printer thermal (ESC/POS) terhubung LAN; atur IP & port di menu **Printer LAN**.
6. **QR Meja:** menu **Meja & QR** menampilkan QR siap cetak; scan membuka `/order/?token=<qr_token>`.

## 🔄 Workflow Pemesanan
**Dine-in:** scan QR meja → sesi meja aktif → isi nama & pilih menu → kasir konfirmasi (tiket dapur otomatis tercetak) → dapur proses sampai siap → pelayan mengantar → kasir terima pembayaran & cetak struk → sesi meja ditutup.

**Takeaway:** scan QR area tunggu → pesan seperti biasa → ambil struk di kasir → bayar → ambil pesanan saat siap.

**Penggabungan meja:** semua pesanan satu meja terhubung via `table_session_id`; kasir dapat menagih gabungan (mis. Andi 2 Nasi Goreng + Budi 1 Mie Goreng + Citra 2 Es Teh = satu tagihan).

## 🔁 Sinkronisasi Website
Perubahan menu/artikel/pengaturan dicatat di tabel `sync_queue`; saat internet tersedia, admin dapat mengirim perubahan ke API website publik di hosting cPanel.
