<?php
/**
 * Diagnostics helper functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_Diagnostics {

	/**
	 * Map diagnostics check labels to workspace taxonomy categories.
	 */
	private static function categorize_check_label( $label ) {
		$normalized = strtolower( trim( (string) $label ) );

		if ( preg_match( '/seo title|seo title length|meta description|meta description length|google preview/', $normalized ) ) {
			return 'search';
		}

		if ( preg_match( '/open graph|twitter/', $normalized ) ) {
			return 'social';
		}

		if ( preg_match( '/schema|structured data|organization schema|article schema|faq schema|breadcrumb schema/', $normalized ) ) {
			return 'schema';
		}

		if ( preg_match( '/canonical|indexability|http status|redirect status|final destination|robots meta|x-robots-tag|data source|page fetch|local fallback|post freshness|post context/', $normalized ) ) {
			return 'advanced';
		}

		if ( preg_match( '/internal links|external links|nofollow links/', $normalized ) ) {
			return 'links';
		}

		if ( preg_match( '/word count|h1|multiple h1|images found|missing alt|empty alt|featured image|content present/', $normalized ) ) {
			return 'quality';
		}

		if ( preg_match( '/readability|sections coverage|heading structure|semantic heading structure|topic consistency|clear page purpose|summary section|semantic headings|faq ready|faq content|structured content|structured data present|schema validation|primary entity|author information|published date|last updated date|organization information|language declaration|internal references|external references|table\/list detection|definition content|media context/', $normalized ) ) {
			return 'ai';
		}

		return 'overview';
	}

	/**
	 * Ensure every check includes a stable taxonomy category for UI grouping.
	 */
	private static function with_check_categories( $checks ) {
		if ( ! is_array( $checks ) ) {
			return array();
		}

		foreach ( $checks as $index => $check ) {
			if ( ! is_array( $check ) ) {
				continue;
			}

			if ( empty( $check['category'] ) ) {
				$check['category'] = self::categorize_check_label( $check['label'] ?? '' );
			}

			$checks[ $index ] = $check;
		}

		return $checks;
	}

	public static function ajax_http_test() {
		check_ajax_referer( 'ASNERISSEO_http_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		// Rate limit: one outbound fetch per user per 5 seconds.
		$rate_key = 'asnerisseo_http_test_' . get_current_user_id();
		if ( get_transient( $rate_key ) ) {
			wp_send_json_error( 'Too many requests. Please wait a moment before running another test.' );
			return;
		}
		set_transient( $rate_key, 1, 5 );

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( empty( $url ) ) {
			wp_send_json_error( 'No URL provided' );
			return;
		}

		// Validate URL format
		if ( ! wp_http_validate_url( $url ) ) {
			wp_send_json_error( 'Invalid URL provided' );
			return;
		}

		// SSRF Protection: Only allow URLs from this site
		$host      = wp_parse_url( $url, PHP_URL_HOST );
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! $host || ! $site_host || strtolower( $host ) !== strtolower( $site_host ) ) {
			wp_send_json_error( 'Security: Only URLs from this site can be analyzed' );
			return;
		}

		$checks = self::http_test_checks( $url );

		if ( is_wp_error( $checks ) ) {
			wp_send_json_error( $checks->get_error_message() );
			return;
		}

		wp_send_json_success( array( 'checks' => $checks ) );
	}

	public static function http_test_checks( $url ) {
		$checks               = array();
		$head_error_message   = '';
		$head_x_robots_header = '';

		$response = wp_remote_head(
			$url,
			array(
				'timeout'            => 10,
				'redirection'        => 0,
				'reject_unsafe_urls' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			$head_error_message = $response->get_error_message();
			$checks[]           = array(
				'label'   => 'HTTP Status',
				'status'  => 'fail',
				'result'  => 'Error',
				'details' => $head_error_message,
			);
		} else {
			$status_code          = wp_remote_retrieve_response_code( $response );
			$status_type          = $status_code === 200 ? 'pass' : ( $status_code >= 300 && $status_code < 400 ? 'warning' : 'fail' );
			$head_x_robots_header = trim( (string) wp_remote_retrieve_header( $response, 'x-robots-tag' ) );

			$checks[] = array(
				'label'             => 'HTTP Status',
				'status'            => $status_type,
				'result'            => $status_code,
				'details'           => 'Direct response from server' . ( '' !== $head_x_robots_header ? ' | X-Robots-Tag: ' . $head_x_robots_header : '' ),
				'rawEvidence'       => array(
					'httpStatus' => (int) $status_code,
					'xRobotsTag' => $head_x_robots_header,
				),
				'rawEvidenceFields' => array( 'httpStatus', 'xRobotsTag' ),
			);

			$redirect_location = wp_remote_retrieve_header( $response, 'location' );
			if ( ! empty( $redirect_location ) ) {
				$checks[] = array(
					'label'   => 'Redirect Status',
					'status'  => 'warning',
					'result'  => 'Redirect detected',
					'details' => esc_html( $redirect_location ),
				);

				$final_response = wp_remote_head(
					$url,
					array(
						'timeout'            => 10,
						'redirection'        => 5,
						'reject_unsafe_urls' => true,
					)
				);

				if ( ! is_wp_error( $final_response ) ) {
						$final_status = wp_remote_retrieve_response_code( $final_response );
						$final_url    = wp_remote_retrieve_header( $final_response, 'location' );
					if ( empty( $final_url ) ) {
						$final_url = $url;
					}

						$checks[] = array(
							'label'   => 'Final Destination',
							'status'  => $final_status === 200 ? 'pass' : 'fail',
							'result'  => $final_status,
							'details' => 'After following redirects' . ( ! empty( $final_url ) && $final_url !== $url ? ': ' . esc_html( $final_url ) : '' ),
						);
				} else {
					$checks[] = array(
						'label'   => 'Final Destination',
						'status'  => 'warning',
						'result'  => 'Unknown',
						'details' => 'Could not follow redirects: ' . $final_response->get_error_message(),
					);
				}
			} else {
				$checks[] = array(
					'label'   => 'Redirect Status',
					'status'  => 'pass',
					'result'  => 'No redirects',
					'details' => 'URL loads directly',
				);

				$checks[] = array(
					'label'   => 'Final Destination',
					'status'  => $status_code === 200 ? 'pass' : ( $status_code >= 300 && $status_code < 400 ? 'warning' : 'fail' ),
					'result'  => $status_code,
					'details' => 'No redirects detected; final destination is the requested URL: ' . esc_html( $url ),
				);
			}
		}

		$get_response = wp_remote_get(
			$url,
			array(
				'timeout'            => 10,
				'redirection'        => 5,
				'reject_unsafe_urls' => true,
			)
		);

		if ( ! is_wp_error( $get_response ) ) {
			$body = wp_remote_retrieve_body( $get_response );

			$checks[] = array(
				'label'   => 'Page Fetch',
				'status'  => ! empty( $body ) ? 'pass' : 'warning',
				'result'  => ! empty( $body ) ? 'Fetched' : 'Empty response',
				'details' => ! empty( $body ) ? 'Live page HTML was fetched successfully.' : 'Fetch succeeded but returned no analyzable HTML.',
			);

			// Content-level checks extracted from HTML (same-site diagnostics context).
			if ( ! empty( $body ) ) {
				libxml_use_internal_errors( true );
				$dom = new DOMDocument();
				$dom->loadHTML( $body );
				libxml_clear_errors();
				$xpath = new DOMXPath( $dom );

				$title_nodes  = $xpath->query( '//title' );
				$title_count  = $title_nodes instanceof DOMNodeList ? (int) $title_nodes->length : 0;
				$title_text   = $title_count > 0 ? trim( (string) $title_nodes->item( 0 )->textContent ) : '';
				$title_length = function_exists( 'mb_strlen' ) ? mb_strlen( $title_text ) : strlen( $title_text );
				$checks[]     = array(
					'label'   => 'SEO Title',
					'status'  => 1 === $title_count ? 'pass' : ( $title_count > 1 ? 'warning' : 'fail' ),
					'result'  => 1 === $title_count ? 'Present' : ( $title_count > 1 ? 'Multiple' : 'Missing' ),
					'details' => 1 === $title_count ? 'Exactly one title tag found.' : ( $title_count > 1 ? 'Multiple title tags detected.' : 'No title tag found.' ),
				);

				$checks[] = array(
					'label'   => 'SEO Title Length',
					'status'  => 0 === $title_length ? 'warning' : ( $title_length >= 30 && $title_length <= 60 ? 'pass' : 'warning' ),
					'result'  => $title_length . ' chars',
					'details' => 'Recommended range: 30-60 characters.',
				);

				$description_nodes  = $xpath->query( '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="description"]' );
				$description_count  = $description_nodes instanceof DOMNodeList ? (int) $description_nodes->length : 0;
				$description_text   = $description_count > 0 ? trim( (string) $description_nodes->item( 0 )->getAttribute( 'content' ) ) : '';
				$description_length = function_exists( 'mb_strlen' ) ? mb_strlen( $description_text ) : strlen( $description_text );

				$checks[] = array(
					'label'   => 'Meta Description',
					'status'  => $description_count > 0 ? 'pass' : 'warning',
					'result'  => $description_count > 0 ? 'Present' : 'Missing',
					'details' => $description_count > 0 ? 'Meta description tag detected.' : 'No meta description tag found.',
				);

				$checks[] = array(
					'label'   => 'Meta Description Length',
					'status'  => 0 === $description_length ? 'warning' : ( $description_length >= 120 && $description_length <= 160 ? 'pass' : 'warning' ),
					'result'  => $description_length . ' chars',
					'details' => 'Recommended range: 120-160 characters.',
				);

				$google_preview_ready = ( $title_count > 0 && $description_count > 0 );
				$checks[]             = array(
					'label'   => 'Google Preview',
					'status'  => $google_preview_ready ? 'pass' : 'warning',
					'result'  => $google_preview_ready ? 'Ready' : 'Incomplete',
					'details' => $google_preview_ready ? 'Title and meta description are available.' : 'Requires title and meta description.',
				);

				$og_title_nodes       = $xpath->query( '//meta[translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="og:title"]' );
				$og_description_nodes = $xpath->query( '//meta[translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="og:description"]' );
				$og_image_nodes       = $xpath->query( '//meta[translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="og:image"]' );
				$twitter_card_nodes   = $xpath->query( '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="twitter:card"]' );

				$checks[] = array(
					'label'   => 'Open Graph Title',
					'status'  => ( $og_title_nodes instanceof DOMNodeList && $og_title_nodes->length > 0 ) ? 'pass' : 'warning',
					'result'  => ( $og_title_nodes instanceof DOMNodeList && $og_title_nodes->length > 0 ) ? 'Present' : 'Missing',
					'details' => 'Validates presence of og:title.',
				);

				$checks[] = array(
					'label'   => 'Open Graph Description',
					'status'  => ( $og_description_nodes instanceof DOMNodeList && $og_description_nodes->length > 0 ) ? 'pass' : 'warning',
					'result'  => ( $og_description_nodes instanceof DOMNodeList && $og_description_nodes->length > 0 ) ? 'Present' : 'Missing',
					'details' => 'Validates presence of og:description.',
				);

				$og_image_value = ( $og_image_nodes instanceof DOMNodeList && $og_image_nodes->length > 0 )
				? trim( (string) $og_image_nodes->item( 0 )->getAttribute( 'content' ) )
				: '';
				$checks[]       = array(
					'label'   => 'Open Graph Image',
					'status'  => '' !== $og_image_value ? 'pass' : 'warning',
					'result'  => '' !== $og_image_value ? 'Present' : 'Missing',
					'details' => '' !== $og_image_value ? $og_image_value : 'No og:image found.',
				);

				$og_fields_count = 0;
				if ( $og_title_nodes instanceof DOMNodeList && $og_title_nodes->length > 0 ) {
					++$og_fields_count;
				}
				if ( $og_description_nodes instanceof DOMNodeList && $og_description_nodes->length > 0 ) {
					++$og_fields_count;
				}
				if ( '' !== $og_image_value ) {
					++$og_fields_count;
				}

				$checks[] = array(
					'label'   => 'Open Graph Setup',
					'status'  => $og_fields_count >= 2 ? 'pass' : 'warning',
					'result'  => $og_fields_count . '/3 fields',
					'details' => 'Derived from og:title, og:description, and og:image tags.',
				);

				$checks[] = array(
					'label'   => 'Twitter Card',
					'status'  => ( $twitter_card_nodes instanceof DOMNodeList && $twitter_card_nodes->length > 0 ) ? 'pass' : 'warning',
					'result'  => ( $twitter_card_nodes instanceof DOMNodeList && $twitter_card_nodes->length > 0 ) ? 'Present' : 'Missing',
					'details' => 'Validates presence of twitter:card.',
				);

				$schema_nodes              = $xpath->query( '//script[@type="application/ld+json"]' );
				$schema_count              = $schema_nodes instanceof DOMNodeList ? (int) $schema_nodes->length : 0;
				$schema_types_detected     = array();
				$schema_valid_count        = 0;
				$schema_invalid_count      = 0;
				$schema_structured_count   = 0;
				$schema_has_author         = false;
				$schema_has_date_published = false;
				$schema_has_date_modified  = false;
				if ( $schema_nodes instanceof DOMNodeList ) {
					foreach ( $schema_nodes as $schema_node ) {
						$json_raw = trim( (string) $schema_node->textContent );
						if ( '' === $json_raw ) {
								continue;
						}

						$decoded = json_decode( $json_raw, true );
						if ( ! is_array( $decoded ) ) {
							++$schema_invalid_count;
							continue;
						}

						++$schema_valid_count;

						$nodes = isset( $decoded['@graph'] ) && is_array( $decoded['@graph'] ) ? $decoded['@graph'] : array( $decoded );
						foreach ( $nodes as $entry ) {
							if ( ! is_array( $entry ) ) {
								continue;
							}

							if ( ! empty( $entry['author'] ) ) {
								$schema_has_author = true;
							}
							if ( ! empty( $entry['datePublished'] ) ) {
								$schema_has_date_published = true;
							}
							if ( ! empty( $entry['dateModified'] ) ) {
								$schema_has_date_modified = true;
							}

							if ( empty( $entry['@type'] ) ) {
								continue;
							}

							++$schema_structured_count;

							$types = is_array( $entry['@type'] ) ? $entry['@type'] : array( $entry['@type'] );
							foreach ( $types as $type ) {
								$type_name = trim( (string) $type );
								if ( '' !== $type_name ) {
									$schema_types_detected[] = $type_name;
								}
							}
						}
					}
				}
				$schema_types_detected = array_values( array_unique( $schema_types_detected ) );

				$checks[] = array(
					'label'   => 'Structured Data Found',
					'status'  => $schema_count > 0 ? 'pass' : 'warning',
					'result'  => $schema_count,
					'details' => $schema_count > 0 ? 'JSON-LD schema blocks detected.' : 'No JSON-LD schema blocks detected.',
				);

				$checks[] = array(
					'label'   => 'Structured Data Present',
					'status'  => $schema_count > 0 ? 'pass' : 'warning',
					'result'  => $schema_count > 0 ? 'Yes' : 'No',
					'details' => $schema_count > 0 ? 'JSON-LD blocks are present in page HTML.' : 'No JSON-LD blocks detected.',
				);

				$schema_validation_ok = $schema_count > 0 && $schema_invalid_count === 0 && $schema_structured_count > 0;
				$checks[]             = array(
					'label'   => 'Schema Validation',
					'status'  => $schema_validation_ok ? 'pass' : 'warning',
					'result'  => $schema_validation_ok ? 'Valid' : 'Needs review',
					'details' => $schema_validation_ok
					? sprintf( 'Validated %d JSON-LD block(s) with expected structure.', $schema_valid_count )
					: sprintf( 'Valid JSON-LD: %d, invalid JSON-LD: %d, structured nodes: %d.', $schema_valid_count, $schema_invalid_count, $schema_structured_count ),
				);

				$has_org        = in_array( 'Organization', $schema_types_detected, true );
				$has_article    = in_array( 'Article', $schema_types_detected, true ) || in_array( 'BlogPosting', $schema_types_detected, true ) || in_array( 'NewsArticle', $schema_types_detected, true );
				$has_faq        = in_array( 'FAQPage', $schema_types_detected, true );
				$has_breadcrumb = in_array( 'BreadcrumbList', $schema_types_detected, true );

				$primary_entity          = 'Unknown';
				$primary_entity_priority = array( 'Article', 'BlogPosting', 'NewsArticle', 'Product', 'Service', 'Organization', 'FAQPage', 'WebPage' );
				foreach ( $primary_entity_priority as $candidate_type ) {
					if ( in_array( $candidate_type, $schema_types_detected, true ) ) {
						$primary_entity = $candidate_type;
						break;
					}
				}
				if ( 'Unknown' === $primary_entity && ! empty( $schema_types_detected ) ) {
					$primary_entity = (string) $schema_types_detected[0];
				}

				$checks[] = array(
					'label'   => 'Primary Entity',
					'status'  => 'Unknown' !== $primary_entity ? 'pass' : 'warning',
					'result'  => $primary_entity,
					'details' => 'Primary schema entity inferred from detected @type values.',
				);

				$checks[] = array(
					'label'   => 'Organization Schema',
					'status'  => $has_org ? 'pass' : 'warning',
					'result'  => $has_org ? 'Present' : 'Missing',
					'details' => 'Schema type check for Organization.',
				);
				$checks[] = array(
					'label'   => 'Article Schema',
					'status'  => $has_article ? 'pass' : 'warning',
					'result'  => $has_article ? 'Present' : 'Missing',
					'details' => 'Schema type check for Article/BlogPosting.',
				);
				$checks[] = array(
					'label'   => 'FAQ Schema',
					'status'  => $has_faq ? 'pass' : 'warning',
					'result'  => $has_faq ? 'Present' : 'Missing',
					'details' => 'Schema type check for FAQPage.',
				);
				$checks[] = array(
					'label'   => 'Breadcrumb Schema',
					'status'  => $has_breadcrumb ? 'pass' : 'warning',
					'result'  => $has_breadcrumb ? 'Present' : 'Missing',
					'details' => 'Schema type check for BreadcrumbList.',
				);

				$checks[] = array(
					'label'   => 'Organization Information',
					'status'  => $has_org ? 'pass' : 'warning',
					'result'  => $has_org ? 'Present' : 'Missing',
					'details' => 'Organization information detected from schema.',
				);

				$canonical_nodes = $xpath->query( '//link[translate(@rel, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="canonical"]' );
				$canonical_count = $canonical_nodes instanceof DOMNodeList ? (int) $canonical_nodes->length : 0;
				$canonical_url   = $canonical_count > 0 ? trim( (string) $canonical_nodes->item( 0 )->getAttribute( 'href' ) ) : '';
				// Multiple canonicals is inherently problematic - invalidate downstream checks
				$canonical_valid      = 1 === $canonical_count && '' !== $canonical_url && (bool) wp_http_validate_url( $canonical_url );
				$current_normalized   = untrailingslashit( strtolower( trim( (string) $url ) ) );
				$canonical_normalized = untrailingslashit( strtolower( trim( (string) $canonical_url ) ) );

				$canonical_exists_message    = 1 === $canonical_count ? $canonical_url : ( 0 === $canonical_count ? 'No canonical tag found.' : 'Multiple canonical tags found.' );
				$canonical_exists_evidence   = $canonical_exists_message
				. ' | hasCanonical: ' . ( $canonical_count > 0 ? 'true' : 'false' )
				. ' | canonical: ' . ( '' !== $canonical_url ? $canonical_url : 'none' )
				. ' | canonicalCount: ' . $canonical_count;
				$checks[]                    = array(
					'label'   => 'Canonical Exists',
					'status'  => 1 === $canonical_count ? 'pass' : ( 0 === $canonical_count ? 'warning' : 'fail' ),
					'result'  => 1 === $canonical_count ? 'Present' : ( 0 === $canonical_count ? 'Missing' : 'Multiple' ),
					'details' => $canonical_exists_evidence,
				);
				$canonical_valid_url_message = 1 !== $canonical_count ? 'Multiple canonical tags conflict with validation.' : ( '' !== $canonical_url ? $canonical_url : 'No canonical URL to validate.' );
				$checks[]                    = array(
					'label'   => 'Canonical Valid URL',
					'status'  => ( 1 !== $canonical_count ? 'warning' : ( $canonical_valid ? 'pass' : 'warning' ) ),
					'result'  => ( 1 !== $canonical_count ? 'Conflicted' : ( $canonical_valid ? 'Valid' : 'Invalid' ) ),
					'details' => $canonical_valid_url_message . ' | canonical: ' . ( '' !== $canonical_url ? $canonical_url : 'none' ) . ' | canonicalCount: ' . $canonical_count . ' | isValid: ' . ( $canonical_valid ? 'true' : 'false' ),
				);
				$self_canonical_message      = 1 !== $canonical_count ? 'Cannot validate self-reference with multiple canonical tags.' : ( '' !== $canonical_url ? 'Compared canonical to page URL.' : 'No canonical URL to compare.' );
				$checks[]                    = array(
					'label'   => 'Self Canonical',
					'status'  => ( 1 !== $canonical_count ? 'warning' : ( ( $canonical_valid && $canonical_normalized === $current_normalized ) ? 'pass' : 'warning' ) ),
					'result'  => ( 1 !== $canonical_count ? 'Conflicted' : ( ( $canonical_valid && $canonical_normalized === $current_normalized ) ? 'Yes' : 'No' ) ),
					'details' => $self_canonical_message . ' | canonical: ' . ( '' !== $canonical_url ? $canonical_url : 'none' ) . ' | url: ' . $url . ' | isSelf: ' . ( $canonical_normalized === $current_normalized ? 'true' : 'false' ),
				);

				$canonical_status = 0;
				if ( $canonical_valid ) {
					$canonical_response = wp_remote_head(
						$canonical_url,
						array(
							'timeout'            => 10,
							'reject_unsafe_urls' => true,
							'redirection'        => 0,
						)
					);
					if ( ! is_wp_error( $canonical_response ) ) {
							$canonical_status = (int) wp_remote_retrieve_response_code( $canonical_response );
					}
				}
				$canonical_http_message = $canonical_valid ? 'HTTP check on canonical URL target.' : 'Skipped because canonical URL is invalid or missing.';
				$checks[]               = array(
					'label'   => 'Canonical Target HTTP 200',
					'status'  => 200 === (int) $canonical_status ? 'pass' : 'warning',
					'result'  => $canonical_status > 0 ? $canonical_status : 'Unknown',
					'details' => $canonical_http_message . ' | canonical: ' . ( '' !== $canonical_url ? $canonical_url : 'none' ) . ' | httpStatus: ' . ( $canonical_status > 0 ? $canonical_status : 'unknown' ),
				);

				$x_robots_header = trim( (string) wp_remote_retrieve_header( $get_response, 'x-robots-tag' ) );
				if ( '' === $x_robots_header ) {
					$x_robots_header = $head_x_robots_header;
				}
				$x_robots_has_noindex = ! empty( $x_robots_header ) && stripos( $x_robots_header, 'noindex' ) !== false;
				$checks[]             = array(
					'label'             => 'X-Robots-Tag',
					'status'            => $x_robots_has_noindex ? 'warning' : 'pass',
					'result'            => $x_robots_has_noindex ? 'Noindex' : 'Not Detected',
					'details'           => ! empty( $x_robots_header ) ? (string) $x_robots_header : 'No x-robots-tag header found.',
					'rawEvidence'       => array(
						'xRobotsTag' => (string) $x_robots_header,
					),
					'rawEvidenceFields' => array( 'xRobotsTag' ),
				);

				$robots_meta             = self::extract_robots_meta_from_html( $body );
				$robots_meta_has_noindex = ! empty( $robots_meta ) && stripos( $robots_meta, 'noindex' ) !== false;
				$checks[]                = array(
					'label'   => 'Robots Meta',
					'status'  => $robots_meta_has_noindex ? 'warning' : 'pass',
					'result'  => $robots_meta_has_noindex ? 'Noindex' : 'Not Detected',
					'details' => ! empty( $robots_meta ) ? $robots_meta : 'No robots meta tag found.',
				);

				$robots_meta_has_nofollow = ! empty( $robots_meta ) && stripos( $robots_meta, 'nofollow' ) !== false;
				$checks[]                 = array(
					'label'   => 'Follow Directive',
					'status'  => $robots_meta_has_nofollow ? 'warning' : 'pass',
					'result'  => $robots_meta_has_nofollow ? 'Nofollow' : 'Follow',
					'details' => ! empty( $robots_meta ) ? $robots_meta : 'No nofollow directive found.',
				);

				$is_noindex = $x_robots_has_noindex || $robots_meta_has_noindex;
				$checks[]   = array(
					'label'   => 'Indexability',
					'status'  => $is_noindex ? 'warning' : 'pass',
					'result'  => $is_noindex ? 'Noindex' : 'Indexable',
					'details' => $is_noindex ? 'Noindex detected from robots directives.' : 'No noindex directive found.',
				);

				$content_text = trim( wp_strip_all_tags( $body ) );
				$word_count   = str_word_count( strtolower( html_entity_decode( $content_text, ENT_QUOTES, 'UTF-8' ) ) );
				$checks[]     = array(
					'label'   => 'Content Depth (Word Count)',
					'status'  => $word_count >= 300 ? 'pass' : 'warning',
					'result'  => $word_count . ' words',
					'details' => $word_count >= 300 ? 'Content depth is generally sufficient.' : 'Content may be thin (below 300 words).',
				);

				$h1_count = $xpath->query( '//h1' );
				$h1_total = $h1_count instanceof DOMNodeList ? (int) $h1_count->length : 0;
				$checks[] = array(
					'label'   => 'H1 Presence',
					'status'  => $h1_total >= 1 ? 'pass' : 'fail',
					'result'  => $h1_total >= 1 ? 'Yes' : 'No',
					'details' => 'At least one H1 heading is required.',
				);
				$checks[] = array(
					'label'   => 'Multiple H1',
					'status'  => $h1_total <= 1 ? 'pass' : ( 2 === $h1_total ? 'warning' : 'fail' ),
					'result'  => $h1_total,
					'details' => 'Keep one H1 for strongest structure.',
				);

				$checks[] = array(
					'label'   => 'Content Present',
					'status'  => '' !== $content_text ? 'pass' : 'warning',
					'result'  => '' !== $content_text ? 'Present' : 'Missing',
					'details' => '' !== $content_text ? 'Page body contains analyzable content.' : 'Page body is empty.',
				);
				$checks[] = array(
					'label'   => 'Readability',
					'status'  => $word_count >= 300 ? 'pass' : 'warning',
					'result'  => $word_count >= 300 ? 'Readable' : 'Needs improvement',
					'details' => $word_count . ' words available for readability evaluation.',
				);

				$links               = $xpath->query( '//a[@href]' );
				$internal_link_count = 0;
				$external_link_count = 0;
				$nofollow_link_count = 0;
				$site_host           = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
				if ( $links instanceof DOMNodeList ) {
					foreach ( $links as $link ) {
						$href = trim( (string) $link->getAttribute( 'href' ) );
						if ( '' === $href || '#' === $href[0] ) {
								continue;
						}
						if ( 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) || 0 === strpos( $href, 'javascript:' ) ) {
							continue;
						}

						$rel_value = strtolower( trim( (string) $link->getAttribute( 'rel' ) ) );
						if ( false !== strpos( $rel_value, 'nofollow' ) ) {
							++$nofollow_link_count;
						}

						if ( 0 === strpos( $href, '/' ) ) {
							++$internal_link_count;
							continue;
						}

						$href_host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
						if ( '' !== $href_host && '' !== $site_host && $href_host === $site_host ) {
							++$internal_link_count;
						} elseif ( '' !== $href_host ) {
							++$external_link_count;
						}
					}
				}

				$checks[] = array(
					'label'   => 'Internal Links',
					'status'  => $internal_link_count >= 2 ? 'pass' : ( 1 === $internal_link_count ? 'warning' : 'fail' ),
					'result'  => $internal_link_count,
					'details' => 'Internal links found in content.',
				);
				$checks[] = array(
					'label'   => 'External Links',
					'status'  => 'pass',
					'result'  => $external_link_count,
					'details' => 'Informational count of external links.',
				);
				$checks[] = array(
					'label'   => 'Nofollow Links',
					'status'  => 'pass',
					'result'  => $nofollow_link_count,
					'details' => 'Informational count of links marked nofollow.',
				);

				$images            = $xpath->query( '//img' );
				$image_count       = $images instanceof DOMNodeList ? (int) $images->length : 0;
				$missing_alt_count = 0;
				$empty_alt_count   = 0;
				if ( $images instanceof DOMNodeList ) {
					foreach ( $images as $image ) {
						if ( ! $image->hasAttribute( 'alt' ) ) {
								++$missing_alt_count;
								continue;
						}

						$alt = trim( (string) $image->getAttribute( 'alt' ) );
						if ( '' === $alt ) {
							++$empty_alt_count;
						}
					}
				}

				$post_id            = url_to_postid( $url );
				$has_featured_image = ( $post_id > 0 && has_post_thumbnail( $post_id ) );
				$checks[]           = array(
					'label'   => 'Images Found',
					'status'  => $image_count > 0 ? 'pass' : 'warning',
					'result'  => $image_count,
					'details' => $image_count > 0 ? 'Image elements found in content.' : 'No images detected in page content.',
				);
				$checks[]           = array(
					'label'   => 'Missing ALT',
					'status'  => 0 === $missing_alt_count ? 'pass' : ( $missing_alt_count <= 2 ? 'warning' : 'fail' ),
					'result'  => $missing_alt_count,
					'details' => 'Images without an alt attribute.',
				);
				$checks[]           = array(
					'label'   => 'Empty ALT',
					'status'  => 0 === $empty_alt_count ? 'pass' : ( $empty_alt_count <= 2 ? 'warning' : 'fail' ),
					'result'  => $empty_alt_count,
					'details' => 'Images with empty alt text values.',
				);
				$checks[]           = array(
					'label'   => 'Featured Image',
					'status'  => $has_featured_image ? 'pass' : 'warning',
					'result'  => $has_featured_image ? 'Present' : 'Missing',
					'details' => $has_featured_image ? 'Featured image is configured.' : 'No featured image is configured.',
				);

				$h2_count           = $xpath->query( '//h2' );
				$h3_count           = $xpath->query( '//h3' );
				$has_lists          = $xpath->query( '//ul|//ol' );
				$has_tables         = $xpath->query( '//table' );
				$faq_pattern        = 1 === preg_match( '/(faq|frequently asked)/i', strtolower( $content_text ) );
				$definition_pattern = 1 === preg_match( '/\bwhat is\b|\bdefinition\b|\bmeans\b/i', strtolower( $content_text ) );
				$html_lang          = trim( (string) $dom->documentElement->getAttribute( 'lang' ) );
				$h1_h2_h3_nodes     = $xpath->query( '//h1|//h2|//h3' );

				$has_semantic_headings  = ( $h2_count instanceof DOMNodeList && $h2_count->length > 0 ) || ( $h3_count instanceof DOMNodeList && $h3_count->length > 0 );
				$has_structured_content = ( $has_lists instanceof DOMNodeList && $has_lists->length > 0 ) || ( $has_tables instanceof DOMNodeList && $has_tables->length > 0 );
				$has_valid_heading_flow = true;
				$previous_heading_level = 0;
				if ( $h1_h2_h3_nodes instanceof DOMNodeList ) {
					foreach ( $h1_h2_h3_nodes as $heading_node ) {
						if ( ! ( $heading_node instanceof DOMElement ) ) {
								continue;
						}

						$tag   = strtolower( (string) $heading_node->tagName );
						$level = (int) str_replace( 'h', '', $tag );
						if ( $level < 1 || $level > 3 ) {
							continue;
						}

						if ( 0 !== $previous_heading_level && $level > ( $previous_heading_level + 1 ) ) {
							$has_valid_heading_flow = false;
							break;
						}

						$previous_heading_level = $level;
					}
				}
				$semantic_heading_structure_ok = $h1_total >= 1 && $has_semantic_headings && $has_valid_heading_flow;

				$author_meta_nodes   = $xpath->query( '//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="author"]' );
				$author_meta_content = '';
				if ( $author_meta_nodes instanceof DOMNodeList && $author_meta_nodes->length > 0 ) {
					$author_meta_content = trim( (string) $author_meta_nodes->item( 0 )->getAttribute( 'content' ) );
				}
				$post_id_for_author = url_to_postid( $url );
				$author_name        = '';
				if ( $post_id_for_author > 0 ) {
					$author_name = trim( (string) get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id_for_author ) ) );
				}
				$has_author_info = '' !== $author_meta_content || '' !== $author_name || $schema_has_author;

				$published_time_nodes  = $xpath->query( '//meta[translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="article:published_time"]|//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="datepublished"]' );
				$modified_time_nodes   = $xpath->query( '//meta[translate(@property, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="article:modified_time"]|//meta[translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="datemodified"]' );
				$has_published_date    = ( $published_time_nodes instanceof DOMNodeList && $published_time_nodes->length > 0 ) || $schema_has_date_published;
				$has_last_updated_date = ( $modified_time_nodes instanceof DOMNodeList && $modified_time_nodes->length > 0 ) || $schema_has_date_modified;

				$checks[]                  = array(
					'label'   => 'Heading Structure',
					'status'  => $has_semantic_headings ? 'pass' : 'warning',
					'result'  => $has_semantic_headings ? 'Good' : 'Needs work',
					'details' => 'Checks for heading hierarchy beyond H1.',
				);
				$checks[]                  = array(
					'label'   => 'Heading Hierarchy',
					'status'  => $semantic_heading_structure_ok ? 'pass' : 'warning',
					'result'  => $semantic_heading_structure_ok ? 'Good' : 'Needs work',
					'details' => 'Checks logical H1 -> H2 -> H3 progression without level jumps.',
				);
				$checks[]                  = array(
					'label'   => 'FAQ Ready',
					'status'  => ( $faq_pattern || $has_faq ) ? 'pass' : 'warning',
					'result'  => ( $faq_pattern || $has_faq ) ? 'Yes' : 'No',
					'details' => 'Detected from FAQ schema or FAQ content patterns.',
				);
				$checks[]                  = array(
					'label'   => 'FAQ Content',
					'status'  => ( $faq_pattern || $has_faq ) ? 'pass' : 'warning',
					'result'  => ( $faq_pattern || $has_faq ) ? 'Detected' : 'Not detected',
					'details' => 'Detected from FAQ schema or FAQ-like content patterns.',
				);
				$checks[]                  = array(
					'label'   => 'Structured Content',
					'status'  => $has_structured_content ? 'pass' : 'warning',
					'result'  => $has_structured_content ? 'Present' : 'Limited',
					'details' => 'Checks for lists/tables that improve scanability.',
				);
				$checks[]                  = array(
					'label'   => 'Table/List Detection',
					'status'  => $has_structured_content ? 'pass' : 'warning',
					'result'  => $has_structured_content ? 'Detected' : 'Not detected',
					'details' => 'Checks for list or table structures in content.',
				);
				$checks[]                  = array(
					'label'   => 'Author Information',
					'status'  => $has_author_info ? 'pass' : 'warning',
					'result'  => $has_author_info ? 'Present' : 'Missing',
					'details' => 'Detected from schema, author meta tag, or WordPress author data.',
				);
				$checks[]                  = array(
					'label'   => 'Published Date',
					'status'  => $has_published_date ? 'pass' : 'warning',
					'result'  => $has_published_date ? 'Present' : 'Missing',
					'details' => 'Detected from schema or published time metadata.',
				);
				$checks[]                  = array(
					'label'   => 'Last Updated Date',
					'status'  => $has_last_updated_date ? 'pass' : 'warning',
					'result'  => $has_last_updated_date ? 'Present' : 'Missing',
					'details' => 'Detected from schema or modified time metadata.',
				);
				$checks[]                  = array(
					'label'   => 'Language Declaration',
					'status'  => '' !== $html_lang ? 'pass' : 'warning',
					'result'  => '' !== $html_lang ? $html_lang : 'Missing',
					'details' => 'Checks presence of html lang attribute.',
				);
				$checks[]                  = array(
					'label'   => 'Internal References',
					'status'  => $internal_link_count > 0 ? 'pass' : 'warning',
					'result'  => $internal_link_count,
					'details' => 'Internal references (links to related pages).',
				);
				$checks[]                  = array(
					'label'   => 'External References',
					'status'  => $external_link_count > 0 ? 'pass' : 'warning',
					'result'  => $external_link_count,
					'details' => 'External references to other domains.',
				);
				$checks[]                  = array(
					'label'   => 'Definition Content',
					'status'  => $definition_pattern ? 'pass' : 'warning',
					'result'  => $definition_pattern ? 'Detected' : 'Not detected',
					'details' => 'Detects definition-style phrasing such as "What is" patterns.',
				);
				$media_context_issue_count = $missing_alt_count + $empty_alt_count;
				$checks[]                  = array(
					'label'   => 'Media Context',
					'status'  => ( $image_count > 0 && 0 === $media_context_issue_count ) ? 'pass' : 'warning',
					'result'  => $image_count > 0 ? ( 0 === $media_context_issue_count ? 'Good' : 'Needs improvement' ) : 'No images',
					'details' => $image_count > 0
					? sprintf( 'Images: %d, ALT issues: %d.', $image_count, $media_context_issue_count )
					: 'No images detected for media context analysis.',
				);
			} else {
				$checks[] = array(
					'label'   => 'Content Present',
					'status'  => 'warning',
					'result'  => 'Missing',
					'details' => 'Page response returned no analyzable HTML.',
				);
			}
		} else {
			$checks[] = array(
				'label'   => 'Page Fetch',
				'status'  => 'warning',
				'result'  => 'Unavailable',
				'details' => $get_response->get_error_message(),
			);

			self::append_local_fallback_checks( $url, $checks, $head_error_message ? $head_error_message : $get_response->get_error_message() );
		}

		// Store canonical count detected during this scan so fallback flow can use it for consistency
		$post_id_for_storage = url_to_postid( $url );
		if ( $post_id_for_storage > 0 ) {
			// Find canonical count from the checks we just built
			$stored_canonical_count = 0;
			foreach ( $checks as $check ) {
				if ( 'Canonical Exists' === ( $check['label'] ?? '' ) ) {
					if ( 'Multiple' === ( $check['result'] ?? '' ) ) {
						$stored_canonical_count = 2; // 2+ indicates multiple
					} elseif ( 'Present' === ( $check['result'] ?? '' ) ) {
						$stored_canonical_count = 1;
					}
					break;
				}
			}
			if ( $stored_canonical_count > 0 ) {
				update_post_meta( $post_id_for_storage, '_ASNERISSEO_canonical_count_detected', $stored_canonical_count );
			}
		}

		// ===== UNIFIED DESIGN: Add completeness tracking to all checks =====
		// Track which fields were captured in this live scan
		$captured_fields = array();
		$missing_fields  = array();

		// Check each critical field capture status
		if ( isset( $canonical_count ) && $canonical_count >= 0 ) {
			$captured_fields[] = 'canonicalCount';
		} else {
			$missing_fields[] = 'canonicalCount';
		}

		if ( isset( $h1_count ) && $h1_count >= 0 ) {
			$captured_fields[] = 'h1Count';
		} else {
			$missing_fields[] = 'h1Count';
		}

		if ( isset( $word_count ) && $word_count >= 0 ) {
			$captured_fields[] = 'contentWords';
		} else {
			$missing_fields[] = 'contentWords';
		}

		// Add completeness metadata to each check
		$is_data_complete = empty( $missing_fields );
		foreach ( $checks as &$check ) {
			$check['isDataComplete'] = $is_data_complete;
			$check['missingFields']  = $missing_fields;
		}
		unset( $check );

		// Store all captured fields for fallback to use (not just canonical_count)
		if ( $post_id_for_storage > 0 ) {
			update_post_meta( $post_id_for_storage, '_ASNERISSEO_captured_fields', $captured_fields );
			update_post_meta( $post_id_for_storage, '_ASNERISSEO_h1_count', $h1_count ?? 0 );
			update_post_meta( $post_id_for_storage, '_ASNERISSEO_content_words', $word_count ?? 0 );
			update_post_meta( $post_id_for_storage, '_ASNERISSEO_http_status', $http_status ?? 0 );
		}

		return self::with_check_categories( $checks );
	}

	/**
	 * Add local, non-network fallback checks so diagnostics still provides context
	 * when outbound HTTP calls fail.
	 */
	private static function append_local_fallback_checks( $url, array &$checks, $network_error_message = '' ) {
		$post_id = url_to_postid( $url );
		if ( ! $post_id ) {
			$checks[] = array(
				'label'   => 'Local Fallback',
				'status'  => 'warning',
				'result'  => 'Limited',
				'details' => 'Could not map URL to a local post for fallback diagnostics.',
			);
			return;
		}

		$title                    = trim( (string) get_the_title( $post_id ) );
		$post                     = get_post( $post_id );
		$description              = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_description', true ) );
		$canonical                = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_canonical', true ) );
		$canonical_count_detected = (int) get_post_meta( $post_id, '_ASNERISSEO_canonical_count_detected', true );
		$robots_index             = sanitize_key( (string) get_post_meta( $post_id, '_ASNERISSEO_robots_index', true ) );
		$robots_follow            = sanitize_key( (string) get_post_meta( $post_id, '_ASNERISSEO_robots_follow', true ) );
		$og_title                 = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_og_title', true ) );
		$og_description           = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_og_description', true ) );
		$og_image                 = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_og_image', true ) );
		$og_image_disabled        = ! empty( get_post_meta( $post_id, '_ASNERISSEO_og_image_disabled', true ) );
		$schema_enabled           = get_post_meta( $post_id, '_ASNERISSEO_schema_enabled', true );
		$schema_type              = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_schema_type', true ) );

		if ( '' === $robots_follow || ! in_array( $robots_follow, array( 'follow', 'nofollow' ), true ) ) {
			$robots_follow = 'follow';
		}
		if ( ! in_array( $robots_index, array( 'index', 'noindex' ), true ) ) {
			$robots_index = 'index';
		}

		$title_length         = function_exists( 'mb_strlen' ) ? mb_strlen( $title ) : strlen( $title );
		$description_length   = function_exists( 'mb_strlen' ) ? mb_strlen( $description ) : strlen( $description );
		$permalink            = (string) get_permalink( $post_id );
		$input_normalized     = untrailingslashit( strtolower( trim( $url ) ) );
		$canonical_normalized = untrailingslashit( strtolower( trim( $canonical ) ) );

		$content_html = '';
		if ( $post instanceof WP_Post ) {
			$content_html = (string) $post->post_content;
		}
		$content_text = trim( wp_strip_all_tags( $content_html ) );
		$word_count   = str_word_count( strtolower( html_entity_decode( $content_text, ENT_QUOTES, 'UTF-8' ) ) );

		$heading_h1_count = preg_match_all( '/<h1\b[^>]*>/i', $content_html );
		if ( false === $heading_h1_count ) {
			$heading_h1_count = 0;
		}

		$internal_link_count = 0;
		$external_link_count = 0;
		$nofollow_link_count = 0;
		if ( preg_match_all( '/\bhref\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $content_html, $href_matches, PREG_SET_ORDER ) ) {
			$site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
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
				if ( '' === $href || '#' === $href[0] ) {
					continue;
				}
				if ( 0 === strpos( $href, 'mailto:' ) || 0 === strpos( $href, 'tel:' ) || 0 === strpos( $href, 'javascript:' ) ) {
					continue;
				}

				if ( 0 === strpos( $href, '/' ) ) {
					++$internal_link_count;
					continue;
				}

				$href_host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
				if ( $href_host && $site_host && $href_host === $site_host ) {
					++$internal_link_count;
				} elseif ( $href_host ) {
					++$external_link_count;
				}
			}
		}

		if ( preg_match_all( '/<a\b[^>]*\brel\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $content_html, $rel_matches, PREG_SET_ORDER ) ) {
			foreach ( $rel_matches as $rel_match ) {
				$rel_value = '';
				if ( isset( $rel_match[1] ) && '' !== $rel_match[1] ) {
					$rel_value = $rel_match[1];
				} elseif ( isset( $rel_match[2] ) && '' !== $rel_match[2] ) {
					$rel_value = $rel_match[2];
				} elseif ( isset( $rel_match[3] ) ) {
					$rel_value = $rel_match[3];
				}

				if ( false !== strpos( strtolower( $rel_value ), 'nofollow' ) ) {
					++$nofollow_link_count;
				}
			}
		}

		$h2_count = preg_match_all( '/<h2\b[^>]*>/i', $content_html );
		if ( false === $h2_count ) {
			$h2_count = 0;
		}
		$h3_count = preg_match_all( '/<h3\b[^>]*>/i', $content_html );
		if ( false === $h3_count ) {
			$h3_count = 0;
		}
		$list_count = preg_match_all( '/<(ul|ol)\b[^>]*>/i', $content_html );
		if ( false === $list_count ) {
			$list_count = 0;
		}
		$table_count = preg_match_all( '/<table\b[^>]*>/i', $content_html );
		if ( false === $table_count ) {
			$table_count = 0;
		}
		$faq_pattern                   = 1 === preg_match( '/(faq|frequently asked)/i', strtolower( $content_text ) );
		$definition_pattern            = 1 === preg_match( '/\bwhat is\b|\bdefinition\b|\bmeans\b/i', strtolower( $content_text ) );
		$has_semantic_headings         = $h2_count > 0 || $h3_count > 0;
		$has_structured_content        = $list_count > 0 || $table_count > 0;
		$semantic_heading_structure_ok = (int) $heading_h1_count >= 1 && $has_semantic_headings;

		$schema_enabled_bool   = ! ( false === $schema_enabled || '0' === (string) $schema_enabled );
		$schema_type_lower     = strtolower( $schema_type );
		$has_org_schema        = $schema_enabled_bool && ( false !== strpos( $schema_type_lower, 'organization' ) || false !== strpos( $schema_type_lower, 'localbusiness' ) );
		$has_article_schema    = $schema_enabled_bool && ( false !== strpos( $schema_type_lower, 'article' ) || false !== strpos( $schema_type_lower, 'blogposting' ) || false !== strpos( $schema_type_lower, 'newsarticle' ) );
		$has_faq_schema        = $schema_enabled_bool && false !== strpos( $schema_type_lower, 'faq' );
		$has_breadcrumb_schema = $schema_enabled_bool && false !== strpos( $schema_type_lower, 'breadcrumb' );

		$author_name = '';
		if ( $post instanceof WP_Post ) {
			$author_name = trim( (string) get_the_author_meta( 'display_name', (int) $post->post_author ) );
		}
		$has_author_info       = '' !== $author_name;
		$published_date        = get_post_time( 'c', true, $post_id );
		$last_updated_date     = get_post_modified_time( 'c', true, $post_id );
		$has_published_date    = ! empty( $published_date );
		$has_last_updated_date = ! empty( $last_updated_date );

		$site_language = (string) get_bloginfo( 'language' );
		$html_lang     = '';
		if ( '' !== $site_language ) {
			$html_lang = strtolower( str_replace( '_', '-', $site_language ) );
		}

		$image_count       = 0;
		$missing_alt_count = 0;
		$empty_alt_count   = 0;
		if ( preg_match_all( '/<img\b[^>]*>/i', $content_html, $image_matches ) ) {
			$image_count = count( $image_matches[0] );
			foreach ( $image_matches[0] as $image_tag ) {
				if ( ! preg_match( '/\balt\s*=\s*(["\'])(.*?)\1/i', $image_tag, $alt_match ) ) {
					++$missing_alt_count;
					continue;
				}

				$alt_text = trim( wp_strip_all_tags( html_entity_decode( (string) $alt_match[2], ENT_QUOTES, 'UTF-8' ) ) );
				if ( '' === $alt_text ) {
					++$missing_alt_count;
					++$empty_alt_count;
				}
			}
		}
		$media_context_issue_count = $missing_alt_count + $empty_alt_count;

		$checks[] = array(
			'label'   => 'Data Source',
			'status'  => 'warning',
			'result'  => 'Fallback',
			'details' => 'HTTP fetch failed, showing local metadata checks only.' . ( $network_error_message ? ' Error: ' . $network_error_message : '' ),
		);

		$checks[] = array(
			'label'   => 'SEO Title',
			'status'  => '' !== $title ? 'pass' : 'warning',
			'result'  => '' !== $title ? 'Present' : 'Missing',
			'details' => '' !== $title ? 'Title is available from local post data.' : 'No title found in local post data.',
		);

		$checks[] = array(
			'label'   => 'SEO Title Length',
			'status'  => 0 === $title_length ? 'warning' : ( $title_length >= 30 && $title_length <= 60 ? 'pass' : 'warning' ),
			'result'  => $title_length . ' chars',
			'details' => 0 === $title_length
			? 'No title content found.'
			: ( $title_length >= 30 && $title_length <= 60
				? 'Length is within recommended 30-60 range.'
				: 'Outside recommended 30-60 range.' ),
		);

		$checks[] = array(
			'label'   => 'Meta Description',
			'status'  => '' !== $description ? 'pass' : 'warning',
			'result'  => '' !== $description ? 'Present' : 'Missing',
			'details' => '' !== $description ? 'Description is configured in local metadata.' : 'No local meta description configured.',
		);

		$checks[] = array(
			'label'   => 'Meta Description Length',
			'status'  => 0 === $description_length ? 'warning' : ( $description_length >= 120 && $description_length <= 160 ? 'pass' : 'warning' ),
			'result'  => $description_length . ' chars',
			'details' => 0 === $description_length
			? 'No description content found.'
			: ( $description_length >= 120 && $description_length <= 160
				? 'Length is within recommended 120-160 range.'
				: 'Outside recommended 120-160 range.' ),
		);

		// Apply same canonical validation logic as live flow for consistency
		$canonical_has_multiple = $canonical_count_detected > 1;
		$canonical_valid        = ! $canonical_has_multiple && '' !== $canonical && wp_http_validate_url( $canonical );

		$canonical_exists_message_fallback  = $canonical_has_multiple ? 'Multiple canonical tags found (detected during previous live scan).' : ( '' !== $canonical ? $canonical : 'No local canonical URL configured.' );
		$canonical_exists_evidence_fallback = $canonical_exists_message_fallback
		. ' | hasCanonical: ' . ( '' !== $canonical ? 'true' : 'false' )
		. ' | canonical: ' . ( '' !== $canonical ? $canonical : 'none' )
		. ' | canonicalCount: ' . $canonical_count_detected;
		$checks[]                           = array(
			'label'   => 'Canonical Exists',
			'status'  => $canonical_has_multiple ? 'fail' : ( '' !== $canonical ? 'pass' : 'warning' ),
			'result'  => $canonical_has_multiple ? 'Multiple' : ( '' !== $canonical ? 'Present' : 'Missing' ),
			'details' => $canonical_exists_evidence_fallback,
		);

		$canonical_valid_url_message_fb = $canonical_has_multiple ? 'Multiple canonical tags conflict with validation.' : ( '' !== $canonical ? $canonical : 'No canonical URL to validate.' );
		$checks[]                       = array(
			'label'   => 'Canonical Valid URL',
			'status'  => ( $canonical_has_multiple ? 'warning' : ( $canonical_valid ? 'pass' : 'warning' ) ),
			'result'  => ( $canonical_has_multiple ? 'Conflicted' : ( $canonical_valid ? 'Valid' : 'Invalid' ) ),
			'details' => $canonical_valid_url_message_fb . ' | canonical: ' . ( '' !== $canonical ? $canonical : 'none' ) . ' | canonicalCount: ' . $canonical_count_detected . ' | isValid: ' . ( $canonical_valid ? 'true' : 'false' ),
		);

		$self_canonical_message_fb = $canonical_has_multiple ? 'Cannot validate self-reference with multiple canonical tags.' : ( '' === $canonical ? 'Cannot verify self-reference without canonical.' : 'Compared input URL against canonical URL.' );
		$checks[]                  = array(
			'label'   => 'Self Canonical',
			'status'  => ( $canonical_has_multiple ? 'warning' : ( '' === $canonical ? 'warning' : ( $canonical_normalized === $input_normalized ? 'pass' : 'warning' ) ) ),
			'result'  => ( $canonical_has_multiple ? 'Conflicted' : ( '' === $canonical ? 'Unknown' : ( $canonical_normalized === $input_normalized ? 'Self-referencing' : 'Different target' ) ) ),
			'details' => $self_canonical_message_fb . ' | canonical: ' . ( '' !== $canonical ? $canonical : 'none' ) . ' | url: ' . $url . ' | isSelf: ' . ( $canonical_normalized === $input_normalized ? 'true' : 'false' ),
		);

		$checks[] = array(
			'label'   => 'Canonical Target HTTP 200',
			'status'  => 'warning',
			'result'  => 'Unknown',
			'details' => 'Skipped during fallback mode because outbound HTTP checks are unavailable.',
		);

		$checks[] = array(
			'label'   => 'Robots Meta',
			'status'  => 'noindex' === $robots_index ? 'warning' : 'pass',
			'result'  => 'noindex' === $robots_index ? 'Noindex' : 'Not Detected',
			'details' => 'Derived from local robots index metadata.',
		);

		$checks[] = array(
			'label'   => 'X-Robots-Tag',
			'status'  => 'warning',
			'result'  => 'Unknown',
			'details' => 'Not available in fallback mode without live response headers.',
		);

		$checks[] = array(
			'label'   => 'Indexability',
			'status'  => 'noindex' === $robots_index ? 'warning' : 'pass',
			'result'  => 'noindex' === $robots_index ? 'Noindex' : 'Indexable',
			'details' => 'Based on local robots metadata.',
		);

		$checks[] = array(
			'label'   => 'Follow Directive',
			'status'  => 'nofollow' === $robots_follow ? 'warning' : 'pass',
			'result'  => 'nofollow' === $robots_follow ? 'Nofollow' : 'Follow',
			'details' => 'Based on local robots follow metadata.',
		);

		$checks[] = array(
			'label'   => 'Content Depth (Word Count)',
			'status'  => $word_count >= 300 ? 'pass' : 'warning',
			'result'  => $word_count . ' words',
			'details' => $word_count >= 300 ? 'Content depth is generally sufficient.' : 'Content may be thin (below 300 words).',
		);

		$checks[] = array(
			'label'   => 'H1 Presence',
			'status'  => 1 === (int) $heading_h1_count ? 'pass' : 'warning',
			'result'  => (int) $heading_h1_count > 0 ? 'Yes' : 'No',
			'details' => 1 === (int) $heading_h1_count ? 'Exactly one H1 in post content.' : 'Expected at least one H1 in content.',
		);

		$checks[] = array(
			'label'   => 'Readability',
			'status'  => $word_count >= 300 ? 'pass' : 'warning',
			'result'  => $word_count >= 300 ? 'Readable' : 'Needs improvement',
			'details' => $word_count . ' words available for readability evaluation.',
		);

		$checks[] = array(
			'label'   => 'Multiple H1',
			'status'  => (int) $heading_h1_count <= 1 ? 'pass' : ( 2 === (int) $heading_h1_count ? 'warning' : 'fail' ),
			'result'  => (int) $heading_h1_count,
			'details' => 'Keep one H1 for strongest structure.',
		);

		$checks[] = array(
			'label'   => 'Internal Links',
			'status'  => $internal_link_count > 0 ? 'pass' : 'warning',
			'result'  => $internal_link_count,
			'details' => $internal_link_count > 0 ? 'Internal links found in content.' : 'No internal links detected in content.',
		);

		$checks[] = array(
			'label'   => 'External Links',
			'status'  => 'pass',
			'result'  => $external_link_count,
			'details' => 'Informational: external links detected in content.',
		);

		$checks[] = array(
			'label'   => 'Nofollow Links',
			'status'  => 'pass',
			'result'  => $nofollow_link_count,
			'details' => 'Informational count of links marked nofollow.',
		);

		$checks[] = array(
			'label'   => 'Images Found',
			'status'  => $image_count > 0 ? 'pass' : 'warning',
			'result'  => $image_count,
			'details' => $image_count > 0 ? 'Image elements found in post content.' : 'No images detected in post content.',
		);

		$checks[] = array(
			'label'   => 'Missing ALT',
			'status'  => 0 === $missing_alt_count ? 'pass' : ( $missing_alt_count <= 2 ? 'warning' : 'fail' ),
			'result'  => $missing_alt_count,
			'details' => 'Images missing alt attributes or alt values.',
		);

		$checks[] = array(
			'label'   => 'Empty ALT',
			'status'  => 0 === $empty_alt_count ? 'pass' : ( $empty_alt_count <= 2 ? 'warning' : 'fail' ),
			'result'  => $empty_alt_count,
			'details' => 'Images with empty alt text values.',
		);

		$checks[] = array(
			'label'   => 'Featured Image',
			'status'  => has_post_thumbnail( $post_id ) ? 'pass' : 'warning',
			'result'  => has_post_thumbnail( $post_id ) ? 'Present' : 'Missing',
			'details' => has_post_thumbnail( $post_id ) ? 'Featured image is configured.' : 'No featured image is configured.',
		);

		$checks[] = array(
			'label'   => 'Google Preview',
			'status'  => ( '' !== $title && '' !== $description ) ? 'pass' : 'warning',
			'result'  => ( '' !== $title && '' !== $description ) ? 'Ready' : 'Incomplete',
			'details' => 'Derived from local title and meta description.',
		);

		$checks[] = array(
			'label'   => 'Open Graph Title',
			'status'  => '' !== $og_title ? 'pass' : 'warning',
			'result'  => '' !== $og_title ? 'Present' : 'Missing',
			'details' => 'Derived from local OG title metadata.',
		);

		$checks[] = array(
			'label'   => 'Open Graph Description',
			'status'  => '' !== $og_description ? 'pass' : 'warning',
			'result'  => '' !== $og_description ? 'Present' : 'Missing',
			'details' => 'Derived from local OG description metadata.',
		);

		$checks[] = array(
			'label'   => 'Open Graph Image',
			'status'  => ( '' !== $og_image && ! $og_image_disabled ) ? 'pass' : 'warning',
			'result'  => ( '' !== $og_image && ! $og_image_disabled ) ? 'Present' : 'Missing',
			'details' => 'Derived from local OG image metadata.',
		);

		$checks[] = array(
			'label'   => 'Twitter Card',
			'status'  => 'warning',
			'result'  => 'Unknown',
			'details' => 'Twitter card validation requires live HTML response in current implementation.',
		);

		$og_fields_count = 0;
		if ( '' !== $og_title ) {
			++$og_fields_count;
		}
		if ( '' !== $og_description ) {
			++$og_fields_count;
		}
		if ( '' !== $og_image && ! $og_image_disabled ) {
			++$og_fields_count;
		}

		$checks[] = array(
			'label'   => 'Open Graph Setup',
			'status'  => $og_fields_count >= 2 ? 'pass' : 'warning',
			'result'  => $og_fields_count . '/3 fields',
			'details' => 'Based on local OG title/description/image metadata.',
		);

		$checks[] = array(
			'label'   => 'Schema Settings',
			'status'  => ( false === $schema_enabled || '0' === (string) $schema_enabled ) ? 'warning' : 'pass',
			'result'  => ( false === $schema_enabled || '0' === (string) $schema_enabled ) ? 'Disabled' : ( $schema_type ? $schema_type : 'Enabled' ),
			'details' => 'Based on local schema configuration metadata.',
		);

		$checks[] = array(
			'label'   => 'Structured Data Present',
			'status'  => $schema_enabled_bool ? 'pass' : 'warning',
			'result'  => $schema_enabled_bool ? 'Yes' : 'No',
			'details' => 'Derived from local schema configuration.',
		);

		$checks[] = array(
			'label'   => 'Schema Validation',
			'status'  => $schema_enabled_bool ? 'pass' : 'warning',
			'result'  => $schema_enabled_bool ? 'Config present' : 'Not configured',
			'details' => 'Fallback mode cannot validate live JSON-LD markup; uses schema configuration state.',
		);

		$checks[] = array(
			'label'   => 'Primary Entity',
			'status'  => '' !== $schema_type ? 'pass' : 'warning',
			'result'  => '' !== $schema_type ? $schema_type : 'Unknown',
			'details' => 'Derived from local schema type setting.',
		);

		$checks[] = array(
			'label'   => 'Organization Schema',
			'status'  => $has_org_schema ? 'pass' : 'warning',
			'result'  => $has_org_schema ? 'Present' : 'Missing',
			'details' => 'Derived from local schema type setting.',
		);

		$checks[] = array(
			'label'   => 'Article Schema',
			'status'  => $has_article_schema ? 'pass' : 'warning',
			'result'  => $has_article_schema ? 'Present' : 'Missing',
			'details' => 'Derived from local schema type setting.',
		);

		$checks[] = array(
			'label'   => 'FAQ Schema',
			'status'  => $has_faq_schema ? 'pass' : 'warning',
			'result'  => $has_faq_schema ? 'Present' : 'Missing',
			'details' => 'Derived from local schema type setting.',
		);

		$checks[] = array(
			'label'   => 'Breadcrumb Schema',
			'status'  => $has_breadcrumb_schema ? 'pass' : 'warning',
			'result'  => $has_breadcrumb_schema ? 'Present' : 'Missing',
			'details' => 'Derived from local schema type setting.',
		);

		$checks[] = array(
			'label'   => 'Heading Structure',
			'status'  => $has_semantic_headings ? 'pass' : 'warning',
			'result'  => $has_semantic_headings ? 'Good' : 'Needs work',
			'details' => 'Fallback heading structure check from local content.',
		);

		$checks[] = array(
			'label'   => 'Heading Hierarchy',
			'status'  => $semantic_heading_structure_ok ? 'pass' : 'warning',
			'result'  => $semantic_heading_structure_ok ? 'Good' : 'Needs work',
			'details' => 'Fallback heading quality check from local content.',
		);

		$checks[] = array(
			'label'   => 'FAQ Content',
			'status'  => ( $faq_pattern || $has_faq_schema ) ? 'pass' : 'warning',
			'result'  => ( $faq_pattern || $has_faq_schema ) ? 'Detected' : 'Not detected',
			'details' => 'Detected from FAQ terms or local schema type.',
		);

		$checks[] = array(
			'label'   => 'Author Information',
			'status'  => $has_author_info ? 'pass' : 'warning',
			'result'  => $has_author_info ? 'Present' : 'Missing',
			'details' => 'Derived from WordPress author data.',
		);

		$checks[] = array(
			'label'   => 'Published Date',
			'status'  => $has_published_date ? 'pass' : 'warning',
			'result'  => $has_published_date ? 'Present' : 'Missing',
			'details' => 'Derived from WordPress post published timestamp.',
		);

		$checks[] = array(
			'label'   => 'Last Updated Date',
			'status'  => $has_last_updated_date ? 'pass' : 'warning',
			'result'  => $has_last_updated_date ? 'Present' : 'Missing',
			'details' => 'Derived from WordPress post modified timestamp.',
		);

		$checks[] = array(
			'label'   => 'Organization Information',
			'status'  => $has_org_schema ? 'pass' : 'warning',
			'result'  => $has_org_schema ? 'Present' : 'Missing',
			'details' => 'Derived from local schema type setting.',
		);

		$checks[] = array(
			'label'   => 'Language Declaration',
			'status'  => '' !== $html_lang ? 'pass' : 'warning',
			'result'  => '' !== $html_lang ? $html_lang : 'Missing',
			'details' => 'Fallback language from WordPress site language setting.',
		);

		$checks[] = array(
			'label'   => 'Internal References',
			'status'  => $internal_link_count > 0 ? 'pass' : 'warning',
			'result'  => $internal_link_count,
			'details' => 'Internal references (links to related pages).',
		);

		$checks[] = array(
			'label'   => 'External References',
			'status'  => $external_link_count > 0 ? 'pass' : 'warning',
			'result'  => $external_link_count,
			'details' => 'External references to other domains.',
		);

		$checks[] = array(
			'label'   => 'Table/List Detection',
			'status'  => $has_structured_content ? 'pass' : 'warning',
			'result'  => $has_structured_content ? 'Detected' : 'Not detected',
			'details' => 'Checks for list or table structures in content.',
		);

		$checks[] = array(
			'label'   => 'Definition Content',
			'status'  => $definition_pattern ? 'pass' : 'warning',
			'result'  => $definition_pattern ? 'Detected' : 'Not detected',
			'details' => 'Detects definition-style phrasing such as "What is" patterns.',
		);

		$checks[] = array(
			'label'   => 'Media Context',
			'status'  => ( $image_count > 0 && 0 === $media_context_issue_count ) ? 'pass' : 'warning',
			'result'  => $image_count > 0 ? ( 0 === $media_context_issue_count ? 'Good' : 'Needs improvement' ) : 'No images',
			'details' => $image_count > 0
			? sprintf( 'Images: %d, ALT issues: %d.', $image_count, $media_context_issue_count )
			: 'No images detected for media context analysis.',
		);

		$checks[] = array(
			'label'   => 'Post Freshness',
			'status'  => 'pass',
			'result'  => get_post_modified_time( 'Y-m-d H:i:s', true, $post_id ) ?: 'Unknown',
			'details' => 'Last modified timestamp from local post data.',
		);

		$checks[] = array(
			'label'   => 'Post Context',
			'status'  => 'pass',
			'result'  => $post instanceof WP_Post ? strtoupper( $post->post_type ) : 'UNKNOWN',
			'details' => $post instanceof WP_Post
			? 'Status: ' . strtoupper( (string) $post->post_status ) . ( $permalink ? ' | Permalink: ' . $permalink : '' )
			: 'Local post context unavailable.',
		);

		// ===== UNIFIED DESIGN: Add completeness tracking to fallback checks =====
		// Track which fields were available from stored post meta (fallback source)
		$captured_fields = get_post_meta( $post_id, '_ASNERISSEO_captured_fields', true );
		if ( ! is_array( $captured_fields ) ) {
			$captured_fields = array();
		}

		$missing_fields = array();
		if ( empty( $captured_fields ) || ! in_array( 'canonicalCount', $captured_fields, true ) ) {
			$missing_fields[] = 'canonicalCount';
		}
		if ( empty( $captured_fields ) || ! in_array( 'h1Count', $captured_fields, true ) ) {
			$missing_fields[] = 'h1Count';
		}

		// Add completeness metadata to each check in fallback flow
		$is_data_complete = empty( $missing_fields );
		foreach ( $checks as &$check ) {
			$check['isDataComplete'] = $is_data_complete;
			$check['missingFields']  = $missing_fields;
		}
		unset( $check );
	}

	/**
	 * Extract canonical URL from HTML using DOM parsing
	 */
	private static function extract_canonical_from_html( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		// Suppress warnings from malformed HTML
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( $html );

		$links = $dom->getElementsByTagName( 'link' );
		foreach ( $links as $link ) {
			if ( $link->getAttribute( 'rel' ) === 'canonical' ) {
				$href = $link->getAttribute( 'href' );
				libxml_clear_errors();
				return $href;
			}
		}

		libxml_clear_errors();
		return '';
	}

	/**
	 * Extract robots meta content from HTML using DOM parsing
	 */
	private static function extract_robots_meta_from_html( $html ) {
		if ( empty( $html ) ) {
			return '';
		}

		// Suppress warnings from malformed HTML
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->loadHTML( $html );

		$metas = $dom->getElementsByTagName( 'meta' );
		foreach ( $metas as $meta ) {
			if ( strtolower( $meta->getAttribute( 'name' ) ) === 'robots' ) {
				$content = $meta->getAttribute( 'content' );
				libxml_clear_errors();
				return $content;
			}
		}

		libxml_clear_errors();
		return '';
	}
}
