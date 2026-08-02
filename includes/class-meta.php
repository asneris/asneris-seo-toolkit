<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_Meta {
	const KEYS = array(
		'_ASNERISSEO_title'              => 'string',
		'_ASNERISSEO_description'        => 'string',
		'_asneris_seo_title'             => 'string',
		'_asneris_meta_description'      => 'string',
		'_asneris_generated_title'       => 'string',
		'_asneris_generated_description' => 'string',
		'_asneris_generated_hash'        => 'string',
		'_ASNERISSEO_canonical'          => 'string',
		'_ASNERISSEO_robots_index'       => 'string',
		'_ASNERISSEO_robots_follow'      => 'string',
		'_ASNERISSEO_og_title'           => 'string',
		'_ASNERISSEO_og_description'     => 'string',
		'_ASNERISSEO_og_image'           => 'string',
		'_ASNERISSEO_og_image_disabled'  => 'boolean',
		'_ASNERISSEO_schema_enabled'     => 'boolean',
		'_ASNERISSEO_schema_type'        => 'string',
	);

	public static function init() {
		// Add custom columns to Pages and Posts list
		add_filter( 'manage_page_posts_columns', array( __CLASS__, 'add_seo_columns' ) );
		add_action( 'manage_page_posts_custom_column', array( __CLASS__, 'render_seo_column' ), 10, 2 );
		add_filter( 'manage_edit-page_sortable_columns', array( __CLASS__, 'make_columns_sortable' ) );

		add_filter( 'manage_post_posts_columns', array( __CLASS__, 'add_seo_columns' ) );
		add_action( 'manage_post_posts_custom_column', array( __CLASS__, 'render_seo_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( __CLASS__, 'make_columns_sortable' ) );

		// Register column widths
		add_action( 'admin_head', array( __CLASS__, 'column_width' ) );
	}

	/**
	 * Set column widths via CSS
	 */
	public static function column_width() {
		$screen = get_current_screen();
		if ( $screen && ( $screen->id === 'edit-page' || $screen->id === 'edit-post' ) ) {
			wp_add_inline_style( 'asnerisseo-admin', '.column-asneris_seo_info{width:110px!important}' );
		}
	}

	/**
	 * Post types that expose SEO metadata.
	 * Includes public post types (posts, pages, CPTs) and excludes attachments/nav items.
	 */
	private static function get_supported_post_types() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);

		$excluded = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' );

		return array_values(
			array_filter(
				(array) $post_types,
				static function ( $post_type ) use ( $excluded ) {
					return ! in_array( (string) $post_type, $excluded, true );
				}
			)
		);
	}

	public static function register_post_meta(): void {
		foreach ( self::get_supported_post_types() as $post_type ) {
			foreach ( self::KEYS as $key => $type ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $type,
						'single'            => true,
						'show_in_rest'      => true,
						'auth_callback'     => function ( $allowed, $meta_key, $object_id ) {
							return current_user_can( 'edit_post', (int) $object_id );
						},
						'sanitize_callback' => array( __CLASS__, 'sanitize' ),
						'default'           => self::default_for( $key ),
					)
				);
			}
		}
	}

	public static function default_for( $key ) {
		if ( $key === '_ASNERISSEO_robots_index' ) {
			return 'index';
		}
		if ( $key === '_ASNERISSEO_robots_follow' ) {
			return 'follow';
		}
		if ( $key === '_ASNERISSEO_schema_enabled' ) {
			return true;
		}
		if ( $key === '_ASNERISSEO_og_image_disabled' ) {
			return false;
		}
		return '';
	}

	public static function sanitize( $value, $key ) {
		// URL fields: canonical and OG image - strict validation
		if ( in_array( $key, array( '_ASNERISSEO_canonical', '_ASNERISSEO_og_image' ), true ) ) {
			$value = is_string( $value ) ? trim( $value ) : '';

			// Empty is allowed
			if ( $value === '' ) {
				return '';
			}

			// Sanitize and validate URL format
			// esc_url_raw() already blocks dangerous protocols (javascript:, data:, vbscript:, etc.)
			$sanitized = esc_url_raw( $value, array( 'http', 'https' ) );
			if ( ! filter_var( $sanitized, FILTER_VALIDATE_URL ) ) {
				return '';
			}

			// Parse and validate protocol
			$parsed = wp_parse_url( $sanitized );
			if ( ! isset( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
				return '';
			}

			// Additional validation for OG image: must be an image file
			if ( $key === '_ASNERISSEO_og_image' ) {
				$path               = isset( $parsed['path'] ) ? $parsed['path'] : '';
				$allowed_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico' );
				$extension          = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

				if ( empty( $extension ) || ! in_array( $extension, $allowed_extensions, true ) ) {
					return '';
				}
			}

			return $sanitized;
		}

		// Boolean field: normalize to integer 1/0 for consistent database storage
		// WordPress stores meta as strings, so 1/0 is more reliable than true/false
		if ( $key === '_ASNERISSEO_schema_enabled' ) {
			return $value ? 1 : 0;
		}

		if ( $key === '_ASNERISSEO_og_image_disabled' ) {
			return $value ? 1 : 0;
		}

		// Robots index: strict whitelist validation
		if ( $key === '_ASNERISSEO_robots_index' ) {
			$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
			if ( ! in_array( $value, array( 'index', 'noindex' ), true ) ) {
				// Return default value instead of WP_Error
				return 'index';
			}
			return $value;
		}

		// Robots follow: strict whitelist validation
		if ( $key === '_ASNERISSEO_robots_follow' ) {
			$value = is_string( $value ) ? sanitize_text_field( $value ) : '';
			if ( ! in_array( $value, array( 'follow', 'nofollow' ), true ) ) {
				// Return default value instead of WP_Error
				return 'follow';
			}
			return $value;
		}

		// Schema type: strict whitelist validation (Schema.org article types)
		if ( $key === '_ASNERISSEO_schema_type' ) {
			$allowed_schema_types = array(
				'Article',
				'NewsArticle',
				'BlogPosting',
				'WebPage',
				'Product',
				'Review',
				'Event',
				'FAQPage',
				'HowTo',
				'Recipe',
			);
			$value                = is_string( $value ) ? sanitize_text_field( $value ) : '';
			if ( ! in_array( $value, $allowed_schema_types, true ) ) {
				// Return empty string instead of WP_Error
				return '';
			}
			return $value;
		}

		// Default: text field sanitization
		// sanitize_text_field() already strips ALL HTML tags and dangerous content
		$value = is_string( $value ) ? sanitize_text_field( $value ) : '';

		return $value;
	}

	/**
	 * Add custom SEO columns to Pages list
	 */
	public static function add_seo_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $title ) {
			// Skip the old individual columns if somehow present
			if ( $key === 'asneris_seo_title' || $key === 'asneris_seo_description' ) {
				continue;
			}
			$new_columns[ $key ] = $title;
			// Add single SEO Info column after the date column
			if ( $key === 'date' ) {
				$new_columns['asneris_seo_info'] = __( 'SEO Info', 'asneris-seo-toolkit' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom column content.
	 */
	public static function render_seo_column( $column, $post_id ) {
		if ( 'asneris_seo_info' === $column ) {
			$seo_title = get_post_meta( $post_id, '_ASNERISSEO_title', true );
			if ( '' === (string) $seo_title ) {
				$seo_title = get_post_meta( $post_id, '_asneris_seo_title', true );
			}

			$seo_desc = get_post_meta( $post_id, '_ASNERISSEO_description', true );
			if ( '' === (string) $seo_desc ) {
				$seo_desc = get_post_meta( $post_id, '_asneris_meta_description', true );
			}

			$title_updated = ! empty( $seo_title );
			$desc_updated  = ! empty( $seo_desc );

			$blue = '#2271b1';
			$red  = '#d63638';
			$dot  = 'display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:5px;vertical-align:middle;flex-shrink:0;';

			$title_color = $title_updated ? $blue : $red;
			$desc_color  = $desc_updated ? $blue : $red;

			echo '<div style="display:flex;flex-direction:column;gap:5px;min-width:100px;">';

			// Title row
			echo '<div style="display:flex;align-items:center;font-size:12px;">';
			echo '<span style="' . esc_attr( $dot . 'background:' . $title_color . ';' ) . '" title="' . esc_attr( $title_updated ? __( 'SEO Title set', 'asneris-seo-toolkit' ) : __( 'No SEO Title', 'asneris-seo-toolkit' ) ) . '"></span>';
			echo '<span style="color:#3c434a;">' . esc_html__( 'Title', 'asneris-seo-toolkit' ) . '</span>';
			echo '</div>';

			// Description row
			echo '<div style="display:flex;align-items:center;font-size:12px;">';
			echo '<span style="' . esc_attr( $dot . 'background:' . $desc_color . ';' ) . '" title="' . esc_attr( $desc_updated ? __( 'SEO Description set', 'asneris-seo-toolkit' ) : __( 'No SEO Description', 'asneris-seo-toolkit' ) ) . '"></span>';
			echo '<span style="color:#3c434a;">' . esc_html__( 'Description', 'asneris-seo-toolkit' ) . '</span>';
			echo '</div>';

			echo '</div>';
		}
	}

	/**
	 * Make columns sortable
	 */
	public static function make_columns_sortable( $columns ) {
		return $columns;
	}
}
