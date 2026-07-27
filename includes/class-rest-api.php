<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_REST_API {
  const NAMESPACE = 'asneris-seo/v1';

  public static function register_routes() {
    register_rest_route(
      self::NAMESPACE,
      '/status',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_status' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_site_settings' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/social',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_social_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_social_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/schema',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_schema_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_schema_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/indexnow',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_indexnow_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_indexnow_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/general',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_general_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_general_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/verification',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_verification_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_verification_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/templates',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_templates_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_templates_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-settings/maintenance',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_maintenance_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'run_maintenance_action' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/dashboard-summary',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_dashboard_summary' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/page-diagnostics/overview',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_page_diagnostics_overview' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-diagnostics',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_site_diagnostics' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/site-diagnostics/url-check',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'run_site_diagnostics_url_check' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/redirects',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_redirects_overview' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'add_redirect' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/redirects/clear-auto',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'clear_auto_redirects' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_404_logs' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs/(?P<id>\d+)',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_404_log' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'patch_404_log' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
        [
          'methods'             => WP_REST_Server::DELETABLE,
          'callback'            => [ __CLASS__, 'delete_404_log' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs/bulk',
      [
        [
          'methods'             => WP_REST_Server::CREATABLE,
          'callback'            => [ __CLASS__, 'bulk_404_logs' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs/stats',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_404_logs_stats' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs/analyze',
      [
        [
          'methods'             => WP_REST_Server::CREATABLE,
          'callback'            => [ __CLASS__, 'analyze_404_logs' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs/settings',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_404_logs_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_404_logs_settings' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/404-logs/export',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'export_404_logs' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings_with_nonce' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/redirects/(?P<index>\d+)',
      [
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'update_redirect' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::DELETABLE,
          'callback'            => [ __CLASS__, 'delete_redirect' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/redirects/(?P<index>\d+)/toggle',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'toggle_redirect' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/robots',
      [
        [
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => [ __CLASS__, 'get_robots' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
        [
          'methods'             => WP_REST_Server::EDITABLE,
          'callback'            => [ __CLASS__, 'save_robots' ],
          'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/bulk-edit/content',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_bulk_edit_content' ],
        'permission_callback' => [ __CLASS__, 'can_edit_posts' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/bulk-edit/save',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'save_bulk_edit_content' ],
        'permission_callback' => [ __CLASS__, 'can_edit_posts' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/editor-config',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_editor_config' ],
        'permission_callback' => [ __CLASS__, 'can_access_editor_config' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/post-seo/(?P<id>\d+)',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_post_seo' ],
        'permission_callback' => [ __CLASS__, 'can_edit_post' ],
        'args'                => [
          'id' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/page-diagnostics/run/(?P<id>\d+)',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'run_page_diagnostics_scan' ],
        'permission_callback' => [ __CLASS__, 'can_edit_post' ],
        'args'                => [
          'id' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/page-diagnostics/draft-policy',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'evaluate_draft_policy' ],
        'permission_callback' => [ __CLASS__, 'can_edit_posts' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/page-diagnostics/history/(?P<id>\d+)',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_page_diagnostics_history' ],
        'permission_callback' => [ __CLASS__, 'can_edit_post' ],
        'args'                => [
          'id' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
          'limit' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/page-diagnostics/history/(?P<id>\d+)/delete',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'delete_page_diagnostics_history_record' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        'args'                => [
          'id' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/page-diagnostics/records/clear/(?P<id>\d+)',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'clear_page_diagnostics_records' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        'args'                => [
          'id' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/diagnostics/(?P<id>\d+)',
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [ __CLASS__, 'get_diagnostics' ],
        'permission_callback' => [ __CLASS__, 'can_edit_post' ],
        'args'                => [
          'id' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value > 0;
            },
            'sanitize_callback' => 'absint',
          ],
        ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/diagnostics-url',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'run_diagnostics_url' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
      ]
    );

    register_rest_route(
      self::NAMESPACE,
      '/seo-generator/batch',
      [
        'methods'             => WP_REST_Server::EDITABLE,
        'callback'            => [ __CLASS__, 'run_seo_generator_batch' ],
        'permission_callback' => [ __CLASS__, 'can_manage_settings' ],
        'args'                => [
          'batchSize' => [
            'validate_callback' => static function( $value ) {
              return is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 100;
            },
            'sanitize_callback' => 'absint',
          ],
          'regenerate' => [
            'sanitize_callback' => static function( $value ) {
              return rest_sanitize_boolean( $value );
            },
          ],
          'resetCursor' => [
            'sanitize_callback' => static function( $value ) {
              return rest_sanitize_boolean( $value );
            },
          ],
        ],
      ]
    );
  }

  public static function get_status( WP_REST_Request $request ) {
    unset( $request );

    $batch_failure_log = get_option( ASNERISSEO_SEO_Generator::BATCH_FAILURE_LOG_OPTION, array() );
    if ( ! is_array( $batch_failure_log ) ) {
      $batch_failure_log = array();
    }

    return rest_ensure_response(
      [
        'plugin'        => 'asneris-seo-toolkit',
        'version'       => ASNERISSEO_VERSION,
        'restNamespace' => self::NAMESPACE,
        'seoGenerator'  => [
          'batchCursor' => (int) get_option( ASNERISSEO_SEO_Generator::BATCH_CURSOR_OPTION, 0 ),
          'failureLogSize' => count( $batch_failure_log ),
          'recentFailures' => array_slice( $batch_failure_log, -10 ),
          'lastBatchMetrics' => ASNERISSEO_SEO_Generator::get_last_batch_metrics(),
        ],
      ]
    );
  }

  public static function run_seo_generator_batch( WP_REST_Request $request ) {
    $batch_size = (int) $request->get_param( 'batchSize' );
    if ( $batch_size < 1 ) {
      $batch_size = 50;
    }

    $regenerate = rest_sanitize_boolean( $request->get_param( 'regenerate' ) );
    $reset_cursor = rest_sanitize_boolean( $request->get_param( 'resetCursor' ) );

    $result = ASNERISSEO_SEO_Generator::run_batch(
      $batch_size,
      [
        'regenerate' => $regenerate,
        'resetCursor' => $reset_cursor,
      ]
    );

    return rest_ensure_response(
      [
        'success' => true,
        'message' => $regenerate
          ? __( 'Regeneration batch completed.', 'asneris-seo-toolkit' )
          : __( 'Generation batch completed.', 'asneris-seo-toolkit' ),
        'result'  => $result,
      ]
    );
  }

  public static function get_site_settings( WP_REST_Request $request ) {
    unset( $request );

    return rest_ensure_response(
      [
        'settings' => get_option( ASNERISSEO_Admin_Settings::OPT, [] ),
      ]
    );
  }

  public static function get_dashboard_summary( WP_REST_Request $request ) {
    unset( $request );

    return rest_ensure_response( ASNERISSEO_Dashboard::get_dashboard_summary_payload() );
  }

  public static function get_social_settings( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );

    return rest_ensure_response(
      [
        'default_og_image' => isset( $settings['default_og_image'] ) ? esc_url_raw( $settings['default_og_image'] ) : '',
        'twitter_username' => isset( $settings['twitter_username'] ) ? sanitize_text_field( $settings['twitter_username'] ) : '',
        'facebook_app_id'  => isset( $settings['facebook_app_id'] ) ? sanitize_text_field( $settings['facebook_app_id'] ) : '',
        'theme_color'      => isset( $settings['theme_color'] ) ? sanitize_text_field( $settings['theme_color'] ) : '',
      ]
    );
  }

  public static function update_social_settings( WP_REST_Request $request ) {
    $existing = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $incoming = $request->get_json_params();

    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $updated = array_merge(
      $existing,
      [
        'default_og_image' => isset( $incoming['default_og_image'] ) ? $incoming['default_og_image'] : ( $existing['default_og_image'] ?? '' ),
        'twitter_username' => isset( $incoming['twitter_username'] ) ? $incoming['twitter_username'] : ( $existing['twitter_username'] ?? '' ),
        'facebook_app_id'  => isset( $incoming['facebook_app_id'] ) ? $incoming['facebook_app_id'] : ( $existing['facebook_app_id'] ?? '' ),
        'theme_color'      => isset( $incoming['theme_color'] ) ? $incoming['theme_color'] : ( $existing['theme_color'] ?? '' ),
      ]
    );

    $clean = ASNERISSEO_Admin_Settings::sanitize( $updated );
    $validation_errors = get_transient( 'asneris_settings_validation_errors' );

    if ( ! empty( $validation_errors ) ) {
      delete_transient( 'asneris_settings_validation_errors' );
      return new WP_Error(
        'asnerisseo_social_validation_failed',
        __( 'Social settings validation failed.', 'asneris-seo-toolkit' ),
        [
          'status' => 400,
          'errors' => $validation_errors,
        ]
      );
    }

    update_option( ASNERISSEO_Admin_Settings::OPT, $clean );

    return rest_ensure_response(
      [
        'success'  => true,
        'settings' => [
          'default_og_image' => isset( $clean['default_og_image'] ) ? esc_url_raw( $clean['default_og_image'] ) : '',
          'twitter_username' => isset( $clean['twitter_username'] ) ? sanitize_text_field( $clean['twitter_username'] ) : '',
          'facebook_app_id'  => isset( $clean['facebook_app_id'] ) ? sanitize_text_field( $clean['facebook_app_id'] ) : '',
          'theme_color'      => isset( $clean['theme_color'] ) ? sanitize_text_field( $clean['theme_color'] ) : '',
        ],
      ]
    );
  }

  public static function get_schema_settings( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $business_type = isset( $settings['business_type'] ) ? sanitize_text_field( $settings['business_type'] ) : 'LocalBusiness';
    if ( '' === $business_type ) {
      $business_type = 'LocalBusiness';
    }

    $business_hours = '';
    if ( isset( $settings['business_hours'] ) ) {
      $business_hours = ASNERISSEO_Admin_Settings::format_business_hours_for_textarea( $settings['business_hours'] );
    }

    return rest_ensure_response(
      [
        'enable_breadcrumbs'    => ! empty( $settings['enable_breadcrumbs'] ),
        'enable_local_business' => ! empty( $settings['enable_local_business'] ),
        'business_type'         => $business_type,
        'business_phone'        => isset( $settings['business_phone'] ) ? sanitize_text_field( $settings['business_phone'] ) : '',
        'business_address'      => isset( $settings['business_address'] ) ? sanitize_textarea_field( $settings['business_address'] ) : '',
        'business_hours'        => sanitize_textarea_field( $business_hours ),
        'service_area'          => isset( $settings['service_area'] ) ? sanitize_textarea_field( $settings['service_area'] ) : '',
        'price_range'           => isset( $settings['price_range'] ) ? sanitize_text_field( $settings['price_range'] ) : '',
        'payment_methods'       => isset( $settings['payment_methods'] ) ? sanitize_text_field( $settings['payment_methods'] ) : '',
        'languages_spoken'      => isset( $settings['languages_spoken'] ) ? sanitize_text_field( $settings['languages_spoken'] ) : '',
      ]
    );
  }

  public static function update_schema_settings( WP_REST_Request $request ) {
    $existing = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $incoming = $request->get_json_params();

    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $updated = array_merge(
      $existing,
      [
        'enable_breadcrumbs'    => ! empty( $incoming['enable_breadcrumbs'] ) ? 1 : 0,
        'enable_local_business' => ! empty( $incoming['enable_local_business'] ) ? 1 : 0,
        'business_type'         => isset( $incoming['business_type'] ) ? $incoming['business_type'] : ( $existing['business_type'] ?? 'LocalBusiness' ),
        'business_phone'        => isset( $incoming['business_phone'] ) ? $incoming['business_phone'] : ( $existing['business_phone'] ?? '' ),
        'business_address'      => isset( $incoming['business_address'] ) ? $incoming['business_address'] : ( $existing['business_address'] ?? '' ),
        'business_hours'        => isset( $incoming['business_hours'] ) ? $incoming['business_hours'] : ( $existing['business_hours'] ?? '' ),
        'service_area'          => isset( $incoming['service_area'] ) ? $incoming['service_area'] : ( $existing['service_area'] ?? '' ),
        'price_range'           => isset( $incoming['price_range'] ) ? $incoming['price_range'] : ( $existing['price_range'] ?? '' ),
        'payment_methods'       => isset( $incoming['payment_methods'] ) ? $incoming['payment_methods'] : ( $existing['payment_methods'] ?? '' ),
        'languages_spoken'      => isset( $incoming['languages_spoken'] ) ? $incoming['languages_spoken'] : ( $existing['languages_spoken'] ?? '' ),
      ]
    );

    $clean = ASNERISSEO_Admin_Settings::sanitize( $updated );
    $validation_errors = get_transient( 'asneris_settings_validation_errors' );

    if ( ! empty( $validation_errors ) ) {
      delete_transient( 'asneris_settings_validation_errors' );
      return new WP_Error(
        'asnerisseo_schema_validation_failed',
        __( 'Schema settings validation failed.', 'asneris-seo-toolkit' ),
        [
          'status' => 400,
          'errors' => $validation_errors,
        ]
      );
    }

    update_option( ASNERISSEO_Admin_Settings::OPT, $clean );

    $clean_business_type = isset( $clean['business_type'] ) ? sanitize_text_field( $clean['business_type'] ) : 'LocalBusiness';
    if ( '' === $clean_business_type ) {
      $clean_business_type = 'LocalBusiness';
    }

    $clean_business_hours = ASNERISSEO_Admin_Settings::format_business_hours_for_textarea( $clean['business_hours'] ?? '' );

    return rest_ensure_response(
      [
        'success'  => true,
        'settings' => [
          'enable_breadcrumbs'    => ! empty( $clean['enable_breadcrumbs'] ),
          'enable_local_business' => ! empty( $clean['enable_local_business'] ),
          'business_type'         => $clean_business_type,
          'business_phone'        => isset( $clean['business_phone'] ) ? sanitize_text_field( $clean['business_phone'] ) : '',
          'business_address'      => isset( $clean['business_address'] ) ? sanitize_textarea_field( $clean['business_address'] ) : '',
          'business_hours'        => sanitize_textarea_field( $clean_business_hours ),
          'service_area'          => isset( $clean['service_area'] ) ? sanitize_textarea_field( $clean['service_area'] ) : '',
          'price_range'           => isset( $clean['price_range'] ) ? sanitize_text_field( $clean['price_range'] ) : '',
          'payment_methods'       => isset( $clean['payment_methods'] ) ? sanitize_text_field( $clean['payment_methods'] ) : '',
          'languages_spoken'      => isset( $clean['languages_spoken'] ) ? sanitize_text_field( $clean['languages_spoken'] ) : '',
        ],
      ]
    );
  }

  public static function get_indexnow_settings( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $key_mode = isset( $settings['indexnow_key_mode'] ) ? sanitize_key( $settings['indexnow_key_mode'] ) : 'auto';
    if ( ! in_array( $key_mode, [ 'auto', 'custom' ], true ) ) {
      $key_mode = 'auto';
    }
    $indexnow_key = isset( $settings['indexnow_key'] ) ? sanitize_text_field( $settings['indexnow_key'] ) : '';
    $indexnow_key_url = $indexnow_key ? esc_url_raw( home_url( '/' . $indexnow_key . '.txt' ) ) : '';

    return rest_ensure_response(
      [
        'indexnow_enabled'  => ! empty( $settings['indexnow_enabled'] ),
        'indexnow_key_mode' => $key_mode,
        'indexnow_key'      => $indexnow_key,
        'indexnow_key_url'  => $indexnow_key_url,
      ]
    );
  }

  public static function get_general_settings( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $wp_cron_enabled = self::is_wp_cron_enabled();
    $snapshot_tables = ASNERISSEO_Page_Diagnostics_Snapshots::get_tables_status();
    $page_diag_cron = ASNERISSEO_Page_Diagnostics_Snapshots::get_scan_cron_details();
    $priority_page_ids = isset( $settings['priority_page_ids'] ) && is_array( $settings['priority_page_ids'] )
      ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $settings['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
      : [];

    return rest_ensure_response(
      [
        'org_name' => isset( $settings['org_name'] ) ? sanitize_text_field( $settings['org_name'] ) : get_bloginfo( 'name' ),
        'org_logo' => isset( $settings['org_logo'] ) ? esc_url_raw( $settings['org_logo'] ) : '',
        'default_robots_index' => isset( $settings['default_robots_index'] ) ? sanitize_text_field( $settings['default_robots_index'] ) : 'index',
        'default_robots_follow' => isset( $settings['default_robots_follow'] ) ? sanitize_text_field( $settings['default_robots_follow'] ) : 'follow',
        'page_diagnostics_priority_enabled' => ! empty( $settings['page_diagnostics_priority_enabled'] ),
        'page_diagnostics_scan_cron_frequency' => $wp_cron_enabled
          ? ( isset( $settings['page_diagnostics_scan_cron_frequency'] )
            ? sanitize_key( (string) $settings['page_diagnostics_scan_cron_frequency'] )
            : 'disabled' )
          : 'disabled',
        'page_diagnostics_scan_cron_status' => (string) ( $page_diag_cron['status'] ?? 'not_scheduled' ),
        'page_diagnostics_scan_next_run_gmt' => (string) ( $page_diag_cron['next_run_gmt'] ?? '' ),
        'wp_cron_enabled' => $wp_cron_enabled,
        'system_cron_status' => $wp_cron_enabled
          ? __( 'WP-Cron Enabled', 'asneris-seo-toolkit' )
          : __( 'WP-Cron Disabled', 'asneris-seo-toolkit' ),
        'wp_cron_note' => $wp_cron_enabled
          ? ''
          : __( 'WP-Cron is disabled in this environment. Automatic scans are disabled by default. Run scans manually whenever needed, or enable WP-Cron / set up a system cron for scheduled scans.', 'asneris-seo-toolkit' ),
        'snapshot_tables' => $snapshot_tables,
        'priority_page_ids' => $priority_page_ids,
      ]
    );
  }

  public static function update_general_settings( WP_REST_Request $request ) {
    $existing = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $wp_cron_enabled = self::is_wp_cron_enabled();
    $incoming = $request->get_json_params();

    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $existing_priority_ids = isset( $existing['priority_page_ids'] ) && is_array( $existing['priority_page_ids'] )
      ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $existing['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
      : [];

    $updated = array_merge(
      $existing,
      [
        'org_name' => isset( $incoming['org_name'] ) ? $incoming['org_name'] : ( $existing['org_name'] ?? get_bloginfo( 'name' ) ),
        'org_logo' => isset( $incoming['org_logo'] ) ? $incoming['org_logo'] : ( $existing['org_logo'] ?? '' ),
        'default_robots_index' => isset( $incoming['default_robots_index'] ) ? $incoming['default_robots_index'] : ( $existing['default_robots_index'] ?? 'index' ),
        'default_robots_follow' => isset( $incoming['default_robots_follow'] ) ? $incoming['default_robots_follow'] : ( $existing['default_robots_follow'] ?? 'follow' ),
        'page_diagnostics_priority_enabled' => isset( $incoming['page_diagnostics_priority_enabled'] )
          ? ( ! empty( $incoming['page_diagnostics_priority_enabled'] ) ? 1 : 0 )
          : ( ! empty( $existing['page_diagnostics_priority_enabled'] ) ? 1 : 0 ),
        'page_diagnostics_scan_cron_frequency' => isset( $incoming['page_diagnostics_scan_cron_frequency'] )
          ? sanitize_key( (string) $incoming['page_diagnostics_scan_cron_frequency'] )
          : ( isset( $existing['page_diagnostics_scan_cron_frequency'] ) ? sanitize_key( (string) $existing['page_diagnostics_scan_cron_frequency'] ) : 'disabled' ),
        'priority_page_ids' => isset( $incoming['priority_page_ids'] ) && is_array( $incoming['priority_page_ids'] )
          ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $incoming['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
          : ( isset( $existing['priority_page_ids'] ) && is_array( $existing['priority_page_ids'] )
            ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $existing['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
            : [] ),
      ]
    );

    $clean = self::save_settings_with_validation( $updated, 'asnerisseo_general_validation_failed', __( 'General settings validation failed.', 'asneris-seo-toolkit' ) );
    if ( is_wp_error( $clean ) ) {
      return $clean;
    }

    if ( ! $wp_cron_enabled ) {
      $clean['page_diagnostics_scan_cron_frequency'] = 'disabled';
      update_option( ASNERISSEO_Admin_Settings::OPT, $clean );
    }

    ASNERISSEO_Page_Diagnostics_Snapshots::refresh_scan_cron_schedule();
    $page_diag_cron = ASNERISSEO_Page_Diagnostics_Snapshots::get_scan_cron_details();

    $clean_priority_ids = isset( $clean['priority_page_ids'] ) && is_array( $clean['priority_page_ids'] )
      ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $clean['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
      : [];
    $removed_priority_ids = array_values( array_diff( $existing_priority_ids, $clean_priority_ids ) );
    $removed_cleanup_count = 0;
    foreach ( $removed_priority_ids as $removed_id ) {
      $cleanup_result = ASNERISSEO_Page_Diagnostics_Snapshots::delete_page_records( (int) $removed_id );
      $removed_cleanup_count += (int) ( $cleanup_result['latestDeleted'] ?? 0 ) + (int) ( $cleanup_result['historyDeleted'] ?? 0 );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'cleanup' => [
          'removedPriorityIds' => $removed_priority_ids,
          'deletedRows' => $removed_cleanup_count,
        ],
        'settings' => [
          'org_name' => isset( $clean['org_name'] ) ? sanitize_text_field( $clean['org_name'] ) : get_bloginfo( 'name' ),
          'org_logo' => isset( $clean['org_logo'] ) ? esc_url_raw( $clean['org_logo'] ) : '',
          'default_robots_index' => isset( $clean['default_robots_index'] ) ? sanitize_text_field( $clean['default_robots_index'] ) : 'index',
          'default_robots_follow' => isset( $clean['default_robots_follow'] ) ? sanitize_text_field( $clean['default_robots_follow'] ) : 'follow',
          'page_diagnostics_priority_enabled' => ! empty( $clean['page_diagnostics_priority_enabled'] ),
          'page_diagnostics_scan_cron_frequency' => $wp_cron_enabled
            ? ( isset( $clean['page_diagnostics_scan_cron_frequency'] )
              ? sanitize_key( (string) $clean['page_diagnostics_scan_cron_frequency'] )
              : 'disabled' )
            : 'disabled',
          'page_diagnostics_scan_cron_status' => (string) ( $page_diag_cron['status'] ?? 'not_scheduled' ),
          'page_diagnostics_scan_next_run_gmt' => (string) ( $page_diag_cron['next_run_gmt'] ?? '' ),
          'wp_cron_enabled' => $wp_cron_enabled,
          'system_cron_status' => $wp_cron_enabled
            ? __( 'WP-Cron Enabled', 'asneris-seo-toolkit' )
            : __( 'WP-Cron Disabled', 'asneris-seo-toolkit' ),
          'wp_cron_note' => $wp_cron_enabled
            ? ''
            : __( 'WP-Cron is disabled in this environment. Automatic scans are disabled by default. Run scans manually whenever needed, or enable WP-Cron / set up a system cron for scheduled scans.', 'asneris-seo-toolkit' ),
          'snapshot_tables' => ASNERISSEO_Page_Diagnostics_Snapshots::get_tables_status(),
          'priority_page_ids' => isset( $clean['priority_page_ids'] ) && is_array( $clean['priority_page_ids'] )
            ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $clean['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
            : [],
        ],
      ]
    );
  }

  private static function is_wp_cron_enabled() {
    if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
      return false;
    }

    return true;
  }

  public static function get_verification_settings( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );

    return rest_ensure_response(
      [
        'google_verification' => isset( $settings['google_verification'] ) ? sanitize_text_field( $settings['google_verification'] ) : '',
        'bing_verification' => isset( $settings['bing_verification'] ) ? sanitize_text_field( $settings['bing_verification'] ) : '',
        'yandex_verification' => isset( $settings['yandex_verification'] ) ? sanitize_text_field( $settings['yandex_verification'] ) : '',
      ]
    );
  }

  public static function update_verification_settings( WP_REST_Request $request ) {
    $existing = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $incoming = $request->get_json_params();

    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $updated = array_merge(
      $existing,
      [
        'google_verification' => isset( $incoming['google_verification'] ) ? $incoming['google_verification'] : ( $existing['google_verification'] ?? '' ),
        'bing_verification' => isset( $incoming['bing_verification'] ) ? $incoming['bing_verification'] : ( $existing['bing_verification'] ?? '' ),
        'yandex_verification' => isset( $incoming['yandex_verification'] ) ? $incoming['yandex_verification'] : ( $existing['yandex_verification'] ?? '' ),
      ]
    );

    $clean = self::save_settings_with_validation( $updated, 'asnerisseo_verification_validation_failed', __( 'Verification settings validation failed.', 'asneris-seo-toolkit' ) );
    if ( is_wp_error( $clean ) ) {
      return $clean;
    }

    return rest_ensure_response(
      [
        'success' => true,
        'settings' => [
          'google_verification' => isset( $clean['google_verification'] ) ? sanitize_text_field( $clean['google_verification'] ) : '',
          'bing_verification' => isset( $clean['bing_verification'] ) ? sanitize_text_field( $clean['bing_verification'] ) : '',
          'yandex_verification' => isset( $clean['yandex_verification'] ) ? sanitize_text_field( $clean['yandex_verification'] ) : '',
        ],
      ]
    );
  }

  public static function get_templates_settings( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $post_types = get_post_types( [ 'public' => true ], 'objects' );

    $title_templates = isset( $settings['title_templates'] ) && is_array( $settings['title_templates'] ) ? $settings['title_templates'] : [];
    $description_templates = isset( $settings['description_templates'] ) && is_array( $settings['description_templates'] ) ? $settings['description_templates'] : [];

    return rest_ensure_response(
      [
        'title_separator' => isset( $settings['title_separator'] ) ? sanitize_text_field( $settings['title_separator'] ) : '|',
        'title_templates' => $title_templates,
        'description_templates' => $description_templates,
        'post_types' => array_map(
          static function( $pt ) {
            return [
              'value' => $pt->name,
              'label' => $pt->labels->singular_name,
            ];
          },
          array_values( $post_types )
        ),
      ]
    );
  }

  public static function update_templates_settings( WP_REST_Request $request ) {
    $existing = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $incoming = $request->get_json_params();

    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $updated = array_merge(
      $existing,
      [
        'title_separator' => isset( $incoming['title_separator'] ) ? $incoming['title_separator'] : ( $existing['title_separator'] ?? '|' ),
        'title_templates' => isset( $incoming['title_templates'] ) && is_array( $incoming['title_templates'] ) ? $incoming['title_templates'] : ( $existing['title_templates'] ?? [] ),
        'description_templates' => isset( $incoming['description_templates'] ) && is_array( $incoming['description_templates'] ) ? $incoming['description_templates'] : ( $existing['description_templates'] ?? [] ),
      ]
    );

    $clean = self::save_settings_with_validation( $updated, 'asnerisseo_templates_validation_failed', __( 'Template settings validation failed.', 'asneris-seo-toolkit' ) );
    if ( is_wp_error( $clean ) ) {
      return $clean;
    }

    return rest_ensure_response(
      [
        'success' => true,
        'settings' => [
          'title_separator' => isset( $clean['title_separator'] ) ? sanitize_text_field( $clean['title_separator'] ) : '|',
          'title_templates' => isset( $clean['title_templates'] ) && is_array( $clean['title_templates'] ) ? $clean['title_templates'] : [],
          'description_templates' => isset( $clean['description_templates'] ) && is_array( $clean['description_templates'] ) ? $clean['description_templates'] : [],
        ],
      ]
    );
  }

  public static function get_maintenance_settings( WP_REST_Request $request ) {
    unset( $request );

    return rest_ensure_response(
      [
        'version' => ASNERISSEO_VERSION,
      ]
    );
  }

  public static function run_maintenance_action( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $action = isset( $incoming['action'] ) ? sanitize_key( $incoming['action'] ) : '';

    if ( 'export' === $action ) {
      return rest_ensure_response(
        [
          'success' => true,
          'settings' => get_option( ASNERISSEO_Admin_Settings::OPT, [] ),
        ]
      );
    }

    if ( 'import' === $action ) {
      $settings = isset( $incoming['settings'] ) && is_array( $incoming['settings'] ) ? $incoming['settings'] : [];
      if ( empty( $settings ) ) {
        return new WP_Error(
          'asnerisseo_maintenance_import_missing',
          __( 'No settings payload provided for import.', 'asneris-seo-toolkit' ),
          [ 'status' => 400 ]
        );
      }

      $clean = self::save_settings_with_validation( $settings, 'asnerisseo_maintenance_import_failed', __( 'Import validation failed.', 'asneris-seo-toolkit' ) );
      if ( is_wp_error( $clean ) ) {
        return $clean;
      }

      return rest_ensure_response(
        [
          'success' => true,
          'message' => __( 'Settings imported successfully.', 'asneris-seo-toolkit' ),
        ]
      );
    }

    if ( 'reset' === $action ) {
      delete_option( ASNERISSEO_Admin_Settings::OPT );
      return rest_ensure_response(
        [
          'success' => true,
          'message' => __( 'Settings reset successfully.', 'asneris-seo-toolkit' ),
        ]
      );
    }

    return new WP_Error(
      'asnerisseo_maintenance_invalid_action',
      __( 'Invalid maintenance action.', 'asneris-seo-toolkit' ),
      [ 'status' => 400 ]
    );
  }

  public static function update_indexnow_settings( WP_REST_Request $request ) {
    $existing = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $incoming = $request->get_json_params();

    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $previous_mode = isset( $existing['indexnow_key_mode'] ) ? sanitize_key( $existing['indexnow_key_mode'] ) : 'auto';
    if ( ! in_array( $previous_mode, [ 'auto', 'custom' ], true ) ) {
      $previous_mode = 'auto';
    }

    $updated = array_merge(
      $existing,
      [
        'indexnow_enabled'  => ! empty( $incoming['indexnow_enabled'] ) ? 1 : 0,
        'indexnow_key_mode' => isset( $incoming['indexnow_key_mode'] ) ? $incoming['indexnow_key_mode'] : ( $existing['indexnow_key_mode'] ?? 'auto' ),
        'indexnow_key'      => isset( $incoming['indexnow_key'] ) ? $incoming['indexnow_key'] : ( $existing['indexnow_key'] ?? '' ),
      ]
    );

    $clean = ASNERISSEO_Admin_Settings::sanitize( $updated );
    $validation_errors = get_transient( 'asneris_settings_validation_errors' );

    if ( ! empty( $validation_errors ) ) {
      delete_transient( 'asneris_settings_validation_errors' );
      return new WP_Error(
        'asnerisseo_indexnow_validation_failed',
        __( 'IndexNow settings validation failed.', 'asneris-seo-toolkit' ),
        [
          'status' => 400,
          'errors' => $validation_errors,
        ]
      );
    }

    update_option( ASNERISSEO_Admin_Settings::OPT, $clean );

    $response_mode = isset( $clean['indexnow_key_mode'] ) ? sanitize_key( $clean['indexnow_key_mode'] ) : 'auto';
    if ( ! in_array( $response_mode, [ 'auto', 'custom' ], true ) ) {
      $response_mode = 'auto';
    }

    $response_key = isset( $clean['indexnow_key'] ) ? sanitize_text_field( $clean['indexnow_key'] ) : '';
    $response_key_url = $response_key ? esc_url_raw( home_url( '/' . $response_key . '.txt' ) ) : '';
    $mode_changed = $previous_mode !== $response_mode;

    $key_management_notice = '';
    if ( $mode_changed && 'auto' === $response_mode ) {
      $key_management_notice = __( 'Switched to auto mode. Key management is now handled by the plugin.', 'asneris-seo-toolkit' );
    } elseif ( $mode_changed && 'custom' === $response_mode ) {
      $key_management_notice = __( 'Switched to custom mode. Please ensure your manual key is valid before saving.', 'asneris-seo-toolkit' );
    }

    return rest_ensure_response(
      [
        'success'              => true,
        'mode_changed'         => $mode_changed,
        'key_management_notice'=> $key_management_notice,
        'settings'             => [
          'indexnow_enabled'  => ! empty( $clean['indexnow_enabled'] ),
          'indexnow_key_mode' => $response_mode,
          'indexnow_key'      => $response_key,
          'indexnow_key_url'  => $response_key_url,
        ],
      ]
    );
  }

  public static function get_page_diagnostics_overview( WP_REST_Request $request ) {
    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );

    $post_type_filter = sanitize_key( (string) $request->get_param( 'postType' ) );
    if ( ! in_array( $post_type_filter, [ 'all', 'post', 'page' ], true ) ) {
      $post_type_filter = 'all';
    }

    $post_status_filter = 'publish';

    $search_query = sanitize_text_field( (string) $request->get_param( 'search' ) );
    $scope = sanitize_key( (string) $request->get_param( 'scope' ) );
    if ( ! in_array( $scope, [ 'all', 'priority', 'non_priority' ], true ) ) {
      $scope = 'all';
    }
    $per_page = absint( $request->get_param( 'perPage' ) );
    if ( $per_page < 1 ) {
      $per_page = 100;
    }
    $per_page = min( $per_page, 200 );
    $target_post_id = absint( $request->get_param( 'postId' ) );
    $page_number = absint( $request->get_param( 'page' ) );
    if ( $page_number < 1 ) {
      $page_number = 1;
    }

    $target_post_types = 'all' === $post_type_filter ? [ 'post', 'page' ] : [ $post_type_filter ];
    $target_post_statuses = [ 'publish' ];

    $query_args = [
      'post_type'              => $target_post_types,
      'post_status'            => $target_post_statuses,
      'posts_per_page'         => $per_page,
      'paged'                  => $page_number,
      'orderby'                => 'modified',
      'order'                  => 'DESC',
      'suppress_filters'       => false,
      'ignore_sticky_posts'    => true,
      'no_found_rows'          => false,
      'update_post_meta_cache' => false,
      'update_post_term_cache' => false,
      's'                      => $search_query,
    ];

    if ( $target_post_id > 0 ) {
      $query_args['p'] = $target_post_id;
      $query_args['posts_per_page'] = 1;
      $query_args['paged'] = 1;
    }

    $configured_priority_ids = isset( $settings['priority_page_ids'] ) && is_array( $settings['priority_page_ids'] )
      ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $settings['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
      : [];
    $priority_feature_enabled = ! empty( $settings['page_diagnostics_priority_enabled'] );

    if ( 'priority' === $scope ) {
      if ( empty( $configured_priority_ids ) ) {
        return rest_ensure_response(
          [
            'total' => 0,
            'priorityItems' => [],
            'items' => [],
            'pagination' => [
              'page' => 1,
              'perPage' => $per_page,
              'totalPages' => 1,
              'hasPrev' => false,
              'hasNext' => false,
            ],
            'filters' => [
              'postType' => $post_type_filter,
              'postStatus' => $post_status_filter,
              'search' => $search_query,
              'perPage' => $per_page,
              'scope' => $scope,
              'postTypes' => [
                [ 'value' => 'all', 'label' => __( 'All (Pages + Posts)', 'asneris-seo-toolkit' ) ],
                [ 'value' => 'page', 'label' => __( 'Pages', 'asneris-seo-toolkit' ) ],
                [ 'value' => 'post', 'label' => __( 'Posts', 'asneris-seo-toolkit' ) ],
              ],
              'postStatuses' => [
                [ 'value' => 'publish', 'label' => __( 'Published', 'asneris-seo-toolkit' ) ],
              ],
            ],
            'priorityFeatureEnabled' => $priority_feature_enabled,
          ]
        );
      }

      $query_args['post__in'] = $configured_priority_ids;
    } elseif ( 'non_priority' === $scope && ! empty( $configured_priority_ids ) ) {
      // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Bounded exclusion list (max 50) from plugin settings.
      $query_args['post__not_in'] = $configured_priority_ids;
    }

    $recent_posts_query = new WP_Query( $query_args );
    $recent_posts = $recent_posts_query->posts;

    if ( 'priority' === $scope && ! empty( $configured_priority_ids ) ) {
      $priority_position = array_flip( $configured_priority_ids );
      usort(
        $recent_posts,
        static function( $left, $right ) use ( $priority_position ) {
          $left_pos = isset( $priority_position[ (int) $left->ID ] ) ? (int) $priority_position[ (int) $left->ID ] : PHP_INT_MAX;
          $right_pos = isset( $priority_position[ (int) $right->ID ] ) ? (int) $priority_position[ (int) $right->ID ] : PHP_INT_MAX;
          return $left_pos <=> $right_pos;
        }
      );
    }

    $total_items = (int) $recent_posts_query->found_posts;
    $total_pages = max( 1, (int) $recent_posts_query->max_num_pages );

    $site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
    $items = [];
    foreach ( $recent_posts as $post ) {
      $items[] = self::attach_unified_overview_item(
        self::build_page_diagnostics_overview_item( $post, $site_host ),
        'overview'
      );
    }

    if ( ! empty( $configured_priority_ids ) && 'non_priority' !== $scope ) {
      $priority_query_args = $query_args;
      $priority_query_args['post__in'] = $configured_priority_ids;
      $priority_query_args['posts_per_page'] = ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES;
      $priority_query_args['paged'] = 1;
      $priority_query_args['no_found_rows'] = true;

      $priority_query = new WP_Query( $priority_query_args );
      $priority_posts = $priority_query->posts;
      $priority_position = array_flip( $configured_priority_ids );
      usort(
        $priority_posts,
        static function( $left, $right ) use ( $priority_position ) {
          $left_pos = isset( $priority_position[ (int) $left->ID ] ) ? (int) $priority_position[ (int) $left->ID ] : PHP_INT_MAX;
          $right_pos = isset( $priority_position[ (int) $right->ID ] ) ? (int) $priority_position[ (int) $right->ID ] : PHP_INT_MAX;
          return $left_pos <=> $right_pos;
        }
      );
      $priority_items = [];
      foreach ( $priority_posts as $post ) {
        $priority_items[] = self::attach_unified_overview_item(
          self::build_page_diagnostics_overview_item( $post, $site_host ),
          'priority_overview'
        );
      }
      wp_reset_postdata();
    } elseif ( 'all' === $scope ) {
      $priority_query_args = $query_args;
      $priority_query_args['posts_per_page'] = 200;
      $priority_query_args['paged'] = 1;
      $priority_query_args['no_found_rows'] = true;

      $priority_query = new WP_Query( $priority_query_args );
      $priority_candidates = [];
      foreach ( $priority_query->posts as $post ) {
        $priority_item = self::attach_unified_overview_item(
          self::build_page_diagnostics_overview_item( $post, $site_host ),
          'priority_candidate'
        );
        $priority_item['priorityScore'] = self::calculate_priority_page_score( $priority_item );
        $priority_candidates[] = $priority_item;
      }
      wp_reset_postdata();

      usort(
        $priority_candidates,
        static function( $left, $right ) {
          $left_score = isset( $left['priorityScore'] ) ? (int) $left['priorityScore'] : 0;
          $right_score = isset( $right['priorityScore'] ) ? (int) $right['priorityScore'] : 0;
          if ( $left_score === $right_score ) {
            $left_seo = isset( $left['seoScore'] ) ? (int) $left['seoScore'] : 100;
            $right_seo = isset( $right['seoScore'] ) ? (int) $right['seoScore'] : 100;
            return $left_seo <=> $right_seo;
          }

          return $right_score <=> $left_score;
        }
      );

      $priority_items = array_slice(
        array_values(
          array_filter(
            $priority_candidates,
            static function( $candidate ) {
              return isset( $candidate['priorityScore'] ) && (int) $candidate['priorityScore'] > 0;
            }
          )
        ),
        0,
        ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES
      );
    } else {
      $priority_items = [];
    }

    $items_contract = self::validate_unified_collection_contract( $items, 'overview.items' );
    if ( is_wp_error( $items_contract ) ) {
      return $items_contract;
    }

    $priority_contract = self::validate_unified_collection_contract( $priority_items, 'overview.priorityItems' );
    if ( is_wp_error( $priority_contract ) ) {
      return $priority_contract;
    }

    $post_status_options = [
      [ 'value' => 'publish', 'label' => __( 'Published', 'asneris-seo-toolkit' ) ],
    ];


    return rest_ensure_response(
      [
        'total' => $total_items,
        'priorityItems' => array_map(
          static function( $item ) {
            if ( isset( $item['priorityScore'] ) ) {
              unset( $item['priorityScore'] );
            }
            return $item;
          },
          $priority_items
        ),
        'items' => $items,
        'pagination' => [
          'page' => $page_number,
          'perPage' => $per_page,
          'totalPages' => $total_pages,
          'hasPrev' => $page_number > 1,
          'hasNext' => $page_number < $total_pages,
        ],
        'filters' => [
          'postType' => $post_type_filter,
          'postStatus' => $post_status_filter,
          'search' => $search_query,
          'perPage' => $per_page,
          'scope' => $scope,
          'postTypes' => [
            [ 'value' => 'all', 'label' => __( 'All (Pages + Posts)', 'asneris-seo-toolkit' ) ],
            [ 'value' => 'page', 'label' => __( 'Pages', 'asneris-seo-toolkit' ) ],
            [ 'value' => 'post', 'label' => __( 'Posts', 'asneris-seo-toolkit' ) ],
          ],
          'postStatuses' => $post_status_options,
        ],
        'priorityFeatureEnabled' => $priority_feature_enabled,
      ]
    );
  }

  private static function build_page_diagnostics_overview_item( $post, $site_host ) {
    $permalink = get_permalink( $post->ID );
    if ( ! $permalink ) {
      $permalink = get_preview_post_link( $post );
    }
    if ( ! $permalink ) {
      $permalink = get_edit_post_link( $post->ID, '' );
    }

    $title = (string) get_the_title( $post->ID );
    $author_name = trim( (string) get_the_author_meta( 'display_name', (int) $post->post_author ) );
    if ( '' === $author_name ) {
      $author_name = __( 'Unknown', 'asneris-seo-toolkit' );
    }
    $meta_title = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_title', true ) );
    $effective_title = '' !== $meta_title ? $meta_title : $title;
    $title_plain = wp_strip_all_tags( $effective_title );
    $title_length = function_exists( 'mb_strlen' ) ? mb_strlen( $title_plain ) : strlen( $title_plain );

    $description = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_description', true ) );
    $excerpt_text = trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $post->ID ) ) );
    if ( '' === $excerpt_text ) {
      $excerpt_text = trim( wp_strip_all_tags( (string) get_post_field( 'post_content', $post->ID ) ) );
    }
    $og_title = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_og_title', true ) );
    $og_description = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_og_description', true ) );
    $og_image = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_og_image', true ) );
    $og_image_disabled = ! empty( get_post_meta( $post->ID, '_ASNERISSEO_og_image_disabled', true ) );
    $effective_description = '' !== $description ? $description : $excerpt_text;
    $effective_description_length = function_exists( 'mb_strlen' ) ? mb_strlen( $effective_description ) : strlen( $effective_description );
    $has_custom_description = '' !== $description;
    $has_custom_title = '' !== $meta_title;
    $canonical_url = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_canonical', true ) );
    $has_canonical = '' !== $canonical_url;
    $x_robots_tag = trim( (string) get_post_meta( $post->ID, '_ASNERISSEO_x_robots_tag', true ) );

    $robots_index = sanitize_key( (string) get_post_meta( $post->ID, '_ASNERISSEO_robots_index', true ) );
    if ( ! in_array( $robots_index, [ 'index', 'noindex' ], true ) ) {
      $robots_index = 'index';
    }
    $robots_follow = sanitize_key( (string) get_post_meta( $post->ID, '_ASNERISSEO_robots_follow', true ) );
    if ( ! in_array( $robots_follow, [ 'follow', 'nofollow' ], true ) ) {
      $robots_follow = 'follow';
    }
    $is_priority = ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post->ID );

    $content_raw = (string) get_post_field( 'post_content', $post->ID );
    $content_word_count = self::count_words_from_html( $content_raw );
    $language_declaration = strtolower( str_replace( '_', '-', sanitize_text_field( get_bloginfo( 'language' ) ) ) );
    $last_scan_gmt = (string) get_post_meta( $post->ID, '_ASNERISSEO_last_diagnostics_scan_gmt', true );
    $h1_count = preg_match_all( '/<h1\\b[^>]*>/i', $content_raw );
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
          $faq_count++;
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

    $schema_settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $has_breadcrumb_schema = ! empty( $schema_settings['enable_breadcrumbs'] );
    $has_organization_schema = ! empty( $schema_settings['enable_local_business'] );
    $has_structured_data = '' !== $schema_type || (int) $faq_count > 0 || $has_breadcrumb_schema || $has_organization_schema;

    $image_count = 0;
    $images_missing_alt = 0;
    $images_empty_alt = 0;
    if ( preg_match_all( '/<img\\b[^>]*>/i', $content_raw, $image_matches ) ) {
      $image_count = count( $image_matches[0] );
      foreach ( $image_matches[0] as $image_tag ) {
        if ( ! preg_match( '/\\balt\\s*=\\s*(["\'])(.*?)\\1/i', $image_tag, $alt_match ) ) {
          $images_missing_alt++;
          continue;
        }

        $alt_text = trim( wp_strip_all_tags( html_entity_decode( (string) $alt_match[2], ENT_QUOTES, 'UTF-8' ) ) );
        if ( '' === $alt_text ) {
          $images_empty_alt++;
          $images_missing_alt++;
        }
      }
    }
    $has_featured_image = function_exists( 'has_post_thumbnail' ) ? has_post_thumbnail( $post->ID ) : false;

    $link_metrics = self::count_link_metrics_from_html( $content_raw, $site_host );
    $internal_link_count = isset( $link_metrics['internal'] ) ? (int) $link_metrics['internal'] : 0;
    $external_link_count = isset( $link_metrics['external'] ) ? (int) $link_metrics['external'] : 0;
    $nofollow_link_count = isset( $link_metrics['nofollow'] ) ? (int) $link_metrics['nofollow'] : 0;
    $http_status = 0;

    $scores = self::calculate_page_overview_scores(
      [
        'runId' => sprintf( 'overview-%d-%s', (int) $post->ID, gmdate( 'YmdHis' ) ),
        'effectiveTitleLength' => (int) $title_length,
        'effectiveDescriptionLength' => (int) $effective_description_length,
        'hasCanonical' => (bool) $has_canonical,
        'robotsIndex' => (string) $robots_index,
        'robotsFollow' => (string) $robots_follow,
        'contentRaw' => (string) $content_raw,
        'contentWords' => (int) $content_word_count,
        'imageCount' => (int) $image_count,
        'imagesMissingAlt' => (int) $images_missing_alt,
        'internalLinks' => (int) $internal_link_count,
        'httpStatus' => (int) $http_status,
        'languageDeclaration' => (string) $language_declaration,
        'siteName' => (string) get_bloginfo( 'name' ),
      ]
    );
    $seo_score = (int) $scores['seoScore'];
    $ai_score = (int) $scores['aiScore'];

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
      // Priority list must reflect latest snapshot values from xxx_asneris_page_diag_latest.
      if ( isset( $snapshot_report['seoScore'] ) ) {
        $seo_score = max( 0, min( 100, (int) $snapshot_report['seoScore'] ) );
      }
      if ( isset( $snapshot_report['health'] ) ) {
        $snapshot_health = sanitize_key( (string) $snapshot_report['health'] );
        if ( in_array( $snapshot_health, [ 'good', 'warning', 'poor' ], true ) ) {
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

    return [
      'postId' => (int) $post->ID,
      'title' => $title,
      'postType' => $post->post_type,
      'postStatus' => $post->post_status,
      'author' => $author_name,
      'isDraftQualityOnly' => false,
      'url' => esc_url_raw( $permalink ),
      'modifiedGmt' => get_post_modified_time( 'c', true, $post->ID ),
      'metaTitle' => $meta_title,
      'seoTitle' => $meta_title,
      'seoDescription' => $description,
      'metaDescription' => $description,
      'excerpt' => $excerpt_text,
      'ogTitle' => $og_title,
      'ogDescription' => $og_description,
      'ogImage' => $og_image,
      'ogImageDisabled' => $og_image_disabled,
      'hasCustomTitle' => $has_custom_title,
      'hasCustomDescription' => $has_custom_description,
      'hasCanonical' => $has_canonical,
      'canonical' => $canonical_url,
      'metaTitleLength' => (int) $title_length,
      'titleLength' => (int) $title_length,
      'seoScore' => $seo_score,
      'aiScore' => $ai_score,
      'health' => $health,
      'robotsIndex' => $robots_index,
      'robotsFollow' => $robots_follow,
      'xRobotsTag' => $x_robots_tag,
      'metaSummary' => $meta_summary,
      'contentWords' => $content_word_count,
      'h1Count' => (int) $h1_count,
      'h2Count' => (int) $h2_count,
      'faqCount' => (int) $faq_count,
      'schemaEnabled' => (bool) $has_structured_data,
      'schemaType' => $schema_type,
      'organizationSchema' => (bool) $has_organization_schema,
      'breadcrumbSchema' => (bool) $has_breadcrumb_schema,
      'internalLinks' => $internal_link_count,
      'externalLinks' => $external_link_count,
      'nofollowLinks' => $nofollow_link_count,
      'httpStatus' => (int) $http_status,
      'languageDeclaration' => $language_declaration,
      'imageCount' => $image_count,
      'imagesMissingAlt' => $images_missing_alt,
      'imagesEmptyAlt' => $images_empty_alt,
      'featuredImage' => $has_featured_image,
      'issueGroups' => $scores['issueGroups'],
      'overviewIssueRecords' => isset( $scores['overviewIssueRecords'] ) && is_array( $scores['overviewIssueRecords'] ) ? $scores['overviewIssueRecords'] : [],
      'aiIssueRecords' => isset( $scores['aiIssueRecords'] ) && is_array( $scores['aiIssueRecords'] ) ? $scores['aiIssueRecords'] : [],
      'aiCanonicalSignals' => isset( $scores['aiCanonicalSignals'] ) && is_array( $scores['aiCanonicalSignals'] ) ? $scores['aiCanonicalSignals'] : [],
      'tabIssueRecords' => isset( $scores['tabIssueRecords'] ) && is_array( $scores['tabIssueRecords'] ) ? $scores['tabIssueRecords'] : [],
      'overviewRunId' => isset( $scores['overviewRunId'] ) ? (string) $scores['overviewRunId'] : '',
      'seoScoreMessage' => isset( $scores['seoScoreMessage'] ) ? sanitize_text_field( (string) $scores['seoScoreMessage'] ) : '',
      'scoreEngine' => 'weightage_policy_v4_1',
      'lastScanGmt' => $last_scan_gmt,
    ];
  }

  private static function calculate_priority_page_score( $item ) {
    $health = isset( $item['health'] ) ? sanitize_key( (string) $item['health'] ) : 'good';
    $health_weight = 0;
    if ( 'poor' === $health ) {
      $health_weight = 180;
    } elseif ( 'warning' === $health ) {
      $health_weight = 100;
    }

    $issue_groups = isset( $item['issueGroups'] ) && is_array( $item['issueGroups'] ) ? $item['issueGroups'] : [];
    $issue_count = 0;
    foreach ( $issue_groups as $issue_enabled ) {
      if ( ! empty( $issue_enabled ) ) {
        $issue_count++;
      }
    }

    $seo_score = isset( $item['seoScore'] ) ? (int) $item['seoScore'] : 100;
    $seo_penalty = max( 0, 100 - $seo_score );
    $noindex_boost = ( isset( $item['robotsIndex'] ) && 'noindex' === sanitize_key( (string) $item['robotsIndex'] ) ) ? 40 : 0;
    $never_scanned_boost = empty( $item['lastScanGmt'] ) ? 10 : 0;

    return $health_weight + ( $issue_count * 25 ) + $seo_penalty + $noindex_boost + $never_scanned_boost;
  }

  private static function extract_http_status_from_checks( $checks, $fallback = 0 ) {
    $rows = is_array( $checks ) ? $checks : [];
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

  private static function get_check_row_by_label( $checks, $target_label ) {
    $rows = is_array( $checks ) ? $checks : [];
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
    $best_index = null;
    $best_score = 0;

    foreach ( $checks as $index => $check ) {
      if ( ! is_array( $check ) ) {
        continue;
      }

      $label = (string) ( $check['label'] ?? '' );
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

      $overlap = array_intersect( $canonical_tokens, $label_tokens );
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
      : [];

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

      $canonical_status = strtolower( trim( (string) ( $record['canonical_status'] ?? '' ) ) );
      $normalized_status = in_array( $canonical_status, [ 'pass', 'warning', 'fail' ], true ) ? $canonical_status : 'warning';

      $canonical_result = '';
      $raw_evidence = isset( $record['raw_evidence'] ) && is_array( $record['raw_evidence'] ) ? $record['raw_evidence'] : [];
      $linked_fields = isset( $record['linked_raw_evidence_fields'] ) && is_array( $record['linked_raw_evidence_fields'] )
        ? $record['linked_raw_evidence_fields']
        : [];

      if ( ! empty( $linked_fields ) ) {
        $values = [];
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

  private static function apply_weightage_scores_from_checks( WP_Post $post, array $overview_item, array $check_rows, $run_id_prefix = 'overview-live' ) {
    $post_id = (int) $post->ID;
    $db_meta_title = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_title', true ) );
    $db_meta_description = trim( (string) get_post_meta( $post_id, '_ASNERISSEO_description', true ) );

    $detected_http_status = self::extract_http_status_from_checks(
      $check_rows,
      isset( $overview_item['httpStatus'] ) ? (int) $overview_item['httpStatus'] : 0
    );

    $raw_meta_title = trim( (string) ( $overview_item['metaTitle'] ?? $overview_item['seoTitle'] ?? $overview_item['title'] ?? '' ) );
    $fallback_title_length = isset( $overview_item['metaTitleLength'] )
      ? (int) $overview_item['metaTitleLength']
      : ( isset( $overview_item['titleLength'] ) ? (int) $overview_item['titleLength'] : 0 );
    if ( $fallback_title_length < 1 && '' !== $raw_meta_title ) {
      $title_plain = wp_strip_all_tags( $raw_meta_title );
      $fallback_title_length = function_exists( 'mb_strlen' ) ? mb_strlen( $title_plain ) : strlen( $title_plain );
    }

    $detected_title_length = max(
      0,
      (int) $fallback_title_length
    );

    $detected_description_length = self::extract_numeric_check_result(
      $check_rows,
      'Meta Description Length',
      strlen( (string) ( $overview_item['seoDescription'] ?? $overview_item['metaDescription'] ?? '' ) )
    );

    $debug_payload = [
      'postId' => $post_id,
      'runPrefix' => sanitize_key( (string) $run_id_prefix ),
      'dbMetaTitleLength' => function_exists( 'mb_strlen' ) ? mb_strlen( $db_meta_title ) : strlen( $db_meta_title ),
      'dbMetaDescriptionLength' => function_exists( 'mb_strlen' ) ? mb_strlen( $db_meta_description ) : strlen( $db_meta_description ),
      'dbMetaTitlePreview' => function_exists( 'mb_substr' ) ? mb_substr( $db_meta_title, 0, 80 ) : substr( $db_meta_title, 0, 80 ),
      'dbMetaDescriptionPreview' => function_exists( 'mb_substr' ) ? mb_substr( $db_meta_description, 0, 120 ) : substr( $db_meta_description, 0, 120 ),
      'overviewMetaTitleLength' => isset( $overview_item['metaTitleLength'] ) ? (int) $overview_item['metaTitleLength'] : null,
      'overviewTitleLength' => isset( $overview_item['titleLength'] ) ? (int) $overview_item['titleLength'] : null,
      'overviewSeoDescriptionLength' => strlen( (string) ( $overview_item['seoDescription'] ?? '' ) ),
      'overviewMetaDescriptionLength' => strlen( (string) ( $overview_item['metaDescription'] ?? '' ) ),
      'detectedTitleLength' => (int) $detected_title_length,
      'detectedDescriptionLength' => (int) $detected_description_length,
      'checkRowCount' => is_array( $check_rows ) ? count( $check_rows ) : 0,
    ];

    unset( $debug_payload );

    $detected_internal_links = self::extract_numeric_check_result(
      $check_rows,
      'Internal Links',
      isset( $overview_item['internalLinks'] ) ? (int) $overview_item['internalLinks'] : 0
    );

    $detected_content_words = self::extract_numeric_check_result(
      $check_rows,
      'Word Count',
      isset( $overview_item['contentWords'] ) ? (int) $overview_item['contentWords'] : 0
    );

    $h1_exists_status = self::extract_check_status( $check_rows, 'H1 Exists', '' );
    $has_heading_override = '' !== $h1_exists_status
      ? ( 'pass' === $h1_exists_status )
      : ( 1 === preg_match( '/<h[1-6]\\b[^>]*>/i', (string) $post->post_content ) );

    $robots_status = self::extract_check_status( $check_rows, 'Robots Meta', '' );
    $detected_robots_index = (string) ( $overview_item['robotsIndex'] ?? 'index' );
    $detected_robots_follow = (string) ( $overview_item['robotsFollow'] ?? 'follow' );
    if ( 'warning' === $robots_status || 'fail' === $robots_status || 'error' === $robots_status ) {
      $detected_robots_index = 'noindex';
      $detected_robots_follow = 'follow';
    }

    $live_overview_scores = self::calculate_page_overview_scores(
      [
        'runId' => sprintf( '%s-%d-%s', sanitize_key( (string) $run_id_prefix ), $post_id, gmdate( 'YmdHis' ) ),
        'effectiveTitleLength' => $detected_title_length,
        'effectiveDescriptionLength' => $detected_description_length,
        'hasCanonical' => ! empty( $overview_item['hasCanonical'] ),
        'robotsIndex' => $detected_robots_index,
        'robotsFollow' => $detected_robots_follow,
        'contentRaw' => (string) $post->post_content,
        'contentWords' => $detected_content_words,
        'imageCount' => isset( $overview_item['imageCount'] ) ? (int) $overview_item['imageCount'] : 0,
        'imagesMissingAlt' => isset( $overview_item['imagesMissingAlt'] ) ? (int) $overview_item['imagesMissingAlt'] : 0,
        'internalLinks' => $detected_internal_links,
        'httpStatus' => (int) $detected_http_status,
        'hasHeading' => $has_heading_override,
        'languageDeclaration' => (string) ( $overview_item['languageDeclaration'] ?? '' ),
        'siteName' => (string) get_bloginfo( 'name' ),
      ]
    );

    $live_seo_score = isset( $live_overview_scores['seoScore'] ) ? (int) $live_overview_scores['seoScore'] : 0;
    $live_health = 'poor';
    if ( $live_seo_score >= 85 ) {
      $live_health = 'good';
    } elseif ( $live_seo_score >= 65 ) {
      $live_health = 'warning';
    }

    $overview_item['httpStatus'] = (int) $detected_http_status;
    $overview_item['metaTitleLength'] = (int) $detected_title_length;
    $overview_item['titleLength'] = (int) $detected_title_length;
    $overview_item['effectiveTitleLength'] = (int) $detected_title_length;
    $overview_item['descriptionLength'] = (int) $detected_description_length;
    $overview_item['effectiveDescriptionLength'] = (int) $detected_description_length;
    $overview_item['seoScore'] = $live_seo_score;
    $overview_item['aiScore'] = isset( $live_overview_scores['aiScore'] ) ? (int) $live_overview_scores['aiScore'] : (int) ( $overview_item['aiScore'] ?? 0 );
    $overview_item['health'] = $live_health;
    $overview_item['issueGroups'] = isset( $live_overview_scores['issueGroups'] ) && is_array( $live_overview_scores['issueGroups'] ) ? $live_overview_scores['issueGroups'] : [];
    $overview_item['overviewIssueRecords'] = isset( $live_overview_scores['overviewIssueRecords'] ) && is_array( $live_overview_scores['overviewIssueRecords'] ) ? $live_overview_scores['overviewIssueRecords'] : [];
    $overview_item['aiIssueRecords'] = isset( $live_overview_scores['aiIssueRecords'] ) && is_array( $live_overview_scores['aiIssueRecords'] ) ? $live_overview_scores['aiIssueRecords'] : [];
    $overview_item['aiCanonicalSignals'] = isset( $live_overview_scores['aiCanonicalSignals'] ) && is_array( $live_overview_scores['aiCanonicalSignals'] ) ? $live_overview_scores['aiCanonicalSignals'] : [];
    $overview_item['overviewRunId'] = isset( $live_overview_scores['overviewRunId'] ) ? (string) $live_overview_scores['overviewRunId'] : '';
    $overview_item['seoScoreMessage'] = isset( $live_overview_scores['seoScoreMessage'] ) ? sanitize_text_field( (string) $live_overview_scores['seoScoreMessage'] ) : '';

    return $overview_item;
  }

  public static function build_weightage_score_override_for_post( WP_Post $post, array $checks ) {
    $site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
    $overview_item = self::build_page_diagnostics_overview_item( $post, $site_host );
    $scored = self::apply_weightage_scores_from_checks( $post, $overview_item, $checks, 'overview-snapshot' );

    return [
      'seoScore' => isset( $scored['seoScore'] ) ? (int) $scored['seoScore'] : 0,
      'aiScore' => isset( $scored['aiScore'] ) ? (int) $scored['aiScore'] : 0,
      'health' => isset( $scored['health'] ) ? sanitize_key( (string) $scored['health'] ) : 'poor',
      'issueGroups' => isset( $scored['issueGroups'] ) && is_array( $scored['issueGroups'] ) ? $scored['issueGroups'] : [],
      'overviewIssueRecords' => isset( $scored['overviewIssueRecords'] ) && is_array( $scored['overviewIssueRecords'] ) ? $scored['overviewIssueRecords'] : [],
      'aiIssueRecords' => isset( $scored['aiIssueRecords'] ) && is_array( $scored['aiIssueRecords'] ) ? $scored['aiIssueRecords'] : [],
      'aiCanonicalSignals' => isset( $scored['aiCanonicalSignals'] ) && is_array( $scored['aiCanonicalSignals'] ) ? $scored['aiCanonicalSignals'] : [],
      'overviewRunId' => isset( $scored['overviewRunId'] ) ? (string) $scored['overviewRunId'] : '',
      'seoScoreMessage' => isset( $scored['seoScoreMessage'] ) ? sanitize_text_field( (string) $scored['seoScoreMessage'] ) : '',
      'scoreEngine' => 'weightage_policy_v4_1',
    ];
  }

  private static function normalize_space( $value ) {
    $string_value = is_scalar( $value ) ? (string) $value : '';
    $normalized = preg_replace( '/\s+/u', ' ', $string_value );
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
        $internal_links++;
        continue;
      }

      $href_host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
      $href_scheme = strtolower( (string) wp_parse_url( $href, PHP_URL_SCHEME ) );

      if ( '' === $href_host && '' === $href_scheme ) {
        $internal_links++;
        continue;
      }

      if ( '' !== $href_host && '' !== $normalized_site_host && $href_host === $normalized_site_host ) {
        $internal_links++;
      }
    }

    return $internal_links;
  }

  private static function count_link_metrics_from_html( $html, $site_host ) {
    $metrics = [
      'internal' => 0,
      'external' => 0,
      'nofollow' => 0,
    ];

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
        $href_host = strtolower( (string) wp_parse_url( $href, PHP_URL_HOST ) );
        $href_scheme = strtolower( (string) wp_parse_url( $href, PHP_URL_SCHEME ) );

        if ( '' === $href_host && '' === $href_scheme ) {
          $is_internal = true;
        } elseif ( '' !== $href_host && '' !== $normalized_site_host && $href_host === $normalized_site_host ) {
          $is_internal = true;
        }
      }

      if ( $is_internal ) {
        $metrics['internal']++;
      } else {
        $metrics['external']++;
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
          $metrics['nofollow']++;
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
    $stop_words = [
      'the', 'and', 'for', 'with', 'that', 'this', 'from', 'your', 'have', 'will',
      'into', 'about', 'page', 'content',
    ];
    $stop_lookup = array_fill_keys( $stop_words, true );

    $tokens = preg_split( '/[^a-z0-9]+/i', (string) $text );
    if ( ! is_array( $tokens ) ) {
      return [ 'topCount' => 0, 'ratio' => 0.0 ];
    }

    $counts = [];
    $total = 0;
    foreach ( $tokens as $token ) {
      $word = strtolower( trim( (string) $token ) );
      if ( strlen( $word ) < 4 || isset( $stop_lookup[ $word ] ) ) {
        continue;
      }

      $total++;
      if ( ! isset( $counts[ $word ] ) ) {
        $counts[ $word ] = 0;
      }
      $counts[ $word ]++;
    }

    $top_count = 0;
    foreach ( $counts as $count ) {
      if ( $count > $top_count ) {
        $top_count = (int) $count;
      }
    }

    return [
      'topCount' => $top_count,
      'ratio' => $total > 0 ? ( (float) $top_count / (float) $total ) : 0.0,
    ];
  }

  private static function calculate_page_overview_scores( $context ) {
    $title_length = isset( $context['effectiveTitleLength'] ) ? (int) $context['effectiveTitleLength'] : 0;
    $description_length = isset( $context['effectiveDescriptionLength'] ) ? (int) $context['effectiveDescriptionLength'] : 0;
    $has_canonical = isset( $context['hasCanonical'] ) ? (bool) $context['hasCanonical'] : false;
    $robots_index = isset( $context['robotsIndex'] ) ? sanitize_key( (string) $context['robotsIndex'] ) : 'index';
    $robots_follow = isset( $context['robotsFollow'] ) ? sanitize_key( (string) $context['robotsFollow'] ) : 'follow';
    $content_raw = isset( $context['contentRaw'] ) ? (string) $context['contentRaw'] : '';
    $content_words = isset( $context['contentWords'] ) ? (int) $context['contentWords'] : 0;
    $image_count = isset( $context['imageCount'] ) ? (int) $context['imageCount'] : 0;
    $images_missing_alt = isset( $context['imagesMissingAlt'] ) ? (int) $context['imagesMissingAlt'] : 0;
    $internal_links = isset( $context['internalLinks'] ) ? (int) $context['internalLinks'] : 0;
    $http_status = isset( $context['httpStatus'] ) ? (int) $context['httpStatus'] : 0;
    $site_name = strtolower( trim( (string) ( $context['siteName'] ?? '' ) ) );
    $language_declaration = strtolower( str_replace( '_', '-', sanitize_text_field( (string) ( $context['languageDeclaration'] ?? '' ) ) ) );
    $meta_partial_credit = ! isset( $context['metaPartialCredit'] ) || (bool) $context['metaPartialCredit'];

    $has_heading = isset( $context['hasHeading'] )
      ? (bool) $context['hasHeading']
      : ( 1 === preg_match( '/<h[1-6]\\b[^>]*>/i', $content_raw ) );
    $alt_coverage = $image_count > 0
      ? ( ( (float) max( 0, $image_count - $images_missing_alt ) / (float) $image_count ) * 100.0 )
      : 100.0;

    // 4.1 SEO Model (doc/redeusbg/score-weightage-all-flows.md): max 100.
    $title_length_points = ( $title_length >= 30 && $title_length <= 60 ) ? 10 : ( ( $meta_partial_credit && $title_length > 0 ) ? 5 : 0 );
    $description_length_points = ( $description_length >= 120 && $description_length <= 160 ) ? 10 : ( ( $meta_partial_credit && $description_length > 0 ) ? 5 : 0 );
    $canonical_points = 0;
    $robots_points = ( 'index' === $robots_index && 'follow' === $robots_follow ) ? 20 : 10;
    $http_status_points = ( $http_status >= 200 && $http_status < 300 ) ? 30 : ( $http_status >= 300 && $http_status < 400 ? 15 : 0 );
    $heading_points = $has_heading ? 10 : 2;
    $image_alt_points = 0;
    $internal_points = $internal_links >= 2 ? 10 : ( 1 === $internal_links ? 6 : 2 );
    $content_depth_points = $content_words >= 300 ? 10 : 5;

    $seo_score = self::clamp_score(
      $title_length_points +
      $description_length_points +
      $robots_points +
      $http_status_points +
      $heading_points +
      $internal_points +
      $content_depth_points
    );

    $content_text = self::normalize_space( strtolower( wp_strip_all_tags( $content_raw ) ) );
    $heading_levels = [];
    if ( preg_match_all( '/<h([1-6])\\b[^>]*>(.*?)<\\/h\\1>/is', $content_raw, $heading_matches, PREG_SET_ORDER ) ) {
      foreach ( $heading_matches as $heading_match ) {
        $heading_levels[] = (int) $heading_match[1];
      }
    }
    $heading_count = count( $heading_levels );
    $has_h1 = in_array( 1, $heading_levels, true );

    $hierarchy_valid = true;
    for ( $index = 1; $index < $heading_count; $index++ ) {
      if ( abs( $heading_levels[ $index ] - $heading_levels[ $index - 1 ] ) > 2 ) {
        $hierarchy_valid = false;
        break;
      }
    }

    $has_list = 1 === preg_match( '/<(ul|ol)\\b/i', $content_raw );
    $has_table = 1 === preg_match( '/<table\\b/i', $content_raw );
    $word_count = self::count_words_from_html( $content_raw );

    $sentences = preg_split( '/[.!?]+/u', $content_text );
    $sentence_lengths = [];
    if ( is_array( $sentences ) ) {
      foreach ( $sentences as $sentence ) {
        $normalized_sentence = self::normalize_space( $sentence );
        if ( '' === $normalized_sentence ) {
          continue;
        }
        $sentence_lengths[] = count( preg_split( '/\\s+/u', $normalized_sentence ) );
      }
    }
    $avg_sentence_length = count( $sentence_lengths ) > 0
      ? (int) round( array_sum( $sentence_lengths ) / count( $sentence_lengths ) )
      : 0;

    $keyword_stats = self::calculate_keyword_stats( $content_text );

    $signal_h1_present = $has_h1;
    $signal_heading_hierarchy_valid = $hierarchy_valid;
    $signal_sections_coverage = $heading_count >= 3;
    $signal_list_detected = $has_list;
    $signal_table_detected = $has_table;
    $signal_clear_page_purpose = ( $word_count >= 180 && $heading_count >= 1 );
    $signal_topic_consistency = ( $keyword_stats['topCount'] >= 3 && $keyword_stats['ratio'] >= 0.03 && $keyword_stats['ratio'] <= 0.16 );
    $signal_summary_section = 1 === preg_match( '/(summary|in summary|conclusion|tl;dr)/i', $content_text );
    $signal_readability = ( $avg_sentence_length >= 8 && $avg_sentence_length <= 24 );
    $signal_brand_mentions = ( '' !== $site_name && false !== strpos( $content_text, $site_name ) );
    $signal_product_context_mentions = 1 === preg_match( '/(plugin|toolkit|product|service|platform)/i', $content_text );
    $signal_faq = 1 === preg_match( '/(faq|frequently asked)/i', $content_text );
    $signal_definition = 1 === preg_match( '/(defined as|means|for example|e\.g\.|how to|step-by-step|steps)/i', $content_text );
    $signal_trust = 1 === preg_match( '/(author|written by|contact|support|about|updated|last updated|20\d\d)/i', $content_text );
    $signal_content_completeness = ( $word_count >= 300 && $internal_links >= 2 && $image_count >= 1 );
    $signal_language_declaration = '' !== $language_declaration;
    $signal_internal_references = $internal_links >= 1;

    $ai_points_total = 0;
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

    $ai_score = self::clamp_score( $ai_points_total );

    $run_id = isset( $context['runId'] ) ? sanitize_text_field( (string) $context['runId'] ) : gmdate( 'YmdHis' ) . '-overview';

    $canonical_checks = [];
    $canonical_checks[] = [
      'category' => 'meta',
      'canonical_field' => 'SEO Title Length',
      'canonical_status' => $title_length >= 30 && $title_length <= 60 ? 'pass' : ( $title_length > 0 ? 'warning' : 'fail' ),
      'linked_raw_evidence_fields' => [ 'effectiveTitleLength' ],
      'raw_evidence' => [ 'effectiveTitleLength' => $title_length ],
      'score_impact' => max( 0, 10 - $title_length_points ),
      'recommended_fix' => 'Target 30-60 characters for the title.',
    ];
    $canonical_checks[] = [
      'category' => 'meta',
      'canonical_field' => 'Meta Description Length',
      'canonical_status' => $description_length >= 120 && $description_length <= 160 ? 'pass' : ( $description_length > 0 ? 'warning' : 'fail' ),
      'linked_raw_evidence_fields' => [ 'effectiveDescriptionLength' ],
      'raw_evidence' => [ 'effectiveDescriptionLength' => $description_length ],
      'score_impact' => max( 0, 10 - $description_length_points ),
      'recommended_fix' => 'Target 120-160 characters for the description.',
    ];
    $canonical_checks[] = [
      'category' => 'meta',
      'canonical_field' => 'Canonical',
      'canonical_status' => $has_canonical ? 'pass' : 'warning',
      'linked_raw_evidence_fields' => [ 'hasCanonical' ],
      'raw_evidence' => [ 'hasCanonical' => $has_canonical ],
      'score_impact' => 0,
      'recommended_fix' => 'Set a canonical URL for this page.',
    ];

    $robots_raw_states = [
      'index' === $robots_index ? 'pass' : 'fail',
      'follow' === $robots_follow ? 'pass' : 'fail',
    ];
    $canonical_checks[] = [
      'category' => 'indexability',
      'canonical_field' => 'Robots Meta',
      'canonical_status' => self::canonical_status_from_raw_states( $robots_raw_states ),
      'linked_raw_evidence_fields' => [ 'robotsIndex', 'robotsFollow' ],
      'raw_evidence' => [
        'robotsIndex' => $robots_index,
        'robotsFollow' => $robots_follow,
      ],
      'score_impact' => max( 0, 20 - $robots_points ),
      'recommended_fix' => 'Use index and follow for crawlable pages.',
    ];

    $canonical_checks[] = [
      'category' => 'indexability',
      'canonical_field' => 'HTTP Status',
      'canonical_status' => ( $http_status >= 200 && $http_status < 300 ) ? 'pass' : ( $http_status >= 300 && $http_status < 400 ? 'warning' : 'fail' ),
      'linked_raw_evidence_fields' => [ 'httpStatus' ],
      'raw_evidence' => [ 'httpStatus' => $http_status ],
      'score_impact' => max( 0, 30 - $http_status_points ),
      'recommended_fix' => 'Ensure this URL resolves to HTTP 200 for the primary page.',
    ];

    $canonical_checks[] = [
      'category' => 'content',
      'canonical_field' => 'H1 Presence',
      'canonical_status' => $has_heading ? 'pass' : 'warning',
      'linked_raw_evidence_fields' => [ 'hasHeading' ],
      'raw_evidence' => [ 'hasHeading' => $has_heading ],
      'score_impact' => max( 0, 10 - $heading_points ),
      'recommended_fix' => 'Add a clear H1 heading in page content.',
    ];

    $canonical_checks[] = [
      'category' => 'images',
      'canonical_field' => 'Image ALT Coverage',
      'canonical_status' => 0 === $image_count ? 'warning' : ( $alt_coverage >= 80.0 ? 'pass' : 'warning' ),
      'linked_raw_evidence_fields' => [ 'imageCount', 'imagesMissingAlt' ],
      'raw_evidence' => [
        'imageCount' => $image_count,
        'imagesMissingAlt' => $images_missing_alt,
      ],
      'score_impact' => 0,
      'recommended_fix' => 'Improve ALT coverage to at least 80% across images.',
    ];

    $canonical_checks[] = [
      'category' => 'content',
      'canonical_field' => 'Internal Links',
      'canonical_status' => $internal_links >= 2 ? 'pass' : ( 1 === $internal_links ? 'warning' : 'fail' ),
      'linked_raw_evidence_fields' => [ 'internalLinks' ],
      'raw_evidence' => [ 'internalLinks' => $internal_links ],
      'score_impact' => max( 0, 10 - $internal_points ),
      'recommended_fix' => 'Add at least 2 internal links.',
    ];

    $canonical_checks[] = [
      'category' => 'content',
      'canonical_field' => 'Content Depth (Word Count)',
      'canonical_status' => $content_words >= 300 ? 'pass' : 'warning',
      'linked_raw_evidence_fields' => [ 'contentWords' ],
      'raw_evidence' => [ 'contentWords' => $content_words ],
      'score_impact' => max( 0, 10 - $content_depth_points ),
      'recommended_fix' => 'Expand content depth to at least 300 words where appropriate.',
    ];

    $overview_issue_records = self::build_overview_issue_records( $canonical_checks, $run_id );

    $ai_table_list_status = self::canonical_status_from_raw_states(
      [
        $signal_list_detected ? 'pass' : 'warning',
        $signal_table_detected ? 'pass' : 'warning',
      ]
    );
    $ai_clear_page_purpose_status = self::canonical_status_from_raw_states(
      [
        $signal_h1_present ? 'pass' : 'warning',
        $signal_heading_hierarchy_valid ? 'pass' : 'warning',
        $signal_sections_coverage ? 'pass' : 'warning',
        $signal_clear_page_purpose ? 'pass' : 'warning',
        $signal_readability ? 'pass' : 'warning',
      ]
    );

    $ai_canonical_signals = [
      'Topic Consistency' => [
        'canonical_field' => 'Topic Consistency',
        'canonical_status' => $signal_topic_consistency ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'keywordTopCount', 'keywordRatio' ],
        'raw_evidence' => [ 'keywordTopCount' => (int) $keyword_stats['topCount'], 'keywordRatio' => (float) $keyword_stats['ratio'] ],
        'result' => $signal_topic_consistency ? 'Aligned' : 'Needs work',
        'details' => 'Source: keyword top-term concentration from page content.',
      ],
      'Clear Page Purpose' => [
        'canonical_field' => 'Clear Page Purpose',
        'canonical_status' => $ai_clear_page_purpose_status,
        'linked_raw_evidence_fields' => [ 'h1Present', 'headingHierarchyValid', 'sectionsCoverage', 'wordCount', 'avgSentenceLength' ],
        'raw_evidence' => [ 'h1Present' => (bool) $signal_h1_present, 'headingHierarchyValid' => (bool) $signal_heading_hierarchy_valid, 'sectionsCoverage' => (bool) $signal_sections_coverage, 'wordCount' => (int) $word_count, 'avgSentenceLength' => (int) $avg_sentence_length ],
        'result' => 'Composite',
        'details' => 'Source: heading structure, sections coverage, readability, and content depth signals.',
      ],
      'Summary Section' => [
        'canonical_field' => 'Summary Section',
        'canonical_status' => $signal_summary_section ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'summaryPattern' ],
        'raw_evidence' => [ 'summaryPattern' => (bool) $signal_summary_section ],
        'result' => $signal_summary_section ? 'Detected' : 'Not detected',
        'details' => 'Source: summary/conclusion pattern detection.',
      ],
      'Content Completeness' => [
        'canonical_field' => 'Content Completeness',
        'canonical_status' => $signal_content_completeness ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'wordCount', 'internalLinks', 'imageCount' ],
        'raw_evidence' => [ 'wordCount' => (int) $word_count, 'internalLinks' => (int) $internal_links, 'imageCount' => (int) $image_count ],
        'result' => $signal_content_completeness ? 'Complete' : 'Needs improvement',
        'details' => 'Source: word count, internal linking, and image coverage.',
      ],
      'Brand Mentions' => [
        'canonical_field' => 'Brand Mentions',
        'canonical_status' => $signal_brand_mentions ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'siteName', 'contentText' ],
        'raw_evidence' => [ 'siteName' => (string) $site_name, 'siteNameMentioned' => (bool) $signal_brand_mentions ],
        'result' => $signal_brand_mentions ? 'Detected' : 'Not detected',
        'details' => 'Source: site-name mention detection in content.',
      ],
      'Product/Context Mentions' => [
        'canonical_field' => 'Product/Context Mentions',
        'canonical_status' => $signal_product_context_mentions ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'productContextPattern' ],
        'raw_evidence' => [ 'productContextPattern' => (bool) $signal_product_context_mentions ],
        'result' => $signal_product_context_mentions ? 'Detected' : 'Not detected',
        'details' => 'Source: product/context phrase detection.',
      ],
      'Trust Signals' => [
        'canonical_field' => 'Trust Signals',
        'canonical_status' => $signal_trust ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'trustPattern' ],
        'raw_evidence' => [ 'trustPattern' => (bool) $signal_trust ],
        'result' => $signal_trust ? 'Detected' : 'Not detected',
        'details' => 'Source: trust/author/contact/update pattern detection.',
      ],
      'Table/List Detection' => [
        'canonical_field' => 'Table/List Detection',
        'canonical_status' => $ai_table_list_status,
        'linked_raw_evidence_fields' => [ 'listDetected', 'tableDetected' ],
        'raw_evidence' => [ 'listDetected' => (bool) $signal_list_detected, 'tableDetected' => (bool) $signal_table_detected ],
        'result' => ( $signal_list_detected || $signal_table_detected ) ? 'Detected' : 'Not detected',
        'details' => 'Source: HTML list/table structure checks.',
      ],
      'Definition Content' => [
        'canonical_field' => 'Definition Content',
        'canonical_status' => $signal_definition ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'definitionPattern' ],
        'raw_evidence' => [ 'definitionPattern' => (bool) $signal_definition ],
        'result' => $signal_definition ? 'Detected' : 'Not detected',
        'details' => 'Source: definition/examples/how-to pattern detection.',
      ],
      'FAQ Signals' => [
        'canonical_field' => 'FAQ Signals',
        'canonical_status' => $signal_faq ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'faqPattern' ],
        'raw_evidence' => [ 'faqPattern' => (bool) $signal_faq ],
        'result' => $signal_faq ? 'Detected' : 'Not detected',
        'details' => 'Source: FAQ phrase detection in content.',
      ],
      'Language Declaration' => [
        'canonical_field' => 'Language Declaration',
        'canonical_status' => $signal_language_declaration ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'languageDeclaration' ],
        'raw_evidence' => [ 'languageDeclaration' => (string) $language_declaration ],
        'result' => '' !== $language_declaration ? $language_declaration : 'Not detected',
        'details' => 'Source: WordPress site language setting.',
      ],
      'Internal References' => [
        'canonical_field' => 'Internal References',
        'canonical_status' => $signal_internal_references ? 'pass' : 'warning',
        'linked_raw_evidence_fields' => [ 'internalLinks' ],
        'raw_evidence' => [ 'internalLinks' => (int) $internal_links ],
        'result' => (string) $internal_links,
        'details' => 'Source: internal links count from page content.',
      ],
    ];

    $ai_canonical_checks = [];
    foreach ( $ai_canonical_signals as $ai_signal ) {
      if ( ! is_array( $ai_signal ) ) {
        continue;
      }

      $ai_canonical_checks[] = [
        'category' => 'ai',
        'canonical_field' => isset( $ai_signal['canonical_field'] ) ? (string) $ai_signal['canonical_field'] : 'AI Signal',
        'canonical_status' => isset( $ai_signal['canonical_status'] ) ? (string) $ai_signal['canonical_status'] : 'warning',
        'linked_raw_evidence_fields' => isset( $ai_signal['linked_raw_evidence_fields'] ) && is_array( $ai_signal['linked_raw_evidence_fields'] ) ? $ai_signal['linked_raw_evidence_fields'] : [],
        'raw_evidence' => isset( $ai_signal['raw_evidence'] ) && is_array( $ai_signal['raw_evidence'] ) ? $ai_signal['raw_evidence'] : [],
        'score_impact' => 0,
        'recommended_fix' => isset( $ai_signal['details'] ) ? (string) $ai_signal['details'] : '',
      ];
    }
    $ai_issue_records = self::build_overview_issue_records( $ai_canonical_checks, $run_id );

    $issue_groups = [
      'meta' => false,
      'indexability' => false,
      'content' => false,
      'ai' => false,
    ];
    foreach ( array_merge( $overview_issue_records, $ai_issue_records ) as $issue_record ) {
      $category = isset( $issue_record['category'] ) ? sanitize_key( (string) $issue_record['category'] ) : '';
      if ( isset( $issue_groups[ $category ] ) ) {
        $issue_groups[ $category ] = true;
      }
    }

    return [
      'seoScore' => $seo_score,
      'aiScore' => $ai_score,
      'seoScoreMessage' => '',
      'issueGroups' => $issue_groups,
      'overviewIssueRecords' => $overview_issue_records,
      'aiIssueRecords' => $ai_issue_records,
      'aiCanonicalSignals' => $ai_canonical_signals,
      'overviewRunId' => $run_id,
    ];
  }

  private static function canonical_status_from_raw_states( $states ) {
    if ( ! is_array( $states ) || empty( $states ) ) {
      return 'warning';
    }

    $sum = 0.0;
    $count = 0;
    foreach ( $states as $state ) {
      $normalized = sanitize_key( (string) $state );
      if ( 'pass' === $normalized ) {
        $sum += 1.0;
      } elseif ( 'warning' === $normalized || 'warn' === $normalized ) {
        $sum += 0.5;
      } else {
        $sum += 0.0;
      }
      $count++;
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

  private static function build_overview_issue_records( $canonical_checks, $run_id ) {
    if ( ! is_array( $canonical_checks ) || empty( $canonical_checks ) ) {
      return [];
    }

    $records = [];
    foreach ( $canonical_checks as $canonical_check ) {
      $status = isset( $canonical_check['canonical_status'] ) ? sanitize_key( (string) $canonical_check['canonical_status'] ) : 'warning';
      if ( 'pass' === $status ) {
        continue;
      }

      $linked_fields = [];
      if ( isset( $canonical_check['linked_raw_evidence_fields'] ) && is_array( $canonical_check['linked_raw_evidence_fields'] ) ) {
        foreach ( $canonical_check['linked_raw_evidence_fields'] as $field_name ) {
          $linked_fields[] = sanitize_key( (string) $field_name );
        }
      }

      $records[] = [
        'run_id' => sanitize_text_field( (string) $run_id ),
        'category' => isset( $canonical_check['category'] ) ? sanitize_key( (string) $canonical_check['category'] ) : 'overview',
        'canonical_field' => sanitize_text_field( (string) ( $canonical_check['canonical_field'] ?? 'Unknown Canonical Field' ) ),
        'canonical_status' => $status,
        'linked_raw_evidence_fields' => $linked_fields,
        'raw_evidence' => isset( $canonical_check['raw_evidence'] ) && is_array( $canonical_check['raw_evidence'] )
          ? $canonical_check['raw_evidence']
          : [],
        'score_impact' => isset( $canonical_check['score_impact'] ) ? max( 0, (int) $canonical_check['score_impact'] ) : 0,
        'recommended_fix' => sanitize_text_field( (string) ( $canonical_check['recommended_fix'] ?? '' ) ),
      ];
    }

    return $records;
  }

  private static function normalize_diagnostics_url( $url ) {
    if ( ! is_string( $url ) ) {
      return '';
    }

    return untrailingslashit( strtolower( trim( $url ) ) );
  }

  private static function build_canonical_consistency_diagnostics() {
    global $wpdb;

    $checks = [];
    $has_conflicts = false;
    $has_warnings = false;

    $home_url_normalized = self::normalize_diagnostics_url( home_url( '/' ) );
    $front_page_id = (int) get_option( 'page_on_front' );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate query for diagnostics.
    $pages_to_home = (int) $wpdb->get_var(
      $wpdb->prepare(
        "
          SELECT COUNT(*)
          FROM {$wpdb->postmeta} pm
          INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
          WHERE pm.meta_key = %s
          AND LOWER(TRIM(TRAILING '/' FROM pm.meta_value)) = %s
          AND p.post_status = %s
          AND p.ID != %d
        ",
        '_ASNERISSEO_canonical',
        $home_url_normalized,
        'publish',
        $front_page_id
      )
    );

    if ( $pages_to_home > 5 ) {
      $checks[] = [
        'check' => 'Pages Canonicalizing to Homepage',
        'status' => 'warning',
        'details' => sprintf( '%d pages point their canonical to homepage', $pages_to_home ),
        'why' => 'This may indicate duplicate content or misconfiguration',
      ];
      $has_warnings = true;
    } elseif ( $pages_to_home > 0 ) {
      $checks[] = [
        'check' => 'Pages Canonicalizing to Homepage',
        'status' => 'pass',
        'details' => sprintf( '%d pages (within normal range)', $pages_to_home ),
      ];
    } else {
      $checks[] = [
        'check' => 'Pages Canonicalizing to Homepage',
        'status' => 'pass',
        'details' => 'No pages canonicalize to homepage',
      ];
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only diagnostics sample query.
    $canonical_rows = $wpdb->get_results(
      $wpdb->prepare(
        "
          SELECT p.ID, pm.meta_value AS canonical_url
          FROM {$wpdb->postmeta} pm
          INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
          WHERE pm.meta_key = %s
          AND pm.meta_value != ''
          AND p.post_status = %s
          LIMIT 100
        ",
        '_ASNERISSEO_canonical',
        'publish'
      )
    );

    $canonical_map = [];
    foreach ( (array) $canonical_rows as $row ) {
      if ( ! isset( $row->ID, $row->canonical_url ) ) {
        continue;
      }

      $canonical_map[ (int) $row->ID ] = self::normalize_diagnostics_url( $row->canonical_url );
    }

    $loop_detected = false;
    foreach ( $canonical_map as $post_id => $canonical_url ) {
      $permalink = get_permalink( $post_id );
      if ( ! $permalink ) {
        continue;
      }

      $normalized_permalink = self::normalize_diagnostics_url( $permalink );
      $reverse_match = array_search( $normalized_permalink, $canonical_map, true );
      if ( false !== $reverse_match && (int) $reverse_match !== (int) $post_id ) {
        $loop_detected = true;
        break;
      }
    }

    if ( $loop_detected ) {
      $checks[] = [
        'check' => 'Canonical Loops',
        'status' => 'conflict',
        'details' => 'Canonical loop detected (pages pointing to each other)',
        'why' => 'This creates ambiguity about which page is canonical',
      ];
      $has_conflicts = true;
    } else {
      $checks[] = [
        'check' => 'Canonical Loops',
        'status' => 'pass',
        'details' => 'No canonical loops detected',
      ];
    }

    $redirected_canonicals = 0;
    $sampled_canonicals = array_slice( (array) $canonical_rows, 0, 10 );
    foreach ( $sampled_canonicals as $row ) {
      if ( empty( $row->canonical_url ) ) {
        continue;
      }

      $head_response = wp_remote_head(
        $row->canonical_url,
        [
          'timeout' => 3,
          'redirection' => 0,
          'sslverify' => true,
        ]
      );
      if ( is_wp_error( $head_response ) ) {
        continue;
      }

      $status_code = wp_remote_retrieve_response_code( $head_response );
      if ( in_array( (int) $status_code, [ 301, 302, 307, 308 ], true ) ) {
        $redirected_canonicals++;
      }
    }

    $sampled_count = count( $sampled_canonicals );
    if ( $redirected_canonicals > 0 ) {
      $checks[] = [
        'check' => 'Canonicals to Redirected URLs',
        'status' => 'warning',
        'details' => sprintf( '%d of %d sampled canonicals redirect', $redirected_canonicals, $sampled_count ),
        'why' => 'Canonical URLs should point to the final destination',
      ];
      if ( ! $has_conflicts ) {
        $has_warnings = true;
      }
    } elseif ( $sampled_count > 0 ) {
      $checks[] = [
        'check' => 'Canonicals to Redirected URLs',
        'status' => 'pass',
        'details' => 'Sampled canonicals point to final URLs',
      ];
    } else {
      $checks[] = [
        'check' => 'Canonicals to Redirected URLs',
        'status' => 'pass',
        'details' => 'No custom canonicals to check',
      ];
    }

    $site_protocol = wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );
    $opposite_protocol_like = 'https' === $site_protocol ? 'http://%' : 'https://%';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate query for diagnostics.
    $mixed_protocol = (int) $wpdb->get_var(
      $wpdb->prepare(
        "
          SELECT COUNT(*)
          FROM {$wpdb->postmeta} pm
          INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
          WHERE pm.meta_key = %s
          AND pm.meta_value LIKE %s
          AND p.post_status = %s
        ",
        '_ASNERISSEO_canonical',
        $opposite_protocol_like,
        'publish'
      )
    );

    if ( $mixed_protocol > 0 ) {
      $checks[] = [
        'check' => 'Mixed Protocol (http/https)',
        'status' => 'warning',
        'details' => sprintf( '%d canonicals use different protocol than site', $mixed_protocol ),
        'why' => 'All canonicals should use consistent protocol (https recommended)',
      ];
      if ( ! $has_conflicts ) {
        $has_warnings = true;
      }
    } else {
      $checks[] = [
        'check' => 'Mixed Protocol (http/https)',
        'status' => 'pass',
        'details' => 'Consistent protocol usage',
      ];
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate query for diagnostics.
    $total_posts = (int) $wpdb->get_var(
      $wpdb->prepare(
        "
          SELECT COUNT(*)
          FROM {$wpdb->posts}
          WHERE post_status = %s
          AND post_type IN ('post', 'page')
        ",
        'publish'
      )
    );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Read-only aggregate query for diagnostics.
    $posts_with_custom_canonical = (int) $wpdb->get_var(
      $wpdb->prepare(
        "
          SELECT COUNT(*)
          FROM {$wpdb->postmeta}
          WHERE meta_key = %s
          AND meta_value != ''
        ",
        '_ASNERISSEO_canonical'
      )
    );

    if ( $total_posts > 0 && $posts_with_custom_canonical > 0 ) {
      $usage_percent = (int) round( ( $posts_with_custom_canonical / $total_posts ) * 100 );
      $checks[] = [
        'check' => 'Custom Canonical Usage',
        'status' => 'pass',
        'details' => sprintf( '%d of %d posts (%d%%) have custom canonicals', $posts_with_custom_canonical, $total_posts, $usage_percent ),
      ];
    } else {
      $checks[] = [
        'check' => 'Custom Canonical Usage',
        'status' => 'pass',
        'details' => 'Using default WordPress permalinks as canonicals',
      ];
    }

    return [
      'checks' => $checks,
      'has_conflicts' => $has_conflicts,
      'has_warnings' => $has_warnings,
    ];
  }

  public static function get_site_diagnostics( WP_REST_Request $request ) {
    unset( $request );

    $sitemap = [
      'found' => false,
      'url' => __( 'Not available', 'asneris-seo-toolkit' ),
      'http_status' => 0,
      'http_message' => __( 'Check unavailable', 'asneris-seo-toolkit' ),
      'in_robots' => false,
      'robots_message' => __( 'Check unavailable', 'asneris-seo-toolkit' ),
      'controller' => __( 'Unknown', 'asneris-seo-toolkit' ),
      'error' => '',
    ];
    $duplicates = [
      'active_plugins' => [],
      'duplicates' => [],
      'error' => '',
    ];
    $robots = [
      'status' => 'warning',
      'checks' => [],
      'error' => '',
    ];
    $canonical = [
      'checks' => [],
      'has_conflicts' => false,
      'has_warnings' => false,
      'error' => '',
    ];
    $summary = [
      'passed' => 0,
      'warnings' => 0,
      'conflicts' => 0,
      'last_checked' => null,
      'error' => '',
    ];

    try {
      $raw_sitemap = ASNERISSEO_Validation::check_sitemap_visibility();
      if ( is_array( $raw_sitemap ) ) {
        $sitemap = array_merge( $sitemap, $raw_sitemap );
      }
    } catch ( Throwable $e ) {
      $sitemap['error'] = sanitize_text_field( $e->getMessage() );
      $sitemap['http_message'] = __( 'Sitemap check failed (continuing with other diagnostics).', 'asneris-seo-toolkit' );
    }

    try {
      $raw_duplicates = ASNERISSEO_Validation::detect_duplicate_outputs();
      if ( is_array( $raw_duplicates ) ) {
        $duplicates = array_merge( $duplicates, $raw_duplicates );
      }
    } catch ( Throwable $e ) {
      $duplicates['error'] = sanitize_text_field( $e->getMessage() );
    }

    try {
      $raw_robots = ASNERISSEO_Robots::get_validation_results();
      if ( is_array( $raw_robots ) ) {
        $robots = array_merge( $robots, $raw_robots );
      }
    } catch ( Throwable $e ) {
      $robots['error'] = sanitize_text_field( $e->getMessage() );
    }

    try {
      $raw_canonical = self::build_canonical_consistency_diagnostics();
      if ( is_array( $raw_canonical ) ) {
        $canonical = array_merge( $canonical, $raw_canonical );
      }
    } catch ( Throwable $e ) {
      $canonical['error'] = sanitize_text_field( $e->getMessage() );
      $canonical['checks'] = [
        [
          'check' => 'Canonical consistency diagnostics',
          'status' => 'warning',
          'details' => __( 'Canonical checks could not be fully loaded.', 'asneris-seo-toolkit' ),
          'why' => __( 'Review PHP error logs and try again.', 'asneris-seo-toolkit' ),
        ],
      ];
      $canonical['has_warnings'] = true;
    }

    try {
      $raw_summary = ASNERISSEO_Validation::get_saved_results();
      if ( is_array( $raw_summary ) ) {
        $summary = array_merge( $summary, $raw_summary );
      }
    } catch ( Throwable $e ) {
      $summary['error'] = sanitize_text_field( $e->getMessage() );
    }

    return rest_ensure_response(
      [
        'sitemap' => $sitemap,
        'duplicates' => $duplicates,
        'robots' => $robots,
        'canonical' => $canonical,
        'summary' => $summary,
      ]
    );
  }

  public static function run_site_diagnostics_url_check( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $url = isset( $incoming['url'] ) ? esc_url_raw( wp_unslash( (string) $incoming['url'] ) ) : '';
    if ( '' === $url ) {
      return new WP_Error(
        'asnerisseo_site_diagnostics_url_missing',
        __( 'No URL provided.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $analysis = ASNERISSEO_Validation::analyze_url( $url );
    if ( ! is_array( $analysis ) || isset( $analysis['error'] ) ) {
      return new WP_Error(
        'asnerisseo_site_diagnostics_url_failed',
        is_array( $analysis ) && isset( $analysis['error'] )
          ? sanitize_text_field( (string) $analysis['error'] )
          : __( 'Failed to analyze URL.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    return rest_ensure_response(
      [
        'url' => esc_url_raw( $analysis['url'] ?? $url ),
        'analysis' => $analysis,
      ]
    );
  }

  public static function get_robots( WP_REST_Request $request ) {
    unset( $request );

    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
      WP_Filesystem();
    }

    $robots_file = ABSPATH . 'robots.txt';
    $content = file_exists( $robots_file ) ? (string) $wp_filesystem->get_contents( $robots_file ) : '';
    if ( '' === $content ) {
      $content = self::get_default_robots_content();
    }

    return rest_ensure_response(
      [
        'content' => $content,
        'validation' => ASNERISSEO_Robots::get_validation_results(),
      ]
    );
  }

  public static function save_robots( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $content = isset( $incoming['content'] ) ? wp_unslash( (string) $incoming['content'] ) : '';
    $validation_errors = self::validate_robots_content( $content );
    if ( ! empty( $validation_errors ) ) {
      return new WP_Error(
        'asnerisseo_robots_validation_failed',
        __( 'Robots content validation failed.', 'asneris-seo-toolkit' ),
        [
          'status' => 400,
          'errors' => $validation_errors,
        ]
      );
    }

    $content = sanitize_textarea_field( $content );

    global $wp_filesystem;
    if ( empty( $wp_filesystem ) ) {
      require_once ABSPATH . 'wp-admin/includes/file.php';
      WP_Filesystem();
    }

    $saved = $wp_filesystem->put_contents( ABSPATH . 'robots.txt', $content, FS_CHMOD_FILE );
    if ( false === $saved ) {
      return new WP_Error(
        'asnerisseo_robots_save_failed',
        __( 'Failed to save robots.txt. Check filesystem permissions.', 'asneris-seo-toolkit' ),
        [ 'status' => 500 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( 'robots.txt saved successfully.', 'asneris-seo-toolkit' ),
        'content' => $content,
        'validation' => ASNERISSEO_Robots::get_validation_results(),
      ]
    );
  }

  public static function get_bulk_edit_content( WP_REST_Request $request ) {
    $post_type = sanitize_key( (string) $request->get_param( 'postType' ) );
    if ( '' === $post_type ) {
      $post_type = 'post';
    }

    $indexing = sanitize_key( (string) $request->get_param( 'indexing' ) );
    if ( ! in_array( $indexing, [ 'all', 'indexed', 'noindex' ], true ) ) {
      $indexing = 'all';
    }

    $search_query = sanitize_text_field( (string) $request->get_param( 'search' ) );
    $per_page = absint( $request->get_param( 'perPage' ) );
    if ( $per_page < 1 ) {
      $per_page = 50;
    }
    $per_page = min( $per_page, 200 );

    $page_number = absint( $request->get_param( 'page' ) );
    if ( $page_number < 1 ) {
      $page_number = 1;
    }

    $post_types = get_post_types( [ 'public' => true ], 'objects' );
    if ( ! isset( $post_types[ $post_type ] ) ) {
      $post_type = 'post';
    }

    $args = [
      'post_type' => $post_type,
      'post_status' => 'publish',
      'posts_per_page' => $per_page,
      'paged' => $page_number,
      'orderby' => 'date',
      'order' => 'DESC',
      'no_found_rows' => false,
      'ignore_sticky_posts' => true,
      's' => $search_query,
    ];

    if ( 'indexed' === $indexing ) {
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter indexed and default-indexed posts in bulk editor.
      $args['meta_query'] = [
        'relation' => 'OR',
        [ 'key' => '_ASNERISSEO_robots_index', 'compare' => 'NOT EXISTS' ],
        [ 'key' => '_ASNERISSEO_robots_index', 'value' => 'index' ],
      ];
    } elseif ( 'noindex' === $indexing ) {
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to filter noindex posts in bulk editor.
      $args['meta_query'] = [
        [ 'key' => '_ASNERISSEO_robots_index', 'value' => 'noindex' ],
      ];
    }

    $posts_query = new WP_Query( $args );
    $total_items = (int) $posts_query->found_posts;
    $total_pages = max( 1, (int) $posts_query->max_num_pages );
    $items = [];
    if ( $posts_query->have_posts() ) {
      $post_ids = wp_list_pluck( $posts_query->posts, 'ID' );
      update_postmeta_cache( $post_ids );
    }

    foreach ( $posts_query->posts as $post ) {
      $post_id = (int) $post->ID;
      $robots_index = get_post_meta( $post_id, '_ASNERISSEO_robots_index', true );
      if ( '' === $robots_index ) {
        $robots_index = 'index';
      }

      $seo_title = (string) get_post_meta( $post_id, '_ASNERISSEO_title', true );
      $seo_description = (string) get_post_meta( $post_id, '_ASNERISSEO_description', true );
      $title_template_preview = (string) ASNERISSEO_Templates::generate_title( $post );
      $description_template_preview = (string) ASNERISSEO_Templates::generate_description( $post );

      $items[] = [
        'postId' => $post_id,
        'title' => get_the_title( $post_id ),
        'url' => esc_url_raw( get_permalink( $post_id ) ?: '' ),
        'date' => get_post_time( 'Y-m-d', false, $post_id ),
        'excerpt' => wp_strip_all_tags( (string) get_the_excerpt( $post_id ) ),
        'author' => (string) get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
        'term' => (string) wp_strip_all_tags( get_the_term_list( $post_id, 'category', '', ', ', '' ) ?: '' ),
        'metaTitle' => $seo_title,
        'seoTitle' => $seo_title,
        'seoDescription' => $seo_description,
        'titleTemplatePreview' => $title_template_preview,
        'descriptionTemplatePreview' => $description_template_preview,
        'isUsingDefaultTitleTemplate' => '' === trim( $seo_title ) && '' !== trim( $title_template_preview ),
        'isUsingDefaultDescriptionTemplate' => '' === trim( $seo_description ) && '' !== trim( $description_template_preview ),
        'robotsIndex' => in_array( $robots_index, [ 'index', 'noindex' ], true ) ? $robots_index : 'index',
      ];
    }

    wp_reset_postdata();

    return rest_ensure_response(
      [
        'total' => $total_items,
        'filters' => [
          'postType' => $post_type,
          'indexing' => $indexing,
          'search' => $search_query,
          'perPage' => $per_page,
          'postTypes' => array_map(
            static function( $pt ) {
              return [
                'value' => $pt->name,
                'label' => $pt->labels->name,
              ];
            },
            array_values( $post_types )
          ),
        ],
        'pagination' => [
          'page' => $page_number,
          'perPage' => $per_page,
          'totalPages' => $total_pages,
          'hasPrev' => $page_number > 1,
          'hasNext' => $page_number < $total_pages,
        ],
        'items' => $items,
      ]
    );
  }

  public static function save_bulk_edit_content( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $updates = isset( $incoming['updates'] ) && is_array( $incoming['updates'] ) ? $incoming['updates'] : [];
    if ( empty( $updates ) ) {
      return new WP_Error(
        'asnerisseo_bulk_edit_no_updates',
        __( 'No updates provided.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $validation_errors = [];
    foreach ( $updates as $update ) {
      if ( ! is_array( $update ) ) {
        continue;
      }

      $post_id = absint( $update['postId'] ?? 0 );
      if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
        continue;
      }

      $seo_title = isset( $update['seoTitle'] ) ? wp_unslash( (string) $update['seoTitle'] ) : '';
      if ( strlen( $seo_title ) > 100 ) {
        /* translators: %d: Post ID. */
        $validation_errors[] = sprintf( __( 'SEO title exceeds 100 characters for post ID %d.', 'asneris-seo-toolkit' ), $post_id );
      }

      $seo_description = isset( $update['seoDescription'] ) ? wp_unslash( (string) $update['seoDescription'] ) : '';
      if ( strlen( $seo_description ) > 320 ) {
        /* translators: %d: Post ID. */
        $validation_errors[] = sprintf( __( 'SEO description exceeds 320 characters for post ID %d.', 'asneris-seo-toolkit' ), $post_id );
      }

      $robots_index = isset( $update['robotsIndex'] ) ? sanitize_text_field( (string) $update['robotsIndex'] ) : 'index';
      if ( ! in_array( $robots_index, [ 'index', 'noindex' ], true ) ) {
        /* translators: %d: Post ID. */
        $validation_errors[] = sprintf( __( 'Invalid robots index value for post ID %d.', 'asneris-seo-toolkit' ), $post_id );
      }
    }

    if ( ! empty( $validation_errors ) ) {
      return new WP_Error(
        'asnerisseo_bulk_edit_validation_failed',
        __( 'Bulk edit validation failed.', 'asneris-seo-toolkit' ),
        [
          'status' => 400,
          'errors' => $validation_errors,
        ]
      );
    }

    $updated = 0;
    foreach ( $updates as $update ) {
      if ( ! is_array( $update ) ) {
        continue;
      }

      $post_id = absint( $update['postId'] ?? 0 );
      if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
        continue;
      }

      $seo_title = sanitize_text_field( (string) ( $update['seoTitle'] ?? '' ) );
      $seo_description = sanitize_textarea_field( (string) ( $update['seoDescription'] ?? '' ) );
      $robots_index = sanitize_text_field( (string) ( $update['robotsIndex'] ?? 'index' ) );
      if ( ! in_array( $robots_index, [ 'index', 'noindex' ], true ) ) {
        $robots_index = 'index';
      }

      if ( '' !== $seo_title ) {
        update_post_meta( $post_id, '_ASNERISSEO_title', $seo_title );
      } else {
        delete_post_meta( $post_id, '_ASNERISSEO_title' );
      }

      if ( '' !== $seo_description ) {
        update_post_meta( $post_id, '_ASNERISSEO_description', $seo_description );
      } else {
        delete_post_meta( $post_id, '_ASNERISSEO_description' );
      }

      update_post_meta( $post_id, '_ASNERISSEO_robots_index', $robots_index );
      $updated++;
    }

    return rest_ensure_response(
      [
        'success' => true,
        'updated' => $updated,
        /* translators: %d: Number of posts/pages updated. */
        'message' => sprintf( __( '%d post/page(s) were updated successfully.', 'asneris-seo-toolkit' ), $updated ),
      ]
    );
  }

  public static function get_redirects_overview( WP_REST_Request $request ) {
    unset( $request );
    return rest_ensure_response( self::build_redirects_payload() );
  }

  public static function add_redirect( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $from = isset( $incoming['from'] ) ? wp_unslash( (string) $incoming['from'] ) : '';
    $to = isset( $incoming['to'] ) ? wp_unslash( (string) $incoming['to'] ) : '';
    $code = isset( $incoming['code'] ) ? (int) $incoming['code'] : 301;
    if ( ! in_array( $code, [ 301, 302, 307 ], true ) ) {
      $code = 301;
    }

    if ( ! ASNERISSEO_Redirects::add_redirect( $from, $to, $code, 'manual' ) ) {
      return new WP_Error(
        'asnerisseo_redirect_add_failed',
        __( 'Failed to add redirect. Please verify source and target values.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( 'Redirect added successfully.', 'asneris-seo-toolkit' ),
        'data' => self::build_redirects_payload(),
      ]
    );
  }

  public static function update_redirect( WP_REST_Request $request ) {
    $index = absint( $request['index'] );
    $redirects = ASNERISSEO_Redirects::get_redirects();

    if ( ! isset( $redirects[ $index ] ) ) {
      return new WP_Error(
        'asnerisseo_redirect_not_found',
        __( 'Redirect not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $existing = $redirects[ $index ];
    $from = isset( $incoming['from'] ) ? wp_unslash( (string) $incoming['from'] ) : ( $existing['from'] ?? '' );
    $to = isset( $incoming['to'] ) ? wp_unslash( (string) $incoming['to'] ) : ( $existing['to'] ?? '' );
    $code = isset( $incoming['code'] ) ? (int) $incoming['code'] : (int) ( $existing['code'] ?? 301 );
    if ( ! in_array( $code, [ 301, 302, 307 ], true ) ) {
      $code = 301;
    }
    $enabled = isset( $incoming['enabled'] ) ? ! empty( $incoming['enabled'] ) : ! empty( $existing['enabled'] );

    if ( ! ASNERISSEO_Redirects::update_redirect( $index, $from, $to, $code, $enabled ) ) {
      return new WP_Error(
        'asnerisseo_redirect_update_failed',
        __( 'Failed to update redirect. Please verify source and target values.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( 'Redirect updated successfully.', 'asneris-seo-toolkit' ),
        'data' => self::build_redirects_payload(),
      ]
    );
  }

  public static function delete_redirect( WP_REST_Request $request ) {
    $index = absint( $request['index'] );

    if ( ! ASNERISSEO_Redirects::delete_redirect( $index ) ) {
      return new WP_Error(
        'asnerisseo_redirect_delete_failed',
        __( 'Failed to delete redirect. It may no longer exist.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( 'Redirect deleted successfully.', 'asneris-seo-toolkit' ),
        'data' => self::build_redirects_payload(),
      ]
    );
  }

  public static function toggle_redirect( WP_REST_Request $request ) {
    $index = absint( $request['index'] );

    if ( ! ASNERISSEO_Redirects::toggle_redirect( $index ) ) {
      return new WP_Error(
        'asnerisseo_redirect_toggle_failed',
        __( 'Failed to update redirect status. It may no longer exist.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( 'Redirect status updated successfully.', 'asneris-seo-toolkit' ),
        'data' => self::build_redirects_payload(),
      ]
    );
  }

  public static function clear_auto_redirects( WP_REST_Request $request ) {
    unset( $request );

    if ( ! ASNERISSEO_Redirects::clear_auto_redirects() ) {
      return new WP_Error(
        'asnerisseo_redirect_clear_auto_failed',
        __( 'Failed to clear auto redirects.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( 'Automatic redirects cleared successfully.', 'asneris-seo-toolkit' ),
        'data' => self::build_redirects_payload(),
      ]
    );
  }

  public static function get_404_logs( WP_REST_Request $request ) {
    $args = [
      'page' => absint( $request->get_param( 'page' ) ?: 1 ),
      'per_page' => absint( $request->get_param( 'per_page' ) ?: 20 ),
      'search' => sanitize_text_field( (string) $request->get_param( 'search' ) ),
      'priority_filter' => sanitize_text_field( (string) $request->get_param( 'priority_filter' ) ?: 'all' ),
      'recommendation_filter' => sanitize_text_field( (string) $request->get_param( 'recommendation_filter' ) ?: 'all' ),
      'status' => sanitize_key( (string) $request->get_param( 'status' ) ?: 'active' ),
      'date_from' => sanitize_text_field( (string) $request->get_param( 'date_from' ) ),
      'date_to' => sanitize_text_field( (string) $request->get_param( 'date_to' ) ),
      'sort_by' => sanitize_key( (string) $request->get_param( 'sort_by' ) ?: 'last_seen' ),
      'sort_dir' => sanitize_text_field( (string) $request->get_param( 'sort_dir' ) ?: 'desc' ),
    ];

    return rest_ensure_response( ASNERISSEO_404_Monitor::get_logs( $args ) );
  }

  public static function get_404_log( WP_REST_Request $request ) {
    $id = absint( $request['id'] );
    $row = ASNERISSEO_404_Monitor::get_log_by_id( $id );
    if ( ! $row ) {
      return new WP_Error(
        'asnerisseo_404_log_not_found',
        __( '404 log entry not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    return rest_ensure_response( $row );
  }

  public static function patch_404_log( WP_REST_Request $request ) {
    $id = absint( $request['id'] );
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $payload = [];
    if ( isset( $incoming['status'] ) ) {
      $payload['status'] = sanitize_key( (string) $incoming['status'] );
    }
    if ( isset( $incoming['redirect_target'] ) ) {
      $payload['redirect_target'] = esc_url_raw( (string) $incoming['redirect_target'] );
      if ( '' === $payload['redirect_target'] ) {
        $payload['redirect_target'] = sanitize_text_field( (string) $incoming['redirect_target'] );
      }
    }
    if ( isset( $incoming['redirect_code'] ) ) {
      $redirect_code = (int) $incoming['redirect_code'];
      if ( ! in_array( $redirect_code, [ 301, 302, 307 ], true ) ) {
        $redirect_code = 301;
      }
      $payload['redirect_code'] = $redirect_code;
    }

    if ( isset( $payload['status'] ) && 'redirected' === $payload['status'] && empty( $payload['redirect_target'] ) ) {
      return new WP_Error(
        'asnerisseo_404_redirect_target_required',
        __( 'Redirect target is required when marking as redirected.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    if ( ! ASNERISSEO_404_Monitor::update_log( $id, $payload ) ) {
      return new WP_Error(
        'asnerisseo_404_log_update_failed',
        __( 'Failed to update 404 log entry.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( '404 log entry updated successfully.', 'asneris-seo-toolkit' ),
        'item' => ASNERISSEO_404_Monitor::get_log_by_id( $id ),
      ]
    );
  }

  public static function delete_404_log( WP_REST_Request $request ) {
    $id = absint( $request['id'] );

    if ( ! ASNERISSEO_404_Monitor::delete_log( $id ) ) {
      return new WP_Error(
        'asnerisseo_404_log_delete_failed',
        __( 'Failed to delete 404 log entry.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( '404 log entry deleted successfully.', 'asneris-seo-toolkit' ),
      ]
    );
  }

  public static function bulk_404_logs( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $ids = isset( $incoming['ids'] ) && is_array( $incoming['ids'] ) ? array_map( 'absint', $incoming['ids'] ) : [];
    $action = isset( $incoming['action'] ) ? sanitize_key( (string) $incoming['action'] ) : '';
    $redirect_target = isset( $incoming['redirect_target'] ) ? esc_url_raw( (string) $incoming['redirect_target'] ) : '';

    if ( empty( $ids ) ) {
      return new WP_Error(
        'asnerisseo_404_bulk_ids_missing',
        __( 'At least one log ID is required for bulk actions.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    if ( ! in_array( $action, [ 'delete', 'ignore', 'activate', 'redirect', 'analyze' ], true ) ) {
      return new WP_Error(
        'asnerisseo_404_bulk_action_invalid',
        __( 'Invalid bulk action for 404 logs.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    if ( 'redirect' === $action && '' === $redirect_target ) {
      return new WP_Error(
        'asnerisseo_404_bulk_redirect_target_required',
        __( 'Redirect target is required for bulk redirect action.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $performance_scope = null;
    if ( 'analyze' === $action ) {
      $performance_scope = self::start_performance_scope(
        '404_analyzer',
        [
          'limit' => count( $ids ),
          'advisoryMinHeadroomBytes' => 24 * 1024 * 1024,
          'estimatedBytesPerUnit' => 64 * 1024,
        ]
      );
    }

    $result = ASNERISSEO_404_Monitor::bulk_action( $ids, $action, $redirect_target );

    $performance = null;
    if ( 'analyze' === $action ) {
      $analysis_processed = isset( $result['processed'] ) ? (int) $result['processed'] : (int) ( $result['updated'] ?? 0 );
      $performance = self::finish_performance_scope(
        $performance_scope,
        [
          'recordsProcessed' => $analysis_processed,
          'uniqueUrls' => (int) ( $result['unique_urls'] ?? 0 ),
          'suggestedRedirects' => (int) ( $result['suggested_redirects'] ?? 0 ),
          'ignoredRecords' => (int) ( $result['ignored_records'] ?? 0 ),
          'rulesEvaluated' => (int) ( $result['rules_evaluated'] ?? 0 ),
          'warnings' => 0,
          'errors' => 0,
        ],
        [
          'recordsRead' => $analysis_processed,
          'recordsUpdated' => (int) ( $result['updated'] ?? 0 ),
          'recordsInserted' => 0,
        ]
      );
    }

    return rest_ensure_response(
      [
        'success' => true,
        /* translators: 1: Updated row count, 2: Failed row count. */
        'message' => sprintf( __( 'Bulk action completed. Updated: %1$d, Failed: %2$d.', 'asneris-seo-toolkit' ), (int) $result['updated'], (int) $result['failed'] ),
        'result' => $result,
        'performance' => $performance,
      ]
    );
  }

  public static function get_404_logs_stats( WP_REST_Request $request ) {
    unset( $request );
    return rest_ensure_response( ASNERISSEO_404_Monitor::get_stats() );
  }

  public static function analyze_404_logs( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $limit = isset( $incoming['limit'] ) ? absint( $incoming['limit'] ) : 200;
    $performance_scope = self::start_performance_scope(
      '404_analyzer',
      [
        'limit' => $limit,
        'advisoryMinHeadroomBytes' => 24 * 1024 * 1024,
        'estimatedBytesPerUnit' => 64 * 1024,
      ]
    );
    $result = ASNERISSEO_404_Monitor::run_priority_analysis( $limit, true );
    $analysis_processed = isset( $result['processed'] ) ? (int) $result['processed'] : 0;
    $performance = self::finish_performance_scope(
      $performance_scope,
      [
        'recordsProcessed' => $analysis_processed,
        'uniqueUrls' => (int) ( $result['unique_urls'] ?? 0 ),
        'suggestedRedirects' => (int) ( $result['suggested_redirects'] ?? 0 ),
        'ignoredRecords' => (int) ( $result['ignored_records'] ?? 0 ),
        'rulesEvaluated' => (int) ( $result['rules_evaluated'] ?? 0 ),
        'warnings' => 0,
        'errors' => 0,
      ],
      [
        'recordsRead' => $analysis_processed,
        'recordsUpdated' => (int) ( $result['updated'] ?? 0 ),
        'recordsInserted' => 0,
      ]
    );

    return rest_ensure_response(
      [
        'success' => true,
        'message' => __( '404 analysis completed successfully.', 'asneris-seo-toolkit' ),
        'result' => $result,
        'performance' => $performance,
      ]
    );
  }

  public static function get_404_logs_settings( WP_REST_Request $request ) {
    unset( $request );
    return rest_ensure_response( ASNERISSEO_404_Monitor::get_monitor_settings() );
  }

  public static function update_404_logs_settings( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $has_enabled = array_key_exists( 'enabled', $incoming );
    $has_collecting = array_key_exists( 'collecting', $incoming );
    $has_throttle_limit = array_key_exists( 'throttle_limit', $incoming );
    $has_throttle_window = array_key_exists( 'throttle_window', $incoming );
    $has_exclude_urls = array_key_exists( 'exclude_urls', $incoming );
    $has_exclude_keywords = array_key_exists( 'exclude_keywords', $incoming );
    $has_ignore_query_params = array_key_exists( 'ignore_query_params', $incoming );
    $has_analysis_cron_frequency = array_key_exists( 'analysis_cron_frequency', $incoming );
    $acknowledge_first_time = ! empty( $incoming['acknowledge_first_time'] );

    $enabled = null;
    $collecting = null;
    $throttle_limit = null;
    $throttle_window = null;
    $exclude_urls = null;
    $exclude_keywords = null;
    $ignore_query_params = null;
    $analysis_cron_frequency = null;

    if ( $has_enabled ) {
      $enabled = ! empty( $incoming['enabled'] );
    }

    if ( $has_collecting ) {
      $collecting = ! empty( $incoming['collecting'] );
    }

    if ( $has_throttle_limit ) {
      $throttle_limit = absint( $incoming['throttle_limit'] );
    }

    if ( $has_throttle_window ) {
      $throttle_window = absint( $incoming['throttle_window'] );
    }

    if ( $has_exclude_urls ) {
      $exclude_urls = sanitize_textarea_field( (string) $incoming['exclude_urls'] );
    }

    if ( $has_exclude_keywords ) {
      $exclude_keywords = sanitize_textarea_field( (string) $incoming['exclude_keywords'] );
    }

    if ( $has_ignore_query_params ) {
      $ignore_query_params = ! empty( $incoming['ignore_query_params'] );
    }

    if ( $has_analysis_cron_frequency ) {
      $analysis_cron_frequency = sanitize_key( (string) $incoming['analysis_cron_frequency'] );
    }

    return rest_ensure_response(
      ASNERISSEO_404_Monitor::update_monitor_settings( $enabled, $collecting, $acknowledge_first_time, $throttle_limit, $throttle_window, null, null, null, null, null, $exclude_urls, $exclude_keywords, $ignore_query_params, $analysis_cron_frequency )
    );
  }

  public static function export_404_logs( WP_REST_Request $request ) {
    $args = [
      'search' => sanitize_text_field( (string) $request->get_param( 'search' ) ),
      'priority_filter' => sanitize_key( (string) $request->get_param( 'priority_filter' ) ?: 'all' ),
      'recommendation_filter' => sanitize_text_field( (string) $request->get_param( 'recommendation_filter' ) ?: 'all' ),
      'status' => sanitize_key( (string) $request->get_param( 'status' ) ?: 'all' ),
      'date_from' => sanitize_text_field( (string) $request->get_param( 'date_from' ) ),
      'date_to' => sanitize_text_field( (string) $request->get_param( 'date_to' ) ),
      'sort_by' => sanitize_key( (string) $request->get_param( 'sort_by' ) ?: 'last_seen' ),
      'sort_dir' => sanitize_text_field( (string) $request->get_param( 'sort_dir' ) ?: 'desc' ),
    ];

    $payload = ASNERISSEO_404_Monitor::export_logs( $args );
    return rest_ensure_response( $payload );
  }

  private static function build_redirects_payload() {
    $redirects = ASNERISSEO_Redirects::get_redirects();
    if ( ! is_array( $redirects ) ) {
      $redirects = [];
    }

    $items = [];
    $active = 0;
    $automatic = 0;

    foreach ( $redirects as $index => $redirect ) {
      $from = isset( $redirect['from'] ) ? sanitize_text_field( $redirect['from'] ) : '';
      $to = isset( $redirect['to'] ) ? esc_url_raw( $redirect['to'] ) : '';
      if ( '' === $to ) {
        $to = isset( $redirect['to'] ) ? sanitize_text_field( $redirect['to'] ) : '';
      }

      $code = isset( $redirect['code'] ) ? (int) $redirect['code'] : 301;
      if ( ! in_array( $code, [ 301, 302, 307 ], true ) ) {
        $code = 301;
      }

      $enabled = ! empty( $redirect['enabled'] );
      $type = isset( $redirect['type'] ) ? sanitize_key( $redirect['type'] ) : 'manual';
      if ( ! in_array( $type, [ 'manual', 'auto' ], true ) ) {
        $type = 'manual';
      }

      if ( $enabled ) {
        $active++;
      }
      if ( 'auto' === $type ) {
        $automatic++;
      }

      $items[] = [
        'index' => (int) $index,
        'from' => $from,
        'to' => $to,
        'code' => $code,
        'enabled' => $enabled,
        'type' => $type,
        'created' => isset( $redirect['created'] ) ? sanitize_text_field( $redirect['created'] ) : '',
      ];
    }

    return [
      'stats' => [
        'total' => count( $items ),
        'active' => $active,
        'disabled' => count( $items ) - $active,
        'auto' => $automatic,
        'manual' => count( $items ) - $automatic,
      ],
      'items' => $items,
    ];
  }

  public static function get_editor_config( WP_REST_Request $request ) {
    unset( $request );

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );

    return rest_ensure_response(
      [
        'siteName'             => get_bloginfo( 'name' ),
        'titleSeparator'       => isset( $settings['title_separator'] ) ? sanitize_text_field( $settings['title_separator'] ) : '|',
        'titleTemplates'       => isset( $settings['title_templates'] ) && is_array( $settings['title_templates'] ) ? $settings['title_templates'] : [],
        'descriptionTemplates' => isset( $settings['description_templates'] ) && is_array( $settings['description_templates'] ) ? $settings['description_templates'] : [],
        'autoOpenWorkspaceFromMenu' => true,
      ]
    );
  }

  public static function get_post_seo( WP_REST_Request $request ) {
    $post_id = absint( $request['id'] );
    $post = get_post( $post_id );

    if ( ! $post ) {
      return new WP_Error(
        'asnerisseo_post_not_found',
        __( 'Post not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    if ( 'publish' !== get_post_status( $post ) ) {
      return new WP_Error(
        'asnerisseo_post_not_published',
        __( 'Diagnostics can only run on published content.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $meta = [];
    foreach ( ASNERISSEO_Meta::KEYS as $key => $type ) {
      $meta[ $key ] = get_post_meta( $post_id, $key, true );
      if ( '' === $meta[ $key ] ) {
        $meta[ $key ] = ASNERISSEO_Meta::default_for( $key );
      }
      if ( 'boolean' === $type ) {
        $meta[ $key ] = (bool) $meta[ $key ];
      }
    }

    $response = [
      'postId' => $post_id,
      'postType' => $post->post_type,
      'meta' => $meta,
    ];

    if ( class_exists( 'ASNERISSEO_Data_Interface_Normalizer' ) ) {
      $response['unifiedData'] = ASNERISSEO_Data_Interface_Normalizer::normalize_post_seo(
        $response,
        [
          'sourceFlow' => 'page_seo',
          'sourceEngine' => 'meta_store',
          'sourceMode' => 'published',
        ]
      );

      $contract = self::validate_unified_contract( $response, 'post_seo' );
      if ( is_wp_error( $contract ) ) {
        return $contract;
      }
    }

    return rest_ensure_response( $response );
  }

  public static function evaluate_draft_policy( WP_REST_Request $request ) {
    $params = $request->get_json_params();
    $params = is_array( $params ) ? $params : [];

    $post_id = isset( $params['postId'] ) ? absint( $params['postId'] ) : 0;
    if ( $post_id > 0 && ! current_user_can( 'edit_post', $post_id ) ) {
      return new WP_Error(
        'asnerisseo_forbidden_post',
        __( 'You do not have permission to evaluate this post.', 'asneris-seo-toolkit' ),
        [ 'status' => 403 ]
      );
    }

    $meta = isset( $params['meta'] ) && is_array( $params['meta'] ) ? $params['meta'] : [];

    $incoming_title = $params['postTitle'] ?? '';
    if ( is_array( $incoming_title ) ) {
      $incoming_title = (string) ( $incoming_title['raw'] ?? $incoming_title['rendered'] ?? '' );
    }
    $post_title = sanitize_text_field( (string) $incoming_title );

    $incoming_excerpt = $params['postExcerpt'] ?? '';
    if ( is_array( $incoming_excerpt ) ) {
      $incoming_excerpt = (string) ( $incoming_excerpt['raw'] ?? $incoming_excerpt['rendered'] ?? '' );
    }
    $post_excerpt = sanitize_textarea_field( (string) $incoming_excerpt );
    $content_raw = (string) ( $params['content'] ?? '' );
    $content_raw = wp_kses_post( $content_raw );

    $incoming_url = esc_url_raw( (string) ( $params['url'] ?? '' ) );
    $url = '' !== $incoming_url ? $incoming_url : ( $post_id > 0 ? (string) get_permalink( $post_id ) : '' );

    $site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

    $effective_title = sanitize_text_field( (string) ( $meta['_ASNERISSEO_title'] ?? $post_title ) );
    $effective_description = sanitize_textarea_field( (string) ( $meta['_ASNERISSEO_description'] ?? wp_strip_all_tags( $post_excerpt ) ) );
    $canonical = esc_url_raw( (string) ( $meta['_ASNERISSEO_canonical'] ?? '' ) );
    $robots_index = sanitize_key( (string) ( $meta['_ASNERISSEO_robots_index'] ?? 'index' ) );
    $robots_follow = sanitize_key( (string) ( $meta['_ASNERISSEO_robots_follow'] ?? 'follow' ) );
    $http_status = 0;
    $http_status_details = 'Draft policy live HTTP check could not determine status.';

    if ( '' !== $url ) {
      $draft_http_checks = ASNERISSEO_Diagnostics::http_test_checks( $url );
      if ( ! is_wp_error( $draft_http_checks ) ) {
        $http_status = self::extract_http_status_from_checks( $draft_http_checks, 0 );
        $http_status_row = self::get_check_row_by_label( $draft_http_checks, 'HTTP Status' );
        if ( is_array( $http_status_row ) ) {
          $http_status_details = sanitize_text_field( (string) ( $http_status_row['details'] ?? '' ) );
        }
      }
    }

    $word_count = self::count_words_from_html( $content_raw );
    $internal_links = self::count_internal_links_from_html( $content_raw, $site_host );

    $h1_count = 0;
    if ( preg_match_all( '/<h1\\b[^>]*>/i', $content_raw, $heading_matches ) ) {
      $h1_count = is_array( $heading_matches[0] ) ? count( $heading_matches[0] ) : 0;
    }

    $image_count = 0;
    $images_missing_alt = 0;
    if ( preg_match_all( '/<img\\b[^>]*>/i', $content_raw, $image_matches ) ) {
      $images = is_array( $image_matches[0] ) ? $image_matches[0] : [];
      $image_count = count( $images );
      foreach ( $images as $image_tag ) {
        if ( ! preg_match( '/\\balt\\s*=\\s*(["\'])(.*?)\\1/i', (string) $image_tag, $alt_match ) ) {
          $images_missing_alt++;
          continue;
        }

        $alt_text = trim( wp_strip_all_tags( html_entity_decode( (string) ( $alt_match[2] ?? '' ), ENT_QUOTES, 'UTF-8' ) ) );
        if ( '' === $alt_text ) {
          $images_missing_alt++;
        }
      }
    }

    $language_declaration = sanitize_text_field( (string) get_bloginfo( 'language' ) );

    $scores = self::calculate_page_overview_scores(
      [
        'runId' => sprintf( 'overview-draft-%d-%s', (int) $post_id, gmdate( 'YmdHis' ) ),
        'effectiveTitleLength' => strlen( $effective_title ),
        'effectiveDescriptionLength' => strlen( $effective_description ),
        'hasCanonical' => '' !== $canonical,
        'robotsIndex' => $robots_index,
        'robotsFollow' => $robots_follow,
        'contentRaw' => $content_raw,
        'contentWords' => $word_count,
        'imageCount' => $image_count,
        'imagesMissingAlt' => $images_missing_alt,
        'internalLinks' => $internal_links,
        'httpStatus' => $http_status,
        'hasHeading' => $h1_count > 0,
        'languageDeclaration' => $language_declaration,
        'siteName' => (string) get_bloginfo( 'name' ),
      ]
    );

    $seo_score = isset( $scores['seoScore'] ) ? (int) $scores['seoScore'] : 0;
    $ai_score = isset( $scores['aiScore'] ) ? (int) $scores['aiScore'] : 0;
    $health = 'poor';
    if ( $seo_score >= 85 ) {
      $health = 'good';
    } elseif ( $seo_score >= 65 ) {
      $health = 'warning';
    }

    $checks = [
      [
        'label' => 'SEO Title Length',
        'category' => 'search',
        'status' => ( strlen( $effective_title ) >= 30 && strlen( $effective_title ) <= 60 ) ? 'pass' : ( strlen( $effective_title ) > 0 ? 'warning' : 'fail' ),
        'result' => sprintf( '%d chars', (int) strlen( $effective_title ) ),
        'details' => 'Draft policy evaluation for title length.',
      ],
      [
        'label' => 'Meta Description Length',
        'category' => 'search',
        'status' => ( strlen( $effective_description ) >= 120 && strlen( $effective_description ) <= 160 ) ? 'pass' : ( strlen( $effective_description ) > 0 ? 'warning' : 'fail' ),
        'result' => sprintf( '%d chars', (int) strlen( $effective_description ) ),
        'details' => 'Draft policy evaluation for description length.',
      ],
      [
        'label' => 'Robots Meta',
        'category' => 'advanced',
        'status' => ( 'index' === $robots_index && 'follow' === $robots_follow ) ? 'pass' : 'warning',
        'result' => sprintf( '%s/%s', $robots_index, $robots_follow ),
        'details' => 'Draft policy evaluation for robots directives.',
      ],
      [
        'label' => 'HTTP Status',
        'category' => 'advanced',
        'status' => ( $http_status >= 200 && $http_status < 300 ) ? 'pass' : ( $http_status >= 300 && $http_status < 400 ? 'warning' : 'fail' ),
        'result' => $http_status,
        'details' => $http_status_details,
      ],
      [
        'label' => 'H1 Exists',
        'category' => 'quality',
        'status' => $h1_count > 0 ? 'pass' : 'warning',
        'result' => $h1_count > 0 ? 'Yes' : 'No',
        'details' => 'Draft policy evaluation for heading presence.',
      ],
      [
        'label' => 'Internal Links',
        'category' => 'links',
        'status' => $internal_links >= 2 ? 'pass' : ( 1 === $internal_links ? 'warning' : 'fail' ),
        'result' => $internal_links,
        'details' => 'Draft policy evaluation for internal links.',
      ],
      [
        'label' => 'Word Count',
        'category' => 'quality',
        'status' => $word_count >= 300 ? 'pass' : 'warning',
        'result' => $word_count,
        'details' => 'Draft policy evaluation for content depth.',
      ],
    ];

    $payload = self::attach_unified_diagnostics_payload(
      [
        'postId' => $post_id,
        'title' => $post_title,
        'url' => $url,
        'lastScanGmt' => gmdate( 'c' ),
        'source' => 'editor-policy-dirty',
        'scoreEngine' => 'weightage_policy_v4_1',
        'seoScore' => $seo_score,
        'aiScore' => $ai_score,
        'health' => $health,
        'issueGroups' => isset( $scores['issueGroups'] ) && is_array( $scores['issueGroups'] ) ? $scores['issueGroups'] : [],
        'checks' => $checks,
        'effectiveTitle' => $effective_title,
        'titleLength' => strlen( $effective_title ),
        'effectiveDescription' => $effective_description,
        'descriptionLength' => strlen( $effective_description ),
        'canonical' => $canonical,
        'hasCanonical' => '' !== $canonical,
        'robotsIndex' => $robots_index,
        'robotsFollow' => $robots_follow,
        'httpStatus' => $http_status,
        'contentWords' => $word_count,
        'h1Count' => $h1_count,
        'internalLinks' => $internal_links,
        'imageCount' => $image_count,
        'imagesMissingAlt' => $images_missing_alt,
        'overviewIssueRecords' => isset( $scores['overviewIssueRecords'] ) && is_array( $scores['overviewIssueRecords'] ) ? $scores['overviewIssueRecords'] : [],
        'overviewRunId' => isset( $scores['overviewRunId'] ) ? (string) $scores['overviewRunId'] : '',
        'seoScoreMessage' => isset( $scores['seoScoreMessage'] ) ? sanitize_text_field( (string) $scores['seoScoreMessage'] ) : '',
        'isPriority' => false,
      ],
      'editor_policy_dirty'
    );

    $contract = self::validate_unified_contract( $payload, 'diagnostics.editor_policy_dirty' );
    if ( is_wp_error( $contract ) ) {
      return $contract;
    }

    return rest_ensure_response( $payload );
  }

  public static function get_diagnostics( WP_REST_Request $request ) {
    $post_id = absint( $request['id'] );
    $post = get_post( $post_id );
    $is_priority = ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post_id );

    if ( ! $post ) {
      return new WP_Error(
        'asnerisseo_post_not_found',
        __( 'Post not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    if ( 'publish' !== get_post_status( $post ) ) {
      return new WP_Error(
        'asnerisseo_post_not_published',
        __( 'Diagnostics can only run on published content.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $url = get_permalink( $post_id );
    if ( ! $url ) {
      return new WP_Error(
        'asnerisseo_post_permalink_missing',
        __( 'Post URL could not be resolved.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
    $overview_item = self::build_page_diagnostics_overview_item( $post, $site_host );

    $force_refresh = ! empty( $request->get_param( 'force' ) );
    $no_store = ! empty( $request->get_param( 'no_store' ) ) || ! empty( $request->get_param( 'noStore' ) );
    if ( ! $no_store && ! $force_refresh && $is_priority ) {
      $snapshot_report = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post_id );
      if ( is_array( $snapshot_report ) && ! empty( $snapshot_report['checks'] ) ) {
        $payload = self::attach_unified_diagnostics_payload(
          [
            'postId' => $post_id,
            'url' => $url,
            'seoScore' => isset( $snapshot_report['seoScore'] ) ? (int) $snapshot_report['seoScore'] : 0,
            'aiScore' => isset( $snapshot_report['aiScore'] ) ? (int) $snapshot_report['aiScore'] : 0,
            'health' => isset( $snapshot_report['health'] ) ? sanitize_key( (string) $snapshot_report['health'] ) : 'warning',
            'issueGroups' => isset( $snapshot_report['issueGroups'] ) && is_array( $snapshot_report['issueGroups'] ) ? $snapshot_report['issueGroups'] : [],
            'overviewIssueRecords' => isset( $snapshot_report['overviewIssueRecords'] ) && is_array( $snapshot_report['overviewIssueRecords'] ) ? $snapshot_report['overviewIssueRecords'] : [],
            'aiIssueRecords' => isset( $snapshot_report['aiIssueRecords'] ) && is_array( $snapshot_report['aiIssueRecords'] ) ? $snapshot_report['aiIssueRecords'] : [],
            'aiCanonicalSignals' => isset( $snapshot_report['aiCanonicalSignals'] ) && is_array( $snapshot_report['aiCanonicalSignals'] ) ? $snapshot_report['aiCanonicalSignals'] : [],
            'tabIssueRecords' => isset( $snapshot_report['tabIssueRecords'] ) && is_array( $snapshot_report['tabIssueRecords'] ) ? $snapshot_report['tabIssueRecords'] : [],
            'overviewRunId' => isset( $snapshot_report['overviewRunId'] ) ? (string) $snapshot_report['overviewRunId'] : '',
            'seoScoreMessage' => isset( $snapshot_report['seoScoreMessage'] ) ? sanitize_text_field( (string) $snapshot_report['seoScoreMessage'] ) : '',
            'lastScanGmt' => (string) ( $snapshot_report['generatedAtGmt'] ?? get_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt', true ) ),
            'checks' => $snapshot_report['checks'],
            'source' => 'snapshot',
            'isPriority' => true,
          ],
          'snapshot'
        );

        $contract = self::validate_unified_contract( $payload, 'diagnostics.snapshot' );
        if ( is_wp_error( $contract ) ) {
          return $contract;
        }

        return rest_ensure_response(
          $payload
        );
      }
    }

    return self::run_page_diagnostics_scan( $request );
  }

  public static function run_page_diagnostics_scan( WP_REST_Request $request ) {
    $post_id = absint( $request['id'] );
    $post = get_post( $post_id );
    $force_refresh = ! empty( $request->get_param( 'force' ) );
    $no_store = ! empty( $request->get_param( 'no_store' ) ) || ! empty( $request->get_param( 'noStore' ) );
    $is_priority = ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post_id );
    $performance_scope = self::start_performance_scope(
      'page_diagnostics',
      [
        'limit' => 1,
        'advisoryMinHeadroomBytes' => 16 * 1024 * 1024,
        'estimatedBytesPerUnit' => 2 * 1024 * 1024,
      ]
    );

    if ( ! $post ) {
      return new WP_Error(
        'asnerisseo_post_not_found',
        __( 'Post not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    if ( 'publish' !== get_post_status( $post ) ) {
      return new WP_Error(
        'asnerisseo_post_not_published',
        __( 'Diagnostics can only run on published content.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $url = get_permalink( $post_id );
    if ( ! $url ) {
      return new WP_Error(
        'asnerisseo_post_permalink_missing',
        __( 'Post URL could not be resolved.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $site_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
    $overview_item = self::build_page_diagnostics_overview_item( $post, $site_host );

    if ( ! $no_store && $is_priority && ! $force_refresh && ! ASNERISSEO_Page_Diagnostics_Snapshots::should_scan( $post ) ) {
      $snapshot_report = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post_id );
      if ( is_array( $snapshot_report ) && ! empty( $snapshot_report['checks'] ) ) {
        $performance = self::finish_performance_scope(
          $performance_scope,
          [
            'pagesProcessed' => 0,
            'imagesChecked' => 0,
            'linksChecked' => 0,
            'schemaValidated' => 0,
            'seoRulesEvaluated' => 0,
            'warnings' => 0,
            'errors' => 0,
          ],
          [
            'recordsRead' => 1,
            'recordsUpdated' => 0,
            'recordsInserted' => 0,
          ]
        );

        $payload = self::attach_unified_diagnostics_payload(
          [
            'postId' => $post_id,
            'url' => $url,
            'seoScore' => isset( $snapshot_report['seoScore'] ) ? (int) $snapshot_report['seoScore'] : 0,
            'aiScore' => isset( $snapshot_report['aiScore'] ) ? (int) $snapshot_report['aiScore'] : 0,
            'health' => isset( $snapshot_report['health'] ) ? sanitize_key( (string) $snapshot_report['health'] ) : 'warning',
            'issueGroups' => isset( $snapshot_report['issueGroups'] ) && is_array( $snapshot_report['issueGroups'] ) ? $snapshot_report['issueGroups'] : [],
            'overviewIssueRecords' => isset( $snapshot_report['overviewIssueRecords'] ) && is_array( $snapshot_report['overviewIssueRecords'] ) ? $snapshot_report['overviewIssueRecords'] : [],
            'aiIssueRecords' => isset( $snapshot_report['aiIssueRecords'] ) && is_array( $snapshot_report['aiIssueRecords'] ) ? $snapshot_report['aiIssueRecords'] : [],
            'aiCanonicalSignals' => isset( $snapshot_report['aiCanonicalSignals'] ) && is_array( $snapshot_report['aiCanonicalSignals'] ) ? $snapshot_report['aiCanonicalSignals'] : [],
            'tabIssueRecords' => isset( $snapshot_report['tabIssueRecords'] ) && is_array( $snapshot_report['tabIssueRecords'] ) ? $snapshot_report['tabIssueRecords'] : [],
            'overviewRunId' => isset( $snapshot_report['overviewRunId'] ) ? (string) $snapshot_report['overviewRunId'] : '',
            'seoScoreMessage' => isset( $snapshot_report['seoScoreMessage'] ) ? sanitize_text_field( (string) $snapshot_report['seoScoreMessage'] ) : '',
            'lastScanGmt' => (string) ( $snapshot_report['generatedAtGmt'] ?? get_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt', true ) ),
            'checks' => $snapshot_report['checks'],
            'source' => 'snapshot-skip',
            'isPriority' => true,
            'performance' => $performance,
          ],
          'snapshot_skip'
        );

        $contract = self::validate_unified_contract( $payload, 'diagnostics.snapshot_skip' );
        if ( is_wp_error( $contract ) ) {
          return $contract;
        }

        return rest_ensure_response(
          $payload
        );
      }
    }

    $checks = ASNERISSEO_Diagnostics::http_test_checks( $url );
    if ( is_wp_error( $checks ) ) {
      return $checks;
    }

    $check_rows = is_array( $checks ) ? $checks : [];
    $overview_item = self::apply_weightage_scores_from_checks( $post, $overview_item, $check_rows, 'overview-live' );
    $check_rows = self::sync_overview_checks_from_records( $check_rows, $overview_item );
    $live_seo_score = isset( $overview_item['seoScore'] ) ? (int) $overview_item['seoScore'] : 0;
    $warning_count = 0;
    $error_count = 0;
    $images_checked = 0;
    $links_checked = 0;
    $schema_validated = 0;

    foreach ( $check_rows as $check ) {
      if ( ! is_array( $check ) ) {
        continue;
      }

      $label = strtolower( (string) ( $check['label'] ?? '' ) );
      $status = strtolower( (string) ( $check['status'] ?? '' ) );

      if ( 'warning' === $status || 'warn' === $status ) {
        $warning_count++;
      }

      if ( 'fail' === $status || 'failed' === $status || 'error' === $status ) {
        $error_count++;
      }

      if ( false !== strpos( $label, 'image' ) || false !== strpos( $label, 'alt' ) ) {
        $images_checked++;
      }

      if ( false !== strpos( $label, 'link' ) ) {
        $links_checked++;
      }

      if ( false !== strpos( $label, 'schema' ) ) {
        $schema_validated++;
      }
    }

    $analysis_stats = [
      'pagesProcessed' => 1,
      'imagesChecked' => $images_checked,
      'linksChecked' => $links_checked,
      'schemaValidated' => $schema_validated,
      'seoRulesEvaluated' => count( $check_rows ),
      'warnings' => $warning_count,
      'errors' => $error_count,
    ];

    if ( $is_priority ) {
      if ( $no_store ) {
        $performance = self::finish_performance_scope(
          $performance_scope,
          $analysis_stats,
          [
            'recordsRead' => 1,
            'recordsUpdated' => 0,
            'recordsInserted' => 0,
          ]
        );

        $payload = self::attach_unified_diagnostics_payload(
          array_merge(
            $overview_item,
            [
            'postId' => $post_id,
            'url' => $url,
            'lastScanGmt' => gmdate( 'c' ),
            'checks' => $checks,
            'source' => 'live-scan-no-store',
            'isPriority' => true,
            'performance' => $performance,
            ]
          ),
          'live_scan_no_store'
        );

        $contract = self::validate_unified_contract( $payload, 'diagnostics.live_scan_no_store' );
        if ( is_wp_error( $contract ) ) {
          return $contract;
        }

        return rest_ensure_response( $payload );
      }

      $report = ASNERISSEO_Page_Diagnostics_Snapshots::save_snapshot(
        $post,
        $checks,
        $url,
        [
          'seoScore' => isset( $overview_item['seoScore'] ) ? (int) $overview_item['seoScore'] : $live_seo_score,
          'aiScore' => isset( $overview_item['aiScore'] ) ? (int) $overview_item['aiScore'] : 0,
          'health' => isset( $overview_item['health'] ) ? sanitize_key( (string) $overview_item['health'] ) : 'warning',
          'issueGroups' => isset( $overview_item['issueGroups'] ) && is_array( $overview_item['issueGroups'] ) ? $overview_item['issueGroups'] : [],
          'overviewIssueRecords' => isset( $overview_item['overviewIssueRecords'] ) && is_array( $overview_item['overviewIssueRecords'] ) ? $overview_item['overviewIssueRecords'] : [],
          'aiIssueRecords' => isset( $overview_item['aiIssueRecords'] ) && is_array( $overview_item['aiIssueRecords'] ) ? $overview_item['aiIssueRecords'] : [],
          'aiCanonicalSignals' => isset( $overview_item['aiCanonicalSignals'] ) && is_array( $overview_item['aiCanonicalSignals'] ) ? $overview_item['aiCanonicalSignals'] : [],
          'overviewRunId' => isset( $overview_item['overviewRunId'] ) ? (string) $overview_item['overviewRunId'] : '',
          'seoScoreMessage' => isset( $overview_item['seoScoreMessage'] ) ? sanitize_text_field( (string) $overview_item['seoScoreMessage'] ) : '',
          'scoreEngine' => 'weightage_policy_v4_1',
        ]
      );
      $performance = self::finish_performance_scope(
        $performance_scope,
        $analysis_stats,
        [
          'recordsRead' => 1,
          'recordsUpdated' => 1,
          'recordsInserted' => 1,
        ]
      );

      $payload = self::attach_unified_diagnostics_payload(
        array_merge(
          $overview_item,
          [
          'postId' => $post_id,
          'url' => $url,
          'seoScore' => isset( $report['seoScore'] ) ? (int) $report['seoScore'] : ( isset( $overview_item['seoScore'] ) ? (int) $overview_item['seoScore'] : 0 ),
          'health' => isset( $report['health'] ) ? sanitize_key( (string) $report['health'] ) : ( isset( $overview_item['health'] ) ? sanitize_key( (string) $overview_item['health'] ) : 'warning' ),
          'issueGroups' => isset( $report['issueGroups'] ) && is_array( $report['issueGroups'] ) ? $report['issueGroups'] : ( isset( $overview_item['issueGroups'] ) && is_array( $overview_item['issueGroups'] ) ? $overview_item['issueGroups'] : [] ),
          'lastScanGmt' => (string) ( $report['generatedAtGmt'] ?? gmdate( 'c' ) ),
          'checks' => $checks,
          'source' => 'live-scan',
          'isPriority' => true,
          'performance' => $performance,
          ]
        ),
        'live_scan_priority'
      );

      $contract = self::validate_unified_contract( $payload, 'diagnostics.live_scan_priority' );
      if ( is_wp_error( $contract ) ) {
        return $contract;
      }

      return rest_ensure_response( $payload );
    }

    if ( ! $no_store ) {
      update_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt', gmdate( 'c' ) );
    }
    $performance = self::finish_performance_scope(
      $performance_scope,
      $analysis_stats,
      [
        'recordsRead' => 1,
        'recordsUpdated' => 1,
        'recordsInserted' => 0,
      ]
    );

    $payload = self::attach_unified_diagnostics_payload(
      array_merge(
        $overview_item,
        [
        'postId' => $post_id,
        'url' => $url,
        'lastScanGmt' => $no_store ? gmdate( 'c' ) : (string) get_post_meta( $post_id, '_ASNERISSEO_last_diagnostics_scan_gmt', true ),
        'checks' => $checks,
        'source' => $no_store ? 'live-scan-no-store' : 'live-scan-non-priority',
        'isPriority' => false,
        'performance' => $performance,
        ]
      ),
      $no_store ? 'live_scan_no_store' : 'live_scan_non_priority'
    );

    $contract = self::validate_unified_contract( $payload, 'diagnostics.live_scan_non_priority' );
    if ( is_wp_error( $contract ) ) {
      return $contract;
    }

    return rest_ensure_response( $payload );
  }

  private static function attach_unified_overview_item( array $item, $source_mode = 'overview' ) {
    if ( ! class_exists( 'ASNERISSEO_Data_Interface_Normalizer' ) ) {
      return $item;
    }

    $item['unifiedData'] = ASNERISSEO_Data_Interface_Normalizer::normalize_overview_item(
      $item,
      [
        'sourceFlow' => 'admin_pd_overview',
        'sourceEngine' => 'weightage_policy_v4_1',
        'sourceMode' => $source_mode,
      ]
    );

    return $item;
  }

  private static function attach_unified_diagnostics_payload( array $payload, $source_mode = 'live_scan' ) {
    if ( ! class_exists( 'ASNERISSEO_Data_Interface_Normalizer' ) ) {
      return $payload;
    }

    $payload['unifiedData'] = ASNERISSEO_Data_Interface_Normalizer::normalize_diagnostics_payload(
      $payload,
      [
        'sourceFlow' => 'page_diagnostics',
        'sourceEngine' => 'weightage_policy_v4_1',
        'sourceMode' => $source_mode,
      ]
    );

    return $payload;
  }

  private static function validate_unified_collection_contract( $items, $context = 'collection' ) {
    if ( ! is_array( $items ) ) {
      return self::build_unified_contract_error( 'asnerisseo_unified_contract_invalid', (string) $context, 'Expected array collection.' );
    }

    foreach ( $items as $index => $item ) {
      $validation = self::validate_unified_contract( $item, sprintf( '%s[%d]', (string) $context, (int) $index ) );
      if ( is_wp_error( $validation ) ) {
        return $validation;
      }
    }

    return true;
  }

  private static function validate_unified_contract( $payload, $context = 'payload' ) {
    if ( ! is_array( $payload ) || ! isset( $payload['unifiedData'] ) || ! is_array( $payload['unifiedData'] ) ) {
      return self::build_unified_contract_error( 'asnerisseo_unified_contract_missing', (string) $context, 'Missing unifiedData envelope.' );
    }

    $unified = $payload['unifiedData'];
    $required_scalar_fields = [ 'interfaceVersion', 'sourceFlow', 'sourceEngine', 'sourceMode' ];
    foreach ( $required_scalar_fields as $field ) {
      if ( ! isset( $unified[ $field ] ) || ! is_string( $unified[ $field ] ) || '' === trim( $unified[ $field ] ) ) {
        return self::build_unified_contract_error(
          'asnerisseo_unified_contract_invalid',
          (string) $context,
          sprintf( 'Missing or invalid scalar field: %s', (string) $field )
        );
      }
    }

    $required_object_fields = [ 'raw', 'computed', 'fieldStates' ];
    foreach ( $required_object_fields as $field ) {
      if ( ! isset( $unified[ $field ] ) || ! is_array( $unified[ $field ] ) ) {
        return self::build_unified_contract_error(
          'asnerisseo_unified_contract_invalid',
          (string) $context,
          sprintf( 'Missing or invalid object field: %s', (string) $field )
        );
      }
    }

    return true;
  }

  private static function build_unified_contract_error( $code, $context, $details = '' ) {
    return new WP_Error(
      sanitize_key( (string) $code ),
      /* translators: %s: Unified diagnostics contract validation context identifier. */
      sprintf( __( 'Unified diagnostics contract validation failed for %s.', 'asneris-seo-toolkit' ), (string) $context ),
      [
        'status' => 500,
        'context' => (string) $context,
        'details' => (string) $details,
      ]
    );
  }

  private static function parse_bytes_from_ini( $value ) {
    $raw = trim( (string) $value );
    if ( '' === $raw ) {
      return 0;
    }

    if ( '-1' === $raw ) {
      return -1;
    }

    if ( is_numeric( $raw ) ) {
      return (int) $raw;
    }

    $unit = strtolower( substr( $raw, -1 ) );
    $number = (float) $raw;
    $bytes = (int) $number;

    if ( 'g' === $unit ) {
      $bytes *= 1024;
    }
    if ( 'm' === $unit || 'g' === $unit ) {
      $bytes *= 1024;
    }
    if ( 'k' === $unit || 'm' === $unit || 'g' === $unit ) {
      $bytes *= 1024;
    }

    return $bytes;
  }

  private static function start_performance_scope( $job_type, array $args = [] ) {
    $memory_limit_raw = (string) ini_get( 'memory_limit' );
    $memory_limit_bytes = self::parse_bytes_from_ini( $memory_limit_raw );
    $start_memory = (int) memory_get_usage( true );
    $headroom = $memory_limit_bytes > 0 ? max( 0, $memory_limit_bytes - $start_memory ) : -1;
    $limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 0;
    $estimated_per_unit = isset( $args['estimatedBytesPerUnit'] ) ? max( 0, (int) $args['estimatedBytesPerUnit'] ) : 0;
    $estimated_total = $limit > 0 ? $estimated_per_unit * $limit : 0;
    $advisory_min_headroom = isset( $args['advisoryMinHeadroomBytes'] ) ? max( 0, (int) $args['advisoryMinHeadroomBytes'] ) : ( 16 * 1024 * 1024 );

    $recommend_not_to_run = false;
    $advisory_reason = '';
    if ( $headroom >= 0 ) {
      if ( $headroom < $advisory_min_headroom ) {
        $recommend_not_to_run = true;
        $advisory_reason = __( 'Server memory headroom is low. We recommend not running this analysis now.', 'asneris-seo-toolkit' );
      } elseif ( $estimated_total > 0 && $headroom < $estimated_total ) {
        $recommend_not_to_run = true;
        $advisory_reason = __( 'Estimated workload may exceed available memory headroom. We recommend not running this analysis now.', 'asneris-seo-toolkit' );
      }
    }

    return [
      'jobType' => sanitize_key( (string) $job_type ),
      'limit' => $limit,
      'startedAt' => gmdate( 'c' ),
      'startTimestamp' => microtime( true ),
      'startMemoryBytes' => $start_memory,
      'startQueryCount' => (int) get_num_queries(),
      'memoryLimitRaw' => $memory_limit_raw,
      'memoryLimitBytes' => $memory_limit_bytes,
      'headroomBytes' => $headroom,
      'advisory' => [
        'recommendNotToRun' => $recommend_not_to_run,
        'reason' => $advisory_reason,
      ],
      'environment' => [
        'phpVersion' => (string) phpversion(),
        'phpMemoryLimitBytes' => $memory_limit_bytes,
        'phpMaxExecutionTime' => (int) ini_get( 'max_execution_time' ),
        'wpVersion' => (string) get_bloginfo( 'version' ),
        'pluginVersion' => defined( 'ASNERISSEO_VERSION' ) ? (string) ASNERISSEO_VERSION : '',
      ],
    ];
  }

  private static function finish_performance_scope( $scope, array $analysis = [], array $db = [] ) {
    $end_timestamp = microtime( true );
    $end_memory = (int) memory_get_usage( true );
    $peak_memory = (int) memory_get_peak_usage( true );
    $execution_ms = max( 0, (int) round( ( $end_timestamp - (float) ( $scope['startTimestamp'] ?? $end_timestamp ) ) * 1000 ) );
    $memory_increase = max( 0, $end_memory - (int) ( $scope['startMemoryBytes'] ?? 0 ) );
    $query_delta = max( 0, (int) get_num_queries() - (int) ( $scope['startQueryCount'] ?? 0 ) );

    $thresholds = [
      'excellent' => [
        'executionMsMax' => 500,
        'memoryIncreaseMbMax' => 10,
        'queryDeltaMax' => 50,
      ],
      'good' => [
        'executionMsMax' => 1500,
        'memoryIncreaseMbMax' => 20,
        'queryDeltaMax' => 100,
      ],
    ];

    $memory_increase_mb = round( $memory_increase / ( 1024 * 1024 ), 2 );
    $status = 'excellent';
    if (
      $execution_ms > (int) $thresholds['good']['executionMsMax'] ||
      $memory_increase_mb > (float) $thresholds['good']['memoryIncreaseMbMax'] ||
      $query_delta > (int) $thresholds['good']['queryDeltaMax']
    ) {
      $status = 'warning';
    } elseif (
      $execution_ms > (int) $thresholds['excellent']['executionMsMax'] ||
      $memory_increase_mb > (float) $thresholds['excellent']['memoryIncreaseMbMax'] ||
      $query_delta > (int) $thresholds['excellent']['queryDeltaMax']
    ) {
      $status = 'good';
    }

    return [
      'version' => 1,
      'jobType' => (string) ( $scope['jobType'] ?? '' ),
      'status' => $status,
      'advisory' => [
        'recommendNotToRun' => ! empty( $scope['advisory']['recommendNotToRun'] ),
        'reason' => (string) ( $scope['advisory']['reason'] ?? '' ),
      ],
      'timing' => [
        'startedAt' => (string) ( $scope['startedAt'] ?? gmdate( 'c' ) ),
        'endedAt' => gmdate( 'c' ),
        'executionMs' => $execution_ms,
      ],
      'memory' => [
        'startBytes' => (int) ( $scope['startMemoryBytes'] ?? 0 ),
        'endBytes' => $end_memory,
        'peakBytes' => $peak_memory,
        'increaseBytes' => $memory_increase,
        'increaseMb' => $memory_increase_mb,
      ],
      'database' => [
        'queryDelta' => $query_delta,
        'recordsRead' => isset( $db['recordsRead'] ) ? (int) $db['recordsRead'] : 0,
        'recordsUpdated' => isset( $db['recordsUpdated'] ) ? (int) $db['recordsUpdated'] : 0,
        'recordsInserted' => isset( $db['recordsInserted'] ) ? (int) $db['recordsInserted'] : 0,
      ],
      'workload' => [
        'limit' => (int) ( $scope['limit'] ?? 0 ),
        'processed' => isset( $analysis['recordsProcessed'] )
          ? (int) $analysis['recordsProcessed']
          : ( isset( $analysis['pagesProcessed'] ) ? (int) $analysis['pagesProcessed'] : 0 ),
      ],
      'analysis' => $analysis,
      'environment' => [
        'phpVersion' => (string) ( $scope['environment']['phpVersion'] ?? '' ),
        'phpMemoryLimitBytes' => (int) ( $scope['environment']['phpMemoryLimitBytes'] ?? 0 ),
        'phpMaxExecutionTime' => (int) ( $scope['environment']['phpMaxExecutionTime'] ?? 0 ),
        'wpVersion' => (string) ( $scope['environment']['wpVersion'] ?? '' ),
        'pluginVersion' => (string) ( $scope['environment']['pluginVersion'] ?? '' ),
      ],
      'thresholds' => $thresholds,
      'thresholdHits' => [
        'executionExceeded' => $execution_ms > (int) $thresholds['good']['executionMsMax'],
        'memoryExceeded' => $memory_increase_mb > (float) $thresholds['good']['memoryIncreaseMbMax'],
        'queryExceeded' => $query_delta > (int) $thresholds['good']['queryDeltaMax'],
      ],
    ];
  }

  public static function get_page_diagnostics_history( WP_REST_Request $request ) {
    $post_id = absint( $request['id'] );
    $limit = absint( $request->get_param( 'limit' ) );
    if ( $limit < 1 ) {
      $limit = 10;
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
      return new WP_Error(
        'asnerisseo_post_not_found',
        __( 'Post not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    $is_priority = ASNERISSEO_Page_Diagnostics_Snapshots::is_priority_page( $post_id );
    $history = ASNERISSEO_Page_Diagnostics_Snapshots::get_snapshot_history( $post_id, $limit );
    $history_count = ASNERISSEO_Page_Diagnostics_Snapshots::get_history_count( $post_id );

    if ( $is_priority && empty( $history ) ) {
      $latest_row = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot( $post_id );
      $latest_report = ASNERISSEO_Page_Diagnostics_Snapshots::get_latest_snapshot_report( $post_id );

      if ( is_array( $latest_row ) ) {
        $generated_at = isset( $latest_report['generatedAtGmt'] ) ? (string) $latest_report['generatedAtGmt'] : '';
        $created_at = isset( $latest_row['last_scan_gmt'] ) ? (string) $latest_row['last_scan_gmt'] : '';

        $history[] = [
          'id' => 0,
          'pageId' => $post_id,
          'seoScore' => isset( $latest_report['seoScore'] ) ? (int) $latest_report['seoScore'] : ( isset( $latest_row['seo_score'] ) ? (int) $latest_row['seo_score'] : 0 ),
          'health' => isset( $latest_report['health'] ) ? sanitize_key( (string) $latest_report['health'] ) : ( isset( $latest_row['health'] ) ? sanitize_key( (string) $latest_row['health'] ) : 'warning' ),
          'createdAt' => $created_at,
          'generatedAtGmt' => $generated_at,
          'issueGroups' => isset( $latest_report['issueGroups'] ) && is_array( $latest_report['issueGroups'] )
            ? $latest_report['issueGroups']
            : [],
          'overviewIssueRecords' => isset( $latest_report['overviewIssueRecords'] ) && is_array( $latest_report['overviewIssueRecords'] )
            ? array_values( $latest_report['overviewIssueRecords'] )
            : [],
          'aiIssueRecords' => isset( $latest_report['aiIssueRecords'] ) && is_array( $latest_report['aiIssueRecords'] )
            ? array_values( $latest_report['aiIssueRecords'] )
            : [],
          'aiCanonicalSignals' => isset( $latest_report['aiCanonicalSignals'] ) && is_array( $latest_report['aiCanonicalSignals'] )
            ? $latest_report['aiCanonicalSignals']
            : [],
          'tabIssueRecords' => isset( $latest_report['tabIssueRecords'] ) && is_array( $latest_report['tabIssueRecords'] )
            ? $latest_report['tabIssueRecords']
            : [],
          'checks' => isset( $latest_report['checks'] ) && is_array( $latest_report['checks'] ) ? array_values( $latest_report['checks'] ) : [],
          'source' => 'latest-fallback',
        ];
      }
    }

    $history_count = max( (int) $history_count, count( $history ) );
    $history_limit = ASNERISSEO_Page_Diagnostics_Snapshots::DEFAULT_HISTORY_LIMIT;

    return rest_ensure_response(
      [
        'postId' => $post_id,
        'isPriority' => $is_priority,
        'history' => $history,
        'historyCount' => $history_count,
        'historyLimit' => $history_limit,
        'historyLocked' => $history_count >= $history_limit,
      ]
    );
  }

  public static function delete_page_diagnostics_history_record( WP_REST_Request $request ) {
    $post_id = absint( $request['id'] );
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }
    $history_id = isset( $incoming['historyId'] ) ? absint( $incoming['historyId'] ) : 0;

    if ( $post_id < 1 || $history_id < 1 ) {
      return new WP_Error(
        'asnerisseo_history_delete_invalid',
        __( 'Invalid history delete request.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
      return new WP_Error(
        'asnerisseo_post_not_found',
        __( 'Post not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    $deleted = ASNERISSEO_Page_Diagnostics_Snapshots::delete_history_record( $post_id, $history_id );
    $history_count = ASNERISSEO_Page_Diagnostics_Snapshots::get_history_count( $post_id );
    $history_limit = ASNERISSEO_Page_Diagnostics_Snapshots::DEFAULT_HISTORY_LIMIT;

    return rest_ensure_response(
      [
        'success' => $deleted > 0,
        'deleted' => (int) $deleted,
        'historyCount' => $history_count,
        'historyLimit' => $history_limit,
        'historyLocked' => $history_count >= $history_limit,
      ]
    );
  }

  public static function clear_page_diagnostics_records( WP_REST_Request $request ) {
    $post_id = absint( $request['id'] );
    if ( $post_id < 1 ) {
      return new WP_Error(
        'asnerisseo_cleanup_invalid',
        __( 'Invalid cleanup request.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
      return new WP_Error(
        'asnerisseo_post_not_found',
        __( 'Post not found.', 'asneris-seo-toolkit' ),
        [ 'status' => 404 ]
      );
    }

    $settings = get_option( ASNERISSEO_Admin_Settings::OPT, [] );
    $existing_priority_ids = isset( $settings['priority_page_ids'] ) && is_array( $settings['priority_page_ids'] )
      ? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $settings['priority_page_ids'] ) ) ) ), 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES )
      : [];

    $updated_priority_ids = array_values(
      array_filter(
        $existing_priority_ids,
        static function( $priority_id ) use ( $post_id ) {
          return (int) $priority_id !== (int) $post_id;
        }
      )
    );

    $removed_from_priority = count( $updated_priority_ids ) !== count( $existing_priority_ids );

    if ( $removed_from_priority ) {
      $updated_settings = array_merge(
        is_array( $settings ) ? $settings : [],
        [
          'priority_page_ids' => array_slice( $updated_priority_ids, 0, ASNERISSEO_Page_Diagnostics_Snapshots::MAX_PRIORITY_PAGES ),
        ]
      );

      $clean = self::save_settings_with_validation( $updated_settings, 'asnerisseo_cleanup_validation_failed', __( 'Unable to update Priority Pages settings.', 'asneris-seo-toolkit' ) );
      if ( is_wp_error( $clean ) ) {
        return $clean;
      }
    }

    $cleanup = ASNERISSEO_Page_Diagnostics_Snapshots::delete_page_records( $post_id );

    return rest_ensure_response(
      [
        'success' => true,
        'postId' => $post_id,
        'removedFromPriority' => $removed_from_priority,
        'priorityPageIds' => $updated_priority_ids,
        'cleanup' => $cleanup,
      ]
    );
  }

  public static function run_diagnostics_url( WP_REST_Request $request ) {
    $incoming = $request->get_json_params();
    if ( ! is_array( $incoming ) ) {
      $incoming = [];
    }

    $url = isset( $incoming['url'] ) ? esc_url_raw( wp_unslash( (string) $incoming['url'] ) ) : '';
    if ( '' === $url ) {
      return new WP_Error(
        'asnerisseo_diagnostics_url_missing',
        __( 'No URL provided.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    if ( ! wp_http_validate_url( $url ) ) {
      return new WP_Error(
        'asnerisseo_diagnostics_url_invalid',
        __( 'Invalid URL provided.', 'asneris-seo-toolkit' ),
        [ 'status' => 400 ]
      );
    }

    $host = wp_parse_url( $url, PHP_URL_HOST );
    $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
    if ( ! $host || ! $site_host || strtolower( $host ) !== strtolower( $site_host ) ) {
      return new WP_Error(
        'asnerisseo_diagnostics_url_forbidden_host',
        __( 'Security: only URLs from this site can be analyzed.', 'asneris-seo-toolkit' ),
        [ 'status' => 403 ]
      );
    }

    $checks = ASNERISSEO_Diagnostics::http_test_checks( $url );
    if ( is_wp_error( $checks ) ) {
      return $checks;
    }

    return rest_ensure_response(
      [
        'url' => $url,
        'checks' => $checks,
      ]
    );
  }

  private static function get_default_robots_content() {
    $sitemap_url = home_url( '/wp-sitemap.xml' );
    return "# Default robots.txt for WordPress\n"
      . "# Generated by Asneris SEO Toolkit\n\n"
      . "User-agent: *\n"
      . "Disallow: /wp-admin/\n"
      . "Allow: /wp-admin/admin-ajax.php\n\n"
      . "# Sitemap location\n"
      . 'Sitemap: ' . $sitemap_url . "\n";
  }

  private static function validate_robots_content( $content ) {
    $errors = [];

    if ( preg_match( '/<script|<\?php|javascript:/i', $content ) ) {
      $errors[] = __( 'Invalid content: script tags and code are not allowed in robots.txt.', 'asneris-seo-toolkit' );
    }

    if ( preg_match( '/\.(ps1|exe|bat|cmd|sh)\b/i', $content ) ) {
      $errors[] = __( 'Invalid content: executable file references are not allowed.', 'asneris-seo-toolkit' );
    }

    if ( preg_match( '/<[a-z][\s\S]*>/i', $content ) ) {
      $errors[] = __( 'Invalid content: HTML tags are not allowed in robots.txt.', 'asneris-seo-toolkit' );
    }

    $lines = explode( "\n", (string) $content );
    foreach ( $lines as $line_number => $line ) {
      $line = trim( $line );
      if ( '' === $line || '#' === $line[0] ) {
        continue;
      }

      if ( ! preg_match( '/^(User-agent|Allow|Disallow|Sitemap|Crawl-delay)\s*:\s*.+$/i', $line ) ) {
        $errors[] = sprintf(
          /* translators: 1: robots.txt line number, 2: Invalid directive content. */
          __( 'Line %1$d is not a valid robots.txt directive: "%2$s".', 'asneris-seo-toolkit' ),
          $line_number + 1,
          strlen( $line ) > 80 ? substr( $line, 0, 80 ) . '...' : $line
        );
      }

      if ( preg_match( '/\b(file|ftp|data|tel|javascript):/i', $line ) ) {
        $errors[] = sprintf(
          /* translators: %d: robots.txt line number. */
          __( 'Line %d contains a suspicious protocol.', 'asneris-seo-toolkit' ),
          $line_number + 1
        );
      }
    }

    return $errors;
  }

  private static function save_settings_with_validation( $updated, $error_code, $error_message ) {
    $clean = ASNERISSEO_Admin_Settings::sanitize( $updated );
    $validation_errors = get_transient( 'asneris_settings_validation_errors' );

    if ( ! empty( $validation_errors ) ) {
      delete_transient( 'asneris_settings_validation_errors' );
      return new WP_Error(
        $error_code,
        $error_message,
        [
          'status' => 400,
          'errors' => $validation_errors,
        ]
      );
    }

    update_option( ASNERISSEO_Admin_Settings::OPT, $clean );

    return $clean;
  }

  public static function can_manage_settings() {
    return current_user_can( 'manage_options' );
  }

  public static function can_manage_settings_with_nonce( WP_REST_Request $request ) {
    if ( ! current_user_can( 'manage_options' ) ) {
      return false;
    }

    $nonce = $request->get_header( 'X-WP-Nonce' );
    if ( ! is_string( $nonce ) || '' === $nonce ) {
      return false;
    }

    return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
  }

  public static function can_access_editor_config() {
    return current_user_can( 'edit_posts' );
  }

  public static function can_edit_posts() {
    return current_user_can( 'edit_posts' );
  }

  public static function can_edit_post( WP_REST_Request $request ) {
    return current_user_can( 'edit_post', absint( $request['id'] ) );
  }
}
