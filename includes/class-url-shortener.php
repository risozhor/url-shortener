<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://mythemeshop.com/plugins/url-shortener
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/includes
 * @author     MyThemeShop <support-team@mythemeshop.com>
 */
class FIRST_URL_Shortener {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      FIRST_URL_Shortener_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'first-url-shortener';
		$this->version = '1.0.16';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->redirect_current = false;

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - FIRST_URL_Shortener_Loader. Orchestrates the hooks of the plugin.
	 * - FIRST_URL_Shortener_i18n. Defines internationalization functionality.
	 * - FIRST_URL_Shortener_Admin. Defines all hooks for the admin area.
	 * - FIRST_URL_Shortener_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-url-shortener-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-url-shortener-i18n.php';

		/**
		 * The classes responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-url-shortener-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-url-shortener-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-url-shortener-public.php';

		$this->loader = new FIRST_URL_Shortener_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the FIRST_URL_Shortener_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new FIRST_URL_Shortener_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new FIRST_URL_Shortener_Admin( $this->get_plugin_name(), $this->get_version() );
    $this->screen_base = sanitize_title( __('Short Links', 'first-url-shortener') );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'init_meta_boxes' );
		// Metaboxes need to be "done" before admin head to show screen opts
		$this->loader->add_action( 'admin_head-'.$this->screen_base.'_page_url_shortener_add', $plugin_admin, 'do_add_meta_boxes' );
		$this->loader->add_action( 'admin_head-'.$this->screen_base.'_page_url_shortener_edit', $plugin_admin, 'do_add_meta_boxes' );

		$this->loader->add_filter( 'get_user_option_screen_layout_'.$this->screen_base.'_page_url_shortener_add', $plugin_admin, 'fix_screenopts', 10, 3 );
		$this->loader->add_filter( 'get_user_option_screen_layout_'.$this->screen_base.'_page_url_shortener_edit', $plugin_admin, 'fix_screenopts', 10, 3 );
		$this->loader->add_filter( 'get_user_option_metaboxhidden_'.$this->screen_base.'_page_url_shortener_add', $plugin_admin, 'fix_screenopts', 10, 3 );
		$this->loader->add_filter( 'get_user_option_metaboxhidden_'.$this->screen_base.'_page_url_shortener_edit', $plugin_admin, 'fix_screenopts', 10, 3 );

		$this->loader->add_action( 'wp_ajax_add-short-link-category', $plugin_admin, 'ajax_add_link_category' );
		$this->loader->add_action( 'wp_ajax_ls_list_posts', $plugin_admin, 'ajax_list_posts' );

		// Pro version notice
    $this->loader->add_action('admin_notices', $plugin_admin, 'url_shortener_admin_notice');
    $this->loader->add_action('wp_ajax_first_dismiss_urlshortener_notice', $plugin_admin, 'url_shortener_admin_notice_ignore');

		// Core action is hooked to priority 1 so we hook to 0 to override it
		$this->loader->add_action( 'wp_ajax_wp-link-ajax', $plugin_admin, 'wp_ajax_wp_link_ajax', 0 );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'process_actions' );
		//$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'show_notices' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_pages' );
		$this->loader->add_action( 'parent_file', $plugin_admin, 'modify_admin_menu' );
		$this->loader->add_action( 'admin_head', $plugin_admin, 'admin_head' );
		$this->loader->add_action( 'load-toplevel_page_url_shortener_links', $plugin_admin, 'screen_options' );
		$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'save_screen_options', 10, 3 );


		$this->loader->add_action( 'url_shortener_settings_after_section_import', $plugin_admin, 'import_section' );

		// Import Pretty Links
		$this->loader->add_action( 'wp_ajax_shortlink_prli_import', $plugin_admin, 'ajax_import_prli' );

		// Dismiss Import Notice
		$this->loader->add_action( 'wp_ajax_urlshortener_dismiss_importnotice', $plugin_admin, 'ajax_dismiss_notice' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new FIRST_URL_Shortener_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'init', $plugin_public, 'register_taxonomy' );
		//$this->loader->add_action( 'init', $plugin_public, 'add_shortcode' );
		$this->loader->add_action( 'init', $plugin_public, 'add_shortcodes' );
		$this->loader->add_action( 'init', $plugin_public, 'pre_check_redirect' );


		// Disable URL auto-guess
		$this->loader->add_filter( 'redirect_canonical', $plugin_public, 'remove_redirect_guess_404_permalink' );

		// Replace links
		$this->loader->add_filter( 'the_content', $plugin_public, 'replace_links' );

		$this->loader->add_action( 'robots_txt', $plugin_public, 'robots_txt', 10, 2 );


	}
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    FIRST_URL_Shortener_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
