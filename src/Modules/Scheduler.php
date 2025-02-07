<?php

namespace WPMordenInterlinker\Modules;

defined('ABSPATH') || exit;

class Scheduler {
    const CRON_HOOK = 'wmi_process_sitemaps';

    public function __construct() {
        add_action(self::CRON_HOOK, [__CLASS__, 'process_sitemap']);

        // Schedule cron job if not already scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
        }
    }

    public static function process_sitemap() {
        global $wpdb;

        // Get one unprocessed sitemap
        $sitemap = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wmi_sitemap WHERE status = 'uploaded' LIMIT 1");

        if (!$sitemap) {
            return; // No sitemaps left to process
        }

        $sitemap_id = $sitemap->id;
        $sitemap_path = $sitemap->path;

        if (!file_exists($sitemap_path)) {
            error_log("WMI Scheduler: Sitemap file not found - {$sitemap_path}");
            return;
        }

        // Read sitemap XML
        $xml = simplexml_load_file($sitemap_path);
        if (!$xml) {
            error_log("WMI Scheduler: Failed to parse XML sitemap - {$sitemap_path}");
            return;
        }

        $urls = [];
        foreach ($xml->url as $urlNode) {
            $url = (string) $urlNode->loc;
            if (!empty($url)) {
                $urls[] = $url;
            }
        }

        // Insert URLs into wmi_results
        foreach ($urls as $url) {
            $wpdb->insert(
                "{$wpdb->prefix}wmi_results",
                [
                    'sitemap_id' => $sitemap_id,
                    'url' => $url,
                    'status' => 'queued',
                ],
                ['%d', '%s', '%s']
            );
        }

        // Mark sitemap as processed
        $wpdb->update(
            "{$wpdb->prefix}wmi_sitemap",
            ['status' => 'processed'],
            ['id' => $sitemap_id],
            ['%s'],
            ['%d']
        );
        
    }

    public static function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }
}
