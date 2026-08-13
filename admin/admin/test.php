<?php
require 'wp-load.php';
global $wpdb;
$table = $wpdb->prefix . 'about_who_we_are';
$res = $wpdb->get_results('SELECT * FROM ' . $table);
print_r($res);
if($wpdb->last_error) {
    echo "ERROR: " . $wpdb->last_error;
}
