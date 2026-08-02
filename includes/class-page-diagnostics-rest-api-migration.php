<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_Page_Diagnostics_REST_API_Migration {
	private static $canonical_tab_field_registry = array(
		'searchAppearance'  => array( 'SEO Title', 'SEO Title Length', 'Meta Description', 'Meta Description Length', 'Canonical' ),
		'indexability'      => array( 'Redirect Status', 'Final Destination', 'Canonical Exists', 'Self Canonical', 'Canonical Valid URL', 'Canonical Target HTTP 200', 'Robots Meta', 'X-Robots-Tag', 'HTTP Status', 'Indexability', 'Follow Directive' ),
		'contentQuality'    => array( 'SEO Title', 'SEO Title Length', 'Meta Description', 'Meta Description Length', 'H1 Presence', 'Multiple H1', 'Heading Structure', 'Heading Hierarchy', 'Content Depth (Word Count)', 'Content Present', 'Readability' ),
		'links'             => array( 'Internal Links', 'External Links', 'Nofollow Links' ),
		'images'            => array( 'Images Found', 'Image ALT Coverage', 'Missing ALT', 'Empty ALT', 'Featured Image' ),
		'structuredData'    => array( 'Schema Settings', 'Structured Data Found', 'Structured Data Present', 'Schema Validation', 'Primary Schema', 'Primary Entity', 'Organization Schema', 'Article Schema', 'FAQ Schema', 'Breadcrumb Schema' ),
		'aiDiscoverability' => array( 'Content Structure', 'Author Information', 'Machine Readability', 'Primary Entity', 'Topic Consistency', 'Clear Page Purpose', 'Summary Section', 'Content Completeness', 'Brand Mentions', 'Product/Context Mentions', 'Trust Signals', 'Structured Content', 'Table/List Detection', 'Definition Content', 'FAQ Ready', 'FAQ Content', 'FAQ Signals', 'Language Declaration', 'Internal References', 'External References', 'Published Date', 'Last Updated Date', 'Organization Information', 'Media Context' ),
	);

	private static $tab_score_weights = array(
		'searchAppearance'  => array(
			'SEO Title'               => 20,
			'Meta Description'        => 20,
			'SEO Title Length'        => 20,
			'Meta Description Length' => 20,
			'Canonical'               => 20,
		),
		'indexability'      => array(
			'HTTP Status'         => 20,
			'Robots Meta'         => 20,
			'Canonical Exists'    => 15,
			'Self Canonical'      => 15,
			'Canonical Valid URL' => 15,
			'X-Robots-Tag'        => 15,
		),
		'contentQuality'    => array(
			'SEO Title'                  => 25,
			'Meta Description'           => 25,
			'H1 Presence'                => 25,
			'Content Depth (Word Count)' => 25,
		),
		'links'             => array(
			'Internal Links' => 50,
			'External Links' => 30,
			'Nofollow Links' => 20,
		),
		'images'            => array(
			'Images Found'   => 30,
			'Missing ALT'    => 30,
			'Empty ALT'      => 20,
			'Featured Image' => 20,
		),
		'structuredData'    => array(
			'Structured Data Present' => 20,
			'Schema Validation'       => 20,
			'Organization Schema'     => 15,
			'Primary Schema'          => 15,
			'FAQ Schema'              => 15,
			'Breadcrumb Schema'       => 15,
		),
		'aiDiscoverability' => array(
			'Topic Consistency'        => 10,
			'Clear Page Purpose'       => 10,
			'Summary Section'          => 10,
			'Content Completeness'     => 10,
			'Brand Mentions'           => 10,
			'Product/Context Mentions' => 10,
			'Trust Signals'            => 10,
			'Table/List Detection'     => 10,
			'Definition Content'       => 10,
			'FAQ Signals'              => 10,
		),
	);

	public static function register_routes() {
		register_rest_route(
			ASNERISSEO_REST_API::NAMESPACE,
			'/page-diagnostics-v2/run/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'run_page_diagnostics_scan' ),
				'permission_callback' => array( 'ASNERISSEO_REST_API', 'can_edit_post' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			ASNERISSEO_REST_API::NAMESPACE,
			'/page-diagnostics-v2/draft-policy',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'evaluate_draft_policy' ),
				'permission_callback' => array( 'ASNERISSEO_REST_API', 'can_edit_posts' ),
			)
		);

		register_rest_route(
			ASNERISSEO_REST_API::NAMESPACE,
			'/page-diagnostics-v2/history/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_stored_page_diagnostics_history' ),
				'permission_callback' => array( 'ASNERISSEO_REST_API', 'can_edit_post' ),
				'args'                => array(
					'id'    => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
					'limit' => array(
						'validate_callback' => static function ( $value ) {
							return is_numeric( $value ) && (int) $value > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public static function run_page_diagnostics_scan( WP_REST_Request $request ) {
		$context = self::collect_published_request_context( $request );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$post_id       = (int) $context['postId'];
		$post          = $context['post'];
		$url           = (string) $context['url'];
		$overview_item = $context['overviewItem'];
		$force_refresh = ! empty( $context['forceRefresh'] );
		$no_store      = ! empty( $context['noStore'] );
		$is_priority   = ! empty( $context['isPriority'] );

		// UNIFIED DESIGN FIX #1: Always use fresh http_test_checks for priority pages
		// (never use cached snapshot data, ensures completeness metadata is current and accurate)
		$checks = ASNERISSEO_Diagnostics::http_test_checks( $url );
		if ( is_wp_error( $checks ) ) {
			return $checks;
		}

		$processed     = self::process_retrieved_diagnostics( $post, $overview_item, is_array( $checks ) ? $checks : array(), 'overview-live' );
		$overview_item = $processed['overviewItem'];
		$check_rows    = $processed['checks'];

		// UNIFIED DESIGN FIX #2: Extract canonical count from checks for top-level response
		$canonical_count = 0;
		foreach ( $check_rows as $check ) {
			if ( 'Canonical Exists' === ( $check['label'] ?? '' ) ) {
				if ( 'Multiple' === ( $check['result'] ?? '' ) ) {
					$canonical_count = 2;
				} elseif ( 'Present' === ( $check['result'] ?? '' ) ) {
					$canonical_count = 1;
				}
				break;
			}
		}

		if ( $is_priority ) {
			if ( $no_store ) {
				$payload = array_merge(
					$overview_item,
					array(
						'postId'      => $post_id,
						'url'         => $url,
						'lastScanGmt' => gmdate( 'c' ),
						'checks'      => $check_rows,
						'source'      => 'live-scan-no-store',
						'isPriority'  => true,
						'performance' => null,
					)
				);

				return self::build_response( $payload, 'live_scan_no_store', 'page_diagnostics_v2.live_scan_no_store' );
			}

			$report = ASNERISSEO_Page_Diagnostics_Snapshots::save_snapshot(
				$post,
				$check_rows,
				$url,
				self::build_weightage_score_override_for_post( $post, $check_rows )
			);

			$payload = array_merge(
				$overview_item,
				array(
					'postId'               => $post_id,
					'url'                  => $url,
					'seoScore'             => isset( $report['seoScore'] ) ? (int) $report['seoScore'] : (int) ( $overview_item['seoScore'] ?? 0 ),
					'aiScore'              => isset( $report['aiScore'] ) ? (int) $report['aiScore'] : (int) ( $overview_item['aiScore'] ?? 0 ),
					'health'               => isset( $report['health'] ) ? sanitize_key( (string) $report['health'] ) : (string) ( $overview_item['health'] ?? 'warning' ),
					'issueGroups'          => isset( $report['issueGroups'] ) && is_array( $report['issueGroups'] ) ? $report['issueGroups'] : ( isset( $overview_item['issueGroups'] ) && is_array( $overview_item['issueGroups'] ) ? $overview_item['issueGroups'] : array() ),
					'overviewIssueRecords' => isset( $report['overviewIssueRecords'] ) && is_array( $report['overviewIssueRecords'] ) ? $report['overviewIssueRecords'] : ( isset( $overview_item['overviewIssueRecords'] ) && is_array( $overview_item['overviewIssueRecords'] ) ? $overview_item['overviewIssueRecords'] : array() ),
					'overviewScoreRecords' => isset( $report['overviewScoreRecords'] ) && is_array( $report['overviewScoreRecords'] ) ? $report['overviewScoreRecords'] : ( isset( $overview_item['overviewScoreRecords'] ) && is_array( $overview_item['overviewScoreRecords'] ) ? $overview_item['overviewScoreRecords'] : array() ),
					'aiIssueRecords'       => isset( $report['aiIssueRecords'] ) && is_array( $report['aiIssueRecords'] ) ? $report['aiIssueRecords'] : ( isset( $overview_item['aiIssueRecords'] ) && is_array( $overview_item['aiIssueRecords'] ) ? $overview_item['aiIssueRecords'] : array() ),
					'aiCanonicalSignals'   => isset( $report['aiCanonicalSignals'] ) && is_array( $report['aiCanonicalSignals'] ) ? $report['aiCanonicalSignals'] : ( isset( $overview_item['aiCanonicalSignals'] ) && is_array( $overview_item['aiCanonicalSignals'] ) ? $overview_item['aiCanonicalSignals'] : array() ),
					'lastScanGmt'          => (string) ( $report['generatedAtGmt'] ?? gmdate( 'c' ) ),
					'canonicalCount'       => $canonical_count,
					'checks'               => $check_rows,
					'source'               => 'live-scan',
					'sourceIsStale'        => false,
					'isPriority'           => true,
					'performance'          => null,
				)
			);

			return self::build_response( $payload, 'live_scan_priority', 'page_diagnostics_v2.live_scan_priority' );
		}

		if ( ! $no_store ) {
			update_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt', gmdate( 'c' ) );
		}

		$payload = array_merge(
			$overview_item,
			array(
				'postId'         => $post_id,
				'url'            => $url,
				'lastScanGmt'    => $no_store ? gmdate( 'c' ) : (string) get_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt', true ),
				'canonicalCount' => $canonical_count,
				'checks'         => $check_rows,
				'source'         => $no_store ? 'live-scan-no-store' : 'live-scan-non-priority',
				'sourceIsStale'  => false,
				'isPriority'     => false,
				'performance'    => null,
			)
		);

		return self::build_response(
			$payload,
			$no_store ? 'live_scan_no_store' : 'live_scan_non_priority',
			'page_diagnostics_v2.live_scan_non_priority'
		);
	}

	public static function evaluate_draft_policy( WP_REST_Request $request ) {
		$context = self::collect_draft_request_context( $request );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$post_id               = (int) $context['postId'];
		$post_title            = (string) $context['postTitle'];
		$post_excerpt          = (string) $context['postExcerpt'];
		$content_raw           = (string) $context['contentRaw'];
		$url                   = (string) $context['url'];
		$site_host             = (string) $context['siteHost'];
		$raw_meta_title        = (string) $context['rawMetaTitle'];
		$raw_meta_description  = (string) $context['rawMetaDescription'];
		$raw_og_title          = (string) $context['rawOgTitle'];
		$raw_og_description    = (string) $context['rawOgDescription'];
		$raw_og_image          = (string) $context['rawOgImage'];
		$raw_og_image_disabled = ! empty( $context['rawOgImageDisabled'] );
		$effective_title       = (string) $context['effectiveTitle'];
		$effective_description = (string) $context['effectiveDescription'];
		$canonical             = (string) $context['canonical'];
		$robots_index          = (string) $context['robotsIndex'];
		$robots_follow         = (string) $context['robotsFollow'];
		$http_status           = 0;
		$http_status_details   = 'Draft policy live HTTP check could not determine status.';
		$draft_http_checks     = array();

		if ( '' !== $url ) {
			$live_checks = ASNERISSEO_Diagnostics::http_test_checks( $url );
			if ( ! is_wp_error( $live_checks ) ) {
				$draft_http_checks = is_array( $live_checks ) ? $live_checks : array();
				$http_status       = self::extract_http_status_from_checks( $draft_http_checks, 0 );
				$http_status_row   = self::get_check_row_by_label( $draft_http_checks, 'HTTP Status' );
				if ( is_array( $http_status_row ) ) {
					$http_status_details = sanitize_text_field( (string) ( $http_status_row['details'] ?? '' ) );
				}
			}
		}

		$word_count     = self::count_words_from_html( $content_raw );
		$internal_links = self::count_internal_links_from_html( $content_raw, $site_host );
		$h1_count       = 0;
		if ( preg_match_all( '/<h1\b[^>]*>/i', $content_raw, $heading_matches ) ) {
			$h1_count = is_array( $heading_matches[0] ) ? count( $heading_matches[0] ) : 0;
		}

		$image_count        = 0;
		$images_missing_alt = 0;
		if ( preg_match_all( '/<img\b[^>]*>/i', $content_raw, $image_matches ) ) {
			$images      = is_array( $image_matches[0] ) ? $image_matches[0] : array();
			$image_count = count( $images );
			foreach ( $images as $image_tag ) {
				if ( ! preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/i', (string) $image_tag, $alt_match ) ) {
					++$images_missing_alt;
					continue;
				}

				$alt_text = trim( wp_strip_all_tags( html_entity_decode( (string) ( $alt_match[2] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) );
				if ( '' === $alt_text ) {
					++$images_missing_alt;
				}
			}
		}

		$language_declaration = sanitize_text_field( (string) get_bloginfo( 'language' ) );

		$overview_item = self::build_draft_overview_item(
			array(
				'postId'               => $post_id,
				'postTitle'            => $post_title,
				'postExcerpt'          => $post_excerpt,
				'url'                  => $url,
				'rawMetaTitle'         => $raw_meta_title,
				'rawMetaDescription'   => $raw_meta_description,
				'rawOgTitle'           => $raw_og_title,
				'rawOgDescription'     => $raw_og_description,
				'rawOgImage'           => $raw_og_image,
				'rawOgImageDisabled'   => $raw_og_image_disabled,
				'effectiveTitle'       => $effective_title,
				'effectiveDescription' => $effective_description,
				'canonical'            => $canonical,
				'robotsIndex'          => $robots_index,
				'robotsFollow'         => $robots_follow,
				'httpStatus'           => $http_status,
				'contentWords'         => $word_count,
				'h1Count'              => $h1_count,
				'internalLinks'        => $internal_links,
				'imageCount'           => $image_count,
				'imagesMissingAlt'     => $images_missing_alt,
				'languageDeclaration'  => $language_declaration,
			)
		);

		$draft_checks = self::merge_draft_editor_signals_into_checks(
			$draft_http_checks,
			array(
				'effectiveTitleLength'       => function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $effective_title ) ) : strlen( wp_strip_all_tags( $effective_title ) ),
				'effectiveDescriptionLength' => function_exists( 'mb_strlen' ) ? mb_strlen( $effective_description ) : strlen( $effective_description ),
				'robotsIndex'                => $robots_index,
				'robotsFollow'               => $robots_follow,
				'httpStatus'                 => $http_status,
				'httpStatusDetails'          => $http_status_details,
				'h1Count'                    => $h1_count,
				'internalLinks'              => $internal_links,
				'contentWords'               => $word_count,
			)
		);

		$processed = self::process_retrieved_diagnostics(
			array(
				'ID'           => $post_id,
				'post_content' => $content_raw,
			),
			$overview_item,
			$draft_checks,
			'overview-draft',
			array(
				'effectiveTitleLength'       => function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( $effective_title ) ) : strlen( wp_strip_all_tags( $effective_title ) ),
				'effectiveDescriptionLength' => function_exists( 'mb_strlen' ) ? mb_strlen( $effective_description ) : strlen( $effective_description ),
				'internalLinks'              => $internal_links,
				'contentWords'               => $word_count,
				'hasHeading'                 => $h1_count > 0,
				'robotsIndex'                => $robots_index,
				'robotsFollow'               => $robots_follow,
				'httpStatus'                 => $http_status,
				'contentRaw'                 => $content_raw,
				'imageCount'                 => $image_count,
				'imagesMissingAlt'           => $images_missing_alt,
				'languageDeclaration'        => $language_declaration,
			)
		);

		$overview_item = $processed['overviewItem'];
		$checks        = $processed['checks'];

		$payload = array_merge(
			$overview_item,
			array(
				'postId'      => $post_id,
				'url'         => $url,
				'lastScanGmt' => gmdate( 'c' ),
				'checks'      => $checks,
				'source'      => 'editor-policy-dirty',
				'isPriority'  => false,
				'performance' => null,
			)
		);

		return self::build_response( $payload, 'editor_policy_dirty', 'page_diagnostics_v2.editor_policy_dirty' );
	}

	public static function get_stored_page_diagnostics_history( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$limit   = absint( $request->get_param( 'limit' ) );
		if ( $limit < 1 ) {
			$limit = 10;
		}

		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			return new WP_Error(
				'asnerisseo_post_not_found',
				__( 'Post not found.', 'asneris-seo-toolkit' ),
				array( 'status' => 404 )
			);
		}

		$is_priority        = ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post_id );
		$history            = ASNERISSEO_Page_Diagnostics_Snapshots::get_snapshot_history( $post_id, $limit );
		$history_count      = ASNERISSEO_Page_Diagnostics_Snapshots::get_history_count( $post_id );
		$normalized_history = array();

		foreach ( is_array( $history ) ? $history : array() as $history_item ) {
			if ( ! is_array( $history_item ) ) {
				continue;
			}

			$normalized = self::normalize_stored_snapshot_item( $post, $history_item, 'stored_snapshot_history', 'page_diagnostics_v2.stored_snapshot_history' );
			if ( is_wp_error( $normalized ) ) {
				return $normalized;
			}

			$normalized_history[] = $normalized;
		}

		if ( $is_priority && empty( $normalized_history ) ) {
			$latest_row    = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot( $post_id );
			$latest_report = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post_id );

			if ( is_array( $latest_row ) && is_array( $latest_report ) ) {
				$latest_item = array_merge(
					$latest_report,
					array(
						'id'             => 0,
						'pageId'         => $post_id,
						'createdAt'      => isset( $latest_row['last_scan_gmt'] ) ? (string) $latest_row['last_scan_gmt'] : '',
						'generatedAtGmt' => isset( $latest_report['generatedAtGmt'] ) ? (string) $latest_report['generatedAtGmt'] : '',
					)
				);

				$normalized = self::normalize_stored_snapshot_item( $post, $latest_item, 'stored_snapshot_latest_fallback', 'page_diagnostics_v2.stored_snapshot_latest_fallback' );
				if ( is_wp_error( $normalized ) ) {
						return $normalized;
				}

				$normalized['source'] = 'stored-snapshot-latest';
				$normalized_history[] = $normalized;
			}
		}

		$history_count = max( (int) $history_count, count( $normalized_history ) );
		$history_limit = ASNERISSEO_Page_Diagnostics_Snapshots::DEFAULT_HISTORY_LIMIT;

		return rest_ensure_response(
			array(
				'postId'        => $post_id,
				'isPriority'    => $is_priority,
				'history'       => $normalized_history,
				'historyCount'  => $history_count,
				'historyLimit'  => $history_limit,
				'historyLocked' => $history_count >= $history_limit,
			)
		);
	}

	private static function normalize_stored_snapshot_item( WP_Post $post, array $snapshot_item, $source_mode, $context ) {
		$site_host    = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$base_item    = self::build_page_diagnostics_overview_item( $post, $site_host );
		$checks       = isset( $snapshot_item['checks'] ) && is_array( $snapshot_item['checks'] ) ? self::normalize_stored_snapshot_checks( array_values( $snapshot_item['checks'] ) ) : array();
		$generated_at = isset( $snapshot_item['generatedAtGmt'] ) ? (string) $snapshot_item['generatedAtGmt'] : '';
		$created_at   = isset( $snapshot_item['createdAt'] ) ? (string) $snapshot_item['createdAt'] : '';

		$payload = array_merge(
			$base_item,
			$snapshot_item,
			array(
				'id'             => isset( $snapshot_item['id'] ) ? (int) $snapshot_item['id'] : 0,
				'postId'         => (int) $post->ID,
				'pageId'         => (int) $post->ID,
				'url'            => isset( $snapshot_item['url'] ) && '' !== (string) $snapshot_item['url'] ? esc_url_raw( (string) $snapshot_item['url'] ) : (string) ( $base_item['url'] ?? '' ),
				'createdAt'      => $created_at,
				'generatedAtGmt' => $generated_at,
				'lastScanGmt'    => '' !== $generated_at ? $generated_at : $created_at,
				'checks'         => $checks,
				'source'         => 'stored-snapshot',
				'sourceIsStale'  => true,
				'isPriority'     => ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post->ID ),
				'performance'    => null,
			)
		);

		return self::build_response_payload( $payload, $source_mode, $context );
	}

	private static function normalize_stored_snapshot_checks( array $checks ) {
		return array_map(
			static function ( $check ) {
				if ( ! is_array( $check ) ) {
					return $check;
				}

				$label   = strtolower( trim( (string) ( $check['label'] ?? '' ) ) );
				$result  = strtolower( trim( (string) ( $check['result'] ?? '' ) ) );
				$details = strtolower( trim( (string) ( $check['details'] ?? '' ) ) );

				if ( 'x-robots-tag' === $label && ( false !== strpos( $result, 'not detected' ) || false !== strpos( $details, 'no x-robots-tag header found' ) ) ) {
					$check['status'] = 'pass';
					$check['result'] = 'Not Detected';
				}

				if ( 'robots meta' === $label && ( false !== strpos( $result, 'not detected' ) || false !== strpos( $details, 'no robots meta tag found' ) ) ) {
					$check['status'] = 'pass';
					$check['result'] = 'Not Detected';
				}

				if ( 'indexability' === $label && ( false !== strpos( $result, 'indexable' ) || false !== strpos( $details, 'no noindex directive found' ) ) ) {
					$check['status'] = 'pass';
					$check['result'] = 'Indexable';
				}

				return $check;
			},
			$checks
		);
	}

	private static function collect_published_request_context( WP_REST_Request $request ) {
		$post_id = absint( $request['id'] );
		$post    = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'asnerisseo_post_not_found',
				__( 'Post not found.', 'asneris-seo-toolkit' ),
				array( 'status' => 404 )
			);
		}

		if ( 'publish' !== get_post_status( $post ) ) {
			return new WP_Error(
				'asnerisseo_post_not_published',
				__( 'Diagnostics can only run on published content.', 'asneris-seo-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$url = get_permalink( $post_id );
		if ( ! $url ) {
			return new WP_Error(
				'asnerisseo_post_permalink_missing',
				__( 'Post URL could not be resolved.', 'asneris-seo-toolkit' ),
				array( 'status' => 400 )
			);
		}

		$site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

		return array(
			'postId'       => $post_id,
			'post'         => $post,
			'url'          => $url,
			'siteHost'     => $site_host,
			'overviewItem' => self::build_page_diagnostics_overview_item( $post, $site_host ),
			'forceRefresh' => ! empty( $request->get_param( 'force' ) ),
			'noStore'      => ! empty( $request->get_param( 'no_store' ) ) || ! empty( $request->get_param( 'noStore' ) ),
			'isPriority'   => ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post_id ),
		);
	}

	private static function collect_draft_request_context( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		$post_id = isset( $params['postId'] ) ? absint( $params['postId'] ) : 0;
		if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'asnerisseo_forbidden_post',
				__( 'You do not have permission to evaluate this post.', 'asneris-seo-toolkit' ),
				array( 'status' => 403 )
			);
		}

		$meta           = isset( $params['meta'] ) && is_array( $params['meta'] ) ? $params['meta'] : array();
		$incoming_title = $params['postTitle'] ?? '';
		if ( is_array( $incoming_title ) ) {
			$incoming_title = (string) ( $incoming_title['raw'] ?? $incoming_title['rendered'] ?? '' );
		}

		$incoming_excerpt = $params['postExcerpt'] ?? '';
		if ( is_array( $incoming_excerpt ) ) {
			$incoming_excerpt = (string) ( $incoming_excerpt['raw'] ?? $incoming_excerpt['rendered'] ?? '' );
		}

		$post_title           = sanitize_text_field( (string) $incoming_title );
		$post_excerpt         = sanitize_textarea_field( (string) $incoming_excerpt );
		$content_raw          = wp_kses_post( (string) ( $params['content'] ?? '' ) );
		$incoming_url         = esc_url_raw( (string) ( $params['url'] ?? '' ) );
		$url                  = '' !== $incoming_url ? $incoming_url : ( $post_id > 0 ? (string) get_permalink( $post_id ) : '' );
		$raw_meta_title       = sanitize_text_field( (string) ( $meta['_ASNERISSEO_title'] ?? '' ) );
		$raw_meta_description = sanitize_textarea_field( (string) ( $meta['_ASNERISSEO_description'] ?? '' ) );

		return array(
			'postId'               => $post_id,
			'postTitle'            => $post_title,
			'postExcerpt'          => $post_excerpt,
			'contentRaw'           => $content_raw,
			'url'                  => $url,
			'siteHost'             => strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) ),
			'rawMetaTitle'         => $raw_meta_title,
			'rawMetaDescription'   => $raw_meta_description,
			'rawOgTitle'           => sanitize_text_field( (string) ( $meta['_ASNERISSEO_og_title'] ?? '' ) ),
			'rawOgDescription'     => sanitize_textarea_field( (string) ( $meta['_ASNERISSEO_og_description'] ?? '' ) ),
			'rawOgImage'           => esc_url_raw( (string) ( $meta['_ASNERISSEO_og_image'] ?? '' ) ),
			'rawOgImageDisabled'   => ! empty( $meta['_ASNERISSEO_og_image_disabled'] ),
			'effectiveTitle'       => sanitize_text_field( (string) ( '' !== $raw_meta_title ? $raw_meta_title : $post_title ) ),
			'effectiveDescription' => sanitize_textarea_field( (string) ( '' !== $raw_meta_description ? $raw_meta_description : wp_strip_all_tags( $post_excerpt ) ) ),
			'canonical'            => esc_url_raw( (string) ( $meta['_ASNERISSEO_canonical'] ?? '' ) ),
			'robotsIndex'          => sanitize_key( (string) ( $meta['_ASNERISSEO_robots_index'] ?? 'index' ) ),
			'robotsFollow'         => sanitize_key( (string) ( $meta['_ASNERISSEO_robots_follow'] ?? 'follow' ) ),
		);
	}

	private static function build_response( array $payload, $source_mode, $context ) {
		$normalized = self::build_response_payload( $payload, $source_mode, $context );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		return rest_ensure_response( $normalized );
	}

	private static function build_response_payload( array $payload, $source_mode, $context ) {
		// ===== UNIFIED DESIGN: Extract completeness metadata from checks =====
		$captured_fields = array();
		$missing_fields  = array();

		if ( isset( $payload['checks'] ) && is_array( $payload['checks'] ) && ! empty( $payload['checks'] ) ) {
			// Get completeness info from first check (all checks have same completeness in a scan)
			$first_check = reset( $payload['checks'] );
			if ( is_array( $first_check ) ) {
				$missing_fields = isset( $first_check['missingFields'] ) && is_array( $first_check['missingFields'] )
				? $first_check['missingFields']
				: array();
			}
		}

		// Populate completeness metadata in payload
		$payload['completeness'] = array(
			'capturedFields' => empty( $missing_fields ),  // Inverse: if no missing, then captured
			'missingFields'  => $missing_fields,
			'captureQuality' => empty( $missing_fields ) ? 'complete' : 'partial',
		);

		$payload['tabModels'] = self::build_backend_tab_models( $payload );

		$normalized = ASNERISSEO_Page_Diagnostics_Response_Contract::build_payload( $payload, $source_mode );
		$validation = ASNERISSEO_Page_Diagnostics_Response_Contract::validate_payload( $normalized, $context );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		return $normalized;
	}

	private static function normalize_tab_status( $status ) {
		$normalized = sanitize_key( (string) $status );
		if ( 'warn' === $normalized ) {
			return 'warning';
		}
		if ( in_array( $normalized, array( 'pass', 'warning', 'fail' ), true ) ) {
			return $normalized;
		}
		if ( in_array( $normalized, array( 'not_scanned', 'not_checked', 'not_available', 'unknown', 'na' ), true ) ) {
			return 'not_scanned';
		}

		return 'not_scanned';
	}

	private static function normalize_tab_field_label( $label ) {
		$normalized = strtolower( trim( wp_strip_all_tags( (string) $label ) ) );
		$normalized = preg_replace( '/[^a-z0-9]+/', ' ', $normalized );
		return is_string( $normalized ) ? trim( $normalized ) : '';
	}

	private static function find_tab_check_for_field( array $checks, $field_label ) {
		$target = self::normalize_tab_field_label( $field_label );
		if ( '' === $target ) {
			return null;
		}

		$best          = null;
		$best_score    = -1;
		$target_tokens = array_values( array_filter( explode( ' ', $target ), 'strlen' ) );

		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) ) {
				continue;
			}
			$label = self::normalize_tab_field_label( $check['label'] ?? '' );
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
				$score += count( array_intersect( $target_tokens, $label_tokens ) ) * 80;
				$score -= abs( count( $target_tokens ) - count( $label_tokens ) ) * 5;
			}

			if ( $score > $best_score ) {
				$best       = $check;
				$best_score = $score;
			}
		}

		return $best_score >= 80 ? $best : null;
	}

	private static function find_tab_issue_record_for_field( array $records, $field_label ) {
		$target = self::normalize_tab_field_label( $field_label );
		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}
			if ( self::normalize_tab_field_label( $record['canonical_field'] ?? '' ) === $target ) {
				return $record;
			}
		}

		return null;
	}

	private static function build_backend_tab_row( $tab_key, $field_label, array $checks, array $records ) {
		$check  = self::find_tab_check_for_field( $checks, $field_label );
		$record = self::find_tab_issue_record_for_field( $records, $field_label );

		$status  = 'not_scanned';
		$result  = __( 'Not checked', 'asneris-seo-toolkit' );
		$details = __( 'Backend diagnostics did not provide this canonical check.', 'asneris-seo-toolkit' );

		if ( is_array( $check ) ) {
			$status  = self::normalize_tab_status( $check['status'] ?? 'not_scanned' );
			$result  = is_scalar( $check['result'] ?? null ) ? (string) $check['result'] : '';
			$details = is_scalar( $check['details'] ?? null ) ? (string) $check['details'] : '';
		} elseif ( is_array( $record ) ) {
			$status       = self::normalize_tab_status( $record['canonical_status'] ?? 'not_scanned' );
			$raw_evidence = isset( $record['raw_evidence'] ) && is_array( $record['raw_evidence'] ) ? $record['raw_evidence'] : array();
			$result       = isset( $raw_evidence['checkResult'] ) && is_scalar( $raw_evidence['checkResult'] ) ? (string) $raw_evidence['checkResult'] : $status;
			$details      = isset( $record['recommended_fix'] ) && is_scalar( $record['recommended_fix'] ) ? (string) $record['recommended_fix'] : '';
		}

		return array(
			'label'    => (string) $field_label,
			'status'   => $status,
			'result'   => '' !== $result ? $result : '-',
			'details'  => '' !== $details ? $details : '-',
			'category' => (string) $tab_key,
		);
	}

	private static function build_backend_tab_counts( array $rows ) {
		$counts = array(
			'pass'       => 0,
			'warning'    => 0,
			'fail'       => 0,
			'notScanned' => 0,
			'total'      => 0,
			'issues'     => 0,
		);
		foreach ( $rows as $row ) {
			$status = self::normalize_tab_status( $row['status'] ?? 'not_scanned' );
			if ( 'pass' === $status ) {
				++$counts['pass'];
			} elseif ( 'warning' === $status ) {
				++$counts['warning'];
			} elseif ( 'fail' === $status ) {
				++$counts['fail'];
			} else {
				++$counts['notScanned'];
			}
			++$counts['total'];
		}
		$counts['issues'] = $counts['warning'] + $counts['fail'] + $counts['notScanned'];
		return $counts;
	}

	private static function score_backend_tab_rows( $tab_key, array $rows ) {
		$weights = isset( self::$tab_score_weights[ $tab_key ] ) && is_array( self::$tab_score_weights[ $tab_key ] ) ? self::$tab_score_weights[ $tab_key ] : array();
		if ( empty( $weights ) ) {
			return 0;
		}

		$rows_by_label = array();
		foreach ( $rows as $row ) {
			if ( is_array( $row ) ) {
				$rows_by_label[ self::normalize_tab_field_label( $row['label'] ?? '' ) ] = $row;
			}
		}

		$earned   = 0.0;
		$possible = 0.0;
		foreach ( $weights as $field_label => $weight ) {
			$possible += (float) $weight;
			$row       = $rows_by_label[ self::normalize_tab_field_label( $field_label ) ] ?? null;
			$status    = is_array( $row ) ? self::normalize_tab_status( $row['status'] ?? 'not_scanned' ) : 'not_scanned';
			if ( 'pass' === $status ) {
				$earned += (float) $weight;
			} elseif ( 'warning' === $status ) {
				$earned += (float) $weight * 0.5;
			}
		}

		return $possible > 0 ? self::clamp_score( ( $earned / $possible ) * 100 ) : 0;
	}

	private static function build_backend_tab_models( array $payload ) {
		$checks            = isset( $payload['checks'] ) && is_array( $payload['checks'] ) ? array_values( $payload['checks'] ) : array();
		$tab_issue_records = isset( $payload['tabIssueRecords'] ) && is_array( $payload['tabIssueRecords'] ) ? $payload['tabIssueRecords'] : array();
		$models            = array();

		foreach ( self::$canonical_tab_field_registry as $tab_key => $fields ) {
			$records = isset( $tab_issue_records[ $tab_key ] ) && is_array( $tab_issue_records[ $tab_key ] ) ? array_values( $tab_issue_records[ $tab_key ] ) : array();
			$rows    = array();
			foreach ( $fields as $field_label ) {
				$rows[] = self::build_backend_tab_row( $tab_key, $field_label, $checks, $records );
			}

			$counts             = self::build_backend_tab_counts( $rows );
			$score              = self::score_backend_tab_rows( $tab_key, $rows );
			$models[ $tab_key ] = array(
				'key'         => (string) $tab_key,
				'score'       => $score,
				'status'      => $counts['fail'] > 0 ? 'fail' : ( ( $counts['warning'] > 0 || $counts['notScanned'] > 0 ) ? 'warning' : 'pass' ),
				'counts'      => $counts,
				'rows'        => $rows,
				'scoreEngine' => 'backend_tab_model_v1',
			);
		}

		return $models;
	}

	private static function process_retrieved_diagnostics( $post, array $overview_item, array $check_rows, $run_id_prefix, array $score_context_overrides = array() ) {
		$overview_item              = self::apply_weightage_scores_from_checks( $post, $overview_item, $check_rows, $run_id_prefix, $score_context_overrides );
		$check_rows                 = self::sync_overview_checks_from_records( $check_rows, $overview_item );
		$overview_item['tabModels'] = self::build_backend_tab_models( array_merge( $overview_item, array( 'checks' => $check_rows ) ) );

		return array(
			'overviewItem' => $overview_item,
			'checks'       => $check_rows,
		);
	}

	private static function build_draft_overview_item( array $context ) {
		$effective_title       = (string) ( $context['effectiveTitle'] ?? '' );
		$effective_description = (string) ( $context['effectiveDescription'] ?? '' );
		$raw_meta_title        = (string) ( $context['rawMetaTitle'] ?? '' );
		$raw_meta_description  = (string) ( $context['rawMetaDescription'] ?? '' );

		return array(
			'postId'               => isset( $context['postId'] ) ? (int) $context['postId'] : 0,
			'title'                => sanitize_text_field( (string) ( $context['postTitle'] ?? '' ) ),
			'postType'             => '',
			'postStatus'           => 'draft',
			'author'               => '',
			'isDraftQualityOnly'   => true,
			'url'                  => esc_url_raw( (string) ( $context['url'] ?? '' ) ),
			'modifiedGmt'          => gmdate( 'c' ),
			'metaTitle'            => $raw_meta_title,
			'seoTitle'             => $raw_meta_title,
			'seoDescription'       => $raw_meta_description,
			'metaDescription'      => $raw_meta_description,
			'excerpt'              => wp_strip_all_tags( (string) ( $context['postExcerpt'] ?? '' ) ),
			'ogTitle'              => (string) ( $context['rawOgTitle'] ?? '' ),
			'ogDescription'        => (string) ( $context['rawOgDescription'] ?? '' ),
			'ogImage'              => esc_url_raw( (string) ( $context['rawOgImage'] ?? '' ) ),
			'ogImageDisabled'      => ! empty( $context['rawOgImageDisabled'] ),
			'hasCustomTitle'       => '' !== $raw_meta_title,
			'hasCustomDescription' => '' !== $raw_meta_description,
			'hasCanonical'         => '' !== (string) ( $context['canonical'] ?? '' ),
			'canonical'            => esc_url_raw( (string) ( $context['canonical'] ?? '' ) ),
			'metaTitleLength'      => function_exists( 'mb_strlen' ) ? (int) mb_strlen( wp_strip_all_tags( $effective_title ) ) : (int) strlen( wp_strip_all_tags( $effective_title ) ),
			'titleLength'          => function_exists( 'mb_strlen' ) ? (int) mb_strlen( wp_strip_all_tags( $effective_title ) ) : (int) strlen( wp_strip_all_tags( $effective_title ) ),
			'effectiveTitle'       => $effective_title,
			'effectiveDescription' => $effective_description,
			'descriptionLength'    => function_exists( 'mb_strlen' ) ? (int) mb_strlen( $effective_description ) : (int) strlen( $effective_description ),
			'seoScore'             => 0,
			'aiScore'              => 0,
			'health'               => 'warning',
			'robotsIndex'          => sanitize_key( (string) ( $context['robotsIndex'] ?? 'index' ) ),
			'robotsFollow'         => sanitize_key( (string) ( $context['robotsFollow'] ?? 'follow' ) ),
			'xRobotsTag'           => '',
			'metaSummary'          => '' !== $raw_meta_description ? __( 'Complete', 'asneris-seo-toolkit' ) : __( 'Missing', 'asneris-seo-toolkit' ),
			'contentWords'         => isset( $context['contentWords'] ) ? (int) $context['contentWords'] : 0,
			'h1Count'              => isset( $context['h1Count'] ) ? (int) $context['h1Count'] : 0,
			'h2Count'              => 0,
			'faqCount'             => 0,
			'schemaEnabled'        => false,
			'schemaType'           => '',
			'organizationSchema'   => false,
			'breadcrumbSchema'     => false,
			'internalLinks'        => isset( $context['internalLinks'] ) ? (int) $context['internalLinks'] : 0,
			'externalLinks'        => 0,
			'nofollowLinks'        => 0,
			'httpStatus'           => isset( $context['httpStatus'] ) ? (int) $context['httpStatus'] : 0,
			'languageDeclaration'  => sanitize_text_field( (string) ( $context['languageDeclaration'] ?? '' ) ),
			'imageCount'           => isset( $context['imageCount'] ) ? (int) $context['imageCount'] : 0,
			'imagesMissingAlt'     => isset( $context['imagesMissingAlt'] ) ? (int) $context['imagesMissingAlt'] : 0,
			'imagesEmptyAlt'       => 0,
			'featuredImage'        => false,
			'issueGroups'          => array(),
			'overviewIssueRecords' => array(),
			'overviewScoreRecords' => array(),
			'aiIssueRecords'       => array(),
			'aiCanonicalSignals'   => array(),
			'tabIssueRecords'      => array(),
			'overviewRunId'        => '',
			'seoScoreMessage'      => '',
			'scoreEngine'          => 'weightage_policy_v4_1',
			'lastScanGmt'          => gmdate( 'c' ),
		);
	}

	private static function merge_draft_editor_signals_into_checks( array $checks, array $draft_signals ) {
		$effective_title_length       = isset( $draft_signals['effectiveTitleLength'] ) ? (int) $draft_signals['effectiveTitleLength'] : 0;
		$effective_description_length = isset( $draft_signals['effectiveDescriptionLength'] ) ? (int) $draft_signals['effectiveDescriptionLength'] : 0;
		$robots_index                 = sanitize_key( (string) ( $draft_signals['robotsIndex'] ?? 'index' ) );
		$robots_follow                = sanitize_key( (string) ( $draft_signals['robotsFollow'] ?? 'follow' ) );
		$http_status                  = isset( $draft_signals['httpStatus'] ) ? (int) $draft_signals['httpStatus'] : 0;
		$http_status_details          = sanitize_text_field( (string) ( $draft_signals['httpStatusDetails'] ?? '' ) );
		$h1_count                     = isset( $draft_signals['h1Count'] ) ? (int) $draft_signals['h1Count'] : 0;
		$internal_links               = isset( $draft_signals['internalLinks'] ) ? (int) $draft_signals['internalLinks'] : 0;
		$word_count                   = isset( $draft_signals['contentWords'] ) ? (int) $draft_signals['contentWords'] : 0;

		$checks = self::build_or_update_check_row(
			$checks,
			'SEO Title Length',
			array(
				'category' => 'search',
				'status'   => ( $effective_title_length >= 30 && $effective_title_length <= 60 ) ? 'pass' : ( $effective_title_length > 0 ? 'warning' : 'fail' ),
				'result'   => sprintf( '%d chars', $effective_title_length ),
				'details'  => 'Editor-aware title length evaluation.',
			)
		);

		$checks = self::build_or_update_check_row(
			$checks,
			'Meta Description Length',
			array(
				'category' => 'search',
				'status'   => ( $effective_description_length >= 120 && $effective_description_length <= 160 ) ? 'pass' : ( $effective_description_length > 0 ? 'warning' : 'fail' ),
				'result'   => sprintf( '%d chars', $effective_description_length ),
				'details'  => 'Editor-aware description length evaluation.',
			)
		);

		$checks = self::build_or_update_check_row(
			$checks,
			'Robots Meta',
			array(
				'category' => 'advanced',
				'status'   => ( 'index' === $robots_index && 'follow' === $robots_follow ) ? 'pass' : 'warning',
				'result'   => sprintf( '%s/%s', $robots_index, $robots_follow ),
				'details'  => 'Editor-aware robots directives evaluation.',
			)
		);

		$checks = self::build_or_update_check_row(
			$checks,
			'HTTP Status',
			array(
				'category' => 'advanced',
				'status'   => ( $http_status >= 200 && $http_status < 300 ) ? 'pass' : ( $http_status >= 300 && $http_status < 400 ? 'warning' : 'fail' ),
				'result'   => $http_status,
				'details'  => '' !== $http_status_details ? $http_status_details : 'Editor-aware HTTP status evaluation.',
			)
		);

		$checks = self::build_or_update_check_row(
			$checks,
			'H1 Exists',
			array(
				'category' => 'quality',
				'status'   => $h1_count > 0 ? 'pass' : 'warning',
				'result'   => $h1_count > 0 ? 'Yes' : 'No',
				'details'  => 'Editor-aware heading presence evaluation.',
			)
		);

		$checks = self::build_or_update_check_row(
			$checks,
			'Internal Links',
			array(
				'category' => 'links',
				'status'   => $internal_links >= 2 ? 'pass' : ( 1 === $internal_links ? 'warning' : 'fail' ),
				'result'   => $internal_links,
				'details'  => 'Editor-aware internal linking evaluation.',
			)
		);

		return self::build_or_update_check_row(
			$checks,
			'Word Count',
			array(
				'category' => 'quality',
				'status'   => $word_count >= 300 ? 'pass' : 'warning',
				'result'   => $word_count,
				'details'  => 'Editor-aware content depth evaluation.',
			)
		);
	}

	private static function build_or_update_check_row( array $checks, $label, array $updates ) {
		$target_label = strtolower( trim( (string) $label ) );
		$target_index = null;

		foreach ( $checks as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$row_label = strtolower( trim( (string) ( $row['label'] ?? '' ) ) );
			if ( $row_label === $target_label ) {
				$target_index = $index;
				break;
			}
		}

		if ( null === $target_index ) {
			$checks[] = array_merge(
				array(
					'label'    => (string) $label,
					'category' => 'quality',
					'status'   => 'warning',
					'result'   => '',
					'details'  => '',
				),
				$updates
			);

			return $checks;
		}

		$checks[ $target_index ] = array_merge( $checks[ $target_index ], $updates );
		return $checks;
	}

	public static function build_weightage_score_override_for_post( WP_Post $post, array $checks ) {
		$site_host       = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$overview_item   = self::build_page_diagnostics_overview_item( $post, $site_host );
		$scored          = self::apply_weightage_scores_from_checks( $post, $overview_item, $checks, 'overview-snapshot' );
		$canonical_count = self::extract_canonical_count_from_checks( $checks, isset( $scored['canonicalCount'] ) ? (int) $scored['canonicalCount'] : 0 );

		return array_merge(
			array_intersect_key(
				$scored,
				array_flip(
					array(
						'title',
						'postType',
						'postStatus',
						'author',
						'isDraftQualityOnly',
						'url',
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
					)
				)
			),
			array(
				'seoScore'             => isset( $scored['seoScore'] ) ? (int) $scored['seoScore'] : 0,
				'aiScore'              => isset( $scored['aiScore'] ) ? (int) $scored['aiScore'] : 0,
				'health'               => isset( $scored['health'] ) ? sanitize_key( (string) $scored['health'] ) : 'poor',
				'issueGroups'          => isset( $scored['issueGroups'] ) && is_array( $scored['issueGroups'] ) ? $scored['issueGroups'] : array(),
				'overviewIssueRecords' => isset( $scored['overviewIssueRecords'] ) && is_array( $scored['overviewIssueRecords'] ) ? $scored['overviewIssueRecords'] : array(),
				'overviewScoreRecords' => isset( $scored['overviewScoreRecords'] ) && is_array( $scored['overviewScoreRecords'] ) ? $scored['overviewScoreRecords'] : array(),
				'aiIssueRecords'       => isset( $scored['aiIssueRecords'] ) && is_array( $scored['aiIssueRecords'] ) ? $scored['aiIssueRecords'] : array(),
				'aiCanonicalSignals'   => isset( $scored['aiCanonicalSignals'] ) && is_array( $scored['aiCanonicalSignals'] ) ? $scored['aiCanonicalSignals'] : array(),
				'overviewRunId'        => isset( $scored['overviewRunId'] ) ? (string) $scored['overviewRunId'] : '',
				'seoScoreMessage'      => isset( $scored['seoScoreMessage'] ) ? sanitize_text_field( (string) $scored['seoScoreMessage'] ) : '',
				'canonicalCount'       => $canonical_count,
				'tabModels'            => self::build_backend_tab_models( array_merge( $scored, array( 'checks' => $checks ) ) ),
				'scoreEngine'          => 'weightage_policy_v4_1',
			)
		);
	}

	private static function extract_canonical_count_from_checks( array $checks, $fallback = 0 ) {
		foreach ( $checks as $check ) {
			if ( ! is_array( $check ) || 'Canonical Exists' !== ( $check['label'] ?? '' ) ) {
				continue;
			}

			if ( 'Multiple' === ( $check['result'] ?? '' ) ) {
				return 2;
			}
			if ( 'Present' === ( $check['result'] ?? '' ) ) {
				return 1;
			}
			if ( 'Missing' === ( $check['result'] ?? '' ) ) {
				return 0;
			}
		}

		return max( 0, (int) $fallback );
	}

	private static function build_page_diagnostics_overview_item( $post, $site_host ) {
		$permalink = get_permalink( $post->ID );
		if ( ! $permalink ) {
			$permalink = get_preview_post_link( $post );
		}
		if ( ! $permalink ) {
			$permalink = get_edit_post_link( $post->ID, '' );
		}

		$title       = (string) get_the_title( $post->ID );
		$author_name = trim( (string) get_the_author_meta( 'display_name', (int) $post->post_author ) );
		if ( '' === $author_name ) {
			$author_name = __( 'Unknown', 'asneris-seo-toolkit' );
		}
		$meta_title      = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_title', true ) );
		$effective_title = '' !== $meta_title ? $meta_title : $title;
		$title_plain     = wp_strip_all_tags( $effective_title );
		$title_length    = function_exists( 'mb_strlen' ) ? mb_strlen( $title_plain ) : strlen( $title_plain );

		$description  = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_description', true ) );
		$excerpt_text = trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $post->ID ) ) );
		if ( '' === $excerpt_text ) {
			$excerpt_text = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post->ID ) ) );
		}
		$og_title                     = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_og_title', true ) );
		$og_description               = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_og_description', true ) );
		$og_image                     = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_og_image', true ) );
		$og_image_disabled            = ! empty( get_post_meta( $post->ID, '_ASNERISSEO_og_image_disabled', true ) );
		$effective_description        = '' !== $description ? $description : $excerpt_text;
		$effective_description_length = function_exists( 'mb_strlen' ) ? mb_strlen( $effective_description ) : strlen( $effective_description );
		$has_custom_description       = '' !== $description;
		$has_custom_title             = '' !== $meta_title;
		$canonical_url                = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_canonical', true ) );
		$has_canonical                = '' !== $canonical_url;
		$x_robots_tag                 = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_x_robots_tag', true ) );

		$robots_index = sanitize_key( (string) get_post_meta( $post->ID, '_ASNERISSEO_robots_index', true ) );
		if ( ! in_array( $robots_index, array( 'index', 'noindex' ), true ) ) {
			$robots_index = 'index';
		}
		$robots_follow = sanitize_key( (string) get_post_meta( $post->ID, '_ASNERISSEO_robots_follow', true ) );
		if ( ! in_array( $robots_follow, array( 'follow', 'nofollow' ), true ) ) {
			$robots_follow = 'follow';
		}
		$is_priority = ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post->ID );

		$content_raw          = (string) get_post_field( 'post_content', $post->ID );
		$content_word_count   = self::count_words_from_html( $content_raw );
		$language_declaration = strtolower( str_replace( '_', '-', sanitize_text_field( get_bloginfo( 'language' ) ) ) );
		$last_scan_gmt        = (string) get_post_meta( $post->ID, '_ASNERISSEO_last_diagnostics_scan_gmt', true );
		$h1_count             = preg_match_all( '/<h1\\b[^>]*>/i', $content_raw );
		if ( false === $h1_count ) {
			$h1_count = 0;
		}
		$h2_count = preg_match_all( '/<h2\\b[^>]*>/i', $content_raw );
		if ( false === $h2_count ) {
			$h2_count = 0;
		}

		$faq_count = 0;
		if ( preg_match_all( '/<h2\\b[^>]*>(.*?)<\\/h2>/is', $content_raw, $h2_text_matches ) ) {
			foreach ( $h2_text_matches[1] as $heading_text ) {
				$normalized_heading = trim( wp_strip_all_tags( (string) $heading_text ) );
				if ( '' === $normalized_heading ) {
					continue;
				}
				if ( false !== stripos( $normalized_heading, 'faq' ) || false !== strpos( $normalized_heading, '?' ) ) {
					++$faq_count;
				}
			}
		}
		$schema_type = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_schema_type', true ) );
		if ( 'FAQPage' === $schema_type ) {
			$faq_count = max( 1, $faq_count );
		}
		if ( (int) $faq_count < 1 ) {
			$content_lower_for_faq = strtolower( self::normalize_space( wp_strip_all_tags( $content_raw ) ) );
			if ( 1 === preg_match( '/(faq|frequently asked)/i', $content_lower_for_faq ) ) {
				$faq_count = 1;
			}
		}

		$schema_settings         = get_option( ASNERISSEO_Admin_Settings::OPT, array() );
		$has_breadcrumb_schema   = ! empty( $schema_settings['enable_breadcrumbs'] );
		$has_organization_schema = ! empty( $schema_settings['enable_local_business'] );
		$has_structured_data     = '' !== $schema_type || (int) $faq_count > 0 || $has_breadcrumb_schema || $has_organization_schema;

		$image_count        = 0;
		$images_missing_alt = 0;
		$images_empty_alt   = 0;
		if ( preg_match_all( '/<img\\b[^>]*>/i', $content_raw, $image_matches ) ) {
			$image_count = count( $image_matches[0] );
			foreach ( $image_matches[0] as $image_tag ) {
				if ( ! preg_match( '/\\balt\\s*=\\s*(["\'])(.*?)\\1/i', $image_tag, $alt_match ) ) {
					++$images_missing_alt;
					continue;
				}

				$alt_text = trim( wp_strip_all_tags( html_entity_decode( (string) $alt_match[2], ENT_QUOTES, 'UTF-8' ) ) );
				if ( '' === $alt_text ) {
					++$images_empty_alt;
					++$images_missing_alt;
				}
			}
		}
		$has_featured_image = function_exists( 'has_post_thumbnail' ) ? has_post_thumbnail( $post->ID ) : false;

		$link_metrics        = self::count_link_metrics_from_html( $content_raw, $site_host );
		$internal_link_count = isset( $link_metrics['internal'] ) ? (int) $link_metrics['internal'] : 0;
		$external_link_count = isset( $link_metrics['external'] ) ? (int) $link_metrics['external'] : 0;
		$nofollow_link_count = isset( $link_metrics['nofollow'] ) ? (int) $link_metrics['nofollow'] : 0;
		$http_status         = 0;

		$scores    = self::calculate_page_overview_scores(
			array(
				'runId'                      => sprintf( 'overview-%d-%s', (int) $post->ID, gmdate( 'YmdHis' ) ),
				'effectiveTitleLength'       => (int) $title_length,
				'effectiveDescriptionLength' => (int) $effective_description_length,
				'hasCanonical'               => (bool) $has_canonical,
				'robotsIndex'                => (string) $robots_index,
				'robotsFollow'               => (string) $robots_follow,
				'contentRaw'                 => (string) $content_raw,
				'contentWords'               => (int) $content_word_count,
				'imageCount'                 => (int) $image_count,
				'imagesMissingAlt'           => (int) $images_missing_alt,
				'internalLinks'              => (int) $internal_link_count,
				'httpStatus'                 => (int) $http_status,
				'languageDeclaration'        => (string) $language_declaration,
				'siteName'                   => (string) get_bloginfo( 'name' ),
			)
		);
		$seo_score = (int) $scores['seoScore'];
		$ai_score  = (int) $scores['aiScore'];

		$health = 'poor';
		if ( $seo_score >= 85 ) {
			$health = 'good';
		} elseif ( $seo_score >= 65 ) {
			$health = 'warning';
		}

		$meta_summary = $has_custom_description
		? __( 'Complete', 'asneris-seo-toolkit' )
		: __( 'Missing', 'asneris-seo-toolkit' );

		$snapshot_report = $is_priority ? ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post->ID ) : null;
		if ( $is_priority && is_array( $snapshot_report ) ) {
			if ( isset( $snapshot_report['seoScore'] ) ) {
				$seo_score = max( 0, min( 100, (int) $snapshot_report['seoScore'] ) );
			}
			if ( isset( $snapshot_report['health'] ) ) {
				$snapshot_health = sanitize_key( (string) $snapshot_report['health'] );
				if ( in_array( $snapshot_health, array( 'good', 'warning', 'poor' ), true ) ) {
					$health = $snapshot_health;
				}
			}
			if ( isset( $snapshot_report['generatedAtGmt'] ) ) {
				$snapshot_generated = (string) $snapshot_report['generatedAtGmt'];
				if ( '' !== $snapshot_generated ) {
					$last_scan_gmt = $snapshot_generated;
				}
			}
			if ( isset( $snapshot_report['issueGroups'] ) && is_array( $snapshot_report['issueGroups'] ) ) {
				$scores['issueGroups'] = $snapshot_report['issueGroups'];
			}
			if ( isset( $snapshot_report['overviewIssueRecords'] ) && is_array( $snapshot_report['overviewIssueRecords'] ) ) {
				$scores['overviewIssueRecords'] = $snapshot_report['overviewIssueRecords'];
			}
			if ( isset( $snapshot_report['overviewScoreRecords'] ) && is_array( $snapshot_report['overviewScoreRecords'] ) ) {
				$scores['overviewScoreRecords'] = $snapshot_report['overviewScoreRecords'];
			}
			if ( isset( $snapshot_report['aiIssueRecords'] ) && is_array( $snapshot_report['aiIssueRecords'] ) ) {
				$scores['aiIssueRecords'] = $snapshot_report['aiIssueRecords'];
			}
			if ( isset( $snapshot_report['aiCanonicalSignals'] ) && is_array( $snapshot_report['aiCanonicalSignals'] ) ) {
				$scores['aiCanonicalSignals'] = $snapshot_report['aiCanonicalSignals'];
			}
			if ( isset( $snapshot_report['tabIssueRecords'] ) && is_array( $snapshot_report['tabIssueRecords'] ) ) {
				$scores['tabIssueRecords'] = $snapshot_report['tabIssueRecords'];
			}
			if ( isset( $snapshot_report['overviewRunId'] ) ) {
				$scores['overviewRunId'] = (string) $snapshot_report['overviewRunId'];
			}
			if ( isset( $snapshot_report['seoScoreMessage'] ) ) {
				$scores['seoScoreMessage'] = sanitize_text_field( (string) $snapshot_report['seoScoreMessage'] );
			}
		}

		return array(
			'postId'               => (int) $post->ID,
			'title'                => $title,
			'postType'             => $post->post_type,
			'postStatus'           => $post->post_status,
			'author'               => $author_name,
			'isDraftQualityOnly'   => false,
			'url'                  => esc_url_raw( $permalink ),
			'publishedGmt'         => get_post_time( 'c', true, $post->ID ),
			'modifiedGmt'          => get_post_modified_time( 'c', true, $post->ID ),
			'metaTitle'            => $meta_title,
			'seoTitle'             => $meta_title,
			'seoDescription'       => $description,
			'metaDescription'      => $description,
			'excerpt'              => $excerpt_text,
			'ogTitle'              => $og_title,
			'ogDescription'        => $og_description,
			'ogImage'              => $og_image,
			'ogImageDisabled'      => $og_image_disabled,
			'hasCustomTitle'       => $has_custom_title,
			'hasCustomDescription' => $has_custom_description,
			'hasCanonical'         => $has_canonical,
			'canonical'            => $canonical_url,
			'metaTitleLength'      => (int) $title_length,
			'titleLength'          => (int) $title_length,
			'seoScore'             => $seo_score,
			'aiScore'              => $ai_score,
			'health'               => $health,
			'robotsIndex'          => $robots_index,
			'robotsFollow'         => $robots_follow,
			'xRobotsTag'           => $x_robots_tag,
			'metaSummary'          => $meta_summary,
			'contentWords'         => $content_word_count,
			'h1Count'              => (int) $h1_count,
			'h2Count'              => (int) $h2_count,
			'faqCount'             => (int) $faq_count,
			'schemaEnabled'        => (bool) $has_structured_data,
			'schemaType'           => $schema_type,
			'organizationSchema'   => (bool) $has_organization_schema,
			'breadcrumbSchema'     => (bool) $has_breadcrumb_schema,
			'internalLinks'        => $internal_link_count,
			'externalLinks'        => $external_link_count,
			'nofollowLinks'        => $nofollow_link_count,
			'httpStatus'           => (int) $http_status,
			'languageDeclaration'  => $language_declaration,
			'imageCount'           => $image_count,
			'imagesMissingAlt'     => $images_missing_alt,
			'imagesEmptyAlt'       => $images_empty_alt,
			'featuredImage'        => $has_featured_image,
			'issueGroups'          => $scores['issueGroups'],
			'overviewIssueRecords' => isset( $scores['overviewIssueRecords'] ) && is_array( $scores['overviewIssueRecords'] ) ? $scores['overviewIssueRecords'] : array(),
			'overviewScoreRecords' => isset( $scores['overviewScoreRecords'] ) && is_array( $scores['overviewScoreRecords'] ) ? $scores['overviewScoreRecords'] : array(),
			'aiIssueRecords'       => isset( $scores['aiIssueRecords'] ) && is_array( $scores['aiIssueRecords'] ) ? $scores['aiIssueRecords'] : array(),
			'aiCanonicalSignals'   => isset( $scores['aiCanonicalSignals'] ) && is_array( $scores['aiCanonicalSignals'] ) ? $scores['aiCanonicalSignals'] : array(),
			'tabIssueRecords'      => isset( $scores['tabIssueRecords'] ) && is_array( $scores['tabIssueRecords'] ) ? $scores['tabIssueRecords'] : array(),
			'overviewRunId'        => isset( $scores['overviewRunId'] ) ? (string) $scores['overviewRunId'] : '',
			'seoScoreMessage'      => isset( $scores['seoScoreMessage'] ) ? sanitize_text_field( (string) $scores['seoScoreMessage'] ) : '',
			'scoreEngine'          => 'weightage_policy_v4_1',
			'lastScanGmt'          => $last_scan_gmt,
		);
	}

	private static function extract_http_status_from_checks( $checks, $fallback = 0 ) {
		$rows = is_array( $checks ) ? $checks : array();
		foreach ( $rows as $row ) {
			$label = strtolower( trim( (string) ( $row['label'] ?? '' ) ) );
			if ( 'http status' !== $label ) {
				continue;
			}

			$result = isset( $row['result'] ) ? (int) $row['result'] : 0;
			if ( $result > 0 ) {
				return $result;
			}

			$status = strtolower( trim( (string) ( $row['status'] ?? '' ) ) );
			if ( 'pass' === $status ) {
				return 200;
			}

			if ( 'warning' === $status || 'warn' === $status ) {
				return 302;
			}

			if ( 'fail' === $status || 'error' === $status ) {
				return 500;
			}
		}

		return (int) $fallback;
	}

	private static function extract_x_robots_tag_from_checks( $checks, $fallback = '' ) {
		$rows = is_array( $checks ) ? $checks : array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label        = strtolower( trim( (string) ( $row['label'] ?? '' ) ) );
			$raw_evidence = isset( $row['rawEvidence'] ) && is_array( $row['rawEvidence'] ) ? $row['rawEvidence'] : array();
			if ( isset( $raw_evidence['xRobotsTag'] ) && '' !== trim( (string) $raw_evidence['xRobotsTag'] ) ) {
				return sanitize_text_field( (string) $raw_evidence['xRobotsTag'] );
			}

			if ( 'x-robots-tag' === $label && ! empty( $row['details'] ) && false === stripos( (string) $row['details'], 'No x-robots-tag header found' ) ) {
				return sanitize_text_field( (string) $row['details'] );
			}
		}

		return sanitize_text_field( (string) $fallback );
	}

	private static function get_check_row_by_label( $checks, $target_label ) {
		$rows   = is_array( $checks ) ? $checks : array();
		$needle = strtolower( trim( (string) $target_label ) );
		foreach ( $rows as $row ) {
			$label = strtolower( trim( (string) ( $row['label'] ?? '' ) ) );
			if ( $label === $needle ) {
				return is_array( $row ) ? $row : null;
			}
		}

		return null;
	}

	private static function extract_check_status( $checks, $label, $fallback = 'warning' ) {
		$row = self::get_check_row_by_label( $checks, $label );
		if ( ! is_array( $row ) ) {
			return sanitize_key( (string) $fallback );
		}

		$status = sanitize_key( (string) ( $row['status'] ?? '' ) );
		return '' !== $status ? $status : sanitize_key( (string) $fallback );
	}

	private static function extract_numeric_value_from_check_result( $result, $fallback = 0 ) {
		if ( is_numeric( $result ) ) {
			return (int) $result;
		}

		$result_text = strtolower( trim( (string) $result ) );
		if ( '' === $result_text ) {
			return (int) $fallback;
		}

		if ( preg_match( '/-?\d+/', $result_text, $matches ) ) {
			return (int) $matches[0];
		}

		return (int) $fallback;
	}

	private static function extract_numeric_check_result( $checks, $label, $fallback = 0 ) {
		$row = self::get_check_row_by_label( $checks, $label );
		if ( ! is_array( $row ) ) {
			return (int) $fallback;
		}

		return self::extract_numeric_value_from_check_result( $row['result'] ?? 0, $fallback );
	}

	private static function normalize_diagnostics_field_label( $value ) {
		$label = strtolower( trim( (string) $value ) );
		if ( '' === $label ) {
			return '';
		}

		$label = preg_replace( '/[^a-z0-9]+/', ' ', $label );
		if ( ! is_string( $label ) ) {
			return '';
		}

		return trim( preg_replace( '/\s+/', ' ', $label ) );
	}

	private static function find_check_index_for_canonical_field( array $checks, $canonical_field ) {
		$canonical_normalized = self::normalize_diagnostics_field_label( $canonical_field );
		if ( '' === $canonical_normalized ) {
			return null;
		}

		$canonical_tokens = array_values( array_filter( explode( ' ', $canonical_normalized ), 'strlen' ) );
		$best_index       = null;
		$best_score       = 0;

		foreach ( $checks as $index => $check ) {
			if ( ! is_array( $check ) ) {
				continue;
			}

			$label            = (string) ( $check['label'] ?? '' );
			$label_normalized = self::normalize_diagnostics_field_label( $label );
			if ( '' === $label_normalized ) {
				continue;
			}

			if ( $label_normalized === $canonical_normalized ) {
				return $index;
			}

			if ( false !== strpos( $label_normalized, $canonical_normalized ) || false !== strpos( $canonical_normalized, $label_normalized ) ) {
				$score = 100 + min( strlen( $label_normalized ), strlen( $canonical_normalized ) );
				if ( $score > $best_score ) {
					$best_score = $score;
					$best_index = $index;
				}
				continue;
			}

			$label_tokens = array_values( array_filter( explode( ' ', $label_normalized ), 'strlen' ) );
			if ( empty( $label_tokens ) || empty( $canonical_tokens ) ) {
				continue;
			}

			$overlap       = array_intersect( $canonical_tokens, $label_tokens );
			$overlap_count = count( $overlap );
			if ( $overlap_count < 1 ) {
				continue;
			}

			$score = ( $overlap_count * 10 ) - abs( count( $canonical_tokens ) - count( $label_tokens ) );
			if ( $score > $best_score ) {
				$best_score = $score;
				$best_index = $index;
			}
		}

		return $best_index;
	}

	private static function sync_overview_checks_from_records( array $checks, array $overview_item ) {
		$records = isset( $overview_item['overviewIssueRecords'] ) && is_array( $overview_item['overviewIssueRecords'] )
		? $overview_item['overviewIssueRecords']
		: array();

		if ( empty( $records ) ) {
			return $checks;
		}

		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$canonical_field = trim( (string) ( $record['canonical_field'] ?? '' ) );
			if ( '' === $canonical_field ) {
				continue;
			}

			$target_index = self::find_check_index_for_canonical_field( $checks, $canonical_field );
			if ( null === $target_index || ! isset( $checks[ $target_index ] ) || ! is_array( $checks[ $target_index ] ) ) {
				continue;
			}

			$canonical_status  = strtolower( trim( (string) ( $record['canonical_status'] ?? '' ) ) );
			$normalized_status = in_array( $canonical_status, array( 'pass', 'warning', 'fail' ), true ) ? $canonical_status : 'warning';

			$canonical_result = '';
			$raw_evidence     = isset( $record['raw_evidence'] ) && is_array( $record['raw_evidence'] ) ? $record['raw_evidence'] : array();
			$linked_fields    = isset( $record['linked_raw_evidence_fields'] ) && is_array( $record['linked_raw_evidence_fields'] )
			? $record['linked_raw_evidence_fields']
			: array();

			if ( ! empty( $linked_fields ) ) {
				$values = array();
				foreach ( $linked_fields as $field_key ) {
					$key = trim( (string) $field_key );
					if ( '' === $key ) {
						continue;
					}

					if ( array_key_exists( $key, $raw_evidence ) ) {
						$values[] = (string) $raw_evidence[ $key ];
					} elseif ( array_key_exists( $key, $overview_item ) ) {
						$values[] = (string) $overview_item[ $key ];
					}
				}

				if ( ! empty( $values ) ) {
					$canonical_result = implode( '/', array_filter( array_map( 'trim', $values ), 'strlen' ) );
				}
			}

			$checks[ $target_index ]['status'] = $normalized_status;
			if ( '' !== $canonical_result ) {
				$checks[ $target_index ]['result'] = $canonical_result;
			}
		}

		return $checks;
	}

	private static function apply_weightage_scores_from_checks( $post, array $overview_item, array $check_rows, $run_id_prefix = 'overview-live', array $score_context_overrides = array() ) {
		$post_id      = 0;
		$post_content = '';
		if ( $post instanceof WP_Post ) {
			$post_id      = (int) $post->ID;
			$post_content = (string) $post->post_content;
		} elseif ( is_array( $post ) ) {
			$post_id      = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
			$post_content = isset( $post['post_content'] ) ? (string) $post['post_content'] : '';
		}

		$detected_http_status = self::extract_http_status_from_checks(
			$check_rows,
			isset( $overview_item['httpStatus'] ) ? (int) $overview_item['httpStatus'] : 0
		);

		$raw_meta_title        = trim( (string) ( $overview_item['metaTitle'] ?? $overview_item['seoTitle'] ?? $overview_item['title'] ?? '' ) );
		$fallback_title_length = isset( $score_context_overrides['effectiveTitleLength'] )
		? (int) $score_context_overrides['effectiveTitleLength']
		: ( isset( $overview_item['metaTitleLength'] )
		? (int) $overview_item['metaTitleLength']
		: ( isset( $overview_item['titleLength'] ) ? (int) $overview_item['titleLength'] : 0 ) );
		if ( $fallback_title_length < 1 && '' !== $raw_meta_title ) {
			$title_plain           = wp_strip_all_tags( $raw_meta_title );
			$fallback_title_length = function_exists( 'mb_strlen' ) ? mb_strlen( $title_plain ) : strlen( $title_plain );
		}

		$detected_title_length       = max( 0, (int) $fallback_title_length );
		$detected_description_length = isset( $score_context_overrides['effectiveDescriptionLength'] )
		? (int) $score_context_overrides['effectiveDescriptionLength']
		: self::extract_numeric_check_result(
			$check_rows,
			'Meta Description Length',
			strlen( (string) ( $overview_item['seoDescription'] ?? $overview_item['metaDescription'] ?? '' ) )
		);
		$detected_internal_links     = isset( $score_context_overrides['internalLinks'] )
		? (int) $score_context_overrides['internalLinks']
		: self::extract_numeric_check_result(
			$check_rows,
			'Internal Links',
			isset( $overview_item['internalLinks'] ) ? (int) $overview_item['internalLinks'] : 0
		);
		$detected_content_words      = isset( $score_context_overrides['contentWords'] )
		? (int) $score_context_overrides['contentWords']
		: self::extract_numeric_check_result(
			$check_rows,
			'Word Count',
			isset( $overview_item['contentWords'] ) ? (int) $overview_item['contentWords'] : 0
		);

		$h1_exists_status     = self::extract_check_status( $check_rows, 'H1 Exists', '' );
		$has_heading_override = isset( $score_context_overrides['hasHeading'] )
		? (bool) $score_context_overrides['hasHeading']
		: ( '' !== $h1_exists_status
		? ( 'pass' === $h1_exists_status )
		: ( 1 === preg_match( '/<h[1-6]\b[^>]*>/i', $post_content ) ) );

		$robots_status          = self::extract_check_status( $check_rows, 'Robots Meta', '' );
		$detected_robots_index  = isset( $score_context_overrides['robotsIndex'] )
		? sanitize_key( (string) $score_context_overrides['robotsIndex'] )
		: (string) ( $overview_item['robotsIndex'] ?? 'index' );
		$detected_robots_follow = isset( $score_context_overrides['robotsFollow'] )
		? sanitize_key( (string) $score_context_overrides['robotsFollow'] )
		: (string) ( $overview_item['robotsFollow'] ?? 'follow' );
		if ( 'warning' === $robots_status || 'fail' === $robots_status || 'error' === $robots_status ) {
			$detected_robots_index  = 'noindex';
			$detected_robots_follow = 'follow';
		}

		$resolved_http_status          = isset( $score_context_overrides['httpStatus'] ) ? (int) $score_context_overrides['httpStatus'] : (int) $detected_http_status;
		$resolved_content_raw          = isset( $score_context_overrides['contentRaw'] ) ? (string) $score_context_overrides['contentRaw'] : $post_content;
		$resolved_image_count          = isset( $score_context_overrides['imageCount'] ) ? (int) $score_context_overrides['imageCount'] : ( isset( $overview_item['imageCount'] ) ? (int) $overview_item['imageCount'] : 0 );
		$resolved_images_missing_alt   = isset( $score_context_overrides['imagesMissingAlt'] ) ? (int) $score_context_overrides['imagesMissingAlt'] : ( isset( $overview_item['imagesMissingAlt'] ) ? (int) $overview_item['imagesMissingAlt'] : 0 );
		$resolved_language_declaration = isset( $score_context_overrides['languageDeclaration'] )
		? (string) $score_context_overrides['languageDeclaration']
		: (string) ( $overview_item['languageDeclaration'] ?? '' );

		$live_overview_scores = self::calculate_page_overview_scores(
			array(
				'runId'                      => sprintf( '%s-%d-%s', sanitize_key( (string) $run_id_prefix ), $post_id, gmdate( 'YmdHis' ) ),
				'effectiveTitleLength'       => $detected_title_length,
				'effectiveDescriptionLength' => $detected_description_length,
				'hasCanonical'               => ! empty( $overview_item['hasCanonical'] ),
				'robotsIndex'                => $detected_robots_index,
				'robotsFollow'               => $detected_robots_follow,
				'contentRaw'                 => $resolved_content_raw,
				'contentWords'               => $detected_content_words,
				'imageCount'                 => $resolved_image_count,
				'imagesMissingAlt'           => $resolved_images_missing_alt,
				'internalLinks'              => $detected_internal_links,
				'httpStatus'                 => $resolved_http_status,
				'hasHeading'                 => $has_heading_override,
				'languageDeclaration'        => $resolved_language_declaration,
				'siteName'                   => (string) get_bloginfo( 'name' ),
			)
		);

		$live_seo_score = isset( $live_overview_scores['seoScore'] ) ? (int) $live_overview_scores['seoScore'] : 0;
		$live_health    = 'poor';
		if ( $live_seo_score >= 85 ) {
			$live_health = 'good';
		} elseif ( $live_seo_score >= 65 ) {
			$live_health = 'warning';
		}

		$overview_item['httpStatus']                 = $resolved_http_status;
		$overview_item['xRobotsTag']                 = self::extract_x_robots_tag_from_checks( $check_rows, (string) ( $overview_item['xRobotsTag'] ?? '' ) );
		$overview_item['metaTitleLength']            = (int) $detected_title_length;
		$overview_item['titleLength']                = (int) $detected_title_length;
		$overview_item['effectiveTitleLength']       = (int) $detected_title_length;
		$overview_item['descriptionLength']          = (int) $detected_description_length;
		$overview_item['effectiveDescriptionLength'] = (int) $detected_description_length;
		$overview_item['seoScore']                   = $live_seo_score;
		$overview_item['aiScore']                    = isset( $live_overview_scores['aiScore'] ) ? (int) $live_overview_scores['aiScore'] : (int) ( $overview_item['aiScore'] ?? 0 );
		$overview_item['health']                     = $live_health;
		$overview_item['issueGroups']                = isset( $live_overview_scores['issueGroups'] ) && is_array( $live_overview_scores['issueGroups'] ) ? $live_overview_scores['issueGroups'] : array();
		$overview_item['overviewIssueRecords']       = isset( $live_overview_scores['overviewIssueRecords'] ) && is_array( $live_overview_scores['overviewIssueRecords'] ) ? $live_overview_scores['overviewIssueRecords'] : array();
		$overview_item['overviewScoreRecords']       = isset( $live_overview_scores['overviewScoreRecords'] ) && is_array( $live_overview_scores['overviewScoreRecords'] ) ? $live_overview_scores['overviewScoreRecords'] : array();
		$overview_item['aiIssueRecords']             = isset( $live_overview_scores['aiIssueRecords'] ) && is_array( $live_overview_scores['aiIssueRecords'] ) ? $live_overview_scores['aiIssueRecords'] : array();
		$overview_item['aiCanonicalSignals']         = isset( $live_overview_scores['aiCanonicalSignals'] ) && is_array( $live_overview_scores['aiCanonicalSignals'] ) ? $live_overview_scores['aiCanonicalSignals'] : array();
		$overview_item['overviewRunId']              = isset( $live_overview_scores['overviewRunId'] ) ? (string) $live_overview_scores['overviewRunId'] : '';
		$overview_item['seoScoreMessage']            = isset( $live_overview_scores['seoScoreMessage'] ) ? sanitize_text_field( (string) $live_overview_scores['seoScoreMessage'] ) : '';

		return $overview_item;
	}

	private static function normalize_space( $value ) {
		$string_value = is_scalar( $value ) ? (string) $value : '';
		$normalized   = preg_replace( '/\s+/u', ' ', $string_value );
		if ( ! is_string( $normalized ) ) {
			return trim( $string_value );
		}

		return trim( $normalized );
	}

	private static function count_words_from_html( $html ) {
		$text = self::normalize_space( wp_strip_all_tags( (string) $html ) );
		if ( '' === $text ) {
			return 0;
		}

		$tokens = preg_split( '/\s+/u', $text );
		if ( ! is_array( $tokens ) ) {
			return 0;
		}

		return count( array_filter( $tokens, 'strlen' ) );
	}

	private static function count_internal_links_from_html( $html, $site_host ) {
		$internal_links = 0;
		if ( ! preg_match_all( '/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', (string) $html, $href_matches, PREG_SET_ORDER ) ) {
			return 0;
		}

		$normalized_site_host = strtolower( trim( (string) $site_host ) );
		foreach ( $href_matches as $href_match ) {
			$raw_href = '';
			if ( isset( $href_match[1] ) && '' !== $href_match[1] ) {
				$raw_href = $href_match[1];
			} elseif ( isset( $href_match[2] ) && '' !== $href_match[2] ) {
				$raw_href = $href_match[2];
			} elseif ( isset( $href_match[3] ) ) {
				$raw_href = $href_match[3];
			}

			$href = trim( html_entity_decode( (string) $raw_href, ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $href ) {
				continue;
			}

			if ( 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) || 0 === strpos( $href, 'javascript:' ) ) {
				continue;
			}

			if ( 0 === strpos( $href, '/' ) || 0 === strpos( $href, '#' ) ) {
				++$internal_links;
				continue;
			}

			$href_host   = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
			$href_scheme = strtolower( (string) wp_parse_url( $href, PHP_URL_SCHEME ) );

			if ( '' === $href_host && '' === $href_scheme ) {
				++$internal_links;
				continue;
			}

			if ( '' !== $href_host && '' !== $normalized_site_host && $href_host === $normalized_site_host ) {
				++$internal_links;
			}
		}

		return $internal_links;
	}

	private static function count_link_metrics_from_html( $html, $site_host ) {
		$metrics = array(
			'internal' => 0,
			'external' => 0,
			'nofollow' => 0,
		);

		if ( ! preg_match_all( '/<a\b[^>]*>/i', (string) $html, $anchor_matches ) ) {
			return $metrics;
		}

		$normalized_site_host = strtolower( trim( (string) $site_host ) );
		foreach ( $anchor_matches[0] as $anchor_tag ) {
			$href = '';
			if ( preg_match( '/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $anchor_tag, $href_match ) ) {
				if ( isset( $href_match[1] ) && '' !== $href_match[1] ) {
					$href = $href_match[1];
				} elseif ( isset( $href_match[2] ) && '' !== $href_match[2] ) {
					$href = $href_match[2];
				} elseif ( isset( $href_match[3] ) ) {
					$href = $href_match[3];
				}
			}

			$href = trim( html_entity_decode( (string) $href, ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $href ) {
				continue;
			}

			if ( 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) || 0 === strpos( $href, 'javascript:' ) ) {
				continue;
			}

			$is_internal = false;
			if ( 0 === strpos( $href, '/' ) || 0 === strpos( $href, '#' ) ) {
				$is_internal = true;
			} else {
				$href_host   = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
				$href_scheme = strtolower( (string) wp_parse_url( $href, PHP_URL_SCHEME ) );

				if ( '' === $href_host && '' === $href_scheme ) {
					$is_internal = true;
				} elseif ( '' !== $href_host && '' !== $normalized_site_host && $href_host === $normalized_site_host ) {
					$is_internal = true;
				}
			}

			if ( $is_internal ) {
				++$metrics['internal'];
			} else {
				++$metrics['external'];
			}

			$rel_value = '';
			if ( preg_match( '/\brel\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $anchor_tag, $rel_match ) ) {
				if ( isset( $rel_match[1] ) && '' !== $rel_match[1] ) {
					$rel_value = $rel_match[1];
				} elseif ( isset( $rel_match[2] ) && '' !== $rel_match[2] ) {
					$rel_value = $rel_match[2];
				} elseif ( isset( $rel_match[3] ) ) {
					$rel_value = $rel_match[3];
				}
			}

			if ( '' !== $rel_value ) {
				$rels = preg_split( '/\s+/', strtolower( trim( $rel_value ) ) );
				if ( is_array( $rels ) && in_array( 'nofollow', $rels, true ) ) {
					++$metrics['nofollow'];
				}
			}
		}

		return $metrics;
	}

	private static function clamp_score( $value ) {
		$number = (int) round( (float) $value );
		if ( $number < 0 ) {
			return 0;
		}
		if ( $number > 100 ) {
			return 100;
		}

		return $number;
	}

	private static function calculate_keyword_stats( $text ) {
		$stop_words  = array(
			'the',
			'and',
			'for',
			'with',
			'that',
			'this',
			'from',
			'your',
			'have',
			'will',
			'into',
			'about',
			'page',
			'content',
		);
		$stop_lookup = array_fill_keys( $stop_words, true );
		$tokens      = preg_split( '/[^a-z0-9]+/i', (string) $text );
		if ( ! is_array( $tokens ) ) {
			return array(
				'topCount' => 0,
				'ratio'    => 0.0,
			);
		}

		$counts = array();
		$total  = 0;
		foreach ( $tokens as $token ) {
			$word = strtolower( trim( (string) $token ) );
			if ( strlen( $word ) < 4 || isset( $stop_lookup[ $word ] ) ) {
				continue;
			}
			++$total;
			if ( ! isset( $counts[ $word ] ) ) {
				$counts[ $word ] = 0;
			}
			++$counts[ $word ];
		}

		$top_count = 0;
		foreach ( $counts as $count ) {
			if ( $count > $top_count ) {
				$top_count = (int) $count;
			}
		}

		return array(
			'topCount' => $top_count,
			'ratio'    => $total > 0 ? ( (float) $top_count / (float) $total ) : 0.0,
		);
	}

	private static function calculate_page_overview_scores( $context ) {
		$title_length         = isset( $context['effectiveTitleLength'] ) ? (int) $context['effectiveTitleLength'] : 0;
		$description_length   = isset( $context['effectiveDescriptionLength'] ) ? (int) $context['effectiveDescriptionLength'] : 0;
		$has_canonical        = isset( $context['hasCanonical'] ) ? (bool) $context['hasCanonical'] : false;
		$robots_index         = isset( $context['robotsIndex'] ) ? sanitize_key( (string) $context['robotsIndex'] ) : 'index';
		$robots_follow        = isset( $context['robotsFollow'] ) ? sanitize_key( (string) $context['robotsFollow'] ) : 'follow';
		$content_raw          = isset( $context['contentRaw'] ) ? (string) $context['contentRaw'] : '';
		$content_words        = isset( $context['contentWords'] ) ? (int) $context['contentWords'] : 0;
		$image_count          = isset( $context['imageCount'] ) ? (int) $context['imageCount'] : 0;
		$images_missing_alt   = isset( $context['imagesMissingAlt'] ) ? (int) $context['imagesMissingAlt'] : 0;
		$internal_links       = isset( $context['internalLinks'] ) ? (int) $context['internalLinks'] : 0;
		$http_status          = isset( $context['httpStatus'] ) ? (int) $context['httpStatus'] : 0;
		$site_name            = strtolower( trim( (string) ( $context['siteName'] ?? '' ) ) );
		$language_declaration = strtolower( str_replace( '_', '-', sanitize_text_field( (string) ( $context['languageDeclaration'] ?? '' ) ) ) );
		$meta_partial_credit  = ! isset( $context['metaPartialCredit'] ) || (bool) $context['metaPartialCredit'];
		$has_heading          = isset( $context['hasHeading'] ) ? (bool) $context['hasHeading'] : ( 1 === preg_match( '/<h[1-6]\\b[^>]*>/i', $content_raw ) );
		$alt_coverage         = $image_count > 0 ? ( ( (float) max( 0, $image_count - $images_missing_alt ) / (float) $image_count ) * 100.0 ) : 100.0;

		$title_length_points       = ( $title_length >= 30 && $title_length <= 60 ) ? 10 : ( ( $meta_partial_credit && $title_length > 0 ) ? 5 : 0 );
		$description_length_points = ( $description_length >= 120 && $description_length <= 160 ) ? 10 : ( ( $meta_partial_credit && $description_length > 0 ) ? 5 : 0 );
		$robots_points             = ( 'index' === $robots_index && 'follow' === $robots_follow ) ? 20 : 10;
		$http_status_points        = ( $http_status >= 200 && $http_status < 300 ) ? 30 : ( $http_status >= 300 && $http_status < 400 ? 15 : 0 );
		$heading_points            = $has_heading ? 10 : 2;
		$internal_points           = $internal_links >= 2 ? 10 : ( 1 === $internal_links ? 6 : 2 );
		$content_depth_points      = $content_words >= 300 ? 10 : 5;

		$seo_score = self::clamp_score(
			$title_length_points +
			$description_length_points +
			$robots_points +
			$http_status_points +
			$heading_points +
			$internal_points +
			$content_depth_points
		);

		$content_text   = self::normalize_space( strtolower( wp_strip_all_tags( $content_raw ) ) );
		$heading_levels = array();
		if ( preg_match_all( '/<h([1-6])\\b[^>]*>(.*?)<\\/h\\1>/is', $content_raw, $heading_matches, PREG_SET_ORDER ) ) {
			foreach ( $heading_matches as $heading_match ) {
				$heading_levels[] = (int) $heading_match[1];
			}
		}
		$heading_count   = count( $heading_levels );
		$has_h1          = in_array( 1, $heading_levels, true );
		$hierarchy_valid = true;
		for ( $index = 1; $index < $heading_count; $index++ ) {
			if ( abs( $heading_levels[ $index ] - $heading_levels[ $index - 1 ] ) > 2 ) {
				$hierarchy_valid = false;
				break;
			}
		}

		$has_list         = 1 === preg_match( '/<(ul|ol)\\b/i', $content_raw );
		$has_table        = 1 === preg_match( '/<table\\b/i', $content_raw );
		$word_count       = self::count_words_from_html( $content_raw );
		$sentences        = preg_split( '/[.!?]+/u', $content_text );
		$sentence_lengths = array();
		if ( is_array( $sentences ) ) {
			foreach ( $sentences as $sentence ) {
				$normalized_sentence = self::normalize_space( $sentence );
				if ( '' === $normalized_sentence ) {
					continue;
				}
				$sentence_lengths[] = count( preg_split( '/\\s+/u', $normalized_sentence ) );
			}
		}
		$avg_sentence_length = count( $sentence_lengths ) > 0 ? (int) round( array_sum( $sentence_lengths ) / count( $sentence_lengths ) ) : 0;
		$keyword_stats       = self::calculate_keyword_stats( $content_text );

		$signal_h1_present               = $has_h1;
		$signal_heading_hierarchy_valid  = $hierarchy_valid;
		$signal_sections_coverage        = $heading_count >= 3;
		$signal_list_detected            = $has_list;
		$signal_table_detected           = $has_table;
		$signal_clear_page_purpose       = ( $word_count >= 180 && $heading_count >= 1 );
		$signal_topic_consistency        = ( $keyword_stats['topCount'] >= 3 && $keyword_stats['ratio'] >= 0.03 && $keyword_stats['ratio'] <= 0.16 );
		$signal_summary_section          = 1 === preg_match( '/(summary|in summary|conclusion|tl;dr)/i', $content_text );
		$signal_readability              = ( $avg_sentence_length >= 8 && $avg_sentence_length <= 24 );
		$signal_brand_mentions           = ( '' !== $site_name && false !== strpos( $content_text, $site_name ) );
		$signal_product_context_mentions = 1 === preg_match( '/(plugin|toolkit|product|service|platform)/i', $content_text );
		$signal_faq                      = 1 === preg_match( '/(faq|frequently asked)/i', $content_text );
		$signal_definition               = 1 === preg_match( '/(defined as|means|for example|e\.g\.|how to|step-by-step|steps)/i', $content_text );
		$signal_trust                    = 1 === preg_match( '/(author|written by|contact|support|about|updated|last updated|20\d\d)/i', $content_text );
		$signal_content_completeness     = ( $word_count >= 300 && $internal_links >= 2 && $image_count >= 1 );
		$signal_language_declaration     = '' !== $language_declaration;
		$signal_internal_references      = $internal_links >= 1;

		$ai_points_total  = 0;
		$ai_points_total += $signal_h1_present ? 8 : 3;
		$ai_points_total += $signal_heading_hierarchy_valid ? 8 : 4;
		$ai_points_total += $signal_sections_coverage ? 8 : 4;
		$ai_points_total += $signal_list_detected ? 5 : 2;
		$ai_points_total += $signal_table_detected ? 4 : 2;
		$ai_points_total += $signal_clear_page_purpose ? 7 : 3;
		$ai_points_total += $signal_topic_consistency ? 7 : 3;
		$ai_points_total += $signal_summary_section ? 5 : 2;
		$ai_points_total += $signal_readability ? 6 : 3;
		$ai_points_total += $signal_brand_mentions ? 6 : 2;
		$ai_points_total += $signal_product_context_mentions ? 5 : 2;
		$ai_points_total += $signal_faq ? 7 : 2;
		$ai_points_total += $signal_definition ? 7 : 3;
		$ai_points_total += $signal_trust ? 10 : 4;
		$ai_points_total += $signal_content_completeness ? 15 : 8;
		$ai_score         = self::clamp_score( $ai_points_total );

		$run_id                       = isset( $context['runId'] ) ? sanitize_text_field( (string) $context['runId'] ) : gmdate( 'YmdHis' ) . '-overview';
		$canonical_checks             = array();
		$canonical_checks[]           = array(
			'category'                   => 'meta',
			'canonical_field'            => 'SEO Title Length',
			'canonical_status'           => $title_length >= 30 && $title_length <= 60 ? 'pass' : ( $title_length > 0 ? 'warning' : 'fail' ),
			'linked_raw_evidence_fields' => array( 'effectiveTitleLength' ),
			'raw_evidence'               => array( 'effectiveTitleLength' => $title_length ),
			'score_impact'               => max( 0, 10 - $title_length_points ),
			'recommended_fix'            => 'Target 30-60 characters for the title.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'meta',
			'canonical_field'            => 'Meta Description Length',
			'canonical_status'           => $description_length >= 120 && $description_length <= 160 ? 'pass' : ( $description_length > 0 ? 'warning' : 'fail' ),
			'linked_raw_evidence_fields' => array( 'effectiveDescriptionLength' ),
			'raw_evidence'               => array( 'effectiveDescriptionLength' => $description_length ),
			'score_impact'               => max( 0, 10 - $description_length_points ),
			'recommended_fix'            => 'Target 120-160 characters for the description.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'meta',
			'canonical_field'            => 'Canonical',
			'canonical_status'           => $has_canonical ? 'pass' : 'warning',
			'linked_raw_evidence_fields' => array( 'hasCanonical' ),
			'raw_evidence'               => array( 'hasCanonical' => $has_canonical ),
			'score_impact'               => 0,
			'recommended_fix'            => 'Set a canonical URL for this page.',
		);
		$robots_raw_states            = array( 'index' === $robots_index ? 'pass' : 'fail', 'follow' === $robots_follow ? 'pass' : 'fail' );
		$canonical_checks[]           = array(
			'category'                   => 'indexability',
			'canonical_field'            => 'Robots Meta',
			'canonical_status'           => self::canonical_status_from_raw_states( $robots_raw_states ),
			'linked_raw_evidence_fields' => array( 'robotsIndex', 'robotsFollow' ),
			'raw_evidence'               => array(
				'robotsIndex'  => $robots_index,
				'robotsFollow' => $robots_follow,
			),
			'score_impact'               => max( 0, 20 - $robots_points ),
			'recommended_fix'            => 'Use index and follow for crawlable pages.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'indexability',
			'canonical_field'            => 'HTTP Status',
			'canonical_status'           => ( $http_status >= 200 && $http_status < 300 ) ? 'pass' : ( $http_status >= 300 && $http_status < 400 ? 'warning' : 'fail' ),
			'linked_raw_evidence_fields' => array( 'httpStatus' ),
			'raw_evidence'               => array( 'httpStatus' => $http_status ),
			'score_impact'               => max( 0, 30 - $http_status_points ),
			'recommended_fix'            => 'Ensure this URL resolves to HTTP 200 for the primary page.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'content',
			'canonical_field'            => 'H1 Presence',
			'canonical_status'           => $has_heading ? 'pass' : 'warning',
			'linked_raw_evidence_fields' => array( 'hasHeading' ),
			'raw_evidence'               => array( 'hasHeading' => $has_heading ),
			'score_impact'               => max( 0, 10 - $heading_points ),
			'recommended_fix'            => 'Add a clear H1 heading in page content.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'images',
			'canonical_field'            => 'Image ALT Coverage',
			'canonical_status'           => 0 === $image_count ? 'warning' : ( $alt_coverage >= 80.0 ? 'pass' : 'warning' ),
			'linked_raw_evidence_fields' => array( 'imageCount', 'imagesMissingAlt' ),
			'raw_evidence'               => array(
				'imageCount'       => $image_count,
				'imagesMissingAlt' => $images_missing_alt,
			),
			'score_impact'               => 0,
			'recommended_fix'            => 'Improve ALT coverage to at least 80% across images.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'content',
			'canonical_field'            => 'Internal Links',
			'canonical_status'           => $internal_links >= 2 ? 'pass' : ( 1 === $internal_links ? 'warning' : 'fail' ),
			'linked_raw_evidence_fields' => array( 'internalLinks' ),
			'raw_evidence'               => array( 'internalLinks' => $internal_links ),
			'score_impact'               => max( 0, 10 - $internal_points ),
			'recommended_fix'            => 'Add at least 2 internal links.',
		);
		$canonical_checks[]           = array(
			'category'                   => 'content',
			'canonical_field'            => 'Content Depth (Word Count)',
			'canonical_status'           => $content_words >= 300 ? 'pass' : 'warning',
			'linked_raw_evidence_fields' => array( 'contentWords' ),
			'raw_evidence'               => array( 'contentWords' => $content_words ),
			'score_impact'               => max( 0, 10 - $content_depth_points ),
			'recommended_fix'            => 'Expand content depth to at least 300 words where appropriate.',
		);
		$overview_score_records       = self::build_overview_score_records( $canonical_checks, $run_id );
		$overview_issue_records       = self::build_overview_issue_records( $canonical_checks, $run_id );
		$ai_table_list_status         = self::canonical_status_from_raw_states( array( $signal_list_detected ? 'pass' : 'warning', $signal_table_detected ? 'pass' : 'warning' ) );
		$ai_clear_page_purpose_status = self::canonical_status_from_raw_states( array( $signal_h1_present ? 'pass' : 'warning', $signal_heading_hierarchy_valid ? 'pass' : 'warning', $signal_sections_coverage ? 'pass' : 'warning', $signal_clear_page_purpose ? 'pass' : 'warning', $signal_readability ? 'pass' : 'warning' ) );
		$ai_canonical_signals         = array(
			'Topic Consistency'        => array(
				'canonical_field'            => 'Topic Consistency',
				'canonical_status'           => $signal_topic_consistency ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'keywordTopCount', 'keywordRatio' ),
				'raw_evidence'               => array(
					'keywordTopCount' => (int) $keyword_stats['topCount'],
					'keywordRatio'    => (float) $keyword_stats['ratio'],
				),
				'result'                     => $signal_topic_consistency ? 'Aligned' : 'Needs work',
				'details'                    => 'Source: keyword top-term concentration from page content.',
			),
			'Clear Page Purpose'       => array(
				'canonical_field'            => 'Clear Page Purpose',
				'canonical_status'           => $ai_clear_page_purpose_status,
				'linked_raw_evidence_fields' => array( 'h1Present', 'headingHierarchyValid', 'sectionsCoverage', 'wordCount', 'avgSentenceLength' ),
				'raw_evidence'               => array(
					'h1Present'             => (bool) $signal_h1_present,
					'headingHierarchyValid' => (bool) $signal_heading_hierarchy_valid,
					'sectionsCoverage'      => (bool) $signal_sections_coverage,
					'wordCount'             => (int) $word_count,
					'avgSentenceLength'     => (int) $avg_sentence_length,
				),
				'result'                     => 'Composite',
				'details'                    => 'Source: heading structure, sections coverage, readability, and content depth signals.',
			),
			'Summary Section'          => array(
				'canonical_field'            => 'Summary Section',
				'canonical_status'           => $signal_summary_section ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'summaryPattern' ),
				'raw_evidence'               => array( 'summaryPattern' => (bool) $signal_summary_section ),
				'result'                     => $signal_summary_section ? 'Detected' : 'Not detected',
				'details'                    => 'Source: summary/conclusion pattern detection.',
			),
			'Content Completeness'     => array(
				'canonical_field'            => 'Content Completeness',
				'canonical_status'           => $signal_content_completeness ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'wordCount', 'internalLinks', 'imageCount' ),
				'raw_evidence'               => array(
					'wordCount'     => (int) $word_count,
					'internalLinks' => (int) $internal_links,
					'imageCount'    => (int) $image_count,
				),
				'result'                     => $signal_content_completeness ? 'Complete' : 'Needs improvement',
				'details'                    => 'Source: word count, internal linking, and image coverage.',
			),
			'Brand Mentions'           => array(
				'canonical_field'            => 'Brand Mentions',
				'canonical_status'           => $signal_brand_mentions ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'siteName', 'contentText' ),
				'raw_evidence'               => array(
					'siteName'          => (string) $site_name,
					'siteNameMentioned' => (bool) $signal_brand_mentions,
				),
				'result'                     => $signal_brand_mentions ? 'Detected' : 'Not detected',
				'details'                    => 'Source: site-name mention detection in content.',
			),
			'Product/Context Mentions' => array(
				'canonical_field'            => 'Product/Context Mentions',
				'canonical_status'           => $signal_product_context_mentions ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'productContextPattern' ),
				'raw_evidence'               => array( 'productContextPattern' => (bool) $signal_product_context_mentions ),
				'result'                     => $signal_product_context_mentions ? 'Detected' : 'Not detected',
				'details'                    => 'Source: product/context phrase detection.',
			),
			'Trust Signals'            => array(
				'canonical_field'            => 'Trust Signals',
				'canonical_status'           => $signal_trust ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'trustPattern' ),
				'raw_evidence'               => array( 'trustPattern' => (bool) $signal_trust ),
				'result'                     => $signal_trust ? 'Detected' : 'Not detected',
				'details'                    => 'Source: trust/author/contact/update pattern detection.',
			),
			'Table/List Detection'     => array(
				'canonical_field'            => 'Table/List Detection',
				'canonical_status'           => $ai_table_list_status,
				'linked_raw_evidence_fields' => array( 'listDetected', 'tableDetected' ),
				'raw_evidence'               => array(
					'listDetected'  => (bool) $signal_list_detected,
					'tableDetected' => (bool) $signal_table_detected,
				),
				'result'                     => ( $signal_list_detected || $signal_table_detected ) ? 'Detected' : 'Not detected',
				'details'                    => 'Source: HTML list/table structure checks.',
			),
			'Definition Content'       => array(
				'canonical_field'            => 'Definition Content',
				'canonical_status'           => $signal_definition ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'definitionPattern' ),
				'raw_evidence'               => array( 'definitionPattern' => (bool) $signal_definition ),
				'result'                     => $signal_definition ? 'Detected' : 'Not detected',
				'details'                    => 'Source: definition/examples/how-to pattern detection.',
			),
			'FAQ Signals'              => array(
				'canonical_field'            => 'FAQ Signals',
				'canonical_status'           => $signal_faq ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'faqPattern' ),
				'raw_evidence'               => array( 'faqPattern' => (bool) $signal_faq ),
				'result'                     => $signal_faq ? 'Detected' : 'Not detected',
				'details'                    => 'Source: FAQ phrase detection in content.',
			),
			'Language Declaration'     => array(
				'canonical_field'            => 'Language Declaration',
				'canonical_status'           => $signal_language_declaration ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'languageDeclaration' ),
				'raw_evidence'               => array( 'languageDeclaration' => (string) $language_declaration ),
				'result'                     => '' !== $language_declaration ? $language_declaration : 'Not detected',
				'details'                    => 'Source: WordPress site language setting.',
			),
			'Internal References'      => array(
				'canonical_field'            => 'Internal References',
				'canonical_status'           => $signal_internal_references ? 'pass' : 'warning',
				'linked_raw_evidence_fields' => array( 'internalLinks' ),
				'raw_evidence'               => array( 'internalLinks' => (int) $internal_links ),
				'result'                     => (string) $internal_links,
				'details'                    => 'Source: internal links count from page content.',
			),
		);
		$ai_canonical_checks          = array();
		foreach ( $ai_canonical_signals as $ai_signal ) {
			if ( ! is_array( $ai_signal ) ) {
				continue;
			}
			$ai_canonical_checks[] = array(
				'category'                   => 'ai',
				'canonical_field'            => isset( $ai_signal['canonical_field'] ) ? (string) $ai_signal['canonical_field'] : 'AI Signal',
				'canonical_status'           => isset( $ai_signal['canonical_status'] ) ? (string) $ai_signal['canonical_status'] : 'warning',
				'linked_raw_evidence_fields' => isset( $ai_signal['linked_raw_evidence_fields'] ) && is_array( $ai_signal['linked_raw_evidence_fields'] ) ? $ai_signal['linked_raw_evidence_fields'] : array(),
				'raw_evidence'               => isset( $ai_signal['raw_evidence'] ) && is_array( $ai_signal['raw_evidence'] ) ? $ai_signal['raw_evidence'] : array(),
				'score_impact'               => 0,
				'recommended_fix'            => isset( $ai_signal['details'] ) ? (string) $ai_signal['details'] : '',
			);
		}
		$ai_issue_records = self::build_overview_issue_records( $ai_canonical_checks, $run_id );
		$issue_groups     = array(
			'meta'         => false,
			'indexability' => false,
			'content'      => false,
			'ai'           => false,
		);
		foreach ( array_merge( $overview_issue_records, $ai_issue_records ) as $issue_record ) {
			$category = isset( $issue_record['category'] ) ? sanitize_key( (string) $issue_record['category'] ) : '';
			if ( isset( $issue_groups[ $category ] ) ) {
				$issue_groups[ $category ] = true;
			}
		}

		return array(
			'seoScore'             => $seo_score,
			'aiScore'              => $ai_score,
			'seoScoreMessage'      => '',
			'issueGroups'          => $issue_groups,
			'overviewScoreRecords' => isset( $overview_score_records ) && is_array( $overview_score_records ) ? $overview_score_records : array(),
			'overviewIssueRecords' => $overview_issue_records,
			'aiIssueRecords'       => $ai_issue_records,
			'aiCanonicalSignals'   => $ai_canonical_signals,
			'overviewRunId'        => $run_id,
		);
	}

	private static function canonical_status_from_raw_states( $states ) {
		if ( ! is_array( $states ) || empty( $states ) ) {
			return 'warning';
		}

		$sum   = 0.0;
		$count = 0;
		foreach ( $states as $state ) {
			$normalized = sanitize_key( (string) $state );
			if ( 'pass' === $normalized ) {
				$sum += 1.0;
			} elseif ( 'warning' === $normalized || 'warn' === $normalized ) {
				$sum += 0.5;
			}
			++$count;
		}

		if ( $count < 1 ) {
			return 'warning';
		}

		$score = $sum / (float) $count;
		if ( $score >= 0.85 ) {
			return 'pass';
		}
		if ( $score >= 0.5 ) {
			return 'warning';
		}

		return 'fail';
	}

	private static function resolve_raw_evidence_value( array $raw_evidence, $field_name ) {
		$key = trim( (string) $field_name );
		if ( '' === $key ) {
			return null;
		}

		if ( array_key_exists( $key, $raw_evidence ) ) {
			return $raw_evidence[ $key ];
		}

		$normalized_key = sanitize_key( $key );
		if ( '' !== $normalized_key ) {
			foreach ( $raw_evidence as $raw_key => $value ) {
				if ( sanitize_key( (string) $raw_key ) === $normalized_key ) {
					return $value;
				}
			}
		}

		foreach ( $raw_evidence as $raw_key => $value ) {
			if ( strtolower( trim( (string) $raw_key ) ) === strtolower( $key ) ) {
				return $value;
			}
		}

		return null;
	}

	private static function build_overview_score_records( $canonical_checks, $run_id ) {
		if ( ! is_array( $canonical_checks ) || empty( $canonical_checks ) ) {
			return array();
		}

		$records = array();
		foreach ( $canonical_checks as $canonical_check ) {
			$status = isset( $canonical_check['canonical_status'] ) ? sanitize_key( (string) $canonical_check['canonical_status'] ) : 'warning';

			$linked_fields = array();
			if ( isset( $canonical_check['linked_raw_evidence_fields'] ) && is_array( $canonical_check['linked_raw_evidence_fields'] ) ) {
				foreach ( $canonical_check['linked_raw_evidence_fields'] as $field_name ) {
					$linked_field = trim( (string) $field_name );
					if ( '' !== $linked_field ) {
						$linked_fields[] = $linked_field;
					}
				}
			}

			$records[] = array(
				'run_id'                     => sanitize_text_field( (string) $run_id ),
				'category'                   => isset( $canonical_check['category'] ) ? sanitize_key( (string) $canonical_check['category'] ) : 'overview',
				'canonical_field'            => sanitize_text_field( (string) ( $canonical_check['canonical_field'] ?? 'Unknown Canonical Field' ) ),
				'canonical_status'           => $status,
				'linked_raw_evidence_fields' => $linked_fields,
				'raw_evidence'               => isset( $canonical_check['raw_evidence'] ) && is_array( $canonical_check['raw_evidence'] ) ? $canonical_check['raw_evidence'] : array(),
				'score_impact'               => isset( $canonical_check['score_impact'] ) ? max( 0, (int) $canonical_check['score_impact'] ) : 0,
				'recommended_fix'            => sanitize_text_field( (string) ( $canonical_check['recommended_fix'] ?? '' ) ),
			);
		}

		return $records;
	}

	private static function build_overview_issue_records( $canonical_checks, $run_id ) {
		return array_values(
			array_filter(
				self::build_overview_score_records( $canonical_checks, $run_id ),
				function ( $record ) {
					return is_array( $record ) && 'pass' !== sanitize_key( (string) ( $record['canonical_status'] ?? '' ) );
				}
			)
		);
	}
}
