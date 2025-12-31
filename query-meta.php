<?php
require '/var/www/html/wp-load.php';
global $wpdb;
$results = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE meta_key LIKE '_gscseo%' ORDER BY post_id, meta_key LIMIT 20", ARRAY_A);
echo json_encode($results, JSON_PRETTY_PRINT);
