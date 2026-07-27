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
$asnerisseo_options_to_delete = array(
	'ASNERISSEO_settings',
	'ASNERISSEO_redirects',
	'ASNERISSEO_migration_version',
	'ASNERISSEO_indexnow_submissions',
	'ASNERISSEO_validation_summary',
	'asnerisseo_404_db_version',
	'asnerisseo_404_enabled',
	'asnerisseo_404_collecting',
	'asnerisseo_404_first_time_notice',
	'asnerisseo_404_throttle_limit',
	'asnerisseo_404_throttle_window',
	'asnerisseo_404_analysis_cron_frequency',
	'asnerisseo_404_exclude_urls',
	'asnerisseo_404_exclude_keywords',
	'asnerisseo_404_ignore_query_params',
	'asnerisseo_page_diagnostics_scan_cron_frequency',
);

foreach ( $asnerisseo_options_to_delete as $asnerisseo_option ) {
	delete_option( $asnerisseo_option );
}

// 2. Delete all post meta created by this plugin.
$asnerisseo_meta_keys_to_delete = array(
	'_ASNERISSEO_title',
	'_ASNERISSEO_description',
	'_ASNERISSEO_canonical',
	'_ASNERISSEO_robots_index',
	'_ASNERISSEO_robots_follow',
	'_ASNERISSEO_og_title',
	'_ASNERISSEO_og_description',
	'_ASNERISSEO_og_image',
	'_ASNERISSEO_og_image_id',
	'_ASNERISSEO_schema_enabled',
	'_ASNERISSEO_schema_type',
	'_ASNERISSEO_indexnow_last',
	// Extended schema meta keys
	'_ASNERISSEO_event_start_date',
	'_ASNERISSEO_event_end_date',
	'_ASNERISSEO_event_location_name',
	'_ASNERISSEO_event_location_address',
	'_ASNERISSEO_recipe_cook_time',
	'_ASNERISSEO_recipe_prep_time',
	'_ASNERISSEO_video_url',
	'_ASNERISSEO_video_duration',
	'_ASNERISSEO_faq_items',
	'_ASNERISSEO_howto_steps',
	'_ASNERISSEO_job_location',
	'_ASNERISSEO_job_employment_type',
);

foreach ( $asnerisseo_meta_keys_to_delete as $asnerisseo_meta_key ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Cleanup during uninstall, no caching needed
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $asnerisseo_meta_key ) );
}

// 3. Delete previous-version post meta (gscseo_* prefix).
$asnerisseo_previous_meta_keys = array(
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

foreach ( $asnerisseo_previous_meta_keys as $asnerisseo_meta_key ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Cleanup during uninstall, no caching needed
	$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $asnerisseo_meta_key ) );
}

// 4. Delete previous-version options.
$asnerisseo_previous_options = array(
	'gscseo_settings',
	'gscseo_redirects',
	'gscseo_indexnow_submissions',
);

foreach ( $asnerisseo_previous_options as $asnerisseo_option ) {
	delete_option( $asnerisseo_option );
}

// 5. Drop plugin-owned custom tables.
$asnerisseo_tables_to_drop = array(
	$wpdb->prefix . 'asneris_404_logs',
	$wpdb->prefix . 'asneris_page_diag_latest',
	$wpdb->prefix . 'asneris_page_diag_history',
);

foreach ( $asnerisseo_tables_to_drop as $asnerisseo_table_name ) {
	$asnerisseo_table_name = (string) $asnerisseo_table_name;
	if ( '' === $asnerisseo_table_name || 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $asnerisseo_table_name ) ) {
		continue;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Uninstall cleanup for plugin-owned validated table names.
	$wpdb->query( "DROP TABLE IF EXISTS `{$asnerisseo_table_name}`" );
}

// 6. Flush rewrite rules to clean up IndexNow key file route.
flush_rewrite_rules();
