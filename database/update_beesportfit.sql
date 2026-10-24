-- =====================================================================
-- UPDATE for databases that were imported BEFORE the BeeSportFit rebrand.
-- Safe to run: it only changes values that are still the old placeholders,
-- so anything you already edited in Admin > Pengaturan stays untouched.
-- (New installs don't need this file - schema.sql already has the new values.)
-- =====================================================================
-- (select your database in phpMyAdmin before importing this file)

-- 1. New hero-label settings + dummy hero numbers
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('stat_products_label',  'PRODUK IMPOR'),
  ('stat_gyms_label',      'KONSULTASI LAYOUT'),
  ('stat_provinces_label', 'PROVINSI JANGKAUAN KIRIM');
UPDATE settings SET setting_value = '100%'    WHERE setting_key = 'stat_products' AND setting_value = '500+';
UPDATE settings SET setting_value = 'GRATIS'  WHERE setting_key = 'stat_gyms'     AND setting_value = '12K+';
UPDATE settings SET setting_value = 'BeeSportFit' WHERE setting_key = 'site_name';

-- 2. Old contact placeholders -> BeeSportFit dummy contact
UPDATE settings SET setting_value = 'Jl. Contoh No. 123, Kota Contoh, Indonesia (alamat dummy)' WHERE setting_key = 'address'       AND setting_value = 'Client address here';
UPDATE settings SET setting_value = 'info@beesportfit.example'                                  WHERE setting_key = 'contact_email' AND setting_value = 'client@example.com';
UPDATE settings SET setting_value = 'https://instagram.com/beesportfit'                         WHERE setting_key = 'instagram'     AND setting_value = 'https://instagram.com/yourclient';

-- 3. (Removed) brand renames: brands are now managed in Admin > Brand, see update_brand_logos.sql

-- 4. Sample products that carried other companies' model names -> neutral names
UPDATE products SET name='Seated Dip Machine BSF-101',         slug='seated-dip-machine-bsf-101',         sku='BSF-101' WHERE slug='seated-dip-brtw-bf06';
UPDATE products SET name='Shoulder Press Machine BSF-102',     slug='shoulder-press-machine-bsf-102',     sku='BSF-102' WHERE slug='shoulder-press-brtw-bf03';
UPDATE products SET name='Pec Fly / Rear Delt Machine BSF-103',slug='pec-fly-rear-delt-machine-bsf-103',  sku='BSF-103' WHERE slug='pec-fly-rear-delt-brtw-bf02';
UPDATE products SET name='Treadmill Komersial AC 5 HP',        slug='treadmill-komersial-ac-5-hp',        sku='TM-AC5'  WHERE slug='treadmill-komersial-dhz-t9';
UPDATE products SET name='Spin Bike Studio 20 kg',             slug='spin-bike-studio-20-kg',             sku='SB-20'   WHERE slug='spin-bike-insight-sb200';
UPDATE products SET name='Reformer Pilates Kayu Maple',        slug='reformer-pilates-kayu-maple',        sku='RF-01'   WHERE slug='reformer-pilates-dhz-r1';
UPDATE products SET discount_price = 49980000 WHERE sku = 'BSF-101' AND discount_price = 29400000;
UPDATE products SET discount_price = 49980000 WHERE sku = 'BSF-102' AND discount_price = 29400000;
UPDATE products SET discount_price = NULL     WHERE sku = 'BSF-103' AND discount_price = 29400000;
