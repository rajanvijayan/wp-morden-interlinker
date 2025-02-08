<?php

namespace WPMordenInterlinker\Modules;

use AIEngine\AIEngine;

defined('ABSPATH') || exit;

class Processor {
    const CRON_HOOK = 'wmi_process_url_';

    public function __construct() {
        self::init();
    }

    public static function init() {
        // Schedule jobs for all queued URLs
        self::schedule_all_urls();
    }

    public static function schedule_all_urls() {
        global $wpdb;

        $queued_urls = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wmi_results WHERE status = 'queued'");

        foreach ($queued_urls as $entry) {
            $hook_name = self::CRON_HOOK . $entry->id;

            // Schedule a cron job for each URL if not already scheduled
            if (!wp_next_scheduled($hook_name, [$entry->id])) {
                wp_schedule_single_event(time() + 60, $hook_name, [$entry->id]);
            }

            // Bind the function dynamically
            add_action($hook_name, [__CLASS__, 'process_url'], 10, 1);
        }

        // Set sitemap status to completed if no URLs are queued
        if (empty($queued_urls)) {
            $wpdb->update("{$wpdb->prefix}wmi_sitemap", ['status' => 'completed'], ['status' => 'processed']);
        }
    }

    public static function process_url($result_id) {
        global $wpdb;

        $url_entry = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wmi_results WHERE id = %d AND status = 'queued'", $result_id));

        if (!$url_entry) {
            return;
        }

        $url = $url_entry->url;

        // Update status to initiated
        $wpdb->update("{$wpdb->prefix}wmi_results", ['status' => 'initiated'], ['id' => $result_id]);

        // Get API key
        $api_key = get_option('api_key', '');
        if (empty($api_key)) {
            return;
        }

        $ai_client = new AIEngine($api_key);

        // AI Processing Prompt
        $prompt = "I am an SEO expert, and I need to analyze content for keyword optimization and interlinking.
        
        1. Extract a single primary keyword from the URL $url by analyzing heading tags (H1-H6), meta title, meta description, and the intent of the content. Verify the keyword semantically with the full page content.
        
        2. Identify up to 2 natural places in the content where the keyword 'WordPress Performance and Accessibility' can be interlinked. The sentences must exist exactly in the content—do not modify them. The URL to link to is: $url
        
        Content to analyze: {text_content[:4000]}
        
        Return a JSON object with the following structure without ```:
        
        ```json
        {
          'primary_keyword': 'extracted keyword',
          'interlinking_opportunities': [
            {
              'keyword': 'WordPress Performance and Accessibility',
              'sentence': 'full sentence from content',
              'position': 'exact phrase (max 3 words) in the sentence to interlink'
            }
          ]
        }";

        $response = $ai_client->generateContent($prompt);

        // Clean AI response
        $response = self::clean_json_response(self::clean_ai_response($response));

        // Decode JSON
        $response_data = json_decode($response, true);

        // Log response

        // Validate AI response
        if (!is_array($response_data) || empty($response_data['primary_keyword'])) {
            $wpdb->update("{$wpdb->prefix}wmi_results", ['status' => 'failed'], ['id' => $result_id]);
        } else {
            $wpdb->update(
                "{$wpdb->prefix}wmi_results",
                [
                    'meta_info' => wp_json_encode(['keyword' => $response_data['primary_keyword']]),
                    'ai_result' => wp_json_encode($response_data),
                    'status' => 'completed',
                ],
                ['id' => $result_id]
            );
        }
    }

    public static function clean_ai_response($response) {
        return preg_replace('/^```json\s*|\s*```$/', '', trim($response));
    }

    public static function clean_json_response($response) {
        return str_replace("'", '"', trim($response)); // Convert single quotes to double for valid JSON
    }

    public static function deactivate() {
        global $wpdb;

        // Clear all dynamically scheduled hooks
        $queued_urls = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}wmi_results WHERE status = 'queued' OR status = 'initiated'");

        foreach ($queued_urls as $entry) {
            wp_clear_scheduled_hook(self::CRON_HOOK . $entry->id, [$entry->id]);
        }
    }
}