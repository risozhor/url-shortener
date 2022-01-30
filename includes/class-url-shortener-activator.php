<?php

/**
 * Fired during plugin activation
 *
 * @link       https://mythemeshop.com/plugins/url-shortener/
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/includes
 * @author     MyThemeShop <support-team@mythemeshop.com>
 */
class FIRST_URL_Shortener_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate( $network_wide = false ) {
		global $wpdb;
		
		if ( is_multisite() && $network_wide ) { 
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	        foreach ( $blog_ids as $blog_id ) {
	            //switch_to_blog( $blog_id );
	            self::add_tables( $blog_id );
	            //restore_current_blog();
	        }
		} else {
			self::add_tables();
		}
	}

	public static function add_tables( $blog_id = null ) {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		
		if ( $blog_id && $blog_id != $wpdb->blogid )
			$old_blog_id = $wpdb->set_blog_id( $blog_id );

		// Engage multisite if in the middle of turning it on from network.php.
		$is_multisite = is_multisite() || ( defined( 'WP_INSTALLING_NETWORK' ) && WP_INSTALLING_NETWORK );
		$max_index_length = 191;

		$wp_short_links = $wpdb->prefix . 'short_links';
		//$wp_short_link_groups = $wpdb->prefix . 'short_link_groups';
		$wp_short_link_replacements = $wpdb->prefix . 'short_link_replacements';
		$wp_short_link_clicks = $wpdb->prefix . 'short_link_clicks';

		$sql = "CREATE TABLE $wp_short_links (
	link_id bigint(20) unsigned NOT NULL auto_increment,
	link_order int(11) NOT NULL default '0',
	link_url text NOT NULL,
	link_name varchar(255) NOT NULL default '',
	link_cloak_url varchar(255) NOT NULL default '',
	link_anchor varchar(255) NOT NULL default '',
	link_title varchar(255) NOT NULL default '',
	link_image varchar(255) NOT NULL default '',
	link_description mediumtext NOT NULL,
	link_status varchar(64) NOT NULL default '',
	link_owner bigint(20) unsigned NOT NULL default '1',
	link_created datetime NOT NULL default '0000-00-00 00:00:00',
	link_updated datetime NOT NULL default '0000-00-00 00:00:00',
	link_attr_target varchar(128) NOT NULL default '',
	link_attr_rel varchar(128) NOT NULL default '',
	link_attr_title varchar(128) NOT NULL default '',
	link_attr_class varchar(128) NOT NULL default '',
	link_attributes varchar(255) NOT NULL default '',
	link_notes mediumtext NOT NULL,
	link_redirection_method varchar(64) NOT NULL default '',
	link_forward_parameters tinyint(1) NOT NULL default '0',
	link_remove_referrer tinyint(1) NOT NULL default '0',
	link_css mediumtext NOT NULL,
	link_hover_css mediumtext NOT NULL,
	PRIMARY KEY  (link_id),
	KEY link_name (link_name($max_index_length)),
	KEY link_status (link_status)
) $charset_collate;

CREATE TABLE $wp_short_link_clicks (
	click_id bigint(20) unsigned NOT NULL auto_increment,
	link_id bigint(20) unsigned NOT NULL default '0',
	click_date datetime NOT NULL default '0000-00-00 00:00:00',
	click_ip varchar(255) NOT NULL default '',
	click_useragent text NOT NULL,
	click_robot tinyint(1) NOT NULL default '0',
	click_handheld tinyint(1) NOT NULL default '0',
	click_browser varchar(255) NOT NULL default '',
	click_os varchar(255) NOT NULL default '',
	click_device varchar(255) NOT NULL default '',
	click_uri varchar(255) NOT NULL default '',
	click_referrer varchar(255) NOT NULL default '',
	PRIMARY KEY  (click_id),
	KEY link_id (link_id)
) $charset_collate;

CREATE TABLE $wp_short_link_replacements (
	replacement_id bigint(20) unsigned NOT NULL auto_increment,
	replace_key varchar(512) NOT NULL,
	type varchar(64) NOT NULL default '',
	link_id bigint(20) unsigned NOT NULL default '0',
	link_status varchar(64) NOT NULL default '',
	PRIMARY KEY  (replacement_id),
	KEY link_id (link_id)
) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

}
