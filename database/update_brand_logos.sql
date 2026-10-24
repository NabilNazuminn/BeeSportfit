-- =====================================================================
-- BRAND LOGOS for a database imported BEFORE this feature.
-- Adds the brands.logo_white column and turns the dummy brands (Brand A-D) into
-- Insight, Force USA, BRTW and DHZ with their logos.
-- Also copy the folder public/uploads/brands/ (the 4 logo files) to the server.
-- Safe to run twice; it never overwrites a logo you uploaded yourself.
-- (Works on MySQL and MariaDB.)
-- =====================================================================
-- (select your database in phpMyAdmin before importing this file)

-- 1. add the column only if it is missing
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'brands' AND COLUMN_NAME = 'logo_white');
SET @sql := IF(@has = 0, 'ALTER TABLE brands ADD COLUMN logo_white TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- 2. rename the dummy brands (only if the real name is not taken yet)
UPDATE brands SET name = 'Insight',   slug = 'insight'   WHERE slug = 'brand-a' AND NOT EXISTS (SELECT 1 FROM (SELECT slug FROM brands) x WHERE x.slug = 'insight');
UPDATE brands SET name = 'Force USA', slug = 'force-usa' WHERE slug = 'brand-b' AND NOT EXISTS (SELECT 1 FROM (SELECT slug FROM brands) x WHERE x.slug = 'force-usa');
UPDATE brands SET name = 'BRTW',      slug = 'brtw'      WHERE slug = 'brand-c' AND NOT EXISTS (SELECT 1 FROM (SELECT slug FROM brands) x WHERE x.slug = 'brtw');
UPDATE brands SET name = 'DHZ',       slug = 'dhz'       WHERE slug = 'brand-d' AND NOT EXISTS (SELECT 1 FROM (SELECT slug FROM brands) x WHERE x.slug = 'dhz');

-- 3. attach the logo files (only where no logo is set yet)
UPDATE brands SET logo = 'brands/insight.png'   WHERE slug = 'insight'   AND (logo IS NULL OR logo = '');
UPDATE brands SET logo = 'brands/force-usa.png' WHERE slug = 'force-usa' AND (logo IS NULL OR logo = '');
UPDATE brands SET logo = 'brands/brtw.png'      WHERE slug = 'brtw'      AND (logo IS NULL OR logo = '');
UPDATE brands SET logo = 'brands/dhz.png'       WHERE slug = 'dhz'       AND (logo IS NULL OR logo = '');
UPDATE brands SET logo_white = 1 WHERE slug = 'force-usa' AND logo = 'brands/force-usa.png';
