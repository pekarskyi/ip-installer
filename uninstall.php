<?php
// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Видалення таблиці налаштувань
global $wpdb;
$table_name = $wpdb->prefix . 'ip_installer_settings';

// Видаляємо таблицю незалежно від налаштувань
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Видаляємо налаштування з wp_options, якщо такі існують
delete_option('ip_installer_settings');
delete_option('ip_installer_github_api_key');
delete_option('ip_installer_version'); 