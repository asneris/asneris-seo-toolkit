<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Meta {
  const KEYS = [
    '_ASNERISSEO_title' => 'string',
    '_ASNERISSEO_description' => 'string',
    '_ASNERISSEO_canonical' => 'string',
    '_ASNERISSEO_robots_index' => 'string',
    '_ASNERISSEO_robots_follow' => 'string',
    '_ASNERISSEO_og_title' => 'string',
    '_ASNERISSEO_og_description' => 'string',
    '_ASNERISSEO_og_image' => 'string',
    '_ASNERISSEO_schema_enabled' => 'boolean',
    '_ASNERISSEO_schema_type' => 'string',
  ];

  public static function init() {
    // Add custom columns to Pages list only
    add_filter( 'manage_page_posts_columns', array( __CLASS__, 'add_seo_columns' ) );
    add_action( 'manage_page_posts_custom_column', array( __CLASS__, 'render_seo_column' ), 10, 2 );
    add_filter( 'manage_edit-page_sortable_columns', array( __CLASS__, 'make_columns_sortable' ) );
    
    // Register column widths
    add_action('admin_head', [__CLASS__, 'column_width']);
  }
  
  /**
   * Set column widths via CSS
   */
  public static function column_width() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'edit-page') {
      wp_add_inline_style('asnerisseo-admin', '.column-asneris_seo_info{width:110px!important}');
    }
  }

  /**
   * Post types that expose the SEO meta box.
   * Extend this list if the plugin is used with custom post types.
   */
  const SUPPORTED_POST_TYPES = [ 'post', 'page' ];

  public static function register_post_meta(): void {
    foreach ( self::SUPPORTED_POST_TYPES as $post_type ) {
      foreach ( self::KEYS as $key => $type ) {
        register_post_meta( $post_type, $key, [
          'type'              => $type,
          'single'            => true,
          'show_in_rest'      => true,
          'auth_callback'     => function ( $allowed, $meta_key, $object_id ) {
            return current_user_can( 'edit_post', (int) $object_id );
          },
          'sanitize_callback' => [ __CLASS__, 'sanitize' ],
          'default'           => self::default_for( $key ),
        ] );
      }
    }
  }

  public static function default_for($key) {
    if ($key === '_ASNERISSEO_robots_index') return 'index';
    if ($key === '_ASNERISSEO_robots_follow') return 'follow';
    if ($key === '_ASNERISSEO_schema_enabled') return true;
    return '';
  }

  public static function sanitize($value, $key) {
    // URL fields: canonical and OG image
    if (in_array($key, ['_ASNERISSEO_canonical', '_ASNERISSEO_og_image'], true)) {
      $value = is_string($value) ? $value : '';
      return esc_url_raw($value);
    }
    
    // Boolean field: normalize to integer 1/0 for consistent database storage
    // WordPress stores meta as strings, so 1/0 is more reliable than true/false
    if ($key === '_ASNERISSEO_schema_enabled') {
      return $value ? 1 : 0;
    }
    
    // Robots index: whitelist validation
    if ($key === '_ASNERISSEO_robots_index') {
      $value = is_string($value) ? sanitize_text_field($value) : '';
      return in_array($value, ['index', 'noindex'], true) ? $value : 'index';
    }
    
    // Robots follow: whitelist validation
    if ($key === '_ASNERISSEO_robots_follow') {
      $value = is_string($value) ? sanitize_text_field($value) : '';
      return in_array($value, ['follow', 'nofollow'], true) ? $value : 'follow';
    }
    
    // Schema type: whitelist validation (Schema.org article types)
    if ($key === '_ASNERISSEO_schema_type') {
      $allowed_schema_types = [
        'Article', 'NewsArticle', 'BlogPosting', 'WebPage', 'Product', 
        'Review', 'Event', 'FAQPage', 'HowTo', 'Recipe'
      ];
      $value = is_string($value) ? sanitize_text_field($value) : '';
      return in_array($value, $allowed_schema_types, true) ? $value : 'Article';
    }
    
    // Default: text field sanitization with type check
    return is_string($value) ? sanitize_text_field($value) : '';
  }

  /**
   * Add custom SEO columns to Pages list
   */
  public static function add_seo_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $title) {
      // Skip the old individual columns if somehow present
      if ($key === 'asneris_seo_title' || $key === 'asneris_seo_description') {
        continue;
      }
      $new_columns[$key] = $title;
      // Add single SEO Info column after the date column
      if ($key === 'date') {
        $new_columns['asneris_seo_info'] = __('SEO Info', 'asneris-seo-toolkit');
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
      $seo_desc  = get_post_meta( $post_id, '_ASNERISSEO_description', true );

      $title_updated = ! empty( $seo_title );
      $desc_updated  = ! empty( $seo_desc );

      $blue  = '#2271b1';
      $red   = '#d63638';
      $dot   = 'display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:5px;vertical-align:middle;flex-shrink:0;';

      $title_color = $title_updated ? $blue : $red;
      $desc_color  = $desc_updated ? $blue : $red;

      echo '<div style="display:flex;flex-direction:column;gap:5px;min-width:100px;">';

      // Title row
      echo '<div style="display:flex;align-items:center;font-size:12px;">';
      echo '<span style="' . esc_attr( $dot . 'background:' . $title_color . ';' ) . '" title="' . esc_attr( $title_updated ? __('SEO Title set', 'asneris-seo-toolkit') : __('No SEO Title', 'asneris-seo-toolkit') ) . '"></span>';
      echo '<span style="color:#3c434a;">' . esc_html__( 'Title', 'asneris-seo-toolkit' ) . '</span>';
      echo '</div>';

      // Description row
      echo '<div style="display:flex;align-items:center;font-size:12px;">';
      echo '<span style="' . esc_attr( $dot . 'background:' . $desc_color . ';' ) . '" title="' . esc_attr( $desc_updated ? __('SEO Description set', 'asneris-seo-toolkit') : __('No SEO Description', 'asneris-seo-toolkit') ) . '"></span>';
      echo '<span style="color:#3c434a;">' . esc_html__( 'Description', 'asneris-seo-toolkit' ) . '</span>';
      echo '</div>';

      echo '</div>';
    }
  }

  /**
   * Make columns sortable
   */
  public static function make_columns_sortable($columns) {
    return $columns;
  }}