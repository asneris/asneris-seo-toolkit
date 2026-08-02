<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ASNERISSEO_Admin_Settings {
	const OPT = 'ASNERISSEO_settings';

	/**
	 * Ensure JSX runtime handle exists on admin pages (needed on some WP versions).
	 */
	private static function ensure_react_jsx_runtime() {
		if ( wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
			return;
		}

		wp_register_script(
			'react-jsx-runtime',
			false,
			array( 'react' ),
			ASNERISSEO_VERSION,
			true
		);

		wp_add_inline_script(
			'react-jsx-runtime',
			'
      window.ReactJSXRuntime = window.ReactJSXRuntime || {
        jsx: function(type, props, key) {
          var args = [type, props];
          if (props && props.children !== undefined) {
            if (Array.isArray(props.children)) {
              args = args.concat(props.children);
            } else {
              args.push(props.children);
            }
          }
          return React.createElement.apply(React, args);
        },
        jsxs: function(type, props, key) {
          var args = [type, props];
          if (props && props.children !== undefined) {
            if (Array.isArray(props.children)) {
              args = args.concat(props.children);
            } else {
              args.push(props.children);
            }
          }
          return React.createElement.apply(React, args);
        },
        Fragment: window.React.Fragment
      };
    '
		);
	}

	public static function register_settings() {
		// Use option name as group name - WordPress convention
		register_setting( self::OPT, self::OPT, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	/**
	 * Enqueue admin styles and scripts
	 */
	public static function enqueue_admin_assets( $hook ) {
		// The actual hook uses the sanitized menu title, not the menu slug
		// Actual: asneris-seo-toolkit_page_asneris-seo-settings
		// WordPress uses sanitized menu TITLE (not slug) as parent identifier
		if ( $hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-settings' ) {
			return;
		}

		$admin_css_version  = ASNERISSEO_VERSION;
		$admin_js_version   = ASNERISSEO_VERSION;
		$debug_cache_buster = 'debug-' . gmdate( 'YmdHis' );
		$admin_css_path     = ASNERISSEO_DIR . 'assets/css/admin-style.css';
		$admin_js_path      = ASNERISSEO_DIR . 'assets/js/admin-script.js';

		if ( file_exists( $admin_css_path ) ) {
			$admin_css_version .= '.' . filemtime( $admin_css_path );
		}

		if ( file_exists( $admin_js_path ) ) {
			$admin_js_version .= '.' . filemtime( $admin_js_path );
		}

		// Temporary hard cache bust for local debugging so browser always pulls latest files.
		$admin_css_version .= '.' . $debug_cache_buster;
		$admin_js_version  .= '.' . $debug_cache_buster;

		wp_enqueue_style( 'asnerisseo-admin', ASNERISSEO_URL . 'assets/css/admin-style.css', array(), $admin_css_version );
		wp_enqueue_script( 'asnerisseo-admin', ASNERISSEO_URL . 'assets/js/admin-script.js', array( 'jquery' ), $admin_js_version, true );
		wp_enqueue_media(); // For media uploader
		wp_enqueue_script( 'jquery' );

		$react_asset_path = ASNERISSEO_DIR . 'build/admin/index.asset.php';
		if ( file_exists( $react_asset_path ) ) {
			self::ensure_react_jsx_runtime();

			$react_asset          = include $react_asset_path;
			$react_script_path    = ASNERISSEO_DIR . 'build/admin/index.js';
			$react_script_version = $react_asset['version'];
			if ( file_exists( $react_script_path ) ) {
				$react_script_version .= '.' . filemtime( $react_script_path );
			}
			$react_script_version .= '.' . $debug_cache_buster;
			wp_enqueue_script(
				'asnerisseo-admin-dashboard',
				ASNERISSEO_URL . 'build/admin/index.js',
				$react_asset['dependencies'],
				$react_script_version,
				true
			);

			$summary_payload = ASNERISSEO_Dashboard::get_dashboard_summary_payload();
			wp_localize_script(
				'asnerisseo-admin-dashboard',
				'asnerisseoAdminDashboardData',
				array(
					'summary'                        => $summary_payload,
					'dashboardSummaryRestUrl'        => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/dashboard-summary' ) ),
					'generalSettingsRestUrl'         => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/general' ) ),
					'verificationSettingsRestUrl'    => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/verification' ) ),
					'socialSettingsRestUrl'          => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/social' ) ),
					'schemaSettingsRestUrl'          => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/schema' ) ),
					'indexNowSettingsRestUrl'        => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/indexnow' ) ),
					'templatesSettingsRestUrl'       => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/templates' ) ),
					'maintenanceSettingsRestUrl'     => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-settings/maintenance' ) ),
					'pageDiagnosticsRestUrl'         => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/page-diagnostics/overview' ) ),
					'diagnosticsUrlRestUrl'          => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/diagnostics-url' ) ),
					'siteDiagnosticsRestUrl'         => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-diagnostics' ) ),
					'siteDiagnosticsUrlCheckRestUrl' => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/site-diagnostics/url-check' ) ),
					'redirectsRestUrl'               => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/redirects' ) ),
					'robotsRestUrl'                  => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/robots' ) ),
					'aiSearchabilityRestUrl'         => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/ai-searchability' ) ),
					'bulkEditContentRestUrl'         => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/bulk-edit/content' ) ),
					'bulkEditSaveRestUrl'            => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/bulk-edit/save' ) ),
					'logs404SettingsRestUrl'         => esc_url_raw( rest_url( ASNERISSEO_REST_API::NAMESPACE . '/404-logs/settings' ) ),
					'defaultOgImage'                 => esc_url_raw( self::get( 'default_og_image' ) ),
					'default_og_image'               => esc_url_raw( self::get( 'default_og_image' ) ),
					'restNonce'                      => wp_create_nonce( 'wp_rest' ),
					'debugInfo'                      => array(
						'hook'               => (string) $hook,
						'cssVersion'         => (string) $admin_css_version,
						'reactScriptVersion' => (string) $react_script_version,
						'pluginVersion'      => (string) ASNERISSEO_VERSION,
						'pluginUrl'          => (string) ASNERISSEO_URL,
						'source'             => (string) __FILE__,
						'stamp'              => (string) gmdate( 'c' ),
					),
					'mountSelector'                  => '.asnerisseo-fallback-settings',
					'hideFallback'                   => true,
				)
			);
		}

		$nonce     = wp_create_nonce( 'ASNERISSEO_http_test' );
		$inline_js = "jQuery(document).ready(function(\$){\n" .
		"  \$('#ASNERISSEO_run_http_test').on('click', function(){\n" .
		"    var url = \$('#ASNERISSEO_test_url').val();\n" .
		"    var button = this;\n" .
		"    var results = \$('#ASNERISSEO_http_results');\n" .
		"    var tbody = \$('#ASNERISSEO_http_results_body');\n" .
		"    if (!url) { alert('Please enter a URL to test'); return; }\n" .
		"    \$(button).prop('disabled', true).text('Testing...');\n" .
		"    tbody.empty().append(\$('<tr>').append(\$('<td>').attr('colspan','3').text('Running validation...')));\n" .
		"    results.show();\n" .
		"    \$.ajax({\n" .
		"      url: ajaxurl,\n" .
		"      method: 'POST',\n" .
		"      data: { action: 'ASNERISSEO_http_test', url: url, nonce: '" . esc_js( $nonce ) . "' },\n" .
		"      success: function(response){\n" .
		"        if (response.success) {\n" .
		"          tbody.empty();\n" .
		"          response.data.checks.forEach(function(check){\n" .
		"            var statusColor = check.status === 'pass' ? '#46b450' : (check.status === 'warning' ? '#f0ad4e' : '#dc3232');\n" .
		"            var statusIcon = check.status === 'pass' ? '✓' : (check.status === 'warning' ? '⚠' : '✗');\n" .
		"            var \$tr = \$('<tr>');\n" .
		"            \$tr.append(\$('<td>').append(\$('<strong>').text(check.label)));\n" .
		"            \$tr.append(\$('<td>').append(\$('<span>').css('color', statusColor).text(statusIcon + ' ' + check.result)));\n" .
		"            \$tr.append(\$('<td>').text(check.details));\n" .
		"            tbody.append(\$tr);\n" .
		"          });\n" .
		"        } else {\n" .
		"          tbody.empty().append(\$('<tr>').append(\$('<td>').attr('colspan','3').css('color','#dc3232').text('Error: ' + response.data)));\n" .
		"        }\n" .
		"      },\n" .
		"      error: function(){\n" .
		"        tbody.empty().append(\$('<tr>').append(\$('<td>').attr('colspan','3').css('color','#dc3232').text('Request failed. Please try again.')));\n" .
		"      },\n" .
		"      complete: function(){\n" .
		"        \$(button).prop('disabled', false).text('Run Indexing Validation');\n" .
		"      }\n" .
		"    });\n" .
		"  });\n" .
		"\n" .
		"  var autoRadio = document.querySelector('input[name=\"" . esc_js( self::OPT ) . "[indexnow_key_mode]\"][value=\"auto\"]');\n" .
		"  var customRadio = document.querySelector('input[name=\"" . esc_js( self::OPT ) . "[indexnow_key_mode]\"][value=\"custom\"]');\n" .
		"  var keyInput = document.getElementById('indexnow_key');\n" .
		"  var modifiedWarning = document.getElementById('indexnow-key-modified-warning');\n" .
		"  if (autoRadio && customRadio && keyInput && modifiedWarning) {\n" .
		"    var initialMode = autoRadio.checked ? 'auto' : 'custom';\n" .
		"    var initialKey = keyInput.getAttribute('data-initial-value') || '';\n" .
		"    var hasShownModifyAlert = false;\n" .
		"\n" .
		"    var syncReadonly = function() {\n" .
		"      var isAuto = autoRadio.checked;\n" .
		"      keyInput.readOnly = isAuto;\n" .
		"      keyInput.disabled = isAuto;\n" .
		"    };\n" .
		"\n" .
		"    var updateModifiedWarning = function() {\n" .
		"      if (!customRadio.checked) {\n" .
		"        modifiedWarning.style.display = 'none';\n" .
		"        return;\n" .
		"      }\n" .
		"\n" .
		"      var changed = keyInput.value !== initialKey;\n" .
		"      modifiedWarning.style.display = changed ? 'block' : 'none';\n" .
		"\n" .
		"      if (changed && !hasShownModifyAlert) {\n" .
		"        hasShownModifyAlert = true;\n" .
		"        window.alert('You have modified the manual IndexNow key. Save settings to apply this change.');\n" .
		"      }\n" .
		"    };\n" .
		"\n" .
		"    var confirmModeSwitch = function(targetMode) {\n" .
		"      var hasExistingKey = (initialKey.trim() !== '');\n" .
		"      if (!hasExistingKey || targetMode === initialMode) {\n" .
		"        return true;\n" .
		"      }\n" .
		"\n" .
		"      return window.confirm('An IndexNow key already exists. Switching key mode may change how this key is managed. Continue?');\n" .
		"    };\n" .
		"\n" .
		"    autoRadio.addEventListener('change', function() {\n" .
		"      if (!confirmModeSwitch('auto')) {\n" .
		"        customRadio.checked = true;\n" .
		"      }\n" .
		"      syncReadonly();\n" .
		"      updateModifiedWarning();\n" .
		"    });\n" .
		"\n" .
		"    customRadio.addEventListener('change', function() {\n" .
		"      if (!confirmModeSwitch('custom')) {\n" .
		"        autoRadio.checked = true;\n" .
		"      }\n" .
		"      syncReadonly();\n" .
		"      updateModifiedWarning();\n" .
		"    });\n" .
		"\n" .
		"    keyInput.addEventListener('input', updateModifiedWarning);\n" .
		"\n" .
		"    syncReadonly();\n" .
		"    updateModifiedWarning();\n" .
		"  }\n" .
		'});';
		wp_add_inline_script( 'asnerisseo-admin', $inline_js );

		wp_localize_script(
			'asnerisseo-admin',
			'asnerisseoAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'ASNERISSEO_admin_nonce' ),
			)
		);
	}

	private static $cache = null;

	public static function get( $key, $default = '' ) {
		if ( self::$cache === null ) {
			self::$cache = get_option( self::OPT, array() );
		}
		return self::$cache[ $key ] ?? $default;
	}

	/**
	 * Clear the static settings cache. Called after settings are saved.
	 */
	public static function clear_cache() {
		self::$cache = null;
	}

	public static function sanitize( $opt ) {
		// Clear static cache so subsequent get() calls reflect new values
		self::clear_cache();

		// Get existing options to preserve data from other tabs
		$existing = get_option( self::OPT, array() );

		// Detect which fields are actually being submitted (changed from existing)
		// This prevents validation errors when saving one tab while other tabs have empty/unchanged fields
		$field_changed = function ( $field_name ) use ( $opt, $existing ) {
			// Field is changed if it's in POST and different from existing value
			if ( ! isset( $opt[ $field_name ] ) ) {
				return false; // Field not in submission
			}
			$new_value = $opt[ $field_name ];
			$old_value = $existing[ $field_name ] ?? '';
			// Normalize for comparison (empty string vs null vs unset)
			return (string) $new_value !== (string) $old_value;
		};

		// Define whitelists for validation (prevents invalid values)
		$allowed_index       = array( 'index', 'noindex' );
		$allowed_follow      = array( 'follow', 'nofollow' );
		$allowed_price_range = array( '', '$', '$$', '$$$', '$$$$' );
		$allowed_separators  = array( '|', '-', '–', '—', '•', ':', '·' );

		// Track validation errors
		$validation_errors = array();

		// Define maximum lengths for fields
		$max_lengths = array(
			'google_verification' => 100,
			'bing_verification'   => 100,
			'yandex_verification' => 100,
			'org_name'            => 200,
			'twitter_username'    => 50,
			'facebook_app_id'     => 50,
			'payment_methods'     => 500,
			'languages_spoken'    => 500,
			'business_address'    => 1000,
			'business_hours'      => 2000,
			'service_area'        => 1000,
		);

		// Valid template variables
		$valid_variables = array( '{title}', '{site}', '{excerpt}', '{date}', '{author}', '{category}', '{separator}' );

		// Whitelist for Schema.org business types (must match dropdown options in render_schema_tab)
		$allowed_business_types = array(
			'LocalBusiness',
			'Restaurant',
			'FastFoodRestaurant',
			'Cafe',
			'Bakery',
			'BarOrPub',
			'Winery',
			'Store',
			'ClothingStore',
			'FurnitureStore',
			'HardwareStore',
			'JewelryStore',
			'ShoeStore',
			'SportsStore',
			'ToyStore',
			'ConvenienceStore',
			'HealthAndBeautyBusiness',
			'HairSalon',
			'BeautySalon',
			'DaySpa',
			'NailSalon',
			'TattooParlor',
			'Dentist',
			'Physician',
			'MedicalClinic',
			'Pharmacy',
			'VeterinaryCare',
			'ProfessionalService',
			'Attorney',
			'Accountant',
			'RealEstateAgent',
			'Notary',
			'InsuranceAgency',
			'HomeAndConstructionBusiness',
			'Electrician',
			'Plumber',
			'HousePainter',
			'Locksmith',
			'MovingCompany',
			'HVACBusiness',
			'Roofing',
			'AutomotiveBusiness',
			'AutoRepair',
			'AutoDealer',
			'AutoPartsStore',
			'AutoRental',
			'AutoWash',
			'GasStation',
			'LodgingBusiness',
			'Hotel',
			'Motel',
			'Resort',
			'BedAndBreakfast',
			'Hostel',
			'Campground',
			'SportsActivityLocation',
			'FitnessCenter',
			'GolfCourse',
			'PublicSwimmingPool',
			'TennisComplex',
			'EntertainmentBusiness',
			'MovieTheater',
			'NightClub',
			'AnimalShelter',
			'ChildCare',
			'SelfStorage',
		);

		// Validate and sanitize with whitelist checks
		$robots_index = sanitize_text_field( $opt['default_robots_index'] ?? $existing['default_robots_index'] ?? 'index' );
		if ( isset( $opt['default_robots_index'] ) && ! in_array( $robots_index, $allowed_index, true ) ) {
			$validation_errors[] = sprintf( 'Default indexing value "%s" is not allowed. Using "index" instead.', esc_html( $robots_index ) );
			$robots_index        = 'index';
		}

		$robots_follow = sanitize_text_field( $opt['default_robots_follow'] ?? $existing['default_robots_follow'] ?? 'follow' );
		if ( isset( $opt['default_robots_follow'] ) && ! in_array( $robots_follow, $allowed_follow, true ) ) {
			$validation_errors[] = sprintf( 'Default following value "%s" is not allowed. Using "follow" instead.', esc_html( $robots_follow ) );
			$robots_follow       = 'follow';
		}

		$business_type = sanitize_text_field( $opt['business_type'] ?? $existing['business_type'] ?? 'LocalBusiness' );
		if ( isset( $opt['business_type'] ) && ! in_array( $business_type, $allowed_business_types, true ) ) {
			$validation_errors[] = sprintf( 'Business type "%s" is not a valid Schema.org type. Using "LocalBusiness" instead.', esc_html( $business_type ) );
			$business_type       = 'LocalBusiness';
		}

		$price_range = sanitize_text_field( $opt['price_range'] ?? $existing['price_range'] ?? '' );
		if ( isset( $opt['price_range'] ) && ! in_array( $price_range, $allowed_price_range, true ) ) {
			$validation_errors[] = sprintf( 'Price range "%s" is not allowed. Allowed values: empty, $, $$, $$$, $$$$.', esc_html( $price_range ) );
			$price_range         = '';
		}

		$separator = sanitize_text_field( $opt['title_separator'] ?? $existing['title_separator'] ?? '|' );
		if ( isset( $opt['title_separator'] ) && ! in_array( $separator, $allowed_separators, true ) ) {
			$validation_errors[] = sprintf(
				'Title separator "%s" is not allowed. Using default "|" instead. Allowed separators: %s',
				esc_html( $separator ),
				implode( ', ', array_map( 'esc_html', $allowed_separators ) )
			);
			$separator           = '|';
		}

		// Validate and sanitize phone number (allow only phone-safe characters)
		$phone = sanitize_text_field( $opt['business_phone'] ?? $existing['business_phone'] ?? '' );
		if ( ! empty( $phone ) && ! preg_match( '/^\+?[0-9\s\-\(\)\.ext]+$/', $phone ) ) {
			$validation_errors[] = sprintf( 'Phone number "%s" contains invalid characters. Only numbers, spaces, +, -, (), ., and "ext" are allowed.', esc_html( $phone ) );
			$phone               = '';
		}

		// IndexNow key management mode: auto (system-generated) or custom (manual)
		$indexnow_key_mode = sanitize_key( $opt['indexnow_key_mode'] ?? $existing['indexnow_key_mode'] ?? 'auto' );
		if ( ! in_array( $indexnow_key_mode, array( 'auto', 'custom' ), true ) ) {
			$indexnow_key_mode = 'auto';
		}

		// Validate IndexNow key based on selected mode.
		// Rule requested: alphanumeric 8-128 chars for custom keys.
		$indexnow_key = sanitize_text_field( $opt['indexnow_key'] ?? $existing['indexnow_key'] ?? '' );
		if ( $indexnow_key_mode === 'custom' ) {
			if ( trim( $indexnow_key ) === '' ) {
				$validation_errors[] = 'Custom IndexNow key is required when "Use custom key" is selected.';
			} elseif ( ( $field_changed( 'indexnow_key' ) || $field_changed( 'indexnow_key_mode' ) ) && ! preg_match( '/^[A-Za-z0-9]{8,128}$/', $indexnow_key ) ) {
				$validation_errors[] = 'Custom IndexNow key must be 8-128 alphanumeric characters.';
			}
		}

		// If user switches from custom to auto mode, discard the old manual key.
		// Auto mode should be system-managed and regenerate when enabled.
		if ( $indexnow_key_mode === 'auto' && $field_changed( 'indexnow_key_mode' ) ) {
			$indexnow_key = '';
		}

		$clean = array(
			'google_verification'                  => self::validate_verification_code( $opt['google_verification'] ?? $existing['google_verification'] ?? '', $existing['google_verification'] ?? '', 'Google verification code', $max_lengths['google_verification'], $validation_errors, $field_changed( 'google_verification' ) ),
			'bing_verification'                    => self::validate_verification_code( $opt['bing_verification'] ?? $existing['bing_verification'] ?? '', $existing['bing_verification'] ?? '', 'Bing verification code', $max_lengths['bing_verification'], $validation_errors, $field_changed( 'bing_verification' ) ),
			'yandex_verification'                  => self::validate_verification_code( $opt['yandex_verification'] ?? $existing['yandex_verification'] ?? '', $existing['yandex_verification'] ?? '', 'Yandex verification code', $max_lengths['yandex_verification'], $validation_errors, $field_changed( 'yandex_verification' ) ),
			'default_og_image'                     => self::validate_url( $opt['default_og_image'] ?? $existing['default_og_image'] ?? '', 'Default OG image', $validation_errors, true ),
			'org_name'                             => self::validate_text_field( $opt['org_name'] ?? $existing['org_name'] ?? '', 'Organization name', $max_lengths['org_name'], $validation_errors ),
			'org_logo'                             => self::validate_url( $opt['org_logo'] ?? $existing['org_logo'] ?? '', 'Organization logo', $validation_errors, true ),
			'indexnow_enabled'                     => isset( $opt['indexnow_enabled'] ) ? ( ! empty( $opt['indexnow_enabled'] ) ? 1 : 0 ) : ( $existing['indexnow_enabled'] ?? 0 ),
			'indexnow_key_mode'                    => $indexnow_key_mode,
			'indexnow_key'                         => $indexnow_key,
			'twitter_username'                     => self::validate_text_field( ltrim( sanitize_text_field( $opt['twitter_username'] ?? $existing['twitter_username'] ?? '' ), '@' ), 'Twitter username', $max_lengths['twitter_username'], $validation_errors ),
			'facebook_app_id'                      => self::validate_text_field( $opt['facebook_app_id'] ?? $existing['facebook_app_id'] ?? '', 'Facebook App ID', $max_lengths['facebook_app_id'], $validation_errors ),
			'theme_color'                          => self::validate_color( $opt['theme_color'] ?? $existing['theme_color'] ?? '', $validation_errors ),
			'default_robots_index'                 => $robots_index,
			'default_robots_follow'                => $robots_follow,
			'priority_page_ids'                    => isset( $opt['priority_page_ids'] ) && is_array( $opt['priority_page_ids'] )
			? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $opt['priority_page_ids'] ) ) ) ), 0, 30 )
			: ( isset( $existing['priority_page_ids'] ) && is_array( $existing['priority_page_ids'] )
				? array_slice( array_values( array_unique( array_filter( array_map( 'absint', $existing['priority_page_ids'] ) ) ) ), 0, 30 )
				: array() ),
			'page_diagnostics_priority_enabled'    => isset( $opt['page_diagnostics_priority_enabled'] ) ? ( ! empty( $opt['page_diagnostics_priority_enabled'] ) ? 1 : 0 ) : ( $existing['page_diagnostics_priority_enabled'] ?? 0 ),
			'page_diagnostics_scan_cron_frequency' => in_array(
				sanitize_key( $opt['page_diagnostics_scan_cron_frequency'] ?? $existing['page_diagnostics_scan_cron_frequency'] ?? 'disabled' ),
				array( 'disabled', 'hourly', 'daily', 'weekly', 'monthly' ),
				true
			)
			? sanitize_key( $opt['page_diagnostics_scan_cron_frequency'] ?? $existing['page_diagnostics_scan_cron_frequency'] ?? 'disabled' )
			: 'disabled',
			'enable_breadcrumbs'                   => isset( $opt['enable_breadcrumbs'] ) ? ( ! empty( $opt['enable_breadcrumbs'] ) ? 1 : 0 ) : ( $existing['enable_breadcrumbs'] ?? 0 ),
			'enable_local_business'                => isset( $opt['enable_local_business'] ) ? ( ! empty( $opt['enable_local_business'] ) ? 1 : 0 ) : ( $existing['enable_local_business'] ?? 0 ),
			'business_type'                        => $business_type,
			'business_phone'                       => $phone,
			'business_address'                     => self::validate_textarea( $opt['business_address'] ?? $existing['business_address'] ?? '', 'Business address', $max_lengths['business_address'], $validation_errors ),
			'business_hours'                       => self::validate_business_hours( $opt['business_hours'] ?? $existing['business_hours'] ?? '', 'Business hours', $max_lengths['business_hours'], $validation_errors ),
			'service_area'                         => self::validate_textarea( $opt['service_area'] ?? $existing['service_area'] ?? '', 'Service area', $max_lengths['service_area'], $validation_errors ),
			'price_range'                          => $price_range,
			'payment_methods'                      => self::validate_comma_list( $opt['payment_methods'] ?? $existing['payment_methods'] ?? '', 'Payment methods', $max_lengths['payment_methods'], $validation_errors ),
			'languages_spoken'                     => self::validate_comma_list( $opt['languages_spoken'] ?? $existing['languages_spoken'] ?? '', 'Languages spoken', $max_lengths['languages_spoken'], $validation_errors ),
			'title_separator'                      => $separator,
			// Sanitize templates even when using fallback values (prevents bad prior-format data)
			'title_templates'                      => isset( $opt['title_templates'] ) && is_array( $opt['title_templates'] )
			? self::validate_templates( $opt['title_templates'], 'title', $valid_variables, $validation_errors )
			: self::validate_templates( $existing['title_templates'] ?? array(), 'title', $valid_variables, $validation_errors ),
			'description_templates'                => isset( $opt['description_templates'] ) && is_array( $opt['description_templates'] )
			? self::validate_templates( $opt['description_templates'], 'description', $valid_variables, $validation_errors )
			: self::validate_templates( $existing['description_templates'] ?? array(), 'description', $valid_variables, $validation_errors ),
		);

		if ( $clean['indexnow_enabled'] && $clean['indexnow_key_mode'] === 'auto' && $clean['indexnow_key'] === '' ) {
			$clean['indexnow_key'] = ASNERISSEO_IndexNow::generate_key();
		}

		// Store validation errors as transient to display after redirect
		if ( ! empty( $validation_errors ) ) {
			set_transient( 'asneris_settings_validation_errors', $validation_errors, 30 );
			// Strict validation: block save if ANY validation errors exist
			return $existing;
		}

		// Clear any old validation errors on successful save
		delete_transient( 'asneris_settings_validation_errors' );

		return $clean;
	}

	public static function render_page() {
		// Load help modals for this page
		ASNERISSEO_Help_Modal::render_modals( 'settings' );

	  // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab display parameter, no data modification
		$current_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$indexnow_key = esc_attr( self::get( 'indexnow_key', '' ) );
		$key_url      = $indexnow_key ? esc_url( home_url( '/' . $indexnow_key . '.txt' ) ) : '';
		?>
	<div class="wrap ASNERISSEO-admin-wrap">
		<div id="asnerisseo-react-admin-shell-root"></div>
		<?php
		if ( defined( 'ASNERISSEO_REACT_ONLY_ADMIN' ) && ASNERISSEO_REACT_ONLY_ADMIN ) {
			?>
			</div><?php return; } ?>
		<div class="asnerisseo-fallback-settings">
		<h1>
		<span class="dashicons dashicons-search"></span>
		Asneris SEO Toolkit
		<?php ASNERISSEO_Help_Modal::render_help_icon( 'settings-general', 'Learn about settings' ); ?>
		</h1>
		<p class="ASNERISSEO-subtitle"><?php esc_html_e( 'Clear and simple SEO configuration for your WordPress site.', 'asneris-seo-toolkit' ); ?></p>

		<?php
		// Check for validation errors first
		$validation_errors = get_transient( 'asneris_settings_validation_errors' );

		// Display error or success message after settings form submission
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display check set by WordPress core after options.php redirect
		if ( isset( $_GET['settings-updated'] ) && sanitize_key( $_GET['settings-updated'] ) === 'true' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( ! empty( $validation_errors ) ) {
				// Show error message when validation failed (save was blocked)
				delete_transient( 'asneris_settings_validation_errors' );
				?>
			<div class="notice notice-error is-dismissible" style="margin: 15px 0; border-left-color: #dc3232;">
			<p><strong><?php esc_html_e( 'Settings could not be saved due to validation errors:', 'asneris-seo-toolkit' ); ?></strong></p>
			<ul style="margin: 5px 0 0 20px; list-style: disc;">
				<?php foreach ( $validation_errors as $error ) : ?>
				<li><?php echo wp_kses_post( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><?php esc_html_e( 'Please correct the errors above and try again. No changes were saved.', 'asneris-seo-toolkit' ); ?></p>
			</div>
				<?php
			} else {
				// Show success message only when no validation errors
				?>
			<div class="notice notice-success is-dismissible" style="margin: 15px 0;">
			<p><strong><?php esc_html_e( 'Settings saved successfully!', 'asneris-seo-toolkit' ); ?></strong> <?php esc_html_e( 'Your changes have been saved and are now active.', 'asneris-seo-toolkit' ); ?></p>
			</div>
				<?php
			}
		}
		?>

		<!-- Tab Navigation -->
		<nav class="nav-tab-wrapper ASNERISSEO-nav-tab-wrapper">
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=general" class="nav-tab <?php echo esc_attr( $current_tab === 'general' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-admin-generic"></span> General
		</a>
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=verification" class="nav-tab <?php echo esc_attr( $current_tab === 'verification' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-yes-alt"></span> Verification
		</a>
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=indexnow" class="nav-tab <?php echo esc_attr( $current_tab === 'indexnow' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-update"></span> IndexNow
		</a>
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=social" class="nav-tab <?php echo esc_attr( $current_tab === 'social' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-share"></span> Social Media
		</a>
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=schema" class="nav-tab <?php echo esc_attr( $current_tab === 'schema' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-editor-code"></span> Schema
		</a>
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=templates" class="nav-tab <?php echo esc_attr( $current_tab === 'templates' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-text"></span> Templates
		</a>
		<a href="?page=<?php echo esc_attr( ASNERIS_MENU_SLUG ); ?>-settings&tab=maintenance" class="nav-tab <?php echo esc_attr( $current_tab === 'maintenance' ? 'nav-tab-active' : '' ); ?>">
			<span class="dashicons dashicons-admin-tools"></span> Maintenance & Safety
		</a>

		</nav>

		<form method="post" action="options.php" class="ASNERISSEO-settings-form">
		<?php settings_fields( self::OPT ); ?>
		<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( add_query_arg( 'tab', $current_tab, admin_url( 'admin.php?page=' . ASNERIS_MENU_SLUG . '-settings' ) ) ); ?>" />

		<?php
		// Include all settings as hidden fields to preserve data from inactive tabs
		// This ensures validation runs on ALL fields, not just the current tab
		$all_settings       = get_option( self::OPT, array() );
		$current_tab_fields = array(); // Will be populated by render functions
		?>

		<?php if ( $current_tab === 'general' ) : ?>
			<?php self::render_general_tab(); ?>
			<?php $current_tab_fields = array( 'org_name', 'org_logo', 'default_robots_index', 'default_robots_follow', 'enable_breadcrumbs', 'title_separator' ); ?>
		<?php elseif ( $current_tab === 'verification' ) : ?>
			<?php self::render_verification_tab(); ?>
			<?php $current_tab_fields = array( 'google_verification', 'bing_verification', 'yandex_verification' ); ?>
		<?php elseif ( $current_tab === 'indexnow' ) : ?>
			<?php self::render_indexnow_tab( $indexnow_key, $key_url ); ?>
			<?php $current_tab_fields = array( 'indexnow_enabled', 'indexnow_key_mode', 'indexnow_key' ); ?>
		<?php elseif ( $current_tab === 'social' ) : ?>
			<?php self::render_social_tab(); ?>
			<?php $current_tab_fields = array( 'default_og_image', 'twitter_username', 'facebook_app_id', 'theme_color' ); ?>
		<?php elseif ( $current_tab === 'schema' ) : ?>
			<?php self::render_schema_tab(); ?>
			<?php $current_tab_fields = array( 'enable_local_business', 'business_type', 'business_phone', 'business_address', 'business_hours', 'service_area', 'price_range', 'payment_methods', 'languages_spoken' ); ?>
		<?php elseif ( $current_tab === 'templates' ) : ?>
			<?php self::render_templates_tab(); ?>
			<?php $current_tab_fields = array( 'title_templates', 'description_templates' ); ?>
		<?php elseif ( $current_tab === 'maintenance' ) : ?>
			<?php self::render_advanced_tab(); ?>
			<?php $current_tab_fields = array(); // Advanced tab doesn't have settings ?>

		<?php endif; ?>

		<?php
		// Output hidden fields for all other tabs to preserve their data
		foreach ( $all_settings as $key => $value ) {
			if ( ! in_array( $key, $current_tab_fields, true ) ) {
				self::render_hidden_field_recursive( array( self::OPT, $key ), $value );
			}
		}
		?>

		<?php submit_button( 'Save Settings', 'primary large' ); ?>
		</form>

		<?php // ASNERISSEO_Help_Content::render_sidebar('settings'); ?>
		</div>
	</div>
	
		<?php ASNERISSEO_Help_Modal::render_modals( 'settings' ); ?>
	
		<?php
	}

	private static function render_general_tab() {
		?>
	<div class="ASNERISSEO-tab-content">
		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-admin-home"></span> Site Information
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'settings-general', 'Site Information' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="org_name">Organization/Site Name</label>
				<?php ASNERISSEO_Help_Modal::render_help_icon( 'site-name', 'Organization/Site Name Help' ); ?>
			</th>
			<td>
				<input type="text" id="org_name" class="large-text" name="<?php echo esc_attr( self::OPT ); ?>[org_name]" value="<?php echo esc_attr( self::get( 'org_name', get_bloginfo( 'name' ) ) ); ?>">
				<p class="description">Used for schema markup and social meta tags.</p>
			</td>
			</tr>
			<tr>
			<th scope="row">
				<label for="org_logo">Logo URL</label>
				<?php ASNERISSEO_Help_Modal::render_help_icon( 'logo-url', 'Logo URL Help' ); ?>
			</th>
			<td>
				<div class="ASNERISSEO-media-upload">
				<input type="url" id="org_logo" class="large-text ASNERISSEO-media-url" name="<?php echo esc_attr( self::OPT ); ?>[org_logo]" value="<?php echo esc_url( self::get( 'org_logo' ) ); ?>">
				<button type="button" class="button ASNERISSEO-upload-button" data-target="#org_logo">
					<span class="dashicons dashicons-upload"></span> Upload Logo
				</button>
				<div class="ASNERISSEO-image-preview">
					<?php if ( self::get( 'org_logo' ) ) : ?>
					<img src="<?php echo esc_url( self::get( 'org_logo' ) ); ?>" style="max-width: 200px; margin-top: 10px;">
					<?php endif; ?>
				</div>
				</div>
				<p class="description">Recommended: 600x60px for best display across platforms. Allowed formats: jpg, jpeg, png, gif, webp, svg, bmp, ico.</p>
			</td>
			</tr>
		</table>
		</div>

		<!-- Sitemap Info -->
		<?php ASNERISSEO_Sitemap_Helper::render_sitemap_info(); ?>

		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-visibility"></span> Default Robots Settings
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'default-robots', 'Default Robots Settings' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="default_robots_index">Default Indexing</label>
			</th>
			<td>
				<select id="default_robots_index" name="<?php echo esc_attr( self::OPT ); ?>[default_robots_index]">
				<option value="index" <?php selected( self::get( 'default_robots_index', 'index' ), 'index' ); ?>>Index (allow search engines)</option>
				<option value="noindex" <?php selected( self::get( 'default_robots_index', 'index' ), 'noindex' ); ?>>NoIndex (hide from search engines)</option>
				</select>
			</td>
			</tr>
			<tr>
			<th scope="row">
				<label for="default_robots_follow">Default Following</label>
			</th>
			<td>
				<select id="default_robots_follow" name="<?php echo esc_attr( self::OPT ); ?>[default_robots_follow]">
				<option value="follow" <?php selected( self::get( 'default_robots_follow', 'follow' ), 'follow' ); ?>>Follow (allow link following)</option>
				<option value="nofollow" <?php selected( self::get( 'default_robots_follow', 'follow' ), 'nofollow' ); ?>>NoFollow (prevent link following)</option>
				</select>
			</td>
			</tr>
		</table>
		</div>
	</div>
		<?php
	}

	private static function render_verification_tab() {
		?>
	<div class="ASNERISSEO-tab-content">
		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-google"></span> Google Search Console
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'gsc-verification', 'Google Search Console Verification' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="google_verification">Verification Code</label>
			</th>
			<td>
				<input type="text" id="google_verification" class="large-text code" name="<?php echo esc_attr( self::OPT ); ?>[google_verification]" value="<?php echo esc_attr( self::get( 'google_verification' ) ); ?>" placeholder="abc123xyz456">
				<p class="description">
				<strong><?php esc_html_e( 'Enter ONLY the code value:', 'asneris-seo-toolkit' ); ?></strong><br>
				<?php esc_html_e( 'If Google gives you:', 'asneris-seo-toolkit' ); ?> <code>&lt;meta name="google-site-verification" content="<strong style="color: #d63638;">abc123xyz456</strong>" /&gt;</code><br>
				<?php esc_html_e( 'Enter only:', 'asneris-seo-toolkit' ); ?> <strong style="color: #00a32a;">abc123xyz456</strong> <?php esc_html_e( 'in the field above', 'asneris-seo-toolkit' ); ?>
				</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-admin-site"></span> Bing Webmaster Tools
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'bing-verification', 'Bing Webmaster Tools Verification' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="bing_verification">Verification Code</label>
			</th>
			<td>
				<input type="text" id="bing_verification" class="large-text code" name="<?php echo esc_attr( self::OPT ); ?>[bing_verification]" value="<?php echo esc_attr( self::get( 'bing_verification' ) ); ?>" placeholder="1234ABCD5678EFGH">
				<p class="description">
				<strong><?php esc_html_e( 'Enter ONLY the code value:', 'asneris-seo-toolkit' ); ?></strong><br>
				<?php esc_html_e( 'If Bing gives you:', 'asneris-seo-toolkit' ); ?> <code>&lt;meta name="msvalidate.01" content="<strong style="color: #d63638;">1234ABCD5678EFGH</strong>" /&gt;</code><br>
				<?php esc_html_e( 'Enter only:', 'asneris-seo-toolkit' ); ?> <strong style="color: #00a32a;">1234ABCD5678EFGH</strong> <?php esc_html_e( 'in the field above', 'asneris-seo-toolkit' ); ?>
				</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-admin-site-alt2"></span> Yandex Webmaster
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'yandex-verification', 'Yandex Webmaster Verification' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="yandex_verification">Verification Code</label>
			</th>
			<td>
				<input type="text" id="yandex_verification" class="large-text code" name="<?php echo esc_attr( self::OPT ); ?>[yandex_verification]" value="<?php echo esc_attr( self::get( 'yandex_verification' ) ); ?>" placeholder="1234567890abcdef">
				<p class="description">
				<strong><?php esc_html_e( 'Enter ONLY the code value:', 'asneris-seo-toolkit' ); ?></strong><br>
				<?php esc_html_e( 'If Yandex gives you:', 'asneris-seo-toolkit' ); ?> <code>&lt;meta name="yandex-verification" content="<strong style="color: #d63638;">1234567890abcdef</strong>" /&gt;</code><br>
				<?php esc_html_e( 'Enter only:', 'asneris-seo-toolkit' ); ?> <strong style="color: #00a32a;">1234567890abcdef</strong> <?php esc_html_e( 'in the field above', 'asneris-seo-toolkit' ); ?>
				</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-info-box" style="background: #e7f5fe; border-left: 4px solid #00a0d2; padding: 15px;">
		<p style="margin: 0 0 10px; font-weight: 600; color: #23282d;">
			<span class="dashicons dashicons-info" style="color: #00a0d2;"></span>
			<?php esc_html_e( 'What are Webmaster Tools?', 'asneris-seo-toolkit' ); ?>
		</p>
		<p style="margin: 0 0 12px; color: #50575e; line-height: 1.6;">
			<?php esc_html_e( 'Webmaster Tools are free platforms provided by search engines where you can:', 'asneris-seo-toolkit' ); ?>
		</p>
		<ul style="margin: 0 0 12px 20px; color: #50575e; line-height: 1.7;">
			<li><?php esc_html_e( 'Monitor your site\'s search performance and rankings', 'asneris-seo-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Submit sitemaps to help search engines discover your content', 'asneris-seo-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Check indexing status and fix crawling issues', 'asneris-seo-toolkit' ); ?></li>
			<li><?php esc_html_e( 'View search queries that bring visitors to your site', 'asneris-seo-toolkit' ); ?></li>
		</ul>
		<p style="margin: 0 0 10px; font-weight: 600; color: #23282d;">
			<?php esc_html_e( 'Next Steps After Saving:', 'asneris-seo-toolkit' ); ?>
		</p>
		<ol style="margin: 0 0 0 20px; color: #50575e; line-height: 1.7;">
			<li><?php esc_html_e( 'Click "Save Settings" below', 'asneris-seo-toolkit' ); ?></li>
			<li><?php esc_html_e( 'Visit the webmaster tool you\'re verifying and click their "Verify" button:', 'asneris-seo-toolkit' ); ?>
			<ul style="margin: 5px 0 5px 20px;">
				<li>
				<a href="https://search.google.com/search-console" target="_blank" style="text-decoration: none;">
					<?php esc_html_e( 'Google Search Console', 'asneris-seo-toolkit' ); ?>
					<span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
				</a>
				</li>
				<li>
				<a href="https://www.bing.com/webmasters" target="_blank" style="text-decoration: none;">
					<?php esc_html_e( 'Bing Webmaster Tools', 'asneris-seo-toolkit' ); ?>
					<span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
				</a>
				</li>
				<li>
				<a href="https://webmaster.yandex.com" target="_blank" style="text-decoration: none;">
					<?php esc_html_e( 'Yandex Webmaster', 'asneris-seo-toolkit' ); ?>
					<span class="dashicons dashicons-external" style="font-size: 12px; margin-top: 2px;"></span>
				</a>
				</li>
			</ul>
			</li>
			<li><?php esc_html_e( 'Once verified, you can submit your sitemap (see General tab)', 'asneris-seo-toolkit' ); ?></li>
		</ol>
		</div>
	</div>
		<?php
	}

	private static function render_indexnow_tab( $indexnow_key, $key_url ) {
		$indexnow_key_mode = self::get( 'indexnow_key_mode', 'auto' );
		if ( ! in_array( $indexnow_key_mode, array( 'auto', 'custom' ), true ) ) {
			$indexnow_key_mode = 'auto';
		}
		$is_custom_key_mode = ( $indexnow_key_mode === 'custom' );
		?>
	<div class="ASNERISSEO-tab-content">
		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-update"></span> IndexNow Configuration
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'indexnow-protocol', 'IndexNow Protocol' ); ?>
		</h2>
		<p class="description" style="margin-bottom: 20px;">
			Notify Bing, Yandex, and other participating search engines when content changes (Google not included).
		</p>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="indexnow_enabled">Enable IndexNow</label>
			</th>
			<td>
				<label class="ASNERISSEO-toggle">
				<input type="checkbox" id="indexnow_enabled" name="<?php echo esc_attr( self::OPT ); ?>[indexnow_enabled]" value="1" <?php checked( (int) self::get( 'indexnow_enabled', 0 ), 1 ); ?>>
				<span class="ASNERISSEO-toggle-slider"></span>
				</label>
				<p class="description">Automatically submit updated URLs to search engines.</p>
			</td>
			</tr>
			<tr>
			<th scope="row">
				<label>Key Management</label>
			</th>
			<td>
				<label style="display:block; margin-bottom: 8px;">
				<input type="radio" name="<?php echo esc_attr( self::OPT ); ?>[indexnow_key_mode]" value="auto" <?php checked( $indexnow_key_mode, 'auto' ); ?>>
				Auto-generate IndexNow key
				</label>
				<label style="display:block; margin-bottom: 8px;">
				<input type="radio" name="<?php echo esc_attr( self::OPT ); ?>[indexnow_key_mode]" value="custom" <?php checked( $indexnow_key_mode, 'custom' ); ?>>
				Use custom key
				</label>
				<p class="description">Use auto mode for most sites. Use custom mode if you need key reuse across environments or controlled migration.</p>
			</td>
			</tr>
			<tr>
			<th scope="row">
				<label for="indexnow_key">API Key</label>
			</th>
			<td>
				<input type="text" id="indexnow_key" class="large-text code" name="<?php echo esc_attr( self::OPT ); ?>[indexnow_key]" value="<?php echo esc_attr( $indexnow_key ); ?>" data-initial-value="<?php echo esc_attr( $indexnow_key ); ?>" placeholder="Auto-generated when enabled" <?php echo $is_custom_key_mode ? '' : 'readonly disabled'; ?>>
				<?php if ( $is_custom_key_mode ) : ?>
				<p class="description">Custom key validation: alphanumeric only, 8-128 characters.</p>
				<?php else : ?>
				<p class="description">This key is system-managed. Switch to “Use custom key” to edit manually.</p>
				<?php endif; ?>
				<p id="indexnow-key-modified-warning" class="description" style="display:none; color:#d63638; font-weight:600;">
				You have modified the manual IndexNow key. Save settings to apply this change.
				</p>
				<?php if ( $key_url ) : ?>
				<p class="description">
					<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> 
					Key file URL: <code><?php echo esc_url( $key_url ); ?></code>
					<a href="<?php echo esc_url( $key_url ); ?>" target="_blank" class="button button-small">Test Key File</a>
				</p>
				<?php else : ?>
				<p class="description">Enable IndexNow and save to auto-generate a key.</p>
				<?php endif; ?>
			</td>
			</tr>
		</table>
		</div>

		<?php if ( self::get( 'indexnow_enabled' ) ) : ?>
		<div class="ASNERISSEO-info-box ASNERISSEO-success-box">
		<h3><span class="dashicons dashicons-yes"></span> IndexNow is Active</h3>
		<p>Your site is automatically notifying search engines when content is published or updated.</p>
		<p><strong>Important:</strong> If this is your first time enabling IndexNow, go to <strong style="color: #2271b1;">Settings → Permalinks</strong> in WordPress admin and click <strong style="color: #d63638;">"Save Changes"</strong> (you don't need to change anything). This flushes the rewrite rules and registers the key file route.</p>
		</div>
		<?php endif; ?>
	</div>
		<?php
	}

	private static function render_social_tab() {
		?>
	<div class="ASNERISSEO-tab-content">
		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-format-image"></span> Default Open Graph Image
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'social-media-tags', 'Social Media Tags' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="default_og_image">Default Image</label>
			</th>
			<td>
				<div class="ASNERISSEO-media-upload">
				<input type="url" id="default_og_image" class="large-text ASNERISSEO-media-url" name="<?php echo esc_attr( self::OPT ); ?>[default_og_image]" value="<?php echo esc_url( self::get( 'default_og_image' ) ); ?>">
				<button type="button" class="button ASNERISSEO-upload-button" data-target="#default_og_image">
					<span class="dashicons dashicons-upload"></span> Upload Image
				</button>
				<div class="ASNERISSEO-image-preview">
					<?php if ( self::get( 'default_og_image' ) ) : ?>
					<img src="<?php echo esc_url( self::get( 'default_og_image' ) ); ?>" style="max-width: 400px; margin-top: 10px;">
					<?php endif; ?>
				</div>
				</div>
				<p class="description">Used when individual posts don't have a featured image. Recommended: 1200x630px. Allowed formats: jpg, jpeg, png, gif, webp, svg, bmp, ico.</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2><span class="dashicons dashicons-share"></span> Supported Social Media Platforms</h2>
		<p style="margin-bottom: 20px;">This plugin automatically generates optimized meta tags for all major social media platforms:</p>
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">
			<strong>✓ Facebook</strong><br><small>Open Graph + App ID</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #0077b5; border-radius: 3px;">
			<strong>✓ LinkedIn</strong><br><small>Article metadata</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #e60023; border-radius: 3px;">
			<strong>✓ Pinterest</strong><br><small>Rich Pins support</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #25d366; border-radius: 3px;">
			<strong>✓ WhatsApp</strong><br><small>Preview cards</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #5865f2; border-radius: 3px;">
			<strong>✓ Discord</strong><br><small>Rich embeds</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #0088cc; border-radius: 3px;">
			<strong>✓ Telegram</strong><br><small>Instant View</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #611f69; border-radius: 3px;">
			<strong>✓ Slack</strong><br><small>Link unfurling</small>
			</div>
			<div style="padding: 10px; background: #f0f6fc; border-left: 3px solid #1da1f2; border-radius: 3px;">
			<strong>✓ Twitter/X</strong><br><small>Large image cards</small>
			</div>
		</div>
		<p style="padding: 12px; background: #fff7ed; border-left: 3px solid #f59e0b; border-radius: 3px; margin-bottom: 20px;">
			<strong>📌 Note:</strong> Most platforms use Open Graph tags automatically. Only Twitter and Facebook require specific configuration below.
		</p>
		</div>

		<div class="ASNERISSEO-card">
		<h2><span class="dashicons dashicons-twitter"></span> Platform Configuration</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="twitter_username">Twitter Username</label>
			</th>
			<td>
				<div class="ASNERISSEO-input-prefix">
				<span class="prefix">@</span>
				<input type="text" id="twitter_username" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[twitter_username]" value="<?php echo esc_attr( self::get( 'twitter_username' ) ); ?>" placeholder="username">
				</div>
				<p class="description">Your Twitter/X username (without @).</p>
			</td>
			</tr>
			<tr>
			<th scope="row">
				<label for="facebook_app_id">Facebook App ID</label>
			</th>
			<td>
				<input type="text" id="facebook_app_id" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[facebook_app_id]" value="<?php echo esc_attr( self::get( 'facebook_app_id' ) ); ?>">
				<p class="description">Optional: For Facebook Insights and Open Graph validation.</p>
			</td>
			</tr>
			<tr>
			<th scope="row">
				<label for="theme_color">Theme Color</label>
			</th>
			<td>
				<input type="text" id="theme_color" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[theme_color]" value="<?php echo esc_attr( self::get( 'theme_color' ) ); ?>" placeholder="#2271b1">
				<p class="description">Hex color for Discord/Telegram embeds and mobile browser UI (e.g., #2271b1).</p>
			</td>
			</tr>
		</table>
		</div>
	</div>
		<?php
	}

	private static function render_schema_tab() {
		?>
	<div class="ASNERISSEO-tab-content">
		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-admin-home"></span> Organization Schema
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'schema-structured-data', 'Schema & Structured Data' ); ?>
		</h2>
		<p class="description" style="margin-bottom: 20px;">
			Schema.org markup helps search engines understand your content better and can enhance search results with rich snippets.
		</p>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="enable_breadcrumbs">Enable Breadcrumb Structured Data</label>
			</th>
			<td>
				<label class="ASNERISSEO-toggle">
				<input type="checkbox" id="enable_breadcrumbs" name="<?php echo esc_attr( self::OPT ); ?>[enable_breadcrumbs]" value="1" <?php checked( (int) self::get( 'enable_breadcrumbs', 0 ), 1 ); ?>>
				<span class="ASNERISSEO-toggle-slider"></span>
				</label>
				<p class="description">Add breadcrumb schema to posts and pages.</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-store"></span> Local Business Schema
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'local-biz-data', 'Local Business Schema' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="enable_local_business">Enable Local Business</label>
			</th>
			<td>
				<label class="ASNERISSEO-toggle">
				<input type="checkbox" id="enable_local_business" name="<?php echo esc_attr( self::OPT ); ?>[enable_local_business]" value="1" <?php checked( (int) self::get( 'enable_local_business', 0 ), 1 ); ?>>
				<span class="ASNERISSEO-toggle-slider"></span>
				</label>
				<p class="description">Add local business schema for better local search visibility.</p>
			   
				<div style="margin-top: 12px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
				<strong style="color: #856404;">📍 Important: Google Business Profile</strong>
				<p style="margin: 8px 0 0 0; color: #856404; line-height: 1.6;">
					Have you registered your business on <a href="https://business.google.com" target="_blank" style="color: #0073aa; text-decoration: underline;">Google Business Profile</a>?<br>
					If yes, <strong>make sure the Name, Address, and Phone number below EXACTLY match</strong> your Google Business Profile.<br>
					Consistent information = better local search rankings!
				</p>
				</div>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="business_type">Business Type</label>
			</th>
			<td>
				<select id="business_type" name="<?php echo esc_attr( self::OPT ); ?>[business_type]">
				<option value="LocalBusiness" <?php selected( self::get( 'business_type', 'LocalBusiness' ), 'LocalBusiness' ); ?>>Local Business (General)</option>
				
				<optgroup label="Food & Dining">
					<option value="Restaurant" <?php selected( self::get( 'business_type' ), 'Restaurant' ); ?>>Restaurant</option>
					<option value="FastFoodRestaurant" <?php selected( self::get( 'business_type' ), 'FastFoodRestaurant' ); ?>>Fast Food Restaurant</option>
					<option value="Cafe" <?php selected( self::get( 'business_type' ), 'Cafe' ); ?>>Cafe / Coffee Shop</option>
					<option value="Bakery" <?php selected( self::get( 'business_type' ), 'Bakery' ); ?>>Bakery</option>
					<option value="BarOrPub" <?php selected( self::get( 'business_type' ), 'BarOrPub' ); ?>>Bar / Pub</option>
					<option value="Winery" <?php selected( self::get( 'business_type' ), 'Winery' ); ?>>Winery</option>
				</optgroup>
				
				<optgroup label="Retail">
					<option value="Store" <?php selected( self::get( 'business_type' ), 'Store' ); ?>>Store (General)</option>
					<option value="ClothingStore" <?php selected( self::get( 'business_type' ), 'ClothingStore' ); ?>>Clothing Store</option>
					<option value="FurnitureStore" <?php selected( self::get( 'business_type' ), 'FurnitureStore' ); ?>>Furniture Store</option>
					<option value="HardwareStore" <?php selected( self::get( 'business_type' ), 'HardwareStore' ); ?>>Hardware Store</option>
					<option value="JewelryStore" <?php selected( self::get( 'business_type' ), 'JewelryStore' ); ?>>Jewelry Store</option>
					<option value="ShoeStore" <?php selected( self::get( 'business_type' ), 'ShoeStore' ); ?>>Shoe Store</option>
					<option value="SportsStore" <?php selected( self::get( 'business_type' ), 'SportsStore' ); ?>>Sports Store</option>
					<option value="ToyStore" <?php selected( self::get( 'business_type' ), 'ToyStore' ); ?>>Toy Store</option>
					<option value="ConvenienceStore" <?php selected( self::get( 'business_type' ), 'ConvenienceStore' ); ?>>Convenience Store</option>
				</optgroup>
				
				<optgroup label="Health & Beauty">
					<option value="HealthAndBeautyBusiness" <?php selected( self::get( 'business_type' ), 'HealthAndBeautyBusiness' ); ?>>Health & Beauty (General)</option>
					<option value="HairSalon" <?php selected( self::get( 'business_type' ), 'HairSalon' ); ?>>Hair Salon</option>
					<option value="BeautySalon" <?php selected( self::get( 'business_type' ), 'BeautySalon' ); ?>>Beauty Salon</option>
					<option value="DaySpa" <?php selected( self::get( 'business_type' ), 'DaySpa' ); ?>>Day Spa</option>
					<option value="NailSalon" <?php selected( self::get( 'business_type' ), 'NailSalon' ); ?>>Nail Salon</option>
					<option value="TattooParlor" <?php selected( self::get( 'business_type' ), 'TattooParlor' ); ?>>Tattoo Parlor</option>
				</optgroup>
				
				<optgroup label="Medical">
					<option value="Dentist" <?php selected( self::get( 'business_type' ), 'Dentist' ); ?>>Dentist</option>
					<option value="Physician" <?php selected( self::get( 'business_type' ), 'Physician' ); ?>>Physician / Doctor</option>
					<option value="MedicalClinic" <?php selected( self::get( 'business_type' ), 'MedicalClinic' ); ?>>Medical Clinic</option>
					<option value="Pharmacy" <?php selected( self::get( 'business_type' ), 'Pharmacy' ); ?>>Pharmacy</option>
					<option value="VeterinaryCare" <?php selected( self::get( 'business_type' ), 'VeterinaryCare' ); ?>>Veterinary Care</option>
				</optgroup>
				
				<optgroup label="Professional Services">
					<option value="ProfessionalService" <?php selected( self::get( 'business_type' ), 'ProfessionalService' ); ?>>Professional Service (General)</option>
					<option value="Attorney" <?php selected( self::get( 'business_type' ), 'Attorney' ); ?>>Attorney / Lawyer</option>
					<option value="Accountant" <?php selected( self::get( 'business_type' ), 'Accountant' ); ?>>Accountant</option>
					<option value="RealEstateAgent" <?php selected( self::get( 'business_type' ), 'RealEstateAgent' ); ?>>Real Estate Agent</option>
					<option value="Notary" <?php selected( self::get( 'business_type' ), 'Notary' ); ?>>Notary</option>
					<option value="InsuranceAgency" <?php selected( self::get( 'business_type' ), 'InsuranceAgency' ); ?>>Insurance Agency</option>
				</optgroup>
				
				<optgroup label="Home Services">
					<option value="HomeAndConstructionBusiness" <?php selected( self::get( 'business_type' ), 'HomeAndConstructionBusiness' ); ?>>Home Services (General)</option>
					<option value="Electrician" <?php selected( self::get( 'business_type' ), 'Electrician' ); ?>>Electrician</option>
					<option value="Plumber" <?php selected( self::get( 'business_type' ), 'Plumber' ); ?>>Plumber</option>
					<option value="HousePainter" <?php selected( self::get( 'business_type' ), 'HousePainter' ); ?>>House Painter</option>
					<option value="Locksmith" <?php selected( self::get( 'business_type' ), 'Locksmith' ); ?>>Locksmith</option>
					<option value="MovingCompany" <?php selected( self::get( 'business_type' ), 'MovingCompany' ); ?>>Moving Company</option>
					<option value="HVACBusiness" <?php selected( self::get( 'business_type' ), 'HVACBusiness' ); ?>>HVAC Business</option>
					<option value="Roofing" <?php selected( self::get( 'business_type' ), 'Roofing' ); ?>>Roofing Contractor</option>
				</optgroup>
				
				<optgroup label="Automotive">
					<option value="AutomotiveBusiness" <?php selected( self::get( 'business_type' ), 'AutomotiveBusiness' ); ?>>Automotive (General)</option>
					<option value="AutoRepair" <?php selected( self::get( 'business_type' ), 'AutoRepair' ); ?>>Auto Repair</option>
					<option value="AutoDealer" <?php selected( self::get( 'business_type' ), 'AutoDealer' ); ?>>Auto Dealer</option>
					<option value="AutoPartsStore" <?php selected( self::get( 'business_type' ), 'AutoPartsStore' ); ?>>Auto Parts Store</option>
					<option value="AutoRental" <?php selected( self::get( 'business_type' ), 'AutoRental' ); ?>>Auto Rental</option>
					<option value="AutoWash" <?php selected( self::get( 'business_type' ), 'AutoWash' ); ?>>Auto Wash</option>
					<option value="GasStation" <?php selected( self::get( 'business_type' ), 'GasStation' ); ?>>Gas Station</option>
				</optgroup>
				
				<optgroup label="Lodging">
					<option value="LodgingBusiness" <?php selected( self::get( 'business_type' ), 'LodgingBusiness' ); ?>>Lodging (General)</option>
					<option value="Hotel" <?php selected( self::get( 'business_type' ), 'Hotel' ); ?>>Hotel</option>
					<option value="Motel" <?php selected( self::get( 'business_type' ), 'Motel' ); ?>>Motel</option>
					<option value="Resort" <?php selected( self::get( 'business_type' ), 'Resort' ); ?>>Resort</option>
					<option value="BedAndBreakfast" <?php selected( self::get( 'business_type' ), 'BedAndBreakfast' ); ?>>Bed & Breakfast</option>
					<option value="Hostel" <?php selected( self::get( 'business_type' ), 'Hostel' ); ?>>Hostel</option>
					<option value="Campground" <?php selected( self::get( 'business_type' ), 'Campground' ); ?>>Campground</option>
				</optgroup>
				
				<optgroup label="Fitness & Recreation">
					<option value="SportsActivityLocation" <?php selected( self::get( 'business_type' ), 'SportsActivityLocation' ); ?>>Sports / Recreation (General)</option>
					<option value="FitnessCenter" <?php selected( self::get( 'business_type' ), 'FitnessCenter' ); ?>>Fitness Center / Gym</option>
					<option value="GolfCourse" <?php selected( self::get( 'business_type' ), 'GolfCourse' ); ?>>Golf Course</option>
					<option value="PublicSwimmingPool" <?php selected( self::get( 'business_type' ), 'PublicSwimmingPool' ); ?>>Swimming Pool</option>
					<option value="TennisComplex" <?php selected( self::get( 'business_type' ), 'TennisComplex' ); ?>>Tennis Complex</option>
				</optgroup>
				
				<optgroup label="Entertainment">
					<option value="EntertainmentBusiness" <?php selected( self::get( 'business_type' ), 'EntertainmentBusiness' ); ?>>Entertainment (General)</option>
					<option value="MovieTheater" <?php selected( self::get( 'business_type' ), 'MovieTheater' ); ?>>Movie Theater</option>
					<option value="NightClub" <?php selected( self::get( 'business_type' ), 'NightClub' ); ?>>Night Club</option>
				</optgroup>
				
				<optgroup label="Other">
					<option value="AnimalShelter" <?php selected( self::get( 'business_type' ), 'AnimalShelter' ); ?>>Animal Shelter</option>
					<option value="ChildCare" <?php selected( self::get( 'business_type' ), 'ChildCare' ); ?>>Child Care</option>
					<option value="SelfStorage" <?php selected( self::get( 'business_type' ), 'SelfStorage' ); ?>>Self Storage</option>
				</optgroup>
				</select>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="business_phone">Phone Number</label>
			</th>
			<td>
				<input type="tel" id="business_phone" class="regular-text" name="<?php echo esc_attr( self::OPT ); ?>[business_phone]" value="<?php echo esc_attr( self::get( 'business_phone' ) ); ?>">
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="business_address">Address</label>
			</th>
			<td>
				<textarea id="business_address" class="large-text" rows="3" name="<?php echo esc_attr( self::OPT ); ?>[business_address]"><?php echo esc_textarea( self::get( 'business_address' ) ); ?></textarea>
				<p class="description">Full business address for local SEO.</p>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="business_hours">Opening Hours</label>
			</th>
			<td>
				<textarea id="business_hours" class="large-text" rows="6" name="<?php echo esc_attr( self::OPT ); ?>[business_hours]" placeholder="Monday-Friday: 9:00 AM - 5:00 PM&#10;Saturday: 10:00 AM - 2:00 PM&#10;Sunday: Closed"><?php echo esc_textarea( self::format_business_hours_for_textarea( self::get( 'business_hours', array() ) ) ); ?></textarea>
				<p class="description">Use one line per day/day range. Examples: "Monday-Friday: 9:00 AM - 5:00 PM", "Saturday: Closed", "Sunday: 10:00-12:00, 13:00-16:00", "Monday: 24 Hours". Time format supports HH:MM AM/PM or 24-hour HH:MM. Overnight ranges are not supported. Saved as a structured schedule for schema output.</p>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="service_area">Service Area</label>
			</th>
			<td>
				<textarea id="service_area" class="large-text" rows="2" name="<?php echo esc_attr( self::OPT ); ?>[service_area]" placeholder="Singapore, Pasir Ris, Tampines"><?php echo esc_textarea( self::get( 'service_area' ) ); ?></textarea>
				<p class="description">Cities, regions, or areas you serve (comma-separated).</p>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="price_range">Price Range</label>
			</th>
			<td>
				<select id="price_range" name="<?php echo esc_attr( self::OPT ); ?>[price_range]">
				<option value="" <?php selected( self::get( 'price_range' ), '' ); ?>>Not specified</option>
				<option value="$" <?php selected( self::get( 'price_range' ), '$' ); ?>>$ (Budget-friendly)</option>
				<option value="$$" <?php selected( self::get( 'price_range' ), '$$' ); ?>>$$ (Moderate)</option>
				<option value="$$$" <?php selected( self::get( 'price_range' ), '$$$' ); ?>>$$$ (Expensive)</option>
				<option value="$$$$" <?php selected( self::get( 'price_range' ), '$$$$' ); ?>>$$$$ (Luxury)</option>
				</select>
				<p class="description">Relative price indicator for your services/products.</p>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="payment_methods">Payment Methods</label>
			</th>
			<td>
				<input type="text" id="payment_methods" class="large-text" name="<?php echo esc_attr( self::OPT ); ?>[payment_methods]" value="<?php echo esc_attr( self::get( 'payment_methods' ) ); ?>" placeholder="Cash, Credit Card, PayPal, Bank Transfer">
				<p class="description">Accepted payment methods (comma-separated).</p>
			</td>
			</tr>
			<tr class="ASNERISSEO-conditional" data-depends="enable_local_business">
			<th scope="row">
				<label for="languages_spoken">Languages Spoken</label>
			</th>
			<td>
				<input type="text" id="languages_spoken" class="large-text" name="<?php echo esc_attr( self::OPT ); ?>[languages_spoken]" value="<?php echo esc_attr( self::get( 'languages_spoken' ) ); ?>" placeholder="English, Mandarin, Malay">
				<p class="description">Languages your business supports (comma-separated).</p>
			</td>
			</tr>
		</table>
		</div>
	</div>
		<?php
	}

	private static function render_templates_tab() {
		$post_types            = get_post_types( array( 'public' => true ), 'objects' );
		$title_templates       = self::get( 'title_templates', array() );
		$description_templates = self::get( 'description_templates', array() );
		$separator             = self::get( 'title_separator', '|' );
		$variables             = ASNERISSEO_Templates::get_available_variables();
		?>
	<div class="ASNERISSEO-tab-content">
		<div class="ASNERISSEO-info-box ASNERISSEO-info">
		<h3>
			<span class="dashicons dashicons-info"></span> About Templates
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'seo-templates', 'SEO Templates' ); ?>
		</h3>
		<p>
			Title and description templates provide automated fallbacks when per-page values aren't set.
			Use variables like <code>{title}</code>, <code>{site}</code>, and <code>{separator}</code> to create dynamic templates.
		</p>
		<p><strong>Available Variables:</strong></p>
		<ul style="margin: 8px 0 0 20px; line-height: 1.8;">
			<?php foreach ( $variables as $var => $desc ) : ?>
			<li><code><?php echo esc_html( $var ); ?></code> - <?php echo esc_html( $desc ); ?></li>
			<?php endforeach; ?>
		</ul>
		<p style="margin-top: 12px; color: #2271b1;"><strong>Note:</strong> Variables are optional — you don't need to use all of them.</p>
		</div>

		<div class="ASNERISSEO-card">
		<h2><span class="dashicons dashicons-admin-settings"></span> Title Separator</h2>
		<table class="form-table">
			<tr>
			<th scope="row">
				<label for="title_separator">Separator Character</label>
			</th>
			<td>
				<input type="text" id="title_separator" name="<?php echo esc_attr( self::OPT ); ?>[title_separator]" value="<?php echo esc_attr( $separator ); ?>" class="regular-text" maxlength="3">
				<p class="description">Used in template variable <code>{separator}</code>. Common: | - · •</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2><span class="dashicons dashicons-editor-code"></span> Title Templates</h2>
		<p style="margin-top: 0; color: #646970;">Define title templates for each post type. Leave empty to use default behavior.</p>
		<p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> Used only if the page title is not set manually.</p>
		<table class="form-table">
			<?php foreach ( $post_types as $post_type ) : ?>
			<tr>
				<th scope="row">
				<label for="title_template_<?php echo esc_attr( $post_type->name ); ?>">
					<?php echo esc_html( $post_type->labels->singular_name ); ?>
				</label>
				</th>
				<td>
				<input 
					type="text" 
					id="title_template_<?php echo esc_attr( $post_type->name ); ?>" 
					name="<?php echo esc_attr( self::OPT ); ?>[title_templates][<?php echo esc_attr( $post_type->name ); ?>]" 
					value="<?php echo esc_attr( $title_templates[ $post_type->name ] ?? '' ); ?>" 
					class="large-text"
					placeholder="<?php echo esc_attr( '{title} {separator} {site}' ); ?>"
				>
				<p class="description">
					Example: <code>{title} {separator} {site}</code>
				</p>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2><span class="dashicons dashicons-text"></span> Description Templates</h2>
		<p style="margin-top: 0; color: #646970;">Define description templates for each post type. Leave empty to auto-generate from content.</p>
		<p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> Leave empty to automatically generate from page content.</p>
		<table class="form-table">
			<?php foreach ( $post_types as $post_type ) : ?>
			<tr>
				<th scope="row">
				<label for="description_template_<?php echo esc_attr( $post_type->name ); ?>">
					<?php echo esc_html( $post_type->labels->singular_name ); ?>
				</label>
				</th>
				<td>
				<textarea 
					id="description_template_<?php echo esc_attr( $post_type->name ); ?>" 
					name="<?php echo esc_attr( self::OPT ); ?>[description_templates][<?php echo esc_attr( $post_type->name ); ?>]" 
					rows="3"
					class="large-text"
					placeholder="Auto-generated from excerpt or content"
				><?php echo esc_textarea( $description_templates[ $post_type->name ] ?? '' ); ?></textarea>
				<p class="description">
					Variables work here too. Leave empty for automatic excerpt extraction.
				</p>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		</div>
	</div>
		<?php
	}

	private static function render_advanced_tab() {
		?>
	<div class="ASNERISSEO-tab-content">
		<!-- Conflict Detection Status -->
		<?php ASNERISSEO_Conflict_Detector::render_status(); ?>
	   
		<div class="ASNERISSEO-card">
		<h2>
			<span class="dashicons dashicons-admin-tools"></span> Import / Export Settings
			<?php ASNERISSEO_Help_Modal::render_help_icon( 'maintenance-safety', 'Maintenance & Safety' ); ?>
		</h2>
		<table class="form-table">
			<tr>
			<th scope="row">Export Settings</th>
			<td>
				<button type="button" class="button" id="ASNERISSEO-export-settings">
				<span class="dashicons dashicons-download"></span> Export Configuration
				</button>
				<p class="description">Download your current settings as a JSON file.</p>
			</td>
			</tr>
			<tr>
			<th scope="row">Import Settings</th>
			<td>
				<input type="file" id="ASNERISSEO-import-file" accept=".json" style="display:none;">
				<button type="button" class="button" id="ASNERISSEO-import-settings">
				<span class="dashicons dashicons-upload"></span> Import Configuration
				</button>
				<p class="description">Upload a previously exported settings file.</p>
				<p style="margin-top: 8px; color: #2271b1;"><strong>Note:</strong> Use only files previously exported from this plugin.</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-card">
		<h2><span class="dashicons dashicons-trash"></span> Reset Settings</h2>
		<table class="form-table">
			<tr>
			<th scope="row">Clear All Data</th>
			<td>
				<button type="button" class="button button-link-delete" id="ASNERISSEO-reset-settings">
				<span class="dashicons dashicons-warning"></span> Reset All Settings
				</button>
				<p class="description">This will delete all plugin settings. This action cannot be undone.</p>
				<p style="margin-top: 8px; color: #2271b1;"><strong>Tip:</strong> Export your settings before resetting.</p>
			</td>
			</tr>
		</table>
		</div>

		<div class="ASNERISSEO-info-box">
		<h3><span class="dashicons dashicons-info"></span> Plugin Information</h3>
		<p><strong>Version:</strong> <?php echo esc_html( ASNERISSEO_VERSION ); ?></p>
		<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
			<p><strong>Plugin Path:</strong> <code><?php echo esc_html( ASNERISSEO_DIR ); ?></code></p>
		<?php endif; ?>
		</div>
	</div>
		<?php
	}

	/**
	 * AJAX handler for exporting settings
	 */
	public static function ajax_export_settings() {
		check_ajax_referer( 'ASNERISSEO_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$settings = get_option( self::OPT, array() );
		wp_send_json_success( $settings );
	}

	/**
	 * AJAX handler for importing settings
	 */
	public static function ajax_import_settings() {
		check_ajax_referer( 'ASNERISSEO_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		// Get raw settings data WITHOUT pre-sanitization to allow proper validation
	  // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Intentionally accessing raw values to detect dangerous patterns before sanitization strips them
		$settings = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			wp_send_json_error( 'No settings data provided' );
		}

		// IMPORTANT: Pass raw data to sanitize() so validation can detect dangerous patterns
		// before sanitization strips them. The sanitize() method will handle all validation
		// and sanitization with proper error detection.
		$clean_settings = self::sanitize( $settings );

		// Check if sanitize() returned an error (validation failed)
		// Note: sanitize() may have added validation errors to transient
		$validation_errors = get_transient( 'asneris_settings_validation_errors' );
		if ( ! empty( $validation_errors ) ) {
			delete_transient( 'asneris_settings_validation_errors' );
			wp_send_json_error( 'Import validation failed: ' . implode( ', ', $validation_errors ) );
			return;
		}

		update_option( self::OPT, $clean_settings );

		wp_send_json_success( 'Settings imported successfully' );
	}

	/**
	 * AJAX handler for resetting settings
	 */
	public static function ajax_reset_settings() {
		check_ajax_referer( 'ASNERISSEO_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		delete_option( self::OPT );
		wp_send_json_success( 'Settings reset successfully' );
	}

	/**
	 * Validate text field with length limit and dangerous pattern detection
	 *
	 * @param string $value The value to validate
	 * @param string $label The field label for error messages
	 * @param int    $max_length Maximum allowed length
	 * @param array  &$errors Reference to errors array
	 * @return string Sanitized and validated value or empty string on validation failure
	 */
	private static function validate_text_field( $value, $label, $max_length, &$errors ) {
		$original_value = $value;
		$value          = sanitize_text_field( $value );

		// Check for dangerous patterns in ORIGINAL value (before sanitization removes tags)
		if ( self::contains_dangerous_patterns( $original_value, $label, $errors ) ) {
			return ''; // Return empty - will trigger block when errors exist
		}

		// Check length limit
		if ( strlen( $value ) > $max_length ) {
			$errors[] = sprintf( '%s exceeds maximum length of %d characters.', esc_html( $label ), $max_length );
			return ''; // Return empty - will trigger block when errors exist
		}

		return $value;
	}

	/**
	 * Render hidden inputs recursively (supports nested arrays in settings payload).
	 *
	 * @param array $name_parts Parts of the field name.
	 * @param mixed $value Scalar or array value.
	 */
	private static function render_hidden_field_recursive( $name_parts, $value ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $subkey => $subvalue ) {
				$next_parts   = $name_parts;
				$next_parts[] = $subkey;
				self::render_hidden_field_recursive( $next_parts, $subvalue );
			}
			return;
		}

		$name = (string) array_shift( $name_parts );
		foreach ( $name_parts as $part ) {
			$name .= '[' . $part . ']';
		}

		printf(
			'<input type="hidden" name="%s" value="%s" />',
			esc_attr( $name ),
			esc_attr( (string) $value )
		);
	}

	/**
	 * Convert stored business hours to a textarea-friendly display.
	 *
	 * @param mixed $hours Stored business hours value (array or prior-format string).
	 * @return string
	 */
	public static function format_business_hours_for_textarea( $hours ) {
		// Always normalize first so prior-format/plain-text values are converted
		// and displayed consistently (AM/PM + grouped day ranges).
		$hours = self::get_structured_business_hours( $hours );
		if ( ! is_array( $hours ) ) {
			return '';
		}

		$day_labels = array(
			'monday'    => 'Monday',
			'tuesday'   => 'Tuesday',
			'wednesday' => 'Wednesday',
			'thursday'  => 'Thursday',
			'friday'    => 'Friday',
			'saturday'  => 'Saturday',
			'sunday'    => 'Sunday',
		);

		// Normalize each day to a comparable string form first.
		$normalized = array();
		foreach ( $day_labels as $day_key => $day_label ) {
			$slots = $hours[ $day_key ] ?? array();
			$parts = array();

			if ( is_array( $slots ) ) {
				foreach ( $slots as $slot ) {
					if ( ! is_array( $slot ) || ! isset( $slot['open'], $slot['close'] ) ) {
						continue;
					}
					$open_display  = self::format_business_hour_display_time( sanitize_text_field( $slot['open'] ) );
					$close_display = self::format_business_hour_display_time( sanitize_text_field( $slot['close'] ) );
					$parts[]       = $open_display . ' - ' . $close_display;
				}
			}

			$normalized[] = array(
				'key'   => $day_key,
				'label' => $day_label,
				'value' => ! empty( $parts ) ? implode( ', ', $parts ) : 'Closed',
			);
		}

		// Group consecutive days with the same schedule into ranges (e.g., Monday-Friday).
		$lines = array();
		$start = 0;
		$total = count( $normalized );

		while ( $start < $total ) {
			$end = $start;
			while ( $end + 1 < $total && $normalized[ $end + 1 ]['value'] === $normalized[ $start ]['value'] ) {
				++$end;
			}

			$day_part = ( $start === $end )
			? $normalized[ $start ]['label']
			: $normalized[ $start ]['label'] . '-' . $normalized[ $end ]['label'];

			$lines[] = $day_part . ': ' . $normalized[ $start ]['value'];
			$start   = $end + 1;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Format HH:MM (24-hour) into h:MM AM/PM for textarea display.
	 * Returns original value when format does not match expected input.
	 *
	 * @param string $time Time in HH:MM format.
	 * @return string
	 */
	private static function format_business_hour_display_time( $time ) {
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches ) ) {
			return $time;
		}

		$hour   = (int) $matches[1];
		$minute = (int) $matches[2];
		$ampm   = ( $hour >= 12 ) ? 'PM' : 'AM';
		$hour12 = $hour % 12;
		if ( $hour12 === 0 ) {
			$hour12 = 12;
		}

		return sprintf( '%d:%02d %s', $hour12, $minute, $ampm );
	}

	/**
	 * Public helper used by schema generation to normalize prior-format/string/array input.
	 *
	 * @param mixed $value Optional business hours value. Uses saved option when null.
	 * @return array Structured hours by day.
	 */
	public static function get_structured_business_hours( $value = null ) {
		$hours_value = $value;
		if ( $hours_value === null ) {
			$hours_value = self::get( 'business_hours', array() );
		}

		$errors = array();
		return self::normalize_business_hours( $hours_value, $errors, false );
	}

	/**
	 * Validate and normalize business hours input.
	 *
	 * @param mixed  $value Raw input value (textarea text or structured array)
	 * @param string $label Field label
	 * @param int    $max_length Max input length for text mode
	 * @param array  &$errors Validation errors
	 * @return array
	 */
	private static function validate_business_hours( $value, $label, $max_length, &$errors ) {
		return self::normalize_business_hours( $value, $errors, true, $label, $max_length );
	}

	/**
	 * Normalize opening hours from text/array to structured day=>slots format.
	 *
	 * @param mixed  $value Hours input
	 * @param array  &$errors Validation errors
	 * @param bool   $strict_validation Whether to push validation errors
	 * @param string $label Field label for errors
	 * @param int    $max_length Max length for textarea input
	 * @return array
	 */
	private static function normalize_business_hours( $value, &$errors, $strict_validation = true, $label = 'Business hours', $max_length = 2000 ) {
		$days   = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
		$output = array_fill_keys( $days, array() );

		if ( $value === '' || $value === null || $value === array() ) {
			return $output;
		}

		if ( is_string( $value ) ) {
			$original_value = $value;
			$value          = sanitize_textarea_field( $value );

			if ( $strict_validation && self::contains_dangerous_patterns( $original_value, $label, $errors ) ) {
				return $output;
			}

			if ( strlen( $value ) > $max_length ) {
				if ( $strict_validation ) {
					$errors[] = sprintf( '%s exceeds maximum length of %d characters.', esc_html( $label ), $max_length );
				}
				return $output;
			}

			$lines = preg_split( '/\r\n|\r|\n/', $value );
			foreach ( $lines as $line_number => $line_raw ) {
				$line = trim( $line_raw );
				if ( $line === '' ) {
					continue;
				}

				if ( ! preg_match( '/^(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)(?:\s*-\s*(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday))?\s*:\s*(.+)$/i', $line, $matches ) ) {
					if ( $strict_validation ) {
						$errors[] = sprintf( 'Business hours line %d is invalid. Use format like "Monday-Friday: 9:00 AM - 5:00 PM".', (int) $line_number + 1 );
					}
					continue;
				}

				$start_day = strtolower( $matches[1] );
				$end_day   = strtolower( ! empty( $matches[2] ) ? $matches[2] : $matches[1] );
				$time_part = trim( $matches[3] );

				$start_index = array_search( $start_day, $days, true );
				$end_index   = array_search( $end_day, $days, true );
				if ( $start_index === false || $end_index === false || $start_index > $end_index ) {
					if ( $strict_validation ) {
						$errors[] = sprintf( 'Business hours line %d has an invalid day range.', (int) $line_number + 1 );
					}
					continue;
				}

				$validated_slots = array();
				if ( preg_match( '/^(closed|off)$/i', $time_part ) ) {
					$validated_slots = array();
				} elseif ( preg_match( '/^(24\s*hours|24hrs|24h)$/i', $time_part ) ) {
					$validated_slots = array(
						array(
							'open'  => '00:00',
							'close' => '23:59',
						),
					);
				} else {
					$slot_texts = preg_split( '/\s*[;,]\s*/', $time_part );
					foreach ( $slot_texts as $slot_text ) {
						if ( ! preg_match( '/^(.+?)\s*-\s*(.+)$/', trim( $slot_text ), $slot_match ) ) {
							if ( $strict_validation ) {
								$errors[] = sprintf( 'Business hours line %d has an invalid time slot "%s".', (int) $line_number + 1, esc_html( $slot_text ) );
							}
							continue;
						}

						$open  = self::normalize_business_hour_time( $slot_match[1] );
						$close = self::normalize_business_hour_time( $slot_match[2] );

						if ( $open === false || $close === false ) {
							if ( $strict_validation ) {
								$errors[] = sprintf( 'Business hours line %d contains invalid time format. Use HH:MM or HH:MM AM/PM.', (int) $line_number + 1 );
							}
							continue;
						}

						$open_minutes  = self::business_hour_time_to_minutes( $open );
						$close_minutes = self::business_hour_time_to_minutes( $close );

						if ( $open_minutes === $close_minutes || $open_minutes > $close_minutes ) {
							if ( $strict_validation ) {
								$errors[] = sprintf( 'Business hours line %d has invalid range "%s-%s". Overnight ranges are not supported.', (int) $line_number + 1, esc_html( $open ), esc_html( $close ) );
							}
							continue;
						}

						$validated_slots[] = array(
							'open'  => $open,
							'close' => $close,
						);
					}

					$validated_slots = self::remove_overlapping_business_hour_slots( $validated_slots );
				}

				for ( $i = $start_index; $i <= $end_index; $i++ ) {
					$day_key = $days[ $i ];
					if ( empty( $validated_slots ) ) {
						$output[ $day_key ] = array();
						continue;
					}

					$output[ $day_key ] = self::remove_overlapping_business_hour_slots( array_merge( $output[ $day_key ], $validated_slots ) );
				}
			}

			return $output;
		}

		if ( ! is_array( $value ) ) {
			return $output;
		}

		foreach ( $days as $day ) {
			$slots = $value[ $day ] ?? array();
			if ( $slots === 'closed' || empty( $slots ) ) {
				$output[ $day ] = array();
				continue;
			}

			if ( ! is_array( $slots ) ) {
				if ( $strict_validation ) {
					$errors[] = sprintf( 'Business hours for %s must be an array of time slots.', esc_html( ucfirst( $day ) ) );
				}
				$output[ $day ] = array();
				continue;
			}

			$validated_slots = array();
			foreach ( $slots as $slot ) {
				if ( ! is_array( $slot ) ) {
					continue;
				}

				$open  = self::normalize_business_hour_time( $slot['open'] ?? '' );
				$close = self::normalize_business_hour_time( $slot['close'] ?? '' );
				if ( $open === false || $close === false ) {
					continue;
				}

				$open_minutes  = self::business_hour_time_to_minutes( $open );
				$close_minutes = self::business_hour_time_to_minutes( $close );
				if ( $open_minutes === $close_minutes || $open_minutes > $close_minutes ) {
					continue;
				}

				$validated_slots[] = array(
					'open'  => $open,
					'close' => $close,
				);
			}

			$output[ $day ] = self::remove_overlapping_business_hour_slots( $validated_slots );
		}

		return $output;
	}

	/**
	 * Normalize a time string to HH:MM (24-hour format).
	 *
	 * @param mixed $time Time input.
	 * @return string|false
	 */
	private static function normalize_business_hour_time( $time ) {
		$time = sanitize_text_field( (string) $time );

		if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches ) ) {
			return sprintf( '%02d:%02d', (int) $matches[1], (int) $matches[2] );
		}

		if ( preg_match( '/^(1[0-2]|0?[1-9]):([0-5]\d)\s*(AM|PM)$/i', $time, $matches ) ) {
			$hour   = (int) $matches[1];
			$minute = (int) $matches[2];
			$ampm   = strtoupper( $matches[3] );

			if ( $ampm === 'PM' && $hour < 12 ) {
				$hour += 12;
			}
			if ( $ampm === 'AM' && $hour === 12 ) {
				$hour = 0;
			}

			return sprintf( '%02d:%02d', $hour, $minute );
		}

		return false;
	}

	/**
	 * Convert HH:MM to absolute minutes.
	 *
	 * @param string $time
	 * @return int
	 */
	private static function business_hour_time_to_minutes( $time ) {
		$parts = explode( ':', $time );
		if ( count( $parts ) !== 2 ) {
			return 0;
		}
		return ( (int) $parts[0] * 60 ) + (int) $parts[1];
	}

	/**
	 * Remove overlapping time slots and return sorted schedule.
	 *
	 * @param array $slots
	 * @return array
	 */
	private static function remove_overlapping_business_hour_slots( $slots ) {
		if ( empty( $slots ) ) {
			return array();
		}

		usort(
			$slots,
			function ( $a, $b ) {
				return strcmp( (string) ( $a['open'] ?? '' ), (string) ( $b['open'] ?? '' ) );
			}
		);

		$clean = array();
		foreach ( $slots as $slot ) {
			if ( ! isset( $slot['open'], $slot['close'] ) ) {
				continue;
			}

			if ( empty( $clean ) ) {
				$clean[] = array(
					'open'  => $slot['open'],
					'close' => $slot['close'],
				);
				continue;
			}

			$last = $clean[ count( $clean ) - 1 ];
			if ( self::business_hour_time_to_minutes( $slot['open'] ) < self::business_hour_time_to_minutes( $last['close'] ) ) {
				continue;
			}

			$clean[] = array(
				'open'  => $slot['open'],
				'close' => $slot['close'],
			);
		}

		return array_values( $clean );
	}

	/**
	 * Validate textarea field with length limit and dangerous pattern detection
	 *
	 * @param string $value The value to validate
	 * @param string $label The field label for error messages
	 * @param int    $max_length Maximum allowed length
	 * @param array  &$errors Reference to errors array
	 * @return string Sanitized and validated value or empty string on validation failure
	 */
	private static function validate_textarea( $value, $label, $max_length, &$errors ) {
		$original_value = $value;
		$value          = sanitize_textarea_field( $value );

		// Check for dangerous patterns in ORIGINAL value (before sanitization)
		if ( self::contains_dangerous_patterns( $original_value, $label, $errors ) ) {
			return ''; // Return empty - will trigger block when errors exist
		}

		// Check length limit
		if ( strlen( $value ) > $max_length ) {
			$errors[] = sprintf( '%s exceeds maximum length of %d characters.', esc_html( $label ), $max_length );
			return ''; // Return empty - will trigger block when errors exist
		}

		return $value;
	}

	/**
	 * Validate URL with format and protocol checks
	 *
	 * @param string $value The URL to validate
	 * @param string $label The field label for error messages
	 * @param array  &$errors Reference to errors array
	 * @param bool   $require_image Whether to validate that URL points to an image file
	 * @return string Sanitized and validated URL
	 */
	private static function validate_url( $value, $label, &$errors, $require_image = false ) {
		if ( empty( $value ) ) {
			return '';
		}

		$value = esc_url_raw( $value );

		// Validate URL format
		if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$errors[] = sprintf( '%s is not a valid URL format. Field has been cleared.', esc_html( $label ) );
			return '';
		}

		// Check protocol
		$parsed = wp_parse_url( $value );
		if ( ! isset( $parsed['scheme'] ) || ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
			$errors[] = sprintf( '%s must use http:// or https:// protocol. Field has been cleared.', esc_html( $label ) );
			return '';
		}

		// Validate image file extension if required
		if ( $require_image ) {
			$path               = isset( $parsed['path'] ) ? $parsed['path'] : '';
			$allowed_extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico' );
			$extension          = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

			if ( empty( $extension ) || ! in_array( $extension, $allowed_extensions, true ) ) {
				$errors[] = sprintf(
					'%s must be a valid image URL (allowed extensions: %s). Field has been cleared.',
					esc_html( $label ),
					implode( ', ', $allowed_extensions )
				);
				return '';
			}

			// Verify the URL returns an image by checking Content-Type.
			// Some servers return a non-image Content-Type for HEAD requests, so we retry with GET.
			$response      = wp_remote_head(
				$value,
				array(
					'timeout'   => 5,
					'sslverify' => false,
				)
			);
			$allowed_mimes = array( 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp', 'image/x-icon', 'image/vnd.microsoft.icon' );
			$content_type  = '';

			if ( ! is_wp_error( $response ) ) {
				$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			}

			if ( ! empty( $content_type ) ) {
				// Extract base content type (remove charset if present)
				$content_type = strtolower( trim( explode( ';', $content_type )[0] ) );
			}

			if ( empty( $content_type ) || ! in_array( $content_type, $allowed_mimes, true ) ) {
				// Try GET if HEAD didn't return an image content type.
				$response = wp_remote_get(
					$value,
					array(
						'timeout'   => 5,
						'sslverify' => false,
						'headers'   => array( 'Accept' => 'image/*' ),
					)
				);

				if ( ! is_wp_error( $response ) ) {
					$content_type = wp_remote_retrieve_header( $response, 'content-type' );
					if ( ! empty( $content_type ) ) {
						$content_type = strtolower( trim( explode( ';', $content_type )[0] ) );
					}
				}
			}

			if ( ! empty( $content_type ) && ! in_array( $content_type, $allowed_mimes, true ) ) {
				$errors[] = sprintf(
					'%s does not return a valid image. Content-Type: %s. Field has been cleared.',
					esc_html( $label ),
					esc_html( $content_type )
				);
				return '';
			}
			// If both HEAD and GET fail, we still allow it (might be temporary network or remote server behavior).
			// Extension validation already passed, so we trust that value.
		}

		return $value;
	}

	/**
	 * Validate verification code field (Google, Bing, Yandex)
	 * Verification codes are alphanumeric strings with optional hyphens/underscores
	 *
	 * @param string $value The verification code to validate
	 * @param string $old_value The existing/previous value
	 * @param string $label The field label for error messages
	 * @param int    $max_length Maximum allowed length
	 * @param array  &$errors Reference to errors array
	 * @return string Sanitized and validated verification code
	 */
	private static function validate_verification_code( $value, $old_value, $label, $max_length, &$errors, $field_changed = true ) {
		$original_value = $value;
		$value          = sanitize_text_field( $value );

		// If empty after sanitization, check if dangerous content was stripped
		if ( empty( $value ) ) {
			if ( $field_changed && ! empty( $original_value ) && trim( $original_value ) !== '' ) {
				$errors[] = sprintf( '%s contains invalid or suspicious content and was rejected.', esc_html( $label ) );
				return $old_value;
			}
			return ''; // Empty is valid
		}

		// Only validate format if the field was actually changed (not just falling back to existing)
		if ( ! $field_changed ) {
			return $value; // Return existing value without validation
		}

		// Verification codes should be alphanumeric with optional hyphens, underscores, or dots
		// Check if value contains only valid characters
		if ( ! preg_match( '/^[a-zA-Z0-9_\-.]+$/', $value ) ) {
			$errors[] = sprintf( '%s contains invalid characters. Only letters, numbers, hyphens, underscores, and dots are allowed.', esc_html( $label ) );
			return $old_value; // Keep the old value instead of trying to fix it
		}

		// Check length limit
		if ( strlen( $value ) > $max_length ) {
			$errors[] = sprintf( '%s exceeds maximum length of %d characters.', esc_html( $label ), $max_length );
			return $old_value; // Keep the old value instead of truncating
		}

		return $value;
	}

	/**
	 * Validate color field
	 *
	 * @param string $value The color value to validate
	 * @param array  &$errors Reference to errors array
	 * @return string Sanitized and validated color
	 */
	private static function validate_color( $value, &$errors ) {
		$sanitized = sanitize_hex_color( $value );

		if ( ! empty( $value ) && empty( $sanitized ) ) {
			$errors[] = sprintf( 'Theme color "%s" is not a valid hex color code. Field has been cleared.', esc_html( $value ) );
			return '';
		}

		return $sanitized;
	}

	/**
	 * Validate template arrays with variable validation and dangerous pattern detection
	 *
	 * @param array  $templates The template array to validate
	 * @param string $type 'title' or 'description' for context
	 * @param array  $valid_variables List of allowed template variables
	 * @param array  &$errors Reference to errors array
	 * @return array Sanitized and validated templates
	 */
	private static function validate_templates( $templates, $type, $valid_variables, &$errors ) {
		if ( ! is_array( $templates ) ) {
			return array();
		}

		$validated     = array();
		$sanitize_func = ( $type === 'description' ) ? 'sanitize_textarea_field' : 'sanitize_text_field';

		foreach ( $templates as $key => $template ) {
			// Store original value BEFORE sanitization to check for dangerous patterns
			$original_template = $template;
			$template          = $sanitize_func( $template );

			// Check for dangerous patterns in ORIGINAL value before sanitization strips them
			if ( self::contains_dangerous_patterns( $original_template, ucfirst( $type ) . ' template', $errors ) ) {
				continue; // Skip this template - don't add it to validated array
			}

			// Extract and validate template variables
			if ( preg_match_all( '/\{([^}]+)\}/', $template, $matches ) ) {
				foreach ( $matches[1] as $var ) {
					$full_var = '{' . $var . '}';
					if ( ! in_array( $full_var, $valid_variables, true ) ) {
						$errors[] = sprintf(
							'%s template contains invalid variable %s. Valid variables: %s',
							ucfirst( $type ),
							esc_html( $full_var ),
							esc_html( implode( ', ', $valid_variables ) )
						);
					}
				}
			}

			$validated[ $key ] = $template;
		}

		return $validated;
	}

	/**
	 * Validate comma-separated list (e.g., "Cash, Credit Card, PayPal")
	 *
	 * @param string $value The comma-separated list to validate
	 * @param string $label The field label for error messages
	 * @param int    $max_length Maximum allowed length for the entire string
	 * @param array  &$errors Reference to errors array
	 * @return string Sanitized and validated comma-separated list
	 */
	private static function validate_comma_list( $value, $label, $max_length, &$errors ) {
		$original_value = $value;

		// Check for dangerous patterns in ORIGINAL value (before sanitization)
		if ( self::contains_dangerous_patterns( $original_value, $label, $errors ) ) {
			return ''; // Return empty - will trigger block when errors exist
		}

		// Split by comma, trim whitespace from each item, and sanitize each item
		$items = array_map( 'trim', explode( ',', $value ) );
		$items = array_map( 'sanitize_text_field', $items );

		// Filter out empty items
		$items = array_filter(
			$items,
			function ( $item ) {
				return $item !== '';
			}
		);

		// Rejoin into comma-separated string
		$value = implode( ', ', $items );

		// Check length limit on the final string
		if ( strlen( $value ) > $max_length ) {
			$errors[] = sprintf( '%s exceeds maximum length of %d characters.', esc_html( $label ), $max_length );
			return ''; // Return empty - will trigger block when errors exist
		}

		return $value;
	}

	/**
	 * Check for dangerous patterns in user input
	 *
	 * @param string $value The value to check
	 * @param string $label The field label for error messages
	 * @param array  &$errors Reference to errors array
	 * @return bool True if dangerous patterns found
	 */
	private static function contains_dangerous_patterns( $value, $label, &$errors ) {
		$dangerous_patterns = array(
			'/\.ps1\b/i'         => 'PowerShell script',
			'/\.exe\b/i'         => 'executable file',
			'/\.bat\b/i'         => 'batch file',
			'/\.cmd\b/i'         => 'command file',
			'/\.sh\b/i'          => 'shell script',
			'/<script/i'         => 'script tag',
			'/<iframe/i'         => 'iframe tag',
			'/<object/i'         => 'object tag',
			'/<embed/i'          => 'embed tag',
			'/<\?php/i'          => 'PHP code',
			'/javascript:/i'     => 'JavaScript protocol',
			'/data:text\/html/i' => 'data URL',
			'/on\w+\s*=/i'       => 'event handler',
		);

		foreach ( $dangerous_patterns as $pattern => $description ) {
			if ( preg_match( $pattern, $value ) ) {
				$errors[] = sprintf(
					'%s contains suspicious content (%s). This has been removed for security.',
					esc_html( $label ),
					esc_html( $description )
				);
				return true;
			}
		}

		return false;
	}
}
