<?php
/**
 * Uninstall handler for Asneris SEO Toolkit
 *
 * Removes all plugin data when the plugin is deleted via the WordPress admin.
 * This file is called by WordPress automatically during uninstallation.
 *
 * @package Asneris_SEO_Toolkit
 */

// Exit if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete plugin options.
$options_to_delete = array(
	'ASNERISSEO_settings',
	'ASNERISSEO_redirects',
	'ASNERISSEO_migration_version',
	'ASNERISSEO_indexnow_submissions',
	'ASNERISSEO_validation_summary',
);

foreach ( $options_to_delete as $option ) {
	delete_option( $option );
}

// 2. Delete all post meta created by this plugin.
$meta_keys_to_delete = array(
	'_ASNERISSEO_title',
	'_ASNERISSEO_description',
	'_ASNERISSEO_canonical',
	'_ASNERISSEO_robots_index',
	'_ASNERISSEO_robots_follow',
	'_ASNERISSEO_og_title',
	'_ASNERISSEO_og_description',
	'_ASNERISSEO_og_image',
	'_ASNERISSEO_schema_enabled',
	'_ASNERISSEO_schema_type',
	'_ASNERISSEO_indexnow_last',
);

foreach ( $meta_keys_to_delete as $meta_key ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup during uninstall, no caching needed
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ) );
}

// 3. Delete legacy post meta from older versions (gscseo_* prefix).
$legacy_meta_keys = array(
	'_gscseo_title',
	'_gscseo_description',
	'_gscseo_canonical',
	'_gscseo_robots_index',
	'_gscseo_robots_follow',
	'_gscseo_og_title',
	'_gscseo_og_description',
	'_gscseo_og_image',
	'_gscseo_schema_enabled',
	'_gscseo_schema_type',
);

foreach ( $legacy_meta_keys as $meta_key ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup during uninstall, no caching needed
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $meta_key ) );
}

// 4. Delete legacy options.
$legacy_options = array(
	'gscseo_settings',
	'gscseo_redirects',
	'gscseo_indexnow_submissions',
);

foreach ( $legacy_options as $option ) {
	delete_option( $option );
}

// 5. Flush rewrite rules to clean up IndexNow key file route.
flush_rewrite_rules();
