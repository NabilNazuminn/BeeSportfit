<?php
/** SITEMAP for search engines: home, catalog, categories, products, static pages. Submit https://your-domain/sitemap.php in Google Search Console. */
require_once __DIR__ . '/../app/config.php';

header('Content-Type: application/xml; charset=utf-8');
$urls = [[abs_url('index.php'), null], [abs_url('products.php'), null], [abs_url('consultation.php'), null]];
foreach (get_categories() as $c) $urls[] = [abs_url('products.php?category=' . rawurlencode($c['slug'])), null];
foreach (db_all('SELECT p.slug, p.updated_at FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND c.is_active = 1 ORDER BY p.id') as $p) {
    $urls[] = [abs_url('product.php?slug=' . rawurlencode($p['slug'])), substr($p['updated_at'], 0, 10)];
}
foreach (get_all_pages() as $pg) $urls[] = [abs_url('page.php?slug=' . rawurlencode($pg['slug'])), substr($pg['updated_at'], 0, 10)];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$loc, $mod]) echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . ($mod ? '<lastmod>' . $mod . '</lastmod>' : '') . "</url>\n";
echo '</urlset>';
