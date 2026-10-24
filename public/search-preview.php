<?php
/** Header search preview: matching active categories, brands, and public pages. */
require_once __DIR__ . '/../app/config.php';

header('Content-Type: application/json; charset=utf-8');

$query = input_str($_GET, 'q');
$results = search_content($query);
foreach ($results as $type => $items) $results[$type] = array_slice($items, 0, 5);

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);