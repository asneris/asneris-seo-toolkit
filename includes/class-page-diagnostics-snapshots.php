<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
class ASNERISSEO_Page_Diagnostics_Snapshots {
	const MAX_PRIORITY_PAGES                     = 50;
	const DEFAULT_HISTORY_LIMIT                  = 10;
	const DEFAULT_HISTORY_DAYS                   = 90;
	const CLEANUP_CRON_HOOK                      = 'asnerisseo_page_diagnostics_cleanup';
	const SCAN_CRON_HOOK                         = 'asnerisseo_page_diagnostics_priority_scan';
	const SCAN_CRON_FREQUENCY_OPTION             = 'asnerisseo_page_diagnostics_scan_cron_frequency';
	private static $tables_checked               = false;
	private static $canonical_tab_field_registry = array(
		'overview'          => array(
			'Page Fetch',
			'Local Fallback',
			'HTTP Status',
			'Robots Meta',
			'SEO Title Length',
			'Meta Description Length',
			'H1 Presence',
			'Internal Links',
			'Content Depth (Word Count)',
			'Post Freshness',
			'Post Context',
		),
		'searchAppearance'  => array(
			'SEO Title',
			'SEO Title Length',
			'Meta Description',
			'Meta Description Length',
			'Google Preview',
			'Open Graph Setup',
			'Open Graph Title',
			'Open Graph Description',
			'Open Graph Image',
			'Twitter Card',
		),
		'indexability'      => array(
			'Redirect Status',
			'Final Destination',
			'Canonical Exists',
			'Self Canonical',
			'Canonical Valid URL',
			'Canonical Target HTTP 200',
			'Robots Meta',
			'X-Robots-Tag',
			'HTTP Status',
			'Indexability',
			'Follow Directive',
		),
		'contentQuality'    => array(
			'SEO Title',
			'SEO Title Length',
			'Meta Description',
			'Meta Description Length',
			'H1 Presence',
			'Multiple H1',
			'Heading Structure',
			'Heading Hierarchy',
			'Content Depth (Word Count)',
			'Content Present',
			'Readability',
		),
		'images'            => array(
			'Images Found',
			'Image ALT Coverage',
			'Missing ALT',
			'Empty ALT',
			'Featured Image',
		),
		'links'             => array(
			'Internal Links',
			'External Links',
			'Nofollow Links',
		),
		'structuredData'    => array(
			'Schema Settings',
			'Structured Data Found',
			'Structured Data Present',
			'Schema Validation',
			'Primary Schema',
			'Primary Entity',
			'Organization Schema',
			'Article Schema',
			'FAQ Schema',
			'Breadcrumb Schema',
		),
		'aiDiscoverability' => array(
			'Content Structure',
			'Author Information',
			'Machine Readability',
			'Primary Entity',
			'Topic Consistency',
			'Clear Page Purpose',
			'Summary Section',
			'Content Completeness',
			'Brand Mentions',
			'Product/Context Mentions',
			'Trust Signals',
			'Structured Content',
			'Table/List Detection',
			'Definition Content',
			'FAQ Ready',
			'FAQ Content',
			'FAQ Signals',
			'Language Declaration',
			'Internal References',
			'External References',
			'Published Date',
			'Last Updated Date',
			'Organization Information',
			'Media Context',
		),
	);

	public static function init() {
		add_action( self::SCAN_CRON_HOOK, array( __CLASS__, 'run_priority_pages_scan_cron' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'register_custom_cron_schedules' ) );
		self::ensure_scan_cron_scheduled();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( self::SCAN_CRON_HOOK );
	}

	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$latest_table    = self::latest_table_name();
		$history_table   = self::history_table_name();

		$latest_sql = "CREATE TABLE {$latest_table} (
      page_id BIGINT(20) UNSIGNED NOT NULL,
      seo_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      health VARCHAR(20) NOT NULL DEFAULT 'warning',
      content_hash CHAR(64) NOT NULL DEFAULT '',
      post_modified_gmt DATETIME NULL,
      last_scan_gmt DATETIME NULL,
      report_json LONGTEXT NOT NULL,
      created_at DATETIME NOT NULL,
      updated_at DATETIME NOT NULL,
      PRIMARY KEY (page_id),
      KEY idx_last_scan_gmt (last_scan_gmt),
      KEY idx_health (health)
    ) {$charset_collate};";

		$history_sql = "CREATE TABLE {$history_table} (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      page_id BIGINT(20) UNSIGNED NOT NULL,
      seo_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
      health VARCHAR(20) NOT NULL DEFAULT 'warning',
      report_json LONGTEXT NOT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY idx_page_created (page_id, created_at),
      KEY idx_created_at (created_at)
    ) {$charset_collate};";

		dbDelta( $latest_sql );
		dbDelta( $history_sql );
		self::ensure_scan_cron_scheduled();
	}

	public static function register_custom_cron_schedules( $schedules ) {
		if ( ! is_array( $schedules ) ) {
			$schedules = array();
		}

		if ( ! isset( $schedules['monthly'] ) ) {
			$schedules['monthly'] = array(
				'interval' => 30 * DAY_IN_SECONDS,
				'display'  => __( 'Once Monthly', 'asneris-seo-toolkit' ),
			);
		}

		return $schedules;
	}

	public static function latest_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'asneris_page_diag_latest';
	}

	public static function history_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'asneris_page_diag_history';
	}

	private static function table_exists( $table_name ) {
		global $wpdb;

		$table_name = self::sanitize_table_name_for_query( (string) $table_name );
		if ( '' === $table_name ) {
			return false;
		}

		// Use a prepared table probe so table checks stay portable and avoid interpolated SQL.
		$previous_suppress = $wpdb->suppress_errors( true );
		$probe             = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);
		$wpdb->suppress_errors( $previous_suppress );

		if ( null !== $probe ) {
			return true;
		}

		$error = strtolower( (string) $wpdb->last_error );
		if ( '' !== $error ) {
			if ( false !== strpos( $error, 'doesn\'t exist' ) || false !== strpos( $error, 'relation' ) ) {
				return false;
			}
		}

		return false;
	}

	private static function sanitize_table_name_for_query( $table_name ) {
		$table_name = trim( (string) $table_name );
		if ( '' === $table_name ) {
			return '';
		}

		$table_name = str_replace( array( '`', '\\' ), '', $table_name );
		if ( 1 !== preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
			return '';
		}

		return $table_name;
	}

	private static function ensure_tables_ready() {
		if ( self::$tables_checked ) {
			return;
		}

		$latest_table  = self::latest_table_name();
		$history_table = self::history_table_name();

		if ( ! self::table_exists( $latest_table ) || ! self::table_exists( $history_table ) ) {
			self::activate();
		}

		self::$tables_checked = true;
	}

	public static function get_tables_status() {
		$latest_table   = self::latest_table_name();
		$history_table  = self::history_table_name();
		$latest_exists  = self::table_exists( $latest_table );
		$history_exists = self::table_exists( $history_table );

		return array(
			'latest'  => array(
				'name'   => self::mask_table_name_for_display( $latest_table ),
				'exists' => $latest_exists,
			),
			'history' => array(
				'name'   => self::mask_table_name_for_display( $history_table ),
				'exists' => $history_exists,
			),
			'ready'   => $latest_exists && $history_exists,
		);
	}

	private static function mask_table_name_for_display( $table_name ) {
		global $wpdb;

		$name   = (string) $table_name;
		$prefix = (string) ( $wpdb->prefix ?? '' );

		if ( '' !== $prefix && 0 === strpos( $name, $prefix ) ) {
			return 'xxx_' . substr( $name, strlen( $prefix ) );
		}

		return 'xxx_' . ltrim( $name, '_' );
	}

	public static function get_priority_page_ids() {
		$settings     = get_option( ASNERISSEO_Admin_Settings::OPT, array() );
		$priority_ids = isset( $settings['priority_page_ids'] ) && is_array( $settings['priority_page_ids'] )
		? array_map( 'absint', $settings['priority_page_ids'] )
		: array();

		$priority_ids = array_values( array_unique( array_filter( $priority_ids ) ) );

		return array_slice( $priority_ids, 0, self::MAX_PRIORITY_PAGES );
	}

	public static function is_priority_page( $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return false;
		}

		return in_array( $post_id, self::get_priority_page_ids(), true );
	}

	public static function build_content_hash( WP_Post $post ) {
		$hash_payload = array(
			'title'            => (string) get_the_title( $post->ID ),
			'content'          => (string) $post->post_content,
			'excerpt'          => (string) $post->post_excerpt,
			'meta_title'       => (string) get_post_meta( $post->ID, '_ASNERISSEO_title', true ),
			'meta_description' => (string) get_post_meta( $post->ID, '_ASNERISSEO_description', true ),
			'canonical'        => (string) get_post_meta( $post->ID, '_ASNERISSEO_canonical', true ),
			'robots_index'     => (string) get_post_meta( $post->ID, '_ASNERISSEO_robots_index', true ),
			'robots_follow'    => (string) get_post_meta( $post->ID, '_ASNERISSEO_robots_follow', true ),
			'modified_gmt'     => (string) $post->post_modified_gmt,
		);

		return hash( 'sha256', wp_json_encode( $hash_payload ) );
	}

	public static function should_scan( WP_Post $post ) {
		if ( 'publish' !== (string) $post->post_status ) {
			return false;
		}

		$latest = self::get_latest_snapshot( $post->ID );
		if ( empty( $latest ) ) {
			return true;
		}

		// Hash is the authoritative freshness signal because SEO fixes may update
		// post meta without changing post_modified_gmt.
		$current_hash = self::build_content_hash( $post );
		$stored_hash  = isset( $latest['content_hash'] ) ? (string) $latest['content_hash'] : '';

		if ( '' === $stored_hash || ! hash_equals( $stored_hash, $current_hash ) ) {
			return true;
		}

		$last_scan_raw    = isset( $latest['last_scan_gmt'] ) ? (string) $latest['last_scan_gmt'] : '';
		$last_scan_ts     = self::parse_utc_timestamp( $last_scan_raw );
		$post_modified_ts = self::parse_utc_timestamp( (string) $post->post_modified_gmt );

		// Skip if post is older than the latest scan or unchanged at the same timestamp.
		if ( $last_scan_ts > 0 && $post_modified_ts > 0 && $post_modified_ts <= $last_scan_ts ) {
			return false;
		}

		$stored_modified = isset( $latest['post_modified_gmt'] ) ? (string) $latest['post_modified_gmt'] : '';
		$post_modified   = gmdate( 'Y-m-d H:i:s', strtotime( (string) $post->post_modified_gmt ) );

		return $stored_modified !== $post_modified;
	}

	private static function parse_utc_timestamp( $value ) {
		$raw = trim( (string) $value );
		if ( '' === $raw ) {
			return 0;
		}

		$timestamp = strtotime( $raw . ' UTC' );
		if ( false === $timestamp ) {
			return 0;
		}

		return (int) $timestamp;
	}

	public static function run_priority_pages_scan_cron( $limit = null ) {
		self::ensure_tables_ready();

		$frequency = self::get_scan_cron_frequency();
		if ( 'disabled' === $frequency ) {
			return array(
				'processed' => 0,
				'scanned'   => 0,
				'skipped'   => 0,
			);
		}

		$priority_ids = self::get_priority_page_ids();
		if ( empty( $priority_ids ) ) {
			return array(
				'processed' => 0,
				'scanned'   => 0,
				'skipped'   => 0,
			);
		}

		if ( ! class_exists( 'ASNERISSEO_Diagnostics' ) ) {
			return array(
				'processed' => 0,
				'scanned'   => 0,
				'skipped'   => count( $priority_ids ),
			);
		}

		$max_to_scan = is_numeric( $limit ) ? absint( $limit ) : count( $priority_ids );
		if ( $max_to_scan < 1 ) {
			$max_to_scan = count( $priority_ids );
		}
		$max_to_scan = min( self::MAX_PRIORITY_PAGES, $max_to_scan );

		$processed = 0;
		$scanned   = 0;
		$skipped   = 0;

		foreach ( array_slice( $priority_ids, 0, $max_to_scan ) as $post_id ) {
			++$processed;

			$post = get_post( (int) $post_id );
			if ( ! ( $post instanceof WP_Post ) || 'publish' !== (string) $post->post_status ) {
				++$skipped;
				continue;
			}

			if ( ! self::should_scan( $post ) ) {
				++$skipped;
				continue;
			}

			$url = get_permalink( $post->ID );
			if ( ! is_string( $url ) || '' === $url ) {
				++$skipped;
				continue;
			}

			$checks = ASNERISSEO_Diagnostics::http_test_checks( $url );
			if ( is_wp_error( $checks ) || ! is_array( $checks ) ) {
				++$skipped;
				continue;
			}

			$score_override = null;
			if ( class_exists( 'ASNERISSEO_Page_Diagnostics_REST_API_Migration' ) && method_exists( 'ASNERISSEO_Page_Diagnostics_REST_API_Migration', 'build_weightage_score_override_for_post' ) ) {
				$score_override = ASNERISSEO_Page_Diagnostics_REST_API_Migration::build_weightage_score_override_for_post( $post, $checks );
			}

			self::save_snapshot( $post, $checks, $url, $score_override );
			++$scanned;
		}

		return array(
			'processed' => $processed,
			'scanned'   => $scanned,
			'skipped'   => $skipped,
		);
	}

	public static function get_scan_cron_details() {
		self::ensure_scan_cron_scheduled();

		$selected_frequency = self::get_scan_cron_frequency();
		if ( 'disabled' === $selected_frequency ) {
			return array(
				'frequency'    => 'disabled',
				'status'       => 'disabled',
				'next_run_gmt' => '',
			);
		}

		$scheduled_frequency = (string) wp_get_schedule( self::SCAN_CRON_HOOK );
		$next_run            = wp_next_scheduled( self::SCAN_CRON_HOOK );

		if ( ! $next_run ) {
			return array(
				'frequency'    => $selected_frequency,
				'status'       => 'not_scheduled',
				'next_run_gmt' => '',
			);
		}

		if ( '' !== $scheduled_frequency && $scheduled_frequency !== $selected_frequency ) {
			return array(
				'frequency'    => $selected_frequency,
				'status'       => 'schedule_mismatch',
				'next_run_gmt' => gmdate( 'Y-m-d H:i:s', (int) $next_run ),
			);
		}

		return array(
			'frequency'    => $selected_frequency,
			'status'       => 'scheduled',
			'next_run_gmt' => gmdate( 'Y-m-d H:i:s', (int) $next_run ),
		);
	}

	public static function refresh_scan_cron_schedule() {
		self::ensure_scan_cron_scheduled();
	}

	private static function ensure_scan_cron_scheduled() {
		$selected_frequency  = self::get_scan_cron_frequency();
		$next                = wp_next_scheduled( self::SCAN_CRON_HOOK );
		$scheduled_frequency = wp_get_schedule( self::SCAN_CRON_HOOK );

		if ( 'disabled' === $selected_frequency ) {
			if ( $next ) {
				wp_clear_scheduled_hook( self::SCAN_CRON_HOOK );
			}
			return;
		}

		$available_schedules = wp_get_schedules();
		if ( ! isset( $available_schedules[ $selected_frequency ] ) ) {
			$selected_frequency = 'daily';
		}

		if ( $next && $scheduled_frequency !== $selected_frequency ) {
			wp_clear_scheduled_hook( self::SCAN_CRON_HOOK );
			$next = false;
		}

		if ( $next && 'hourly' === $selected_frequency && (int) $next > ( time() + ( 2 * HOUR_IN_SECONDS ) ) ) {
			wp_clear_scheduled_hook( self::SCAN_CRON_HOOK );
			$next = false;
		}

		if ( ! $next ) {
			$start_timestamp = self::get_next_scan_start_timestamp_utc( $selected_frequency );
			wp_schedule_event( $start_timestamp, $selected_frequency, self::SCAN_CRON_HOOK );
		}
	}

	private static function get_next_scan_start_timestamp_utc( $selected_frequency ) {
		if ( 'hourly' === $selected_frequency ) {
			return time() + HOUR_IN_SECONDS;
		}

		return self::get_next_site_anchor_timestamp_utc( 2, 0 );
	}

	private static function get_next_site_anchor_timestamp_utc( $hour, $minute = 0 ) {
		$hour   = max( 0, min( 23, (int) $hour ) );
		$minute = max( 0, min( 59, (int) $minute ) );

		$timezone = function_exists( 'wp_timezone' )
		? wp_timezone()
		: new DateTimeZone( 'UTC' );

		$now    = new DateTimeImmutable( 'now', $timezone );
		$anchor = $now->setTime( $hour, $minute, 0 );

		if ( $anchor <= $now ) {
			$anchor = $anchor->modify( '+1 day' );
		}

		return (int) $anchor->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'U' );
	}

	public static function get_scan_cron_frequency() {
		$settings = get_option( ASNERISSEO_Admin_Settings::OPT, array() );
		$value    = isset( $settings['page_diagnostics_scan_cron_frequency'] )
		? $settings['page_diagnostics_scan_cron_frequency']
		: 'disabled';

		return self::normalize_scan_cron_frequency( $value );
	}

	private static function normalize_scan_cron_frequency( $value ) {
		$value = sanitize_key( (string) $value );
		if ( ! in_array( $value, array( 'disabled', 'hourly', 'daily', 'weekly', 'monthly' ), true ) ) {
			$value = 'disabled';
		}

		return $value;
	}

	public static function save_snapshot( WP_Post $post, array $checks, $url, $score_override = null ) {
		global $wpdb;

		self::ensure_tables_ready();

		$now_mysql              = gmdate( 'Y-m-d H:i:s' );
		$content_hash           = self::build_content_hash( $post );
		$scores                 = self::derive_scores_from_checks( $checks );
		$issue_groups           = self::derive_issue_groups_from_checks( $checks );
		$ai_score               = 0;
		$overview_issue_records = array();
		$ai_issue_records       = array();
		$ai_canonical_signals   = array();
		$tab_issue_records      = array();
		$overview_run_id        = '';
		$seo_score_message      = '';
		$score_engine           = 'weightage_policy_v4_1';

		if ( ! is_array( $score_override ) && class_exists( 'ASNERISSEO_Page_Diagnostics_REST_API_Migration' ) && method_exists( 'ASNERISSEO_Page_Diagnostics_REST_API_Migration', 'build_weightage_score_override_for_post' ) ) {
			$score_override = ASNERISSEO_Page_Diagnostics_REST_API_Migration::build_weightage_score_override_for_post( $post, $checks );
		}

		if ( is_array( $score_override ) ) {
			if ( isset( $score_override['seoScore'] ) && is_numeric( $score_override['seoScore'] ) ) {
				$scores['seoScore'] = max( 0, min( 100, (int) $score_override['seoScore'] ) );
			}

			if ( isset( $score_override['aiScore'] ) && is_numeric( $score_override['aiScore'] ) ) {
				$ai_score = max( 0, min( 100, (int) $score_override['aiScore'] ) );
			}

			if ( isset( $score_override['health'] ) ) {
				$override_health = sanitize_key( (string) $score_override['health'] );
				if ( in_array( $override_health, array( 'good', 'warning', 'poor' ), true ) ) {
					$scores['health'] = $override_health;
				}
			}

			if ( isset( $score_override['issueGroups'] ) && is_array( $score_override['issueGroups'] ) ) {
				$issue_groups = $score_override['issueGroups'];
			}

			if ( isset( $score_override['overviewIssueRecords'] ) && is_array( $score_override['overviewIssueRecords'] ) ) {
				$overview_issue_records = $score_override['overviewIssueRecords'];
			}
			$overview_score_records = array();
			if ( isset( $score_override['overviewScoreRecords'] ) && is_array( $score_override['overviewScoreRecords'] ) ) {
				$overview_score_records = $score_override['overviewScoreRecords'];
			}

			if ( isset( $score_override['aiIssueRecords'] ) && is_array( $score_override['aiIssueRecords'] ) ) {
				$ai_issue_records = $score_override['aiIssueRecords'];
			}

			if ( isset( $score_override['aiCanonicalSignals'] ) && is_array( $score_override['aiCanonicalSignals'] ) ) {
				$ai_canonical_signals = $score_override['aiCanonicalSignals'];
			}

			if ( isset( $score_override['tabIssueRecords'] ) && is_array( $score_override['tabIssueRecords'] ) ) {
				$tab_issue_records = $score_override['tabIssueRecords'];
			}

			if ( isset( $score_override['overviewRunId'] ) ) {
				$overview_run_id = sanitize_text_field( (string) $score_override['overviewRunId'] );
			}

			if ( isset( $score_override['seoScoreMessage'] ) ) {
				$seo_score_message = sanitize_text_field( (string) $score_override['seoScoreMessage'] );
			}

			if ( isset( $score_override['scoreEngine'] ) ) {
				$score_engine = sanitize_key( (string) $score_override['scoreEngine'] );
			} else {
				$score_engine = 'weightage_policy_v4_1';
			}
		}

		if ( is_array( $tab_issue_records ) && ! empty( $tab_issue_records ) ) {
			$tab_issue_records = self::normalize_tab_issue_records( $tab_issue_records, $overview_issue_records, $ai_issue_records, $checks );
		} else {
			$tab_issue_records = self::build_tab_issue_records( $overview_issue_records, $ai_issue_records, $checks );
		}

		$report = array(
			'postId'               => (int) $post->ID,
			'url'                  => esc_url_raw( (string) $url ),
			'seoScore'             => (int) $scores['seoScore'],
			'aiScore'              => (int) $ai_score,
			'health'               => (string) $scores['health'],
			'issueGroups'          => $issue_groups,
			'overviewIssueRecords' => $overview_issue_records,
			'overviewScoreRecords' => $overview_score_records,
			'aiIssueRecords'       => $ai_issue_records,
			'aiCanonicalSignals'   => $ai_canonical_signals,
			'tabIssueRecords'      => $tab_issue_records,
			'tabModels'            => isset( $score_override['tabModels'] ) && is_array( $score_override['tabModels'] ) ? $score_override['tabModels'] : array(),
			'overviewRunId'        => $overview_run_id,
			'seoScoreMessage'      => $seo_score_message,
			'scoreEngine'          => $score_engine,
			'checks'               => $checks,
			'generatedAtGmt'       => gmdate( 'c' ),
			'contentHash'          => $content_hash,
			'postModifiedGmt'      => get_post_modified_time( 'c', true, $post->ID ),
		);

		if ( is_array( $score_override ) ) {
			$snapshot_context_keys = array(
				'title',
				'postType',
				'postStatus',
				'author',
				'isDraftQualityOnly',
				'publishedGmt',
				'modifiedGmt',
				'lastScanGmt',
				'metaTitle',
				'metaTitleLength',
				'seoTitle',
				'metaSummary',
				'metaDescription',
				'seoDescription',
				'excerpt',
				'ogTitle',
				'ogDescription',
				'ogImage',
				'ogImageDisabled',
				'hasCustomTitle',
				'hasCustomDescription',
				'effectiveTitle',
				'titleLength',
				'effectiveDescription',
				'descriptionLength',
				'effectiveTitleLength',
				'effectiveDescriptionLength',
				'canonical',
				'hasCanonical',
				'canonicalCount',
				'robotsIndex',
				'robotsFollow',
				'xRobotsTag',
				'httpStatus',
				'contentWords',
				'h1Count',
				'h2Count',
				'faqCount',
				'schemaEnabled',
				'schemaType',
				'organizationSchema',
				'breadcrumbSchema',
				'internalLinks',
				'externalLinks',
				'nofollowLinks',
				'imageCount',
				'imagesMissingAlt',
				'imagesEmptyAlt',
				'featuredImage',
				'languageDeclaration',
				'tabModels',
			);

			foreach ( $snapshot_context_keys as $snapshot_context_key ) {
				if ( array_key_exists( $snapshot_context_key, $score_override ) ) {
					$report[ $snapshot_context_key ] = $score_override[ $snapshot_context_key ];
				}
			}
		}

		$report_json = wp_json_encode( $report );
		if ( ! is_string( $report_json ) || '' === $report_json ) {
			$report_json = '{}';
		}

		$latest_table  = self::latest_table_name();
		$history_table = self::history_table_name();

		$row_data = array(
			'page_id'           => (int) $post->ID,
			'seo_score'         => (int) $scores['seoScore'],
			'health'            => (string) $scores['health'],
			'content_hash'      => $content_hash,
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( (string) $post->post_modified_gmt ) ),
			'last_scan_gmt'     => $now_mysql,
			'report_json'       => $report_json,
			'updated_at'        => $now_mysql,
		);

		$existing = self::get_latest_snapshot( $post->ID );
		if ( empty( $existing ) ) {
			$row_data['created_at'] = $now_mysql;
			$wpdb->insert(
				$latest_table,
				$row_data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		} else {
			$wpdb->update(
				$latest_table,
				$row_data,
				array( 'page_id' => (int) $post->ID ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}

		if ( self::is_priority_page( $post->ID ) ) {
			$wpdb->insert(
				$history_table,
				array(
					'page_id'     => (int) $post->ID,
					'seo_score'   => (int) $scores['seoScore'],
					'health'      => (string) $scores['health'],
					'report_json' => $report_json,
					'created_at'  => $now_mysql,
				),
				array( '%d', '%d', '%s', '%s', '%s' )
			);

			self::prune_history_for_page( $post->ID );
		}

		update_post_meta( $post->ID, '_ASNERISSEO_last_diagnostics_scan_gmt', gmdate( 'c' ) );

		return $report;
	}

	public static function get_latest_snapshot( $post_id ) {
		global $wpdb;

		self::ensure_tables_ready();

		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return null;
		}

		$table = self::latest_table_name();
	  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared query against internal plugin table.
	  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name comes from internal plugin table resolver.
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE page_id = %d", $post_id ),
			ARRAY_A
		);
	}

	public static function get_latest_snapshot_report( $post_id ) {
		$row = self::get_latest_snapshot( $post_id );
		if ( empty( $row ) || empty( $row['report_json'] ) ) {
			return null;
		}

		$decoded = json_decode( (string) $row['report_json'], true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return self::with_tab_issue_records( $decoded );
	}

	public static function get_snapshot_history( $post_id, $limit = 10 ) {
		global $wpdb;

		self::ensure_tables_ready();

		$post_id = absint( $post_id );
		$limit   = absint( $limit );
		if ( $post_id < 1 ) {
			return array();
		}
		if ( $limit < 1 ) {
			$limit = 10;
		}
		$limit = min( 50, $limit );

		$table = self::history_table_name();
	  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared query against internal plugin table.
	  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name comes from internal plugin table resolver.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, page_id, seo_score, health, report_json, created_at FROM {$table} WHERE page_id = %d ORDER BY created_at DESC LIMIT %d",
				$post_id,
				$limit
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				$decoded = array();
				if ( ! empty( $row['report_json'] ) ) {
					$parsed = json_decode( (string) $row['report_json'], true );
					if ( is_array( $parsed ) ) {
						$decoded = $parsed;
					}
				}

				return array_merge(
					$decoded,
					array(
						'id'                   => (int) ( $row['id'] ?? 0 ),
						'pageId'               => (int) ( $row['page_id'] ?? 0 ),
						'seoScore'             => isset( $row['seo_score'] ) ? (int) $row['seo_score'] : 0,
						'health'               => isset( $row['health'] ) ? sanitize_key( (string) $row['health'] ) : 'warning',
						'createdAt'            => isset( $row['created_at'] ) ? (string) $row['created_at'] : '',
						'generatedAtGmt'       => isset( $decoded['generatedAtGmt'] ) ? (string) $decoded['generatedAtGmt'] : '',
						'issueGroups'          => isset( $decoded['issueGroups'] ) && is_array( $decoded['issueGroups'] ) ? $decoded['issueGroups'] : array(),
						'overviewIssueRecords' => isset( $decoded['overviewIssueRecords'] ) && is_array( $decoded['overviewIssueRecords'] )
								? array_values( $decoded['overviewIssueRecords'] )
								: array(),
						'overviewScoreRecords' => isset( $decoded['overviewScoreRecords'] ) && is_array( $decoded['overviewScoreRecords'] )
								? array_values( $decoded['overviewScoreRecords'] )
								: array(),
						'aiIssueRecords'       => isset( $decoded['aiIssueRecords'] ) && is_array( $decoded['aiIssueRecords'] )
								? array_values( $decoded['aiIssueRecords'] )
								: array(),
						'aiCanonicalSignals'   => isset( $decoded['aiCanonicalSignals'] ) && is_array( $decoded['aiCanonicalSignals'] )
								? $decoded['aiCanonicalSignals']
								: array(),
						'tabIssueRecords'      => self::normalize_tab_issue_records(
							isset( $decoded['tabIssueRecords'] ) && is_array( $decoded['tabIssueRecords'] ) ? $decoded['tabIssueRecords'] : array(),
							isset( $decoded['overviewIssueRecords'] ) && is_array( $decoded['overviewIssueRecords'] ) ? $decoded['overviewIssueRecords'] : array(),
							isset( $decoded['aiIssueRecords'] ) && is_array( $decoded['aiIssueRecords'] ) ? $decoded['aiIssueRecords'] : array(),
							isset( $decoded['checks'] ) && is_array( $decoded['checks'] ) ? $decoded['checks'] : array()
						),
						'tabModels'            => isset( $decoded['tabModels'] ) && is_array( $decoded['tabModels'] ) ? $decoded['tabModels'] : array(),
						'checks'               => isset( $decoded['checks'] ) && is_array( $decoded['checks'] ) ? array_values( $decoded['checks'] ) : array(),
					)
				);
			},
			$rows
		);
	}

	private static function with_tab_issue_records( array $report ) {
		$overview_issue_records = isset( $report['overviewIssueRecords'] ) && is_array( $report['overviewIssueRecords'] )
		? $report['overviewIssueRecords']
		: array();
		$ai_issue_records       = isset( $report['aiIssueRecords'] ) && is_array( $report['aiIssueRecords'] )
		? $report['aiIssueRecords']
		: array();

		if ( isset( $report['tabIssueRecords'] ) && is_array( $report['tabIssueRecords'] ) ) {
			$report['tabIssueRecords'] = self::normalize_tab_issue_records(
				$report['tabIssueRecords'],
				$overview_issue_records,
				$ai_issue_records,
				isset( $report['checks'] ) && is_array( $report['checks'] ) ? $report['checks'] : array()
			);
			return $report;
		}

		$report['tabIssueRecords'] = self::build_tab_issue_records(
			$overview_issue_records,
			$ai_issue_records,
			isset( $report['checks'] ) && is_array( $report['checks'] ) ? $report['checks'] : array()
		);
		return $report;
	}

	private static function get_empty_tab_issue_records() {
		$tab_records = array();
		foreach ( self::$canonical_tab_field_registry as $tab_key => $fields ) {
			$tab_records[ $tab_key ] = array();
		}

		return $tab_records;
	}

	private static function normalize_issue_field_label( $label ) {
		$normalized = strtolower( trim( (string) $label ) );
		if ( '' === $normalized ) {
			return '';
		}

		$normalized = preg_replace( '/[^a-z0-9]+/', ' ', $normalized );
		if ( ! is_string( $normalized ) ) {
			return '';
		}

		return trim( preg_replace( '/\s+/', ' ', $normalized ) );
	}

	private static function normalize_tab_issue_records( array $tab_issue_records, array $overview_issue_records, array $ai_issue_records, array $checks = array() ) {
		$normalized = self::get_empty_tab_issue_records();

		foreach ( $normalized as $tab_key => $rows ) {
			if ( isset( $tab_issue_records[ $tab_key ] ) && is_array( $tab_issue_records[ $tab_key ] ) ) {
				$normalized[ $tab_key ] = array_values( $tab_issue_records[ $tab_key ] );
			}
		}

		if ( empty( $normalized['overview'] ) && ! empty( $overview_issue_records ) ) {
			$normalized['overview'] = array_values( $overview_issue_records );
		}

		if ( empty( $normalized['aiDiscoverability'] ) && ! empty( $ai_issue_records ) ) {
			$normalized['aiDiscoverability'] = array_values( $ai_issue_records );
		}

		return self::materialize_missing_tab_issue_records( $normalized, $checks );
	}

	private static function build_tab_issue_records( array $overview_issue_records, array $ai_issue_records, array $checks = array() ) {
		$tab_records                      = self::get_empty_tab_issue_records();
		$tab_records['overview']          = array_values( $overview_issue_records );
		$tab_records['aiDiscoverability'] = array_values( $ai_issue_records );

		$field_map = array();
		foreach ( self::$canonical_tab_field_registry as $tab_key => $fields ) {
			if ( 'overview' === $tab_key || 'aiDiscoverability' === $tab_key ) {
				continue;
			}

			$field_map[ $tab_key ] = array();
			foreach ( $fields as $field_label ) {
				$normalized_label = self::normalize_issue_field_label( $field_label );
				if ( '' !== $normalized_label ) {
					$field_map[ $tab_key ][ $normalized_label ] = true;
				}
			}
		}

		foreach ( $overview_issue_records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$canonical_field  = isset( $record['canonical_field'] ) ? $record['canonical_field'] : '';
			$normalized_field = self::normalize_issue_field_label( $canonical_field );
			if ( '' === $normalized_field ) {
				continue;
			}

			foreach ( $field_map as $tab_key => $field_lookup ) {
				if ( isset( $field_lookup[ $normalized_field ] ) ) {
					$tab_records[ $tab_key ][] = $record;
				}
			}
		}

		return self::materialize_missing_tab_issue_records( $tab_records, $checks );
	}

	private static function normalize_check_status( $status ) {
		$normalized = sanitize_key( (string) $status );
		if ( 'warn' === $normalized ) {
			return 'warning';
		}

		if ( in_array( $normalized, array( 'pass', 'warning', 'fail' ), true ) ) {
			return $normalized;
		}

		return 'pass';
	}

	private static function detect_tab_default_category( $tab_key ) {
		$key = sanitize_key( (string) $tab_key );
		if ( 'searchappearance' === $key || 'search_appearance' === $key ) {
			return 'meta';
		}

		if ( 'indexability' === $key ) {
			return 'indexability';
		}

		if ( 'aidiscoverability' === $key || 'ai_discoverability' === $key ) {
			return 'ai';
		}

		if ( 'images' === $key ) {
			return 'images';
		}

		if ( 'links' === $key ) {
			return 'links';
		}

		if ( 'structureddata' === $key || 'structured_data' === $key ) {
			return 'schema';
		}

		return 'content';
	}

	private static function find_best_check_for_canonical_field( array $checks, $canonical_field, $preferred_tab = '' ) {
		$target = self::normalize_issue_field_label( $canonical_field );
		if ( '' === $target ) {
			return null;
		}

		$target_tokens      = array_values( array_filter( explode( ' ', $target ), 'strlen' ) );
		$best               = null;
		$best_score         = -1;
		$preferred_category = self::detect_tab_default_category( $preferred_tab );

		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) ) {
				continue;
			}

			$label = self::normalize_issue_field_label( isset( $check['label'] ) ? $check['label'] : '' );
			if ( '' === $label ) {
				continue;
			}

			$score = 0;
			if ( $label === $target ) {
				$score += 1000;
			} elseif ( false !== strpos( $label, $target ) || false !== strpos( $target, $label ) ) {
				$score += 600;
			}

			$label_tokens = array_values( array_filter( explode( ' ', $label ), 'strlen' ) );
			if ( ! empty( $target_tokens ) && ! empty( $label_tokens ) ) {
				$overlap = array_intersect( $target_tokens, $label_tokens );
				$score  += count( $overlap ) * 80;
				$score  -= abs( count( $target_tokens ) - count( $label_tokens ) ) * 5;
			}

			$check_category = sanitize_key( (string) ( $check['category'] ?? '' ) );
			if ( '' !== $preferred_category && $check_category === $preferred_category ) {
				$score += 40;
			}

			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = $check;
			}
		}

		if ( $best_score < 80 ) {
			return null;
		}

		return $best;
	}

	private static function build_materialized_issue_record( $tab_key, $canonical_field, array $check = null, $run_id = '' ) {
		$status        = 'pass';
		$raw_evidence  = array();
		$linked_fields = array();
		$category      = self::detect_tab_default_category( $tab_key );

		if ( is_array( $check ) ) {
			$status         = self::normalize_check_status( isset( $check['status'] ) ? $check['status'] : 'pass' );
			$check_category = sanitize_key( (string) ( $check['category'] ?? '' ) );
			if ( '' !== $check_category ) {
				$category = $check_category;
			}

			if ( isset( $check['label'] ) ) {
				$raw_evidence['checkLabel'] = (string) $check['label'];
			}
			if ( isset( $check['result'] ) ) {
				$raw_evidence['checkResult'] = is_scalar( $check['result'] ) ? (string) $check['result'] : '';
			}
			$raw_evidence['checkStatus'] = $status;

			if ( isset( $check['rawEvidenceFields'] ) && is_array( $check['rawEvidenceFields'] ) ) {
				foreach ( $check['rawEvidenceFields'] as $evidence_field ) {
					$linked_field = trim( (string) $evidence_field );
					if ( '' !== $linked_field ) {
						$linked_fields[] = $linked_field;
					}
				}
			} elseif ( isset( $check['label'] ) ) {
				$linked_fields[] = (string) $check['label'];
			}
		}

		$linked_fields = array_values( array_filter( array_map( 'strval', $linked_fields ), 'strlen' ) );

		return array(
			'run_id'                     => (string) $run_id,
			'category'                   => (string) $category,
			'canonical_field'            => (string) $canonical_field,
			'canonical_status'           => (string) $status,
			'linked_raw_evidence_fields' => $linked_fields,
			'raw_evidence'               => $raw_evidence,
			'score_impact'               => 0,
			'recommended_fix'            => '',
		);
	}

	private static function materialize_missing_tab_issue_records( array $tab_records, array $checks = array() ) {
		$normalized_records = self::get_empty_tab_issue_records();

		foreach ( $normalized_records as $tab_key => $rows ) {
			$existing_rows = isset( $tab_records[ $tab_key ] ) && is_array( $tab_records[ $tab_key ] )
			? array_values( $tab_records[ $tab_key ] )
			: array();

			$run_id = '';
			foreach ( $existing_rows as $row ) {
				if ( is_array( $row ) && isset( $row['run_id'] ) && '' !== trim( (string) $row['run_id'] ) ) {
					$run_id = (string) $row['run_id'];
					break;
				}
			}

			$index_by_field = array();
			foreach ( $existing_rows as $index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$field = self::normalize_issue_field_label( isset( $row['canonical_field'] ) ? $row['canonical_field'] : '' );
				if ( '' === $field ) {
					continue;
				}

				if ( ! isset( $index_by_field[ $field ] ) ) {
					$index_by_field[ $field ] = $index;
				}
			}

			$expected_fields = isset( self::$canonical_tab_field_registry[ $tab_key ] ) && is_array( self::$canonical_tab_field_registry[ $tab_key ] )
			? self::$canonical_tab_field_registry[ $tab_key ]
			: array();

			foreach ( $expected_fields as $expected_field ) {
				$expected_key = self::normalize_issue_field_label( $expected_field );
				if ( '' === $expected_key ) {
					continue;
				}

				if ( isset( $index_by_field[ $expected_key ] ) ) {
					continue;
				}

				$best_check      = self::find_best_check_for_canonical_field( $checks, $expected_field, $tab_key );
				$existing_rows[] = self::build_materialized_issue_record( $tab_key, $expected_field, $best_check, $run_id );
			}

			$normalized_records[ $tab_key ] = $existing_rows;
		}

		return $normalized_records;
	}

	public static function get_history_count( $post_id ) {
		global $wpdb;

		self::ensure_tables_ready();

		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return 0;
		}

		$table = self::history_table_name();
	  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared aggregate query against internal plugin table.
	  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name comes from internal plugin table resolver.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE page_id = %d",
				$post_id
			)
		);

		return (int) $count;
	}

	public static function delete_history_record( $post_id, $history_id ) {
		global $wpdb;

		self::ensure_tables_ready();

		$post_id    = absint( $post_id );
		$history_id = absint( $history_id );
		if ( $post_id < 1 || $history_id < 1 ) {
			return 0;
		}

		$table = self::history_table_name();
		return (int) $wpdb->delete(
			$table,
			array(
				'id'      => $history_id,
				'page_id' => $post_id,
			),
			array( '%d', '%d' )
		);
	}

	public static function delete_page_records( $post_id ) {
		global $wpdb;

		self::ensure_tables_ready();

		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return array(
				'latestDeleted'  => 0,
				'historyDeleted' => 0,
			);
		}

		$latest_deleted = (int) $wpdb->delete(
			self::latest_table_name(),
			array( 'page_id' => $post_id ),
			array( '%d' )
		);

		$history_deleted = (int) $wpdb->delete(
			self::history_table_name(),
			array( 'page_id' => $post_id ),
			array( '%d' )
		);

		delete_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt' );

		return array(
			'latestDeleted'  => $latest_deleted,
			'historyDeleted' => $history_deleted,
		);
	}

	public static function prune_history_for_page( $post_id ) {
		global $wpdb;

		self::ensure_tables_ready();

		$post_id = absint( $post_id );
		if ( $post_id < 1 ) {
			return;
		}

		$table      = self::history_table_name();
		$days_limit = self::DEFAULT_HISTORY_DAYS;

	  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared retention cleanup query.
	  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name comes from internal plugin table resolver.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE page_id = %d AND created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
				$post_id,
				$days_limit
			)
		);

	  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared ID lookup query.
	  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name comes from internal plugin table resolver.
		$history_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE page_id = %d ORDER BY created_at DESC LIMIT 18446744073709551615 OFFSET %d",
				$post_id,
				self::DEFAULT_HISTORY_LIMIT
			)
		);

		if ( ! empty( $history_ids ) ) {
			$history_ids = array_map( 'absint', $history_ids );
			$history_ids = array_filter( $history_ids );

			if ( ! empty( $history_ids ) ) {
				foreach ( $history_ids as $history_id ) {
					$wpdb->delete(
						$table,
						array( 'id' => (int) $history_id ),
						array( '%d' )
					);
				}
			}
		}
	}

	public static function run_retention_cleanup() {
		global $wpdb;

		self::ensure_tables_ready();

		$history_table = self::history_table_name();

	  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Prepared global retention cleanup query.
	  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name comes from internal plugin table resolver.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$history_table} WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL %d DAY)",
				self::DEFAULT_HISTORY_DAYS
			)
		);

		$priority_ids = self::get_priority_page_ids();

		if ( empty( $priority_ids ) ) {
			return;
		}

		foreach ( $priority_ids as $page_id ) {
			self::prune_history_for_page( (int) $page_id );
		}
	}

	private static function derive_scores_from_checks( array $checks ) {
		if ( count( $checks ) < 1 ) {
			return array(
				'seoScore' => 0,
				'health'   => 'poor',
			);
		}

		$canonical_buckets = array();
		foreach ( $checks as $check ) {
			$canonical_field = self::resolve_canonical_field_from_check( $check );
			if ( '' === $canonical_field ) {
				continue;
			}

			if ( ! isset( $canonical_buckets[ $canonical_field ] ) ) {
				$canonical_buckets[ $canonical_field ] = array(
					'sum'   => 0.0,
					'count' => 0,
				);
			}

			$canonical_buckets[ $canonical_field ]['sum'] += self::status_to_points(
				isset( $check['status'] ) ? strtolower( (string) $check['status'] ) : 'fail'
			);
			++$canonical_buckets[ $canonical_field ]['count'];
		}

		if ( empty( $canonical_buckets ) ) {
			return array(
				'seoScore' => 0,
				'health'   => 'poor',
			);
		}

		$canonical_total  = 0;
		$canonical_scored = 0.0;
		foreach ( $canonical_buckets as $bucket ) {
			$count = isset( $bucket['count'] ) ? (int) $bucket['count'] : 0;
			if ( $count < 1 ) {
				continue;
			}

			$average = ( (float) $bucket['sum'] ) / $count;
			++$canonical_total;
			if ( $average >= 0.85 ) {
				$canonical_scored += 1.0;
			} elseif ( $average >= 0.5 ) {
				$canonical_scored += 0.5;
			}
		}

		if ( $canonical_total < 1 ) {
			return array(
				'seoScore' => 0,
				'health'   => 'poor',
			);
		}

		$score = (int) round( ( $canonical_scored / $canonical_total ) * 100 );
		$score = max( 0, min( 100, $score ) );

		$health = 'poor';
		if ( $score >= 85 ) {
			$health = 'good';
		} elseif ( $score >= 65 ) {
			$health = 'warning';
		}

		return array(
			'seoScore' => $score,
			'health'   => $health,
		);
	}

	private static function status_to_points( $status ) {
		if ( 'pass' === $status ) {
			return 1.0;
		}

		if ( 'warning' === $status || 'warn' === $status ) {
			return 0.5;
		}

		return 0.0;
	}

	private static function resolve_canonical_field_from_check( array $check ) {
		$label = isset( $check['label'] ) ? strtolower( trim( (string) $check['label'] ) ) : '';
		if ( '' === $label ) {
			return '';
		}

		$canonical_patterns = array(
			'Robots Meta'      => array( '/robots directives|robots meta|x-robots-tag|indexability|follow directive/' ),
			'HTTP Status'      => array( '/http status|redirect status|final destination|canonical target http 200/' ),
			'SEO Title'        => array( '/^seo title$|title quality|seo title length/' ),
			'Meta Description' => array( '/^meta description$|description quality|meta description length/' ),
			'H1 Heading'       => array( '/h1 exists|h1 presence|h1 present|multiple h1|heading structure|heading hierarchy|content depth|content present|readability/' ),
			'Internal Links'   => array( '/internal links|external links|nofollow links/' ),
		);

		foreach ( $canonical_patterns as $canonical => $patterns ) {
			foreach ( $patterns as $pattern ) {
				if ( preg_match( $pattern, $label ) ) {
					return $canonical;
				}
			}
		}

		return '';
	}

	private static function derive_issue_groups_from_checks( array $checks ) {
		$groups = array(
			'meta'         => false,
			'indexability' => false,
			'content'      => false,
			'ai'           => false,
		);

		foreach ( $checks as $check ) {
			$status = isset( $check['status'] ) ? strtolower( (string) $check['status'] ) : 'fail';
			if ( 'pass' === $status ) {
				continue;
			}

			$category = isset( $check['category'] ) ? sanitize_key( (string) $check['category'] ) : '';
			$label    = strtolower( (string) ( $check['label'] ?? '' ) );

			if ( in_array( $category, array( 'search', 'social', 'schema' ), true ) || preg_match( '/title|meta|description|open graph|twitter/', $label ) ) {
				$groups['meta'] = true;
			}

			if ( 'advanced' === $category || preg_match( '/index|canonical|robots|http|redirect/', $label ) ) {
				$groups['indexability'] = true;
			}

			if ( in_array( $category, array( 'quality', 'links' ), true ) || preg_match( '/word count|h1|h2|image|alt|link/', $label ) ) {
				$groups['content'] = true;
			}

			if ( 'ai' === $category || preg_match( '/ai|geo|readability|topic|faq|entity/', $label ) ) {
				$groups['ai'] = true;
			}
		}

		return $groups;
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
