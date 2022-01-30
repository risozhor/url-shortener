<?php
/**
 * Plugin Name:	1st URL Shortener
 * Plugin URI:	https://www.smarty.ninja/
 * Description:	A simple tool for creating short links.
 * Version:		1.0.0
 * Author:		Shytyi Oleksander
 * Author URI:	https://www.smarty.ninja/
 * Text Domain:	first-url-shortener
 * Domain Path:	/languages
 *
 * @link              https://www.smarty.ninja/
 * @since             1.0.0
 * @package           FIRST_URL_Shortener
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('URL_SHORTENER_PLUGIN_PATH', plugin_dir_path( __FILE__ ));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-url-shortener-activator.php
 */
function activate_url_shortener( $network_wide ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-url-shortener-activator.php';
	FIRST_URL_Shortener_Activator::activate( $network_wide );
	update_option('url_shortener_activated', time());
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-url-shortener-deactivator.php
 */
function deactivate_url_shortener() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-url-shortener-deactivator.php';
	FIRST_URL_Shortener_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_url_shortener' );
register_deactivation_hook( __FILE__, 'deactivate_url_shortener' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-url-shortener.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_url_shortener() {

	$plugin = new FIRST_URL_Shortener();
	$plugin->run();

}
run_url_shortener();
