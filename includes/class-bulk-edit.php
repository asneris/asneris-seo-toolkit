<?php
if (!defined('ABSPATH')) exit;

class ASNERISSEO_Bulk_Edit {
  
  /**
   * Register bulk edit admin page
   */
  public static function register_menu() {
    add_submenu_page(
      ASNERIS_MENU_SLUG,
      esc_html__('Bulk Edit', 'asneris-seo-toolkit'),
      esc_html__('Bulk Edit', 'asneris-seo-toolkit'),
      'edit_posts',
      ASNERIS_MENU_SLUG . '-bulk-edit',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue bulk edit assets
   */
  public static function enqueue_assets($hook) {
    // WordPress uses sanitized menu TITLE (not slug) as parent identifier
    if ($hook !== 'asneris-seo-toolkit_page_' . ASNERIS_MENU_SLUG . '-bulk-edit') return;
    
    // Use timestamp for cache busting during development
    $version = ASNERISSEO_VERSION . '.' . time();
    
    wp_enqueue_style('ASNERISSEO-bulk-edit', ASNERISSEO_URL . 'assets/css/admin-style.css', [], $version);
    wp_enqueue_script('ASNERISSEO-bulk-edit', ASNERISSEO_URL . 'assets/js/bulk-edit.js', ['jquery'], $version, true);
    
    $inline_css = '/* Bulk Edit table layout */
.ASNERISSEO-bulk-table-wrapper{overflow-x:auto;margin:0 -20px;padding:0 20px;}
#ASNERISSEO-bulk-edit-table{width:100%;table-layout:fixed;border-collapse:collapse;min-width:1200px;}
#ASNERISSEO-bulk-edit-table th,#ASNERISSEO-bulk-edit-table td{padding:12px;vertical-align:middle;border-bottom:1px solid #e5e5e5;}
#ASNERISSEO-bulk-edit-table thead th,#ASNERISSEO-bulk-edit-table thead td{background:#f9f9f9;font-weight:600;border-bottom:2px solid #ccc;position:sticky;top:32px;z-index:10;}
#ASNERISSEO-bulk-edit-table .col-checkbox{width:40px;}
#ASNERISSEO-bulk-edit-table .col-title{width:220px;}
#ASNERISSEO-bulk-edit-table .col-seo-title{width:280px;}
#ASNERISSEO-bulk-edit-table .col-description{width:320px;}
#ASNERISSEO-bulk-edit-table .col-robots{width:140px;}
#ASNERISSEO-bulk-edit-table .col-actions{width:60px;text-align:center;}
#ASNERISSEO-bulk-edit-table input[type="text"],#ASNERISSEO-bulk-edit-table textarea,#ASNERISSEO-bulk-edit-table select{width:100%;box-sizing:border-box;padding:8px 10px;font-size:13px;line-height:1.4;border:1px solid #ddd;border-radius:4px;transition:border-color 0.2s;}
#ASNERISSEO-bulk-edit-table input[type="text"]:focus,#ASNERISSEO-bulk-edit-table textarea:focus,#ASNERISSEO-bulk-edit-table select:focus{border-color:#2271b1;outline:none;box-shadow:0 0 0 1px #2271b1;}
#ASNERISSEO-bulk-edit-table textarea{resize:vertical;min-height:60px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;}
#ASNERISSEO-bulk-edit-table .col-title strong{display:block;margin-bottom:4px;color:#2271b1;font-size:14px;}
#ASNERISSEO-bulk-edit-table .row-actions{margin-top:4px;font-size:12px;}
#ASNERISSEO-bulk-edit-table tbody tr:hover{background:#f6f7f7;}
#ASNERISSEO-bulk-edit-table input[type="checkbox"]{margin:0;cursor:pointer;}
.ASNERISSEO-edit-post-link{padding:6px 10px !important;height:auto !important;min-height:32px;display:inline-flex;align-items:center;justify-content:center;}
@media screen and (max-width:1400px){#ASNERISSEO-bulk-edit-table{min-width:1000px;}#ASNERISSEO-bulk-edit-table .col-description{width:260px;}}
@keyframes ASNERISSEO-slideDown{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
#ASNERISSEO-confirm-modal-content{margin:20px;}
@media screen and (max-width:640px){#ASNERISSEO-confirm-modal-content{min-width:auto;max-width:90%;margin:10px;}}';
    wp_add_inline_style('ASNERISSEO-bulk-edit', $inline_css);
    
    wp_localize_script('ASNERISSEO-bulk-edit', 'gscseoBulkEdit', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('ASNERISSEO_bulk_edit'),
    ]);
  }
  
  /**
   * Render bulk edit page
   */
  public static function render_page() {
    // Redirect if form data is in URL (should use AJAX instead)
    // Check for any parameter that looks like form submission
    $has_form_data = false;
    $form_nonce_ok = false;
    if (isset($_GET['ASNERISSEO_bulk_edit_filters'])) {
      $form_nonce_ok = wp_verify_nonce(
        sanitize_text_field(wp_unslash($_GET['ASNERISSEO_bulk_edit_filters'])),
        'ASNERISSEO_bulk_edit_filters'
      );
    }
    if ($form_nonce_ok) {
      foreach ($_GET as $key => $value) {
        $key = sanitize_key($key);
        if ($key === 'seo_title' || $key === 'seo_description' || $key === 'robots_index') {
          $has_form_data = true;
          break;
        }
      }
    }
    
    if ($has_form_data) {
      $redirect_args = [
        'page' => ASNERIS_MENU_SLUG . '-bulk-edit',
        'filter_type' => isset($_GET['filter_type']) ? sanitize_text_field(wp_unslash($_GET['filter_type'])) : 'post',
        'indexing' => isset($_GET['indexing']) ? sanitize_text_field(wp_unslash($_GET['indexing'])) : 'all',
        'ASNERISSEO_bulk_edit_filters' => wp_create_nonce('ASNERISSEO_bulk_edit_filters'),
      ];
      wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
      exit;
    }
    
    $post_types = get_post_types(['public' => true], 'objects');
    
    // Add nonce verification for admin filter parameters
    $nonce_verified = false;
    if (isset($_GET['ASNERISSEO_bulk_edit_filters'])) {
      $nonce_verified = wp_verify_nonce(
        sanitize_text_field(wp_unslash($_GET['ASNERISSEO_bulk_edit_filters'])),
        'ASNERISSEO_bulk_edit_filters'
      );
    }
    $selected_post_type = 'post';
    $indexing_filter = 'all';
    
    if (isset($_GET['filter_type']) && $nonce_verified) {
      $selected_post_type = sanitize_text_field(wp_unslash($_GET['filter_type']));
    }
    
    if (isset($_GET['indexing']) && $nonce_verified) {
      $indexing_filter = sanitize_text_field(wp_unslash($_GET['indexing']));
    }
    
    // Query posts
    $args = [
      'post_type' => $selected_post_type,
      'post_status' => 'publish',
      'posts_per_page' => 50,
      'orderby' => 'date',
      'order' => 'DESC',
    ];
    
    // Apply indexing filter
    if ($indexing_filter === 'indexed') {
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Meta query needed for filtering by indexing status in admin context
      $args['meta_query'] = [
        'relation' => 'OR',
        ['key' => '_ASNERISSEO_robots_index', 'compare' => 'NOT EXISTS'],
        ['key' => '_ASNERISSEO_robots_index', 'value' => 'index'],
      ];
    } elseif ($indexing_filter === 'noindex') {
      // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Meta query needed for filtering by indexing status in admin context
      $args['meta_query'] = [
        ['key' => '_ASNERISSEO_robots_index', 'value' => 'noindex'],
      ];
    }
    
    $posts_query = new WP_Query($args);
    ?>
    <div class="wrap ASNERISSEO-admin-wrap">
      <h1>
        <span class="dashicons dashicons-edit"></span>
        <?php esc_html_e('SEO Bulk Edit', 'asneris-seo-toolkit'); ?>
        <?php ASNERISSEO_Help_Modal::render_help_icon('bulk-overview', 'Learn about bulk edit'); ?>
      </h1>
      <p class="ASNERISSEO-subtitle"><?php esc_html_e('Update SEO titles, descriptions, and indexing settings for multiple pages/posts at once.', 'asneris-seo-toolkit'); ?></p>
      
      <div class="ASNERISSEO-settings-form">
        <div class="ASNERISSEO-tab-content">
      
      <!-- Filters -->
      <div class="ASNERISSEO-card">
        <h2>
          <span class="dashicons dashicons-filter"></span> <?php esc_html_e('Filter Content', 'asneris-seo-toolkit'); ?>
          <?php ASNERISSEO_Help_Modal::render_help_icon('filter-content', 'Learn about filtering content'); ?>
        </h2>
        <p class="description" style="margin-top: 0;"><?php esc_html_e('Filter which content appear in the table below. Use these filters to find specific content you want to edit.', 'asneris-seo-toolkit'); ?></p>
        <form method="get" action="">
          <?php wp_nonce_field('ASNERISSEO_bulk_edit_filters', 'ASNERISSEO_bulk_edit_filters', false); ?>
          <input type="hidden" name="page" value="<?php echo esc_attr(ASNERIS_MENU_SLUG . '-bulk-edit'); ?>">
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="filter_type"><?php esc_html_e('Content Type', 'asneris-seo-toolkit'); ?></label>
              </th>
              <td>
                <select name="filter_type" id="filter_type">
                  <?php foreach ($post_types as $pt): ?>
                    <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($selected_post_type, $pt->name); ?>>
                      <?php echo esc_html($pt->labels->name); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </td>
              
              <th scope="row">
                <label for="indexing"><?php esc_html_e('Indexing Status', 'asneris-seo-toolkit'); ?></label>
              </th>
              <td>
                <select name="indexing" id="indexing">
                  <option value="all" <?php selected($indexing_filter, 'all'); ?>><?php esc_html_e('All', 'asneris-seo-toolkit'); ?></option>
                  <option value="indexed" <?php selected($indexing_filter, 'indexed'); ?>><?php esc_html_e('Indexed', 'asneris-seo-toolkit'); ?></option>
                  <option value="noindex" <?php selected($indexing_filter, 'noindex'); ?>><?php esc_html_e('NoIndex', 'asneris-seo-toolkit'); ?></option>
                </select>
              </td>
              
              <td>
                <button type="submit" class="button"><?php esc_html_e('Filter', 'asneris-seo-toolkit'); ?></button>
              </td>
            </tr>
          </table>
        </form>
      </div>
      
      <!-- Bulk Actions -->
      <div class="ASNERISSEO-card" style="max-width: 100%; margin-top: 20px;">
        <h2>
          <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('Quick Bulk Actions', 'asneris-seo-toolkit'); ?>
          <?php ASNERISSEO_Help_Modal::render_help_icon('quick-bulk-actions', 'Learn about quick bulk actions'); ?>
        </h2>
        <p style="color: #646970;"><?php esc_html_e('Changes are previewed in the table. Nothing is saved until you click \'Save All Changes\'.', 'asneris-seo-toolkit'); ?></p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
          <button type="button" id="ASNERISSEO-bulk-set-index" class="button">
            <?php esc_html_e('✓ Allow in Search (Index)', 'asneris-seo-toolkit'); ?>
          </button>
          <button type="button" id="ASNERISSEO-bulk-set-noindex" class="button">
            <?php esc_html_e('✗ Hide from Search (NoIndex)', 'asneris-seo-toolkit'); ?>
          </button>
          <button type="button" id="ASNERISSEO-bulk-clear-title" class="button">
            <?php esc_html_e('Clear Content Titles', 'asneris-seo-toolkit'); ?>
          </button>
          <button type="button" id="ASNERISSEO-bulk-clear-description" class="button">
            <?php esc_html_e('Clear Descriptions', 'asneris-seo-toolkit'); ?>
          </button>
        </div>
      </div>
      
      <!-- Posts Table -->
      <div class="ASNERISSEO-card" style="max-width: 100%; margin-top: 20px;">
        <div class="ASNERISSEO-bulk-table-wrapper">
          <form id="ASNERISSEO-bulk-edit-form" method="post" action="" onsubmit="return false;">
            <input type="hidden" name="page" value="<?php echo esc_attr(ASNERIS_MENU_SLUG . '-bulk-edit'); ?>">
            <input type="hidden" name="filter_type" value="<?php echo esc_attr($selected_post_type); ?>">
            <input type="hidden" name="indexing" value="<?php echo esc_attr($indexing_filter); ?>">
            <table id="ASNERISSEO-bulk-edit-table" class="wp-list-table widefat striped">
              <thead>
                <tr>
                  <td class="check-column col-checkbox">
                    <input type="checkbox" id="ASNERISSEO-select-all" title="<?php esc_attr_e('Select/deselect all posts', 'asneris-seo-toolkit'); ?>">
                  </td>
                  <th class="col-title"><?php esc_html_e('Post/Page', 'asneris-seo-toolkit'); ?></th>
                  <th class="col-seo-title">
                    <?php esc_html_e('SEO Title', 'asneris-seo-toolkit'); ?>
                    <?php ASNERISSEO_Help_Modal::render_help_icon('seo-title-field', 'Leave blank to use auto-generated values.'); ?>
                  </th>
                  <th class="col-description">
                    <?php esc_html_e('Meta Description', 'asneris-seo-toolkit'); ?>
                    <?php ASNERISSEO_Help_Modal::render_help_icon('meta-description-field', 'Leave blank to use auto-generated values.'); ?>
                  </th>
                  <th class="col-robots">
                    <?php esc_html_e('Search Visibility', 'asneris-seo-toolkit'); ?>
                    <?php ASNERISSEO_Help_Modal::render_help_icon('indexing-status', 'Learn about search visibility'); ?>
                  </th>
                  <th class="col-actions"><?php esc_html_e('Edit', 'asneris-seo-toolkit'); ?></th>
                </tr>
              </thead>
              <tbody>
              <?php if ($posts_query->have_posts()): ?>
                <?php while ($posts_query->have_posts()): $posts_query->the_post(); 
                  $post_id = get_the_ID();
                  $seo_title = get_post_meta($post_id, '_ASNERISSEO_title', true);
                  $seo_desc = get_post_meta($post_id, '_ASNERISSEO_description', true);
                  $robots_index = get_post_meta($post_id, '_ASNERISSEO_robots_index', true) ?: 'index';
                ?>
                  <tr>
                    <td class="check-column col-checkbox">
                      <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post_id); ?>" class="ASNERISSEO-post-checkbox">
                    </td>
                    <td class="col-title">
                      <strong><?php the_title(); ?></strong>
                      <div class="row-actions">
                        <span><a href="<?php echo esc_url(get_permalink()); ?>" target="_blank"><?php esc_html_e('View', 'asneris-seo-toolkit'); ?></a></span>
                      </div>
                    </td>
                    <td class="col-seo-title">
                      <input 
                        type="text" 
                        name="seo_title[<?php echo esc_attr($post_id); ?>]" 
                        value="<?php echo esc_attr($seo_title); ?>" 
                        placeholder="<?php esc_attr_e('Leave blank for auto-generated title', 'asneris-seo-toolkit'); ?>"
                      >
                    </td>
                    <td class="col-description">
                      <textarea 
                        name="seo_description[<?php echo esc_attr($post_id); ?>]" 
                        rows="3" 
                        placeholder="<?php esc_attr_e('Leave blank for auto-generated description', 'asneris-seo-toolkit'); ?>"
                      ><?php echo esc_textarea($seo_desc); ?></textarea>
                    </td>
                    <td class="col-robots">
                      <select name="robots_index[<?php echo esc_attr($post_id); ?>]">
                        <option value="index" <?php selected($robots_index, 'index'); ?>><?php esc_html_e('✓ Allow Indexing', 'asneris-seo-toolkit'); ?></option>
                        <option value="noindex" <?php selected($robots_index, 'noindex'); ?>><?php esc_html_e('✗ Prevent Indexing', 'asneris-seo-toolkit'); ?></option>
                      </select>
                    </td>
                    <td class="col-actions">
                      <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" class="button button-small ASNERISSEO-edit-post-link" title="<?php esc_attr_e('Edit full post in WordPress editor', 'asneris-seo-toolkit'); ?>" target="_blank">
                        <span class="dashicons dashicons-external"></span>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 40px;">
                    <?php esc_html_e('No posts found.', 'asneris-seo-toolkit'); ?>
                  </td>
                </tr>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </tbody>
          </table>
        </div>
        
        <?php if ($posts_query->have_posts()): ?>
          <div style="margin-top: 20px; padding: 20px; background: #f9f9f9; border-top: 2px solid #e5e5e5;">
            <button type="submit" class="button button-primary button-large" title="<?php esc_attr_e('Applies all selected edits to the filtered posts.', 'asneris-seo-toolkit'); ?>" style="padding: 10px 24px; height: auto; font-size: 14px;">
              <span class="dashicons dashicons-yes" style="margin-top: 4px;"></span>
              <?php esc_html_e('Save All Changes', 'asneris-seo-toolkit'); ?>
            </button>
            <span id="ASNERISSEO-bulk-status" style="margin-left: 15px; font-weight: 600;"></span>
          </div>
        <?php endif; ?>
      </form>
    </div>
      
        </div><!-- .ASNERISSEO-tab-content -->
      </div><!-- .ASNERISSEO-settings-form -->
        
      <?php // ASNERISSEO_Help_Content::render_sidebar('bulk-edit'); ?>
    </div>
    <?php ASNERISSEO_Help_Modal::render_modals('bulk-edit'); ?>
    
    <!-- Custom Modal for Confirmations -->
    <div id="ASNERISSEO-confirm-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:100000; align-items:center; justify-content:center;">
      <div id="ASNERISSEO-confirm-modal-content" style="background:#fff; padding:0; border-radius:8px; min-width:400px; max-width:600px; width:auto; box-shadow:0 10px 40px rgba(0,0,0,0.2); overflow:hidden; animation:slideDown 0.3s ease-out;">
        <div style="background:linear-gradient(135deg, #2271b1 0%, #1a5a8a 100%); padding:20px 24px; border-bottom:1px solid #e0e0e0;">
          <h2 style="margin:0; font-size:18px; color:#fff; font-weight:600; display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-info" style="font-size:22px;"></span>
            <?php esc_html_e('Asneris SEO Toolkit', 'asneris-seo-toolkit'); ?>
          </h2>
        </div>
        <div style="padding:24px;">
          <p id="ASNERISSEO-confirm-message" style="margin:0 0 24px 0; font-size:14px; line-height:1.8; color:#3c434a; white-space:pre-line;"></p>
          <div style="display:flex; justify-content:flex-end; gap:12px;">
            <button type="button" class="button" id="ASNERISSEO-confirm-cancel" style="padding:8px 20px; font-size:13px;"><?php esc_html_e('Cancel', 'asneris-seo-toolkit'); ?></button>
            <button type="button" class="button button-primary" id="ASNERISSEO-confirm-ok" style="padding:8px 20px; font-size:13px; background:#2271b1; border-color:#2271b1;"><?php esc_html_e('Confirm', 'asneris-seo-toolkit'); ?></button>
          </div>
        </div>
      </div>
    </div>
    
    <?php
  }
  
  /**
   * AJAX handler for bulk save
   */
  public static function ajax_bulk_save() {
    check_ajax_referer('ASNERISSEO_bulk_edit', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied', 'asneris-seo-toolkit')]);
      return;
    }
    
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
    $titles = isset($_POST['seo_title']) ? map_deep(wp_unslash($_POST['seo_title']), 'sanitize_text_field') : [];
    $descriptions = isset($_POST['seo_description']) ? map_deep(wp_unslash($_POST['seo_description']), 'sanitize_textarea_field') : [];
    $robots = isset($_POST['robots_index']) ? map_deep(wp_unslash($_POST['robots_index']), 'sanitize_text_field') : [];
    
    $updated = 0;
    
    foreach ($post_ids as $post_id) {
      if (!current_user_can('edit_post', $post_id)) continue;
      
      if (isset($titles[$post_id])) {
        update_post_meta($post_id, '_ASNERISSEO_title', $titles[$post_id]);
      }
      
      if (isset($descriptions[$post_id])) {
        update_post_meta($post_id, '_ASNERISSEO_description', $descriptions[$post_id]);
      }
      
      if (isset($robots[$post_id])) {
        update_post_meta($post_id, '_ASNERISSEO_robots_index', $robots[$post_id]);
      }
      
      $updated++;
    }
    
    wp_send_json_success([
      /* translators: %d: number of posts updated */
      'message' => sprintf(esc_html__('%d posts updated successfully!', 'asneris-seo-toolkit'), $updated),
      'updated' => $updated
    ]);
  }
}
