-- =====================================================================
-- DEMO DATA (optional). Import AFTER schema.sql to preview the site.
-- Everything below is DUMMY: product names, prices, photos (none), testimonials.
-- Replace or delete them from the admin panel before going live.
-- Categories (1-5) and brands (1-4, "Brand A-D") come from schema.sql.
-- =====================================================================
-- (select your database in phpMyAdmin before importing this file)

INSERT INTO products (category_id, brand_id, name, slug, sku, short_description, description, specifications, price, discount_price, stock, stock_status, warranty_months, is_featured) VALUES
(3, 1, 'Seated Dip Machine BSF-101',        'seated-dip-machine-bsf-101',        'BSF-101', 'Mesin seated dip untuk melatih trisep dan dada bawah.', 'Mesin kelas komersial dengan rangka baja tebal dan bantalan tahan lama, cocok untuk gym berintensitas tinggi.', 'Berat: 270 kg\nUkuran: 1310x1200x1600 mm\nRangka: Baja 3 mm', 58800000, 49980000, 5, 'in_stock', 12, 1),
(3, 1, 'Shoulder Press Machine BSF-102',    'shoulder-press-machine-bsf-102',    'BSF-102', 'Mesin shoulder press untuk latihan bahu.',               'Gerakan konvergen yang natural dan nyaman untuk semua level latihan.', 'Berat: 273 kg\nUkuran: 1700x1440x1600 mm', 58800000, 49980000, 4, 'in_stock', 12, 1),
(3, 1, 'Pec Fly / Rear Delt Machine BSF-103','pec-fly-rear-delt-machine-bsf-103','BSF-103', 'Mesin 2-in-1 untuk dada dan bahu belakang.',            'Dua fungsi dalam satu mesin, hemat ruang untuk gym komersial.', 'Berat: 285.5 kg\nUkuran: 1490x830x2190 mm', 58800000, NULL, 3, 'in_stock', 12, 1),
(2, 2, 'Treadmill Komersial AC 5 HP',       'treadmill-komersial-ac-5-hp',       'TM-AC5',  'Treadmill motor AC untuk penggunaan komersial.',        'Motor AC 5 HP, layar LED, kecepatan hingga 20 km/jam.', 'Motor: 5 HP AC\nKecepatan: 1-20 km/jam\nBeban maks: 180 kg', 45500000, NULL, 6, 'in_stock', 24, 0),
(2, 2, 'Spin Bike Studio 20 kg',            'spin-bike-studio-20-kg',            'SB-20',   'Sepeda statis dengan flywheel 20 kg.',                  'Resistensi magnetik senyap, cocok untuk studio indoor cycling.', 'Flywheel: 20 kg\nBeban maks: 150 kg', 8900000, 7450000, 12, 'in_stock', 12, 0),
(1, 4, 'Dumbbell Set Hex 2-30 kg',          'dumbbell-set-hex-2-30-kg',          'DB-HEX',  'Set dumbbell hexagon karet 2-30 kg lengkap dengan rak.', 'Pasangan dumbbell 2-30 kg (kelipatan 2 kg) beserta rak dua tingkat.', 'Material: Besi cor + karet\nIsi: 15 pasang', 62000000, NULL, 2, 'pre_order', 6, 0),
(1, 3, 'Olympic Barbell 20 kg',             'olympic-barbell-20-kg',             'BB-20',   'Barbell olimpiade 20 kg, knurling standar.',            'Barbell serbaguna dengan bushing, tahan beban hingga 450 kg.', 'Panjang: 220 cm\nBerat: 20 kg\nBeban maks: 450 kg', 4200000, 3600000, 20, 'in_stock', 12, 0),
(4, 4, 'Foam Roller Pro 90 cm',             'foam-roller-pro-90-cm',             'FR-90',   'Foam roller untuk pemulihan otot.',                     'Permukaan bertekstur untuk massage otot dan relaksasi fascia.', 'Panjang: 90 cm\nDiameter: 15 cm', 450000, NULL, 40, 'in_stock', 3, 0),
(5, 2, 'Reformer Pilates Kayu Maple',       'reformer-pilates-kayu-maple',       'RF-01',   'Reformer pilates rangka kayu untuk studio.',            'Dilengkapi 5 pegas, footbar adjustable, dan tali handle.', 'Material: Kayu maple\nPegas: 5 level', 19800000, 16900000, 8, 'in_stock', 12, 0);

INSERT INTO testimonials (name, role_title, content, sort_order) VALUES
('Budi Santoso',  'Owner, Contoh Gym (dummy)',     'Pengiriman cepat dan teknisi memasang semua alat dengan rapi. Gym kami buka tepat waktu.', 1),
('Rina Wijaya',   'Manager, Contoh Hotel (dummy)', 'Konsultasi layoutnya sangat membantu. Ruang gym hotel kami kini terasa lega dan lengkap.', 2),
('Andi Pratama',  'Home Gym (dummy)',              'Kualitas mesin solid, harga bersaing, dan layanan purna jualnya responsif.', 3);
