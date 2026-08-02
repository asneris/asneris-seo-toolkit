<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_REST_API_AI_Searchability {
	public static function get_ai_searchability( WP_REST_Request $request ) {
		unset( $request );

		$state             = self::get_llms_state();
		$settings          = self::get_llms_settings();
		$draft_content     = isset( $state['draft_content'] ) ? (string) $state['draft_content'] : '';
		$published_content = isset( $state['published_content'] ) ? (string) $state['published_content'] : '';
		$llms_path         = self::get_site_root_file_path( 'llms.txt' );
		$live_content      = '';
		if ( file_exists( $llms_path ) && is_readable( $llms_path ) ) {
			$live_file = file_get_contents( $llms_path );
			if ( is_string( $live_file ) ) {
				$live_content = $live_file;
			}
		}
		if ( $published_content === '' ) {
			$published_content = $live_content;
		}
		$detected   = file_exists( $llms_path ) || ! empty( $published_content );
		$content    = $draft_content !== '' ? $draft_content : $published_content;
		$validation = self::validate_ai_searchability_payload( $content, $detected );

		return rest_ensure_response(
			array(
				'detected'         => $detected,
				'content'          => $content,
				'draftContent'     => $draft_content,
				'liveContent'      => $published_content,
				'settings'         => $settings,
				'status'           => isset( $state['status'] ) ? $state['status'] : 'not_generated',
				'summary'          => isset( $state['summary'] ) ? $state['summary'] : array(),
				'message'          => $detected
					? __( 'A draft or published LLMs.txt version is available.', 'asneris-seo-toolkit' )
					: __( 'No LLMs.txt draft has been generated yet.', 'asneris-seo-toolkit' ),
				'validation'       => $validation,
				'publishedVersion' => isset( $state['published_version'] ) ? $state['published_version'] : 0,
			)
		);
	}

	public static function handle_ai_searchability( WP_REST_Request $request ) {
		$incoming = $request->get_json_params();
		if ( ! is_array( $incoming ) ) {
			$incoming = array();
		}

		$action           = isset( $incoming['action'] ) ? sanitize_key( $incoming['action'] ) : 'generate_draft';
		$incoming_content = '';
		if ( isset( $incoming['content'] ) && is_string( $incoming['content'] ) ) {
			$incoming_content = $incoming['content'];
		} elseif ( isset( $incoming['draft_content'] ) && is_string( $incoming['draft_content'] ) ) {
			$incoming_content = $incoming['draft_content'];
		} elseif ( isset( $incoming['draftContent'] ) && is_string( $incoming['draftContent'] ) ) {
			$incoming_content = $incoming['draftContent'];
		}
		$raw_content       = wp_unslash( $incoming_content );
		$content           = self::normalize_draft_content( $raw_content );
		$approved          = ! empty( $incoming['approved'] );
		$state             = self::get_llms_state();
		$settings          = self::get_llms_settings();
		$incoming_settings = isset( $incoming['settings'] ) && is_array( $incoming['settings'] ) ? $incoming['settings'] : array();

		if ( ! empty( $incoming_settings ) ) {
			$settings = self::save_llms_settings( $incoming_settings );
		}

		if ( 'save_settings' === $action || 'save_draft' === $action || 'validate' === $action ) {
			$state['status']  = 'needs_review';
			$state['message'] = __( 'Use Generate Draft and Approve & Publish to review and publish the content.', 'asneris-seo-toolkit' );
			self::save_llms_state( $state );

			return rest_ensure_response(
				array(
					'content'      => isset( $state['draft_content'] ) ? $state['draft_content'] : '',
					'draftContent' => isset( $state['draft_content'] ) ? (string) $state['draft_content'] : '',
					'liveContent'  => isset( $state['published_content'] ) ? (string) $state['published_content'] : '',
					'status'       => $state['status'],
					'settings'     => $settings,
					'message'      => $state['message'],
					'validation'   => self::validate_ai_searchability_payload( isset( $state['draft_content'] ) ? $state['draft_content'] : '', ! empty( $state['published_content'] ) ),
				)
			);
		}

		if ( 'generate_draft' === $action || 'generate' === $action || 'regenerate' === $action ) {
			if ( empty( $settings['enabled'] ) ) {
				return rest_ensure_response(
					array(
						'content'      => isset( $state['draft_content'] ) ? $state['draft_content'] : '',
						'draftContent' => isset( $state['draft_content'] ) ? (string) $state['draft_content'] : '',
						'liveContent'  => isset( $state['published_content'] ) ? (string) $state['published_content'] : '',
						'status'       => 'disabled',
						'settings'     => $settings,
						'message'      => __( 'LLMs.txt generation is currently disabled.', 'asneris-seo-toolkit' ),
						'validation'   => self::validate_ai_searchability_payload( isset( $state['draft_content'] ) ? $state['draft_content'] : '', ! empty( $state['published_content'] ) ),
					)
				);
			}
			$generated              = self::generate_llms_draft( $state );
			$state['draft_content'] = $generated['content'];
			$state['summary']       = $generated['summary'];
			$state['status']        = ! empty( $state['published_content'] ) ? 'update_available' : 'draft_generated';
			$state['manual_edits']  = false;
			$state['message']       = __( 'A new draft has been generated and is ready for review.', 'asneris-seo-toolkit' );
			self::save_llms_state( $state );

			return rest_ensure_response(
				array(
					'content'      => $state['draft_content'],
					'draftContent' => $state['draft_content'],
					'liveContent'  => isset( $state['published_content'] ) ? (string) $state['published_content'] : '',
					'status'       => $state['status'],
					'summary'      => $state['summary'],
					'settings'     => $settings,
					'message'      => $state['message'],
					'validation'   => self::validate_ai_searchability_payload( $state['draft_content'], ! empty( $state['published_content'] ) ),
				)
			);
		}

		if ( 'publish' === $action || 'approve_publish' === $action ) {
			if ( ! empty( $settings['require_approval'] ) && ! $approved ) {
				return new WP_Error(
					'asnerisseo_ai_searchability_approval_required',
					__( 'Approval is required before publishing the LLMs.txt file.', 'asneris-seo-toolkit' ),
					array( 'status' => 403 )
				);
			}

			$draft_content = $content !== '' ? $content : ( isset( $state['draft_content'] ) ? (string) $state['draft_content'] : '' );
			if ( $draft_content === '' ) {
				return new WP_Error(
					'asnerisseo_ai_searchability_empty_draft',
					__( 'Generate a draft before publishing.', 'asneris-seo-toolkit' ),
					array( 'status' => 400 )
				);
			}

			$llms_path = self::get_site_root_file_path( 'llms.txt' );
			$directory = dirname( $llms_path );
			if ( ! is_dir( $directory ) ) {
				wp_mkdir_p( $directory );
			}

			$written = file_put_contents( $llms_path, $draft_content );
			if ( $written === false ) {
				return new WP_Error(
					'asnerisseo_ai_searchability_publish_failed',
					__( 'The draft could not be written to the public llms.txt location.', 'asneris-seo-toolkit' ),
					array( 'status' => 500 )
				);
			}

			$published_version            = isset( $state['published_version'] ) ? (int) $state['published_version'] + 1 : 1;
			$state['published_content']   = $draft_content;
			$state['draft_content']       = $draft_content;
			$state['published_timestamp'] = current_time( 'mysql' );
			$state['published_version']   = $published_version;
			$state['approved_by']         = get_current_user_id();
			$state['status']              = 'published';
			$state['message']             = __( 'The draft was approved and published to /llms.txt.', 'asneris-seo-toolkit' );
			$state['version_history']     = isset( $state['version_history'] ) && is_array( $state['version_history'] ) ? $state['version_history'] : array();
			$state['version_history'][]   = array(
				'version'   => $published_version,
				'timestamp' => current_time( 'mysql' ),
				'content'   => $draft_content,
			);
			self::save_llms_state( $state );

			return rest_ensure_response(
				array(
					'content'          => $draft_content,
					'draftContent'     => $draft_content,
					'liveContent'      => $draft_content,
					'detected'         => true,
					'status'           => $state['status'],
					'settings'         => $settings,
					'message'          => $state['message'],
					'validation'       => self::validate_ai_searchability_payload( $draft_content, true ),
					'publishedVersion' => $published_version,
				)
			);
		}

		return rest_ensure_response(
			array(
				'content'      => isset( $state['draft_content'] ) ? $state['draft_content'] : '',
				'draftContent' => isset( $state['draft_content'] ) ? (string) $state['draft_content'] : '',
				'liveContent'  => isset( $state['published_content'] ) ? (string) $state['published_content'] : '',
				'status'       => isset( $state['status'] ) ? $state['status'] : 'not_generated',
				'settings'     => $settings,
				'message'      => __( 'No action was performed.', 'asneris-seo-toolkit' ),
				'validation'   => self::validate_ai_searchability_payload( isset( $state['draft_content'] ) ? $state['draft_content'] : '', ! empty( $state['published_content'] ) ),
			)
		);
	}

	public static function get_public_llms_content() {
		$state             = self::get_llms_state();
		$draft_content     = isset( $state['draft_content'] ) ? (string) $state['draft_content'] : '';
		$published_content = isset( $state['published_content'] ) ? (string) $state['published_content'] : '';

		if ( '' !== trim( $draft_content ) ) {
			return $draft_content;
		}

		if ( '' !== trim( $published_content ) ) {
			return $published_content;
		}

		return '';
	}

	public static function get_site_root_file_path( $filename ) {
		$candidates = array();
		if ( function_exists( 'get_home_path' ) ) {
			$home_path = wp_normalize_path( untrailingslashit( get_home_path() ) );
			if ( $home_path !== '' ) {
				$candidates[] = $home_path . '/' . $filename;
			}
		}

		if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			$document_root = wp_normalize_path( untrailingslashit( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) ) );
			if ( $document_root !== '' ) {
				$candidates[] = $document_root . '/' . $filename;
			}
		}

		$candidates[] = wp_normalize_path( untrailingslashit( ABSPATH ) ) . '/' . $filename;

		foreach ( $candidates as $candidate ) {
			$directory = dirname( $candidate );
			if ( is_dir( $directory ) && wp_is_writable( $directory ) ) {
				return $candidate;
			}
		}

		return $candidates[0] ?? wp_normalize_path( untrailingslashit( ABSPATH ) ) . '/' . $filename;
	}

	private static function get_llms_settings() {
		$defaults = array(
			'enabled'                    => true,
			'include_posts'              => true,
			'include_pages'              => true,
			'include_custom_post_types'  => true,
			'max_recommended_urls'       => 50,
			'allow_external_urls'        => false,
			'require_approval'           => true,
			'use_seo_metadata'           => true,
			'use_canonical_urls'         => true,
			'exclude_noindex_content'    => true,
			'exclude_redirects_and_404s' => true,
			'respect_manual_exclusions'  => true,
			'validate_before_publish'    => true,
		);

		$saved = get_option( 'asneris_llms_settings', array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$settings                               = wp_parse_args( $saved, $defaults );
		$settings['enabled']                    = ! empty( $settings['enabled'] );
		$settings['include_posts']              = ! empty( $settings['include_posts'] );
		$settings['include_pages']              = ! empty( $settings['include_pages'] );
		$settings['include_custom_post_types']  = ! empty( $settings['include_custom_post_types'] );
		$settings['max_recommended_urls']       = max( 1, absint( $settings['max_recommended_urls'] ) );
		$settings['allow_external_urls']        = ! empty( $settings['allow_external_urls'] );
		$settings['require_approval']           = ! empty( $settings['require_approval'] );
		$settings['use_seo_metadata']           = ! empty( $settings['use_seo_metadata'] );
		$settings['use_canonical_urls']         = ! empty( $settings['use_canonical_urls'] );
		$settings['exclude_noindex_content']    = ! empty( $settings['exclude_noindex_content'] );
		$settings['exclude_redirects_and_404s'] = ! empty( $settings['exclude_redirects_and_404s'] );
		$settings['respect_manual_exclusions']  = ! empty( $settings['respect_manual_exclusions'] );
		$settings['validate_before_publish']    = ! empty( $settings['validate_before_publish'] );

		return $settings;
	}

	private static function save_llms_settings( $incoming_settings ) {
		$settings = self::get_llms_settings();
		if ( ! is_array( $incoming_settings ) ) {
			$incoming_settings = array();
		}

		$settings['enabled']                   = ! empty( $incoming_settings['enabled'] );
		$settings['include_posts']             = ! empty( $incoming_settings['include_posts'] );
		$settings['include_pages']             = ! empty( $incoming_settings['include_pages'] );
		$settings['include_custom_post_types'] = ! empty( $incoming_settings['include_custom_post_types'] );
		$settings['max_recommended_urls']      = max( 1, absint( $incoming_settings['max_recommended_urls'] ) );
		$settings['allow_external_urls']       = ! empty( $incoming_settings['allow_external_urls'] );
		update_option( 'asneris_llms_settings', $settings );
		return $settings;
	}

	private static function get_llms_state() {
		$state = get_option( 'asneris_llms_state', array() );
		if ( ! is_array( $state ) ) {
			$state = array();
		}

		return wp_parse_args(
			$state,
			array(
				'status'            => 'not_generated',
				'draft_content'     => '',
				'published_content' => '',
				'published_version' => 0,
				'summary'           => array(),
				'manual_edits'      => false,
				'version_history'   => array(),
			)
		);
	}

	private static function save_llms_state( $state ) {
		if ( ! is_array( $state ) ) {
			$state = array();
		}

		update_option( 'asneris_llms_state', $state );
		return $state;
	}

	private static function generate_llms_draft( $state ) {
		$settings            = self::get_llms_settings();
		$site_title          = self::clean_generated_content( get_bloginfo( 'name' ) );
		$site_summary        = self::clean_generated_content( get_bloginfo( 'description' ) );
		if ( empty( $site_summary ) ) {
			$site_summary = __( 'Public website content and important resources.', 'asneris-seo-toolkit' );
		}
		$max_items           = max( 3, absint( isset( $settings['max_recommended_urls'] ) ? $settings['max_recommended_urls'] : 6 ) );
		$per_type_limit      = max( 1, min( 200, $max_items ) );
		$allow_external_urls = ! empty( $settings['allow_external_urls'] );
		$home_url            = home_url( '/' );
		$items               = array(
			array(
				'url'         => $home_url,
				'title'       => __( 'Home', 'asneris-seo-toolkit' ),
				'description' => __( 'Primary landing page for the website.', 'asneris-seo-toolkit' ),
				'section'     => __( 'Main Pages', 'asneris-seo-toolkit' ),
			),
		);

		if ( ! empty( $settings['include_pages'] ) ) {
			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					'posts_per_page' => $per_type_limit,
					'orderby'        => 'menu_order title',
					'order'          => 'ASC',
					'fields'         => 'ids',
				)
			);
			foreach ( $pages as $page_id ) {
				$url = get_permalink( $page_id );
				if ( ! $url || self::is_excluded_url( $url ) || ( ! $allow_external_urls && self::is_external_url( $url ) ) ) {
					continue;
				}
				$items[] = array(
					'url'         => $url,
					'title'       => self::get_page_title( $page_id ),
					'description' => self::generate_description_for_item( $page_id ),
					'section'     => __( 'Main Pages', 'asneris-seo-toolkit' ),
				);
			}
		}

		if ( ! empty( $settings['include_posts'] ) ) {
			$posts = get_posts(
				array(
					'post_type'      => 'post',
					'post_status'    => 'publish',
					'posts_per_page' => $per_type_limit,
					'fields'         => 'ids',
				)
			);
			foreach ( $posts as $post_id ) {
				$url = get_permalink( $post_id );
				if ( ! $url || self::is_excluded_url( $url ) || ( ! $allow_external_urls && self::is_external_url( $url ) ) ) {
					continue;
				}
				$items[] = array(
					'url'         => $url,
					'title'       => self::get_page_title( $post_id ),
					'description' => self::generate_description_for_item( $post_id ),
					'section'     => __( 'Articles', 'asneris-seo-toolkit' ),
				);
			}
		}

		if ( ! empty( $settings['include_custom_post_types'] ) ) {
			$post_types = get_post_types(
				array(
					'public'              => true,
					'exclude_from_search' => false,
				),
				'objects'
			);
			foreach ( $post_types as $post_type ) {
				if ( in_array( $post_type->name, array( 'page', 'post', 'attachment' ), true ) ) {
					continue;
				}
				$custom_posts = get_posts(
					array(
						'post_type'      => $post_type->name,
						'post_status'    => 'publish',
						'posts_per_page' => max( 1, min( 10, $per_type_limit ) ),
						'fields'         => 'ids',
					)
				);
				foreach ( $custom_posts as $post_id ) {
					$url = get_permalink( $post_id );
					if ( ! $url || self::is_excluded_url( $url ) || ( ! $allow_external_urls && self::is_external_url( $url ) ) ) {
						continue;
					}
					$items[] = array(
						'url'         => $url,
						'title'       => self::get_page_title( $post_id ),
						'description' => self::generate_description_for_item( $post_id ),
						'section'     => ucfirst( str_replace( '-', ' ', $post_type->name ) ),
					);
				}
			}
		}

		$deduped   = array();
		$seen_urls = array();
		foreach ( $items as $item ) {
			$url = isset( $item['url'] ) ? $item['url'] : '';
			if ( $url === '' || isset( $seen_urls[ $url ] ) ) {
				continue;
			}
			$seen_urls[ $url ] = true;
			$deduped[]         = $item;
			if ( count( $deduped ) >= $max_items ) {
				break;
			}
		}

		$section_map = array();
		foreach ( $deduped as $item ) {
			$section_name = isset( $item['section'] ) && is_string( $item['section'] ) ? self::clean_generated_content( $item['section'] ) : '';
			if ( $section_name === '' ) {
				$section_name = __( 'Main Pages', 'asneris-seo-toolkit' );
			}
			$section_map[ $section_name ] = true;
		}

		$lines   = array();
		$lines[] = '# ' . ( $site_title !== '' ? $site_title : __( 'AI Searchability', 'asneris-seo-toolkit' ) );
		$lines[] = '';
		$lines[] = $site_summary;
		$lines[] = '';
		if ( empty( $deduped ) ) {
			$lines[] = __( 'No additional public resources were detected yet.', 'asneris-seo-toolkit' );
			$lines[] = '';
		}

		foreach ( $deduped as $item ) {
			$title       = isset( $item['title'] ) ? self::clean_generated_content( $item['title'] ) : __( 'Untitled', 'asneris-seo-toolkit' );
			$url         = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
			$description = isset( $item['description'] ) ? self::clean_generated_content( $item['description'] ) : '';
			if ( $url === '' ) {
				continue;
			}
			$entry = '- [' . $title . '](' . $url . ')';
			if ( $description !== '' ) {
				$entry .= ': ' . $description;
			}
			$lines[] = $entry;
		}

		$replacement_content = preg_replace( '/\n{3,}/', "\n\n", trim( implode( "\n", $lines ) ) );
		$summary             = array(
			'included_count' => count( $deduped ),
			'sections'       => array_keys( $section_map ),
			'status'         => 'draft_generated',
		);

		$state['summary'] = $summary;

		return array(
			'content' => $replacement_content,
			'summary' => $summary,
		);
	}

	private static function get_page_title( $post_id ) {
		$title = get_the_title( $post_id );
		$title = self::normalize_output_text( $title );
		return $title !== '' ? $title : __( 'Untitled page', 'asneris-seo-toolkit' );
	}

	private static function generate_description_for_item( $post_id ) {
		$post_title = self::normalize_output_text( get_the_title( $post_id ) );

		$meta_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
		if ( is_string( $meta_description ) && $meta_description !== '' ) {
			return self::normalize_description_text( $meta_description, $post_title );
		}

		$excerpt = get_the_excerpt( $post_id );
		if ( is_string( $excerpt ) && $excerpt !== '' ) {
			return self::normalize_description_text( $excerpt, $post_title );
		}

		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			$content = wp_strip_all_tags( $post->post_content );
			if ( is_string( $content ) && $content !== '' ) {
				return self::normalize_description_text( $content, $post_title );
			}
		}

		return self::build_fallback_description( $post_title );
	}

	private static function infer_section( $post_id ) {
		$title = strtolower( self::get_page_title( $post_id ) );
		if ( strpos( $title, 'doc' ) !== false || strpos( $title, 'guide' ) !== false ) {
			return 'Documentation';
		}
		if ( strpos( $title, 'service' ) !== false || strpos( $title, 'product' ) !== false ) {
			return 'Resources';
		}
		if ( strpos( $title, 'faq' ) !== false || strpos( $title, 'support' ) !== false ) {
			return 'Support';
		}
		return 'Main Pages';
	}

	private static function infer_reason( $post_id ) {
		$title = self::get_page_title( $post_id );
		if ( preg_match( '/(service|product|documentation|guide|support|faq)/i', $title ) ) {
			return __( 'Important public page', 'asneris-seo-toolkit' );
		}
		return __( 'Published page', 'asneris-seo-toolkit' );
	}

	private static function is_excluded_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return true;
		}

		$normalized = strtolower( $url );
		return strpos( $normalized, 'wp-admin' ) !== false
			|| strpos( $normalized, 'wp-login' ) !== false
			|| strpos( $normalized, '/search/' ) !== false
			|| strpos( $normalized, '/feed/' ) !== false
			|| strpos( $normalized, '?s=' ) !== false
			|| strpos( $normalized, 'attachment' ) !== false;
	}

	private static function is_external_url( $url ) {
		if ( ! is_string( $url ) || $url === '' ) {
			return false;
		}

		return preg_match( '/^https?:\/\//i', $url ) && strpos( $url, home_url( '/' ) ) !== 0;
	}

	private static function normalize_draft_content( $content ) {
		if ( ! is_string( $content ) ) {
			return '';
		}

		$content = trim( $content );
		if ( $content === '' ) {
			return '';
		}

		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
		$content = wp_specialchars_decode( $content, ENT_QUOTES );
		$content = str_replace(
			array(
				'â€™',
				'â€˜',
				'â€œ',
				'â€',
				'â€“',
				'â€”',
				'â€¦',
				'&nbsp;',
				"\t",
			),
			array(
				'’',
				'‘',
				'“',
				'”',
				'–',
				'—',
				'…',
				' ',
				'    ',
			),
			$content
		);
		$content = preg_replace( '/\n{3,}/', "\n\n", $content );
		return trim( $content );
	}

	private static function clean_generated_content( $content ) {
		if ( ! is_string( $content ) ) {
			return '';
		}

		$content = trim( $content );
		if ( $content === '' ) {
			return '';
		}

		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
		$content = wp_specialchars_decode( $content, ENT_QUOTES );

		$lines         = preg_split( '/\n/', $content );
		$cleaned_lines = array();
		$seen_headings = array();

		foreach ( $lines as $line ) {
			$trimmed_line = trim( $line );
			if ( $trimmed_line === '' ) {
				$cleaned_lines[] = '';
				continue;
			}

			$trimmed_line = preg_replace( '/%%[A-Za-z0-9_-]+%%/', '', $trimmed_line );
			$trimmed_line = preg_replace( '/<[^>]+>/', ' ', $trimmed_line );
			$trimmed_line = preg_replace( '/\[(?:read more|more|\.\.\.)\]/i', '', $trimmed_line );
			$trimmed_line = preg_replace( '/\b(?:continue reading|read more|learn more)\b/i', '', $trimmed_line );
			$trimmed_line = preg_replace( '/\s*(?:\.\.\.|…|&#8230;|&hellip;)\s*/u', ' ', $trimmed_line );
			$trimmed_line = preg_replace( '/\[[^\]]*\]/', ' ', $trimmed_line );
			$trimmed_line = preg_replace( '/[ \t]+/', ' ', $trimmed_line );
			$trimmed_line = trim( $trimmed_line );

			if ( preg_match( '/^(#{1,6})\s+(.+)$/', $trimmed_line, $matches ) ) {
				$heading = trim( $matches[2] );
				if ( isset( $seen_headings[ $heading ] ) ) {
					continue;
				}
				$seen_headings[ $heading ] = true;
			}

			$cleaned_lines[] = $trimmed_line;
		}

		$content = implode( "\n", $cleaned_lines );
		$content = preg_replace( '/\n{3,}/', "\n\n", $content );
		$content = trim( $content );

		return $content;
	}

	private static function normalize_output_text( $text ) {
		if ( ! is_string( $text ) ) {
			return '';
		}

		$text = trim( $text );
		if ( $text === '' ) {
			return '';
		}

		$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8' );
		$text = wp_specialchars_decode( $text, ENT_QUOTES );
		$text = preg_replace( '/<[^>]+>/', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );
		if ( $text === '' ) {
			return '';
		}

		$tokens = preg_split( '/\s+/', $text );
		$tokens = array_filter(
			$tokens,
			static function ( $token ) {
				return is_string( $token ) && $token !== '';
			}
		);
		if ( count( $tokens ) >= 4 ) {
			$all_single_letters = true;
			foreach ( $tokens as $token ) {
				if ( strlen( $token ) !== 1 || ! ctype_alpha( $token ) ) {
					$all_single_letters = false;
					break;
				}
			}
			if ( $all_single_letters ) {
				return implode( '', $tokens );
			}
		}

		$text = preg_replace( '/\[(?:read more|more|\.\.\.)\]/i', '', $text );
		$text = preg_replace( '/\b(?:continue reading|read more|learn more)\b/i', '', $text );
		$text = preg_replace( '/\s*(?:\.\.\.|…|&#8230;|&hellip;)\s*/u', ' ', $text );
		$text = preg_replace( '/\[[^\]]*\]/', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		return trim( $text );
	}

	private static function normalize_description_text( $text, $title = '' ) {
		$text = self::clean_generated_content( $text );
		if ( $text === '' ) {
			return '';
		}

		$title = self::normalize_output_text( $title );
		if ( $title !== '' ) {
			$pattern = '/^' . preg_quote( $title, '/' ) . '\s+/iu';
			$text    = preg_replace( $pattern, '', $text, 1 );
		}

		$text = preg_replace( '/https?:\/\/\S+/i', ' ', $text );
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );
		if ( $text === '' ) {
			return '';
		}

		$sentences      = preg_split( '/(?<=[.!?])\s+/', $text );
		$first_sentence = isset( $sentences[0] ) ? trim( $sentences[0] ) : $text;
		$first_sentence = preg_replace( '/\s+/', ' ', $first_sentence );
		$first_sentence = self::clean_generated_content( $first_sentence );
		if ( $first_sentence === '' ) {
			return '';
		}

		$words = preg_split( '/\s+/', $first_sentence );
		$words = array_filter(
			$words,
			static function ( $word ) {
				return is_string( $word ) && $word !== '';
			}
		);
		if ( count( $words ) <= 2 ) {
			return '';
		}

		return self::clean_generated_content( $first_sentence );
	}

	private static function build_fallback_description( $title ) {
		$title = self::normalize_output_text( $title );
		if ( $title !== '' ) {
			/* translators: %s is the page title. */
			return sprintf( __( 'Public page about %s.', 'asneris-seo-toolkit' ), $title );
		}
		return __( 'Public page with useful information.', 'asneris-seo-toolkit' );
	}

	private static function validate_ai_searchability_payload( $content, $detected ) {
		$checks   = array();
		$errors   = array();
		$warnings = array();
		$content  = (string) $content;

		if ( $detected ) {
			$checks[] = array(
				'label'   => __( 'Published File', 'asneris-seo-toolkit' ),
				'status'  => 'pass',
				'message' => __( 'A public llms.txt file is present or has been approved.', 'asneris-seo-toolkit' ),
			);
		} else {
			$checks[] = array(
				'label'   => __( 'Published File', 'asneris-seo-toolkit' ),
				'status'  => 'warning',
				'message' => __( 'The draft has not been published yet.', 'asneris-seo-toolkit' ),
			);
		}

		if ( preg_match( '/^#\s+/', $content ) ) {
			$checks[] = array(
				'label'   => __( 'Markdown Structure', 'asneris-seo-toolkit' ),
				'status'  => 'pass',
				'message' => __( 'The draft has a main title and content sections.', 'asneris-seo-toolkit' ),
			);
		} else {
			$errors[] = __( 'The draft is missing a main title heading.', 'asneris-seo-toolkit' );
			$checks[] = array(
				'label'   => __( 'Markdown Structure', 'asneris-seo-toolkit' ),
				'status'  => 'error',
				'message' => __( 'A main title heading is required before publication.', 'asneris-seo-toolkit' ),
			);
		}

		$public_links = array();
		preg_match_all( '/\[[^\]]+\]\((https?:\/\/[^)]+)\)/i', $content, $markdown_matches );
		foreach ( $markdown_matches[1] as $link ) {
			$public_links[] = rtrim( trim( $link ), '.,;:!' );
		}

		preg_match_all( '/\((https?:\/\/[^)\s]+)\)/i', $content, $parenthetical_matches );
		foreach ( $parenthetical_matches[1] as $link ) {
			$public_links[] = rtrim( trim( $link ), '.,;:!' );
		}

		preg_match_all( '/\bhttps?:\/\/[^\s<>"\')\]]+/i', $content, $bare_matches );
		foreach ( $bare_matches[0] as $link ) {
			$public_links[] = rtrim( trim( $link ), '.,;:!' );
		}

		$public_links = array_values( array_unique( array_filter( $public_links ) ) );
		if ( ! empty( $public_links ) ) {
			$checks[] = array(
				'label'   => __( 'Links', 'asneris-seo-toolkit' ),
				'status'  => 'pass',
				/* translators: %d is the number of public links detected in the draft. */
				'message' => sprintf( __( 'Detected %d public links in the draft.', 'asneris-seo-toolkit' ), count( $public_links ) ),
			);
		} else {
			$warnings[] = __( 'The draft does not contain any public links yet.', 'asneris-seo-toolkit' );
			$checks[]   = array(
				'label'   => __( 'Links', 'asneris-seo-toolkit' ),
				'status'  => 'warning',
				'message' => __( 'Add at least one public link before publishing.', 'asneris-seo-toolkit' ),
			);
		}

		if ( strpos( $content, 'wp-admin' ) !== false || strpos( $content, 'wp-login' ) !== false ) {
			$errors[] = __( 'Admin or login links should not be published.', 'asneris-seo-toolkit' );
		}

		if ( ! empty( $content ) && strlen( $content ) > 20000 ) {
			$warnings[] = __( 'The draft is quite large; consider trimming it for readability.', 'asneris-seo-toolkit' );
		}

		$status = 'warning';
		if ( empty( $errors ) ) {
			$status = $detected && ! empty( $content ) ? 'success' : 'warning';
		} else {
			$status = 'error';
		}

		return array(
			'status'   => $status,
			'checks'   => $checks,
			'warnings' => $warnings,
			'errors'   => $errors,
		);
	}
}
