<?php
namespace WPMordenInterlinker\Admin;

class SettingsPage {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_settings_page() {
        add_options_page(
            'Interlinker Settings',
            'Interlinker',
            'manage_options',
            'ai-comment-moderator',
            [ $this, 'create_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'wp_morden_interlinker_group', 'enabled_post_types' );
        register_setting( 'wp_morden_interlinker_group', 'api_key' );
    }

    public function create_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP Morden Interlinker Settings</h1>
    
            <!-- Tabs for Basic Settings and API Settings -->
            <h2 class="nav-tab-wrapper">
                <a href="#api-settings" class="nav-tab">API Settings</a>
            </h2>
    
            <form method="post" action="options.php">
                <?php settings_fields( 'wp_morden_interlinker_group' ); ?>
                <?php do_settings_sections( 'wp_morden_interlinker_group' ); ?>
    
                <!-- API Settings Tab -->
                <div id="api-settings" class="settings-section">
                    <h2>API Settings</h2>
                    <p class="description">Enter your API key. Ensure it's correct to avoid errors in moderation.</p>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">API Key</th>
                            <td>
                                <input type="password" name="api_key" class="regular-text" value="<?php echo esc_attr( get_option( 'api_key' ) ); ?>" />
                                <p class="description">Enter your API Secret Key here. <a href="https://aistudio.google.com/app/apikey" target="_blank">Get your API key here</a></p>
                            </td>
                        </tr>
                    </table>
                </div>
    
                <?php submit_button(); ?>
            </form>
        </div>
    
        <script>
            // JavaScript to handle the tab navigation
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function (e) {
                    e.preventDefault();
    
                    document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
                    tab.classList.add('nav-tab-active');
    
                    document.querySelectorAll('.settings-section').forEach(section => section.style.display = 'none');
                    document.querySelector(tab.getAttribute('href')).style.display = 'block';
                });
            });
        </script>
        <?php
    }
}