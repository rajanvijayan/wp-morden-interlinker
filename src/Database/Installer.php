<?php

namespace WPMordenInterlinker\Database;

defined('ABSPATH') || exit;

class Installer {
    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tables = [
            'wmi_sitemap' => "CREATE TABLE {$wpdb->prefix}wmi_sitemap (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                url TEXT NOT NULL,
                path TEXT NOT NULL,
                date DATETIME NOT NULL,
                status ENUM('uploaded', 'processed', 'completed') NOT NULL DEFAULT 'uploaded'
            ) $charset_collate;",

            'wmi_results' => "CREATE TABLE {$wpdb->prefix}wmi_results (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sitemap_id BIGINT(20) UNSIGNED NOT NULL,
                url TEXT NOT NULL,
                meta_info TEXT NULL,
                ai_result LONGTEXT NULL,
                status ENUM('queued', 'initiated', 'processed', 'completed') NOT NULL DEFAULT 'queued',
                FOREIGN KEY (sitemap_id) REFERENCES {$wpdb->prefix}wmi_sitemap(id) ON DELETE CASCADE
            ) $charset_collate;"
        ];

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ($tables as $sql) {
            dbDelta($sql);
        }
    }

    public static function uninstall() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wmi_results, {$wpdb->prefix}wmi_sitemap");
    }
}