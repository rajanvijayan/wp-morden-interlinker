<?php
/*
 * Plugin Name: WP Morden Interlinker
 * Plugin URI: 
 * Description: WP Interlinker is an advanced WordPress plugin designed to enhance your site's internal linking structure. 
 * Version: 1.0
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Piperocket
 * Author URI: https://piperocket.digital/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-morden-interlinker
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

// Autoload dependencies using Composer
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

use WPMordenInterlinker\Admin\SettingsPage;
use WPMordenInterlinker\Database\Installer;
use WPMordenInterlinker\Sitemap\UploadHandler;
use WPMordenInterlinker\Modules\Scheduler;
use WPMordenInterlinker\Modules\Processor;
use WPMordenInterlinker\Frontend\Results;

// Initialize the plugin
function wp_morden_interlinker_init() {
    // Load admin settings and logs page
    if ( is_admin() ) {
        new SettingsPage();
        new UploadHandler();
        new Results();
    }
    new Scheduler();
    new Processor();
}
add_action( 'plugins_loaded', 'wp_morden_interlinker_init' );

// Register activation and deactivation hooks
register_activation_hook( __FILE__, [ Installer::class, 'install' ] );
register_deactivation_hook( __FILE__, [ Installer::class, 'uninstall' ] );