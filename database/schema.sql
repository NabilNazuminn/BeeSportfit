-- =====================================================================
-- Fitness Equipment Store - Database Schema (MySQL / MariaDB)
-- Works with XAMPP. Import via phpMyAdmin or: mysql -u root -p < schema.sql
-- =====================================================================

-- HOW TO IMPORT
--   XAMPP / Laragon : in phpMyAdmin create a database named  fitness_store  (collation utf8mb4_unicode_ci),
--                     select it, then Import this file.
--   cPanel hosting  : create the database + user in "MySQL Databases", open phpMyAdmin, SELECT that database
--                     (its name usually has a prefix, e.g. youruser_fitness), then Import this file.
-- (This file no longer contains CREATE DATABASE / USE, which shared hosting does not allow.)

-- ---------------------------------------------------------------------
-- USERS (customers + admins share one table, separated by "role")
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL UNIQUE,
  phone         VARCHAR(30)   NULL,
  password_hash VARCHAR(255)  NOT NULL,           -- result of password_hash(), never plain text
  role          ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  is_active     TINYINT(1)    NOT NULL DEFAULT 1,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATALOG: categories, brands, products, product images
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,                  -- e.g. "Dumbbell, Barbell & Set"
  image       VARCHAR(255) NULL,
  sort_order  INT          NOT NULL DEFAULT 0,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE brands (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  slug       VARCHAR(120) NOT NULL UNIQUE,
  logo       VARCHAR(255) NULL,                       -- path inside public/uploads/, e.g. brands/insight.png
  logo_white TINYINT(1)   NOT NULL DEFAULT 0          -- 1 = logo is black: show it WHITE on the dark brand strip
) ENGINE=InnoDB;

CREATE TABLE products (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id       INT UNSIGNED NOT NULL,
  brand_id          INT UNSIGNED NULL,
  name              VARCHAR(200) NOT NULL,
  slug              VARCHAR(220) NOT NULL UNIQUE,  -- used in product.php?slug=...
  sku               VARCHAR(60)  NULL UNIQUE,      -- model code, e.g. BF06
  short_description VARCHAR(300) NULL,
  description       TEXT         NULL,
  specifications    TEXT         NULL,             -- plain text / simple HTML list
  price             DECIMAL(15,2) NOT NULL,        -- normal price
  discount_price    DECIMAL(15,2) NULL,            -- NULL = no discount
  discount_ends_at  DATETIME     NULL,             -- NULL = no end date
  stock             INT          NOT NULL DEFAULT 0,
  stock_status      ENUM('in_stock','pre_order','out_of_stock') NOT NULL DEFAULT 'in_stock',
  warranty_months   INT          NULL,
  is_featured       TINYINT(1)   NOT NULL DEFAULT 0, -- shows in "Alat Gym Pilihan" on the home page
  is_active         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_products_brand    FOREIGN KEY (brand_id)    REFERENCES brands(id) ON DELETE SET NULL,
  INDEX idx_products_category (category_id),
  INDEX idx_products_active_featured (is_active, is_featured)
) ENGINE=InnoDB;

CREATE TABLE product_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id  INT UNSIGNED NOT NULL,
  image_path  VARCHAR(255) NOT NULL,
  is_primary  TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order  INT          NOT NULL DEFAULT 0,
  CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CART (for logged-in users; guests can use $_SESSION and merge on login)
-- ---------------------------------------------------------------------
CREATE TABLE carts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL UNIQUE,        -- one active cart per user
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity   INT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_cartitems_cart    FOREIGN KEY (cart_id)    REFERENCES carts(id)    ON DELETE CASCADE,
  CONSTRAINT fk_cartitems_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uq_cart_product (cart_id, product_id)  -- same product = update quantity, not a new row
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ORDERS (a "quote" is an order with order_type = 'quote')
-- ---------------------------------------------------------------------
CREATE TABLE orders (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_code       VARCHAR(30)  NOT NULL UNIQUE,   -- human-friendly, e.g. INV-20261015-0001
  user_id          INT UNSIGNED NULL,              -- NULL allowed if user is deleted later
  order_type       ENUM('order','quote') NOT NULL DEFAULT 'order',
  status           ENUM('new','confirmed','paid','shipped','installed','completed','cancelled')
                   NOT NULL DEFAULT 'new',
  -- Snapshot of customer/shipping info at the time of order
  customer_name    VARCHAR(100) NOT NULL,
  customer_phone   VARCHAR(30)  NOT NULL,
  customer_email   VARCHAR(150) NULL,
  shipping_address TEXT         NOT NULL,
  province         VARCHAR(80)  NULL,
  city             VARCHAR(80)  NULL,
  notes            TEXT         NULL,
  -- Money
  subtotal         DECIMAL(15,2) NOT NULL DEFAULT 0,
  shipping_cost    DECIMAL(15,2) NOT NULL DEFAULT 0,
  grand_total      DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_orders_status (status),
  INDEX idx_orders_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,                  -- keep the order even if product is deleted
  product_name VARCHAR(200) NOT NULL,              -- snapshot: name at time of purchase
  unit_price   DECIMAL(15,2) NOT NULL,             -- snapshot: price at time of purchase
  quantity     INT UNSIGNED NOT NULL,
  line_total   DECIMAL(15,2) NOT NULL,             -- unit_price * quantity
  stock_reduced TINYINT(1)   NOT NULL DEFAULT 0,   -- 1 = this line took units from product stock, 2 = given back (order cancelled)
  CONSTRAINT fk_orderitems_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  CONSTRAINT fk_orderitems_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_status_history (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  status     VARCHAR(30)  NOT NULL,
  note       VARCHAR(255) NULL,
  changed_by INT UNSIGNED NULL,                    -- admin user id
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_order FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_history_admin FOREIGN KEY (changed_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- LEADS: free layout-consultation requests
-- ---------------------------------------------------------------------
CREATE TABLE consultations (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  phone      VARCHAR(30)  NOT NULL,
  email      VARCHAR(150) NULL,
  gym_type   ENUM('commercial','home','other') NOT NULL DEFAULT 'commercial',
  room_size  VARCHAR(50)  NULL,                    -- e.g. "10x15 m"
  budget     VARCHAR(50)  NULL,
  message    TEXT         NULL,
  status     ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- LIVE CHAT: visitors <-> admin, inside the website (no WhatsApp)
-- A visitor is recognised by a random token kept in a cookie, so guests
-- can chat without an account and come back to the same conversation.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chat_conversations (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token           CHAR(32)     NOT NULL UNIQUE,      -- secret in the visitor's cookie
  user_id         INT UNSIGNED NULL,                 -- set when a logged-in customer starts the chat
  visitor_name    VARCHAR(100) NOT NULL,
  visitor_contact VARCHAR(150) NULL,                 -- optional phone / email
  status          ENUM('open','closed') NOT NULL DEFAULT 'open',
  last_message_at DATETIME     NOT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chat_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_chat_last (last_message_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  sender          ENUM('visitor','admin') NOT NULL,
  admin_id        INT UNSIGNED NULL,                 -- which admin replied
  body            TEXT         NOT NULL,
  read_at         DATETIME     NULL,                 -- NULL = the other side has not seen it yet
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_chatmsg_conv  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_chatmsg_admin FOREIGN KEY (admin_id)        REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_chatmsg_conv (conversation_id, id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTENT: testimonials, static pages, site settings
-- ---------------------------------------------------------------------
CREATE TABLE testimonials (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(100) NOT NULL,
  role_title VARCHAR(150) NULL,                    -- e.g. "Owner, Iron House Gym Surabaya"
  content    TEXT         NOT NULL,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  sort_order INT          NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE pages (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug       VARCHAR(100) NOT NULL UNIQUE,         -- warranty, terms
  title      VARCHAR(150) NOT NULL,
  content    MEDIUMTEXT   NOT NULL,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rate_limits (                          -- anti-spam / login throttling (by IP)
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket     VARCHAR(190) NOT NULL,
  created_at DATETIME     NOT NULL,
  INDEX idx_rate_bucket (bucket, created_at)
) ENGINE=InnoDB;

CREATE TABLE password_resets (                      -- one-hour "forgot password" links (only a hash of the token is stored)
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64)     NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     NOT NULL,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_reset_token (token_hash)
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key   VARCHAR(80) PRIMARY KEY,
  setting_value TEXT        NULL
) ENGINE=InnoDB;

-- Optional (Phase 4): shipping cost per province
CREATE TABLE shipping_rates (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  province  VARCHAR(80)   NOT NULL UNIQUE,
  cost      DECIMAL(15,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- =====================================================================
-- SEED DATA (starter data so the site isn't empty)
-- =====================================================================

INSERT INTO categories (name, slug, description, sort_order) VALUES
  ('Beban',           'beban',           'Dumbbell, Barbell & Set',          1),
  ('Cardio',          'cardio',          'Treadmill, Elliptical & Bike',     2),
  ('Strength',        'strength',        'Rack, Cable & Smith',              3),
  ('Recovery',        'recovery',        'Yoga, Foam Roller & Aksesoris',    4),
  ('Reformer Pilates','reformer-pilates','Alat Reformer & Aksesoris Pilates',5);

INSERT INTO brands (name, slug, logo, logo_white) VALUES
  ('Insight',   'insight',   'brands/insight.png',   0),
  ('Force USA', 'force-usa', 'brands/force-usa.png', 1),   -- black logo -> shown white on dark
  ('BRTW',      'brtw',      'brands/brtw.png',      0),
  ('DHZ',       'dhz',       'brands/dhz.png',       0);

-- All values below are DUMMY. Change them in Admin > Pengaturan after login.
INSERT INTO settings (setting_key, setting_value) VALUES
  ('site_name',            'BeeSportFit'),
  ('whatsapp_number',      '6281200000000'),   -- international format, no "+" or spaces
  ('contact_email',        'info@beesportfit.example'),
  ('address',              'Jl. Contoh No. 123, Kota Contoh, Indonesia (alamat dummy)'),
  ('instagram',            'https://instagram.com/beesportfit'),
  ('chat_enabled',         '1'),
  ('chat_welcome',         'Halo! Ada yang bisa kami bantu? Tanyakan produk, harga, atau kebutuhan layout gym Anda.'),
  ('stat_products',        '100%'),
  ('stat_products_label',  'PRODUK IMPOR'),
  ('stat_gyms',            'GRATIS'),
  ('stat_gyms_label',      'KONSULTASI LAYOUT'),
  ('stat_provinces',       '38'),
  ('stat_provinces_label', 'PROVINSI JANGKAUAN KIRIM');

INSERT INTO pages (slug, title, content) VALUES
  ('warranty', 'Kebijakan Garansi',   '<p>Isi kebijakan garansi.</p>'),
  ('terms',    'Syarat & Ketentuan',  '<p>Isi syarat dan ketentuan.</p>');

-- ADMIN ACCOUNT: do NOT insert a plain password. Generate the hash in PHP:
--   echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);
-- then run:
--   INSERT INTO users (name, email, password_hash, role)
--   VALUES ('Admin', 'admin@example.com', '<paste hash here>', 'admin');
