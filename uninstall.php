<?php

/**
 *
 * @link       https://mythemeshop.com/plugins/url-shortener/
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
if ( ! current_user_can( 'activate_plugins' ) ) {
	exit;
}

$settings = get_option('urlshortener_defaults');

if(!empty($settings) && isset($settings['delete_data']) && $settings['delete_data'] === 'yes') {

	// Drop tables
	global $wpdb;
	$wp_short_links = $wpdb->prefix . 'short_links';
	$wp_short_link_replacements = $wpdb->prefix . 'short_link_replacements';
	$wp_short_link_clicks = $wpdb->prefix . 'short_link_clicks';
	$wpdb->query( "DROP TABLE IF EXISTS $wp_short_links, $wp_short_link_replacements, $wp_short_link_clicks" );

	// Delete settings
	delete_option('urlshortener_general');
	delete_option('urlshortener_advanced');

	// Delete Short Link Category terms
	$taxonomy = 'short_link_category';
	$wpdb->get_results( $wpdb->prepare( "DELETE t.*, tt.* FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('%s')", $taxonomy ) );	
	$wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
}