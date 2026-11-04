-- ============================================================
-- Kinar Resto - Skema Database MySQL
-- Sistem Manajemen Restoran: QR Order, POS, Dapur, Printer, CMS
-- ============================================================

CREATE DATABASE IF NOT EXISTS kinar_resto
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kinar_resto;

-- ---------- Pengguna & Hak Akses ----------
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','kasir','pelayan','dapur') NOT NULL DEFAULT 'kasir',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Kategori Menu ----------
CREATE TABLE IF NOT EXISTS categories (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(50) NOT NULL,
  slug       VARCHAR(50) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------- Menu (Makanan & Minuman) ----------
CREATE TABLE IF NOT EXISTS menu_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  category_id   INT NOT NULL,
  name          VARCHAR(150) NOT NULL,
  slug          VARCHAR(160) NOT NULL UNIQUE,
  description   TEXT,
  price         INT NOT NULL DEFAULT 0,
  portion       VARCHAR(80),
  image         VARCHAR(255),
  tags          VARCHAR(255) COMMENT 'dipisah koma: Signature Chef, Pedas Gurih, ...',
  is_available  TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'status operasional dapur',
  is_public     TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'status publikasi website',
  is_featured   TINYINT(1) NOT NULL DEFAULT 0,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- ---------- Meja & Sesi Meja ----------
CREATE TABLE IF NOT EXISTS resto_tables (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  table_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'mis. M01',
  label      VARCHAR(50) NOT NULL,
  capacity   INT NOT NULL DEFAULT 4,
  qr_token   VARCHAR(64) NOT NULL UNIQUE COMMENT 'token unik untuk QR code meja',
  status     ENUM('kosong','terisi','dipesan') NOT NULL DEFAULT 'kosong'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS table_sessions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  table_id    INT NOT NULL,
  session_code VARCHAR(32) NOT NULL UNIQUE,
  opened_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at   TIMESTAMP NULL,
  status      ENUM('aktif','ditutup') NOT NULL DEFAULT 'aktif',
  FOREIGN KEY (table_id) REFERENCES resto_tables(id)
) ENGINE=InnoDB;

-- ---------- Pesanan & Item Pesanan ----------
CREATE TABLE IF NOT EXISTS orders (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  order_code      VARCHAR(32) NOT NULL UNIQUE,
  table_id        INT NULL,
  table_session_id INT NULL,
  customer_name   VARCHAR(100) NOT NULL,
  order_type      ENUM('dine_in','takeaway') NOT NULL DEFAULT 'dine_in',
  status          ENUM('menunggu','dikonfirmasi','diproses','siap','disajikan','selesai','dibatalkan')
                  NOT NULL DEFAULT 'menunggu',
  note            TEXT,
  total           INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  confirmed_at    TIMESTAMP NULL,
  completed_at    TIMESTAMP NULL,
  FOREIGN KEY (table_id) REFERENCES resto_tables(id),
  FOREIGN KEY (table_session_id) REFERENCES table_sessions(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  order_id    INT NOT NULL,
  menu_id     INT NOT NULL,
  qty         INT NOT NULL DEFAULT 1,
  price       INT NOT NULL COMMENT 'harga saat transaksi',
  note        VARCHAR(255),
  item_status ENUM('antrian','diproses','siap') NOT NULL DEFAULT 'antrian',
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menu_items(id)
) ENGINE=InnoDB;

-- ---------- Pembayaran ----------
CREATE TABLE IF NOT EXISTS payments (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  payment_code  VARCHAR(32) NOT NULL UNIQUE,
  order_id      INT NOT NULL,
  cashier_id    INT NOT NULL,
  method        ENUM('tunai','qris','transfer','kartu') NOT NULL DEFAULT 'tunai',
  amount_paid   INT NOT NULL,
  change_amount INT NOT NULL DEFAULT 0,
  paid_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (cashier_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- Reservasi & Kontak ----------
CREATE TABLE IF NOT EXISTS reservations (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  contact      VARCHAR(100) NOT NULL,
  date         DATE NOT NULL,
  time         TIME NOT NULL,
  guests       INT NOT NULL DEFAULT 2,
  request      TEXT,
  status       ENUM('baru','dikonfirmasi','ditolak','hadir','tidak_hadir') NOT NULL DEFAULT 'baru',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  contact    VARCHAR(100) NOT NULL,
  message    TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Artikel / Warta Kuliner (CMS) ----------
CREATE TABLE IF NOT EXISTS articles (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(200) NOT NULL,
  slug        VARCHAR(220) NOT NULL UNIQUE,
  category    ENUM('Filosofi Rempah','Cerita Bahan Lokal','Tips Kuliner') NOT NULL,
  author      VARCHAR(100),
  cover_image VARCHAR(255),
  content     TEXT NOT NULL,
  status      ENUM('draft','terbit') NOT NULL DEFAULT 'draft',
  published_at TIMESTAMP NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Pengaturan Restoran / Branding / CMS ----------
CREATE TABLE IF NOT EXISTS settings (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(80) NOT NULL UNIQUE,
  value TEXT
) ENGINE=InnoDB;

-- ---------- Printer Thermal LAN ----------
CREATE TABLE IF NOT EXISTS printers (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(80) NOT NULL,
  location   ENUM('kasir','dapur','bar') NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  port       INT NOT NULL DEFAULT 9100,
  type       VARCHAR(50) DEFAULT 'ESC/POS',
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  mode       ENUM('semua','makanan_ke_dapur_minuman_ke_bar') NOT NULL DEFAULT 'semua'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS print_jobs (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  order_id   INT NOT NULL,
  printer_id INT NOT NULL,
  payload    TEXT NOT NULL,
  status     ENUM('antrian','tercetak','gagal') NOT NULL DEFAULT 'antrian',
  attempts   INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  printed_at TIMESTAMP NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (printer_id) REFERENCES printers(id)
) ENGINE=InnoDB;

-- ---------- Audit Log & Antrian Sinkronisasi ----------
CREATE TABLE IF NOT EXISTS activity_log (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NULL,
  action     VARCHAR(100) NOT NULL,
  detail     TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sync_queue (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  entity     VARCHAR(50) NOT NULL COMMENT 'menu, artikel, pengaturan',
  entity_id  INT NOT NULL,
  action     ENUM('upsert','delete') NOT NULL,
  status     ENUM('pending','synced','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Data Awal ----------
INSERT INTO users (name, username, password_hash, role) VALUES
('Admin Kinar', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- password default: password

INSERT INTO categories (name, slug, sort_order) VALUES
('Makanan Utama', 'makanan-utama', 1),
('Makanan Ringan', 'makanan-ringan', 2),
('Minuman Dingin', 'minuman-dingin', 3),
('Minuman Hangat', 'minuman-hangat', 4);

INSERT INTO resto_tables (table_code, label, capacity, qr_token) VALUES
('M01','Meja 1',4,'tbl-a1b2c3d4e5'),
('M02','Meja 2',2,'tbl-b2c3d4e5f6'),
('M03','Meja 3',6,'tbl-c3d4e5f6g7'),
('M04','Meja 4',4,'tbl-d4e5f6g7h8'),
('TAKEAWAY','Area Tunggu',0,'tbl-takeaway01');

INSERT INTO printers (name, location, ip_address, port, type, mode) VALUES
('Printer Kasir', 'kasir', '192.168.1.100', 9100, 'ESC/POS', 'semua'),
('Printer Dapur', 'dapur', '192.168.1.101', 9100, 'ESC/POS', 'semua');

-- ---------- Data Contoh Menu ----------
INSERT INTO menu_items (category_id, name, slug, description, price, portion, tags, is_available, is_public, is_featured, sort_order) VALUES
(1,'Rendang Wagyu Minang','rendang-wagyu-minang','Daging wagyu empuk yang dimasak 8 jam bersama santan kelapa tua muda dan 18 rempah khas Minang.','285000','Porsi 1-2 orang','Signature Chef,Best Seller',1,1,1,1),
(1,'Bebek Betutu Ginyar','bebek-betutu-ginyar','Bebek muda Balinese yang dibumbui base gede, dimasak dalam daun pisang hingga harum.','240000','Porsi 2 orang','Pedas Gurih,Best Seller',1,1,1,2),
(1,'Gurame Saus Mangga','gurame-saus-mangga','Ikan gurame goreng renyah disiram saus mangga segar manis asam.','195000','Porsi 2-3 orang','Rekomendasi Tamu',1,1,0,3),
(1,'Ayam Tangkap Rempah','ayam-tangkap-rempah','Ayam kampung fillet goreng berbumbu rempah, disajikan dengan lalapan dan sambal.','88000','Porsi 1 orang','Pedas Gurih',1,1,0,4),
(1,'Nasi Ulam Komplit','nasi-ulam-komplit','Nasi hangat dengan aneka lauk, lalapan, sambal, dan kerupuk.','65000','Porsi 1 orang','Halal MUI',1,1,0,5),
(2,'Sate Maranggi Bagung','sate-maranggi-bagung','Sate daging sapi tanpa tusuk dengan bumbu kacang dan kecap.','85000','10 Tusuk','Signature Chef',1,1,1,6),
(2,'Platter Sambal 5 Rasa','platter-sambal-5-rasa','Lima variasi sambal: terasi, ijo, matah, bawang, kemangi.','35000','Porsi rame-rame','Pedas Gurih',1,1,0,7),
(2,'Tumis Pepaya Kecombrang','tumis-pepaya-kecombrang','Pepaya muda ditumis dengan kecombrang wangi dan cabai merah keriting.','48000','Porsi 2 orang','Bebas Santan',1,1,0,8),
(3,'Es Teh Manis Kinar','es-teh-manis','Teh melati diseduh pekat dengan gula batu.','15000','Gelas 250 ml','Best Seller',1,1,0,9),
(3,'Es Cendol Durian','es-cendol-durian','Cendol hijau, santan, gula aren, dan daging durian Montong.','32000','Mangkuk besar','Bebas Santan',1,1,0,10),
(3,'Kopi Tubruk Nusantara','kopi-tubruk','Kopi robusta lokal diseduh langsung di gelas bersama gula.','18000','Gelas 200 ml','Halal MUI',1,1,0,11),
(4,'Wedang Jahe Merah','wedang-jahe-merah','Jahe merah dibakar, diseduh dengan serai dan gula aren.','20000','Gelas 250 ml','Halal MUI',1,1,0,12);

-- ---------- Data Contoh Artikel ----------
INSERT INTO articles (title, slug, category, author, content, status, published_at) VALUES
('Filosofi Rempah: Mengapa Rendang Butuh 8 Jam','filosofi-rempah-rendang','Filosofi Rempah','Chef Kinar','Rendang bukan sekadar masakan; ia adalah kesabaran. Daging dimasak perlahan hingga santan mengering dan bumbu meresap sempurna...','terbit',NOW()),
('Cerita Bahan Lokal: Mengenal Kecombrang dari kebun sendiri','cerita-bahan-lokal-kecombrang','Cerita Bahan Lokal','Tim Dapur Kinar','Kecombrang adalah bunga yang menjadi jiwa dari banyak masakan nusantara. Kami menanamnya sendiri di kebun belakang resto...','terbit',NOW()),
('Tips Kuliner: Cara Menyeduh Kopi Tubruk yang Nikmat','tips-kopi-tubruk','Tips Kuliner','Barista Kinar','Air harus benar-benar mendidih, kopi digiling kasar, dan diamkan 3 menit sebelum diminum...','terbit',NOW());

-- ---------- Pengaturan Branding/CMS Awal ----------
INSERT INTO settings (`key`, value) VALUES
('site_name','Kinar Resto'),
('tagline','Cita Rasa Lestari Nusantara'),
('hero_title','Harmoni Cita Rasa Autentik Nusantara'),
('hero_sub','Perpaduan rempah pilihan, teknik memasak tradisional, dan suasana hangat untuk setiap momen spesial Anda.'),
('meta_description','Kinar Resto — restoran nusantara dengan cita rasa autentik di Jakarta Selatan.'),
('meta_keywords','restoran nusantara, rendang, kuliner jakarta, kinar resto'),
('address','Jl. Senopati Raya No. 48, Kebayoran Baru, Jakarta Selatan 12190'),
('phone','+62 21 555 0148'),
('wa','6281234567890'),
('hours','Senin–Kamis 11.00–21.30 WIB · Jumat–Minggu 10.00–22.30 WIB'),
('resto_open','1'),
('maps_embed','https://www.google.com/maps?q=Senopati,Jakarta&output=embed');
