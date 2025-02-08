<?php

namespace WPMordenInterlinker\Frontend;

defined('ABSPATH') || exit;

class Results {
    const MENU_SLUG = 'wmi-results';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);

        // Add custom styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_enqueue_style('wmi-results', plugin_dir_url(__DIR__) . '../assets/css/wmi-results.css', [], '1.0');
    }

    public function register_menu() {
        add_submenu_page(
            'wmi-sitemap-generator', // Parent menu slug
            __('Sitemaps Results', 'wp-morden-interlinker'),
            __('Sitemaps Results', 'wp-morden-interlinker'),
            'manage_options',
            'wmi-sitemaps',
            [$this, 'render_results_page']
        );
    }

    public function render_results_page() {
        global $wpdb;
        $table_name = "{$wpdb->prefix}wmi_results";
        
        // Pagination setup
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Fetch results
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC LIMIT $per_page OFFSET $offset");

        error_log(print_r($results, true));

        ?>
        <div class="wrap">
            <h1>📌 WMI Sitemap Results</h1>
            <table class="widefat fixed striped wmi-results-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>🔗 URL</th>
                        <th>📌 Keyword</th>
                        <th>🔗 Interlinking Opportunities</th>
                        <th>📊 Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results): ?>
                        <?php 
                            $i = 1 + $offset;                            
                            foreach ($results as $row): ?>
                            <tr>
                                <td class="center"><?php echo $i++; ?></td>
                                <td>
                                    <a href="<?php echo esc_url($row->url); ?>" target="_blank" class="wmi-url">
                                        <?php echo esc_html($row->url); ?>
                                    </a>
                                </td>
                                <td class="wmi-keyword"><?php echo esc_html(self::extract_keyword($row->meta_info)); ?></td>
                                <td>
                                    <?php 
                                    $opportunities = self::extract_interlinking_opportunities($row->ai_result);
                                    if (!empty($opportunities)): ?>
                                        <div class="wmi-interlinking">
                                            <?php foreach ($opportunities as $opportunity): ?>
                                                <div class="wmi-opportunity-card">
                                                    <?php foreach ($opportunity as $key => $value): ?>
                                                        <p><strong><?php echo ucfirst($key); ?>:</strong> <?php echo esc_html($value); ?></p>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="wmi-no-opportunities">No opportunities found.</span>
                                    <?php endif; ?>
                                </td>
                                <td class="wmi-status <?php echo esc_attr($row->status); ?>">
                                    <?php echo esc_html(ucfirst($row->status)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="center">🚫 No results found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            $total_pages = ceil($total_items / $per_page);
            // Pagination
            if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links([
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $current_page,
                            'total'   => $total_pages,
                            'prev_text' => '← Previous',
                            'next_text' => 'Next →',
                        ]);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>

            
        
        <?php
    }

    public static function extract_keyword($meta_info) {
        $data = json_decode($meta_info, true);
        return $data['keyword'] ?? 'N/A';
    }

    // extract interlinking_opportunities 
    public static function extract_interlinking_opportunities($ai_result) {
        $data = json_decode($ai_result, true);
        return $data['interlinking_opportunities'] ?? [];
    }

    public static function format_ai_result($ai_result) {
        $decoded = json_decode($ai_result, true);
        return $decoded ? json_encode($decoded, JSON_PRETTY_PRINT) : 'N/A';
    }
}