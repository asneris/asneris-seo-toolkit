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
      echo '<style>.column-asneris_seo_title{width:250px!important}.column-asneris_seo_description{width:300px!important}</style>';
    }
  }

  public static function register_post_meta(): void {
    foreach (self::KEYS as $key => $type) {
      register_post_meta('', $key, [
        'type' => $type,
        'single' => true,
        'show_in_rest' => true,
        'auth_callback' => function () {
          return current_user_can('edit_posts');
        },
        'sanitize_callback' => [__CLASS__, 'sanitize'],
        'default' => self::default_for($key),
      ]);
    }
  }

  public static function default_for($key) {
    if ($key === '_ASNERISSEO_robots_index') return 'index';
    if ($key === '_ASNERISSEO_robots_follow') return 'follow';
    if ($key === '_ASNERISSEO_schema_enabled') return true;
    return '';
  }

  public static function sanitize($value, $key) {
    if (in_array($key, ['_ASNERISSEO_canonical','_ASNERISSEO_og_image'], true)) {
      return esc_url_raw($value);
    }
    if ($key === '_ASNERISSEO_schema_enabled') {
      return (bool)$value;
    }
    return sanitize_text_field($value);
  }

  /**
   * Add custom SEO columns to Pages list
   */
  public static function add_seo_columns($columns) {
    $new_columns = [];
    foreach ($columns as $key => $title) {
      $new_columns[$key] = $title;
      // Add SEO columns after title column
      if ($key === 'title') {
        $new_columns['asneris_seo_title'] = '<span style="display:inline-block;width:250px;">' . __('SEO Title', 'asneris-seo-toolkit') . '</span>';
        $new_columns['asneris_seo_description'] = '<span style="display:inline-block;width:300px;">' . __('SEO Description', 'asneris-seo-toolkit') . '</span>';
      }
    }
    return $new_columns;
  }

  /**
   * Render custom column content.
   */
  public static function render_seo_column( $column, $post_id ) {
    if ( 'asneris_seo_title' === $column ) {
      $seo_title = get_post_meta( $post_id, '_ASNERISSEO_title', true );
      if ( ! empty( $seo_title ) ) {
        echo '<div style="max-width:250px;line-height:1.4em;max-height:2.8em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;word-wrap:break-word;" title="' . esc_attr( $seo_title ) . '">' . esc_html( $seo_title ) . '</div>';
      } else {
        echo '<span style="color:#999;font-style:italic;">' . esc_html__( 'Auto-generated', 'asneris-seo-toolkit' ) . '</span>';
      }
    }

    if ( 'asneris_seo_description' === $column ) {
      $seo_desc = get_post_meta( $post_id, '_ASNERISSEO_description', true );
      if ( ! empty( $seo_desc ) ) {
        echo '<div style="max-width:300px;line-height:1.4em;max-height:2.8em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;word-wrap:break-word;" title="' . esc_attr( $seo_desc ) . '">' . esc_html( $seo_desc ) . '</div>';
      } else {
        echo '<span style="color:#999;font-style:italic;">' . esc_html__( 'Auto-generated', 'asneris-seo-toolkit' ) . '</span>';
      }
    }
  }

  /**
   * Make columns sortable
   */
  public static function make_columns_sortable($columns) {
    return $columns;
  }}