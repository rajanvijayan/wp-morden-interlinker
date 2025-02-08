<?php

namespace WPMordenInterlinker\Sitemap;

defined('ABSPATH') || exit;

class UploadHandler {
    private $upload_dir;

    public function __construct() {
        $this->upload_dir = wp_upload_dir()['basedir'] . '/wp-morden-interlinker';
        add_action('admin_menu', [$this, 'add_upload_page']);
        add_action('admin_post_wmi_generate_sitemap', [$this, 'handle_sitemap_generation']);
    }

    public function add_upload_page() {
        add_menu_page(
            __('Interlinker', 'wp-morden-interlinker'),
            __('Interlinker', 'wp-morden-interlinker'),
            'manage_options',
            'wmi-sitemap-generator',
            [$this, 'render_input_form'],
            'dashicons-admin-links',
        );


    }

    public function render_input_form() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Generate Sitemap XML', 'wp-morden-interlinker'); ?></h1>
            <?php if (isset($_GET['success'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Sitemap generated and saved successfully!', 'wp-morden-interlinker'); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="validate">
                <input type="hidden" name="action" value="wmi_generate_sitemap">
                <?php wp_nonce_field('wmi_generate_sitemap_action', 'wmi_generate_sitemap_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sitemap_url"><?php _e('Enter Website URL:', 'wp-morden-interlinker'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="sitemap_url" id="sitemap_url" class="regular-text" required>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Generate Sitemap', 'wp-morden-interlinker'), 'primary', 'submit', true); ?>
            </form>
        </div>
        <?php
    }

    public function handle_sitemap_generation() {
        if (!isset($_POST['wmi_generate_sitemap_nonce']) || !wp_verify_nonce($_POST['wmi_generate_sitemap_nonce'], 'wmi_generate_sitemap_action')) {
            wp_die(__('Security check failed', 'wp-morden-interlinker'));
        }
    
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-morden-interlinker'));
        }
    
        if (!isset($_POST['sitemap_url']) || empty($_POST['sitemap_url'])) {
            wp_die(__('No URL provided', 'wp-morden-interlinker'));
        }
    
        $url = esc_url_raw($_POST['sitemap_url']);
    
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            wp_die(__('Invalid URL format.', 'wp-morden-interlinker'));
        }
    
        $sitemap_content = $this->generate_sitemap($url);
        if (!$sitemap_content) {
            wp_die(__('Failed to download the sitemap.', 'wp-morden-interlinker'));
        }
    
        $filename = $this->get_unique_filename($url);
    
        if (!is_dir($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    
        $file_path = $this->upload_dir . '/' . $filename;
    
        if (file_put_contents($file_path, $sitemap_content)) {
            $this->save_sitemap_data($url, $filename, $file_path);
            wp_redirect(admin_url('admin.php?page=wmi-sitemap-generator&success=1'));
            exit;
        } else {
            wp_die(__('Failed to create sitemap file.', 'wp-morden-interlinker'));
        }
    }
    
    /**
     * Generate a unique filename if the file already exists.
     */
    private function get_unique_filename($url) {
        $base_filename = sanitize_title(parse_url($url, PHP_URL_HOST)) . '-sitemap.xml';
        $file_path = $this->upload_dir . '/' . $base_filename;
        $counter = 1;
    
        while (file_exists($file_path)) {
            $base_filename = sanitize_title(parse_url($url, PHP_URL_HOST)) . "-sitemap-{$counter}.xml";
            $file_path = $this->upload_dir . '/' . $base_filename;
            $counter++;
        }
    
        return $base_filename;
    }

    private function generate_sitemap($url) {
        $sitemap_content = wp_remote_get($url);
    
        if (is_wp_error($sitemap_content)) {
            return false;
        }
    
        $body = wp_remote_retrieve_body($sitemap_content);
    
        if (empty($body) || strpos($body, '<?xml') === false) {
            return false;
        }
    
        // Remove XML stylesheet declaration if present
        $body = preg_replace('/<\?xml-stylesheet[^>]+?>\s*/i', '', $body);
    
        return $body;
    }

    private function save_sitemap_data($url, $filename, $file_path) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wmi_sitemap';

        $wpdb->insert(
            $table_name,
            [
                'url' => $url,
                'path' => $file_path,
                'date' => current_time('mysql'),
                'status' => 'uploaded'
            ],
            ['%s', '%s', '%s', '%s']
        );
    }
}