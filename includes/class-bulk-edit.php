<?php
if (!defined('ABSPATH')) exit;

class CFSEO_Bulk_Edit {
  
  /**
   * Register bulk edit admin page
   */
  public static function register_menu() {
    add_submenu_page(
      'clarity-first-seo',
      __('Bulk Edit', 'clarity-first-seo'),
      __('Bulk Edit', 'clarity-first-seo'),
      'edit_posts',
      'cfseo-bulk-edit',
      [__CLASS__, 'render_page']
    );
  }
  
  /**
   * Enqueue bulk edit assets
   */
  public static function enqueue_assets($hook) {
    if ($hook !== 'clarity-first-seo_page_cfseo-bulk-edit' && $hook !== 'toplevel_page_cfseo-bulk-edit') return;
    
    wp_enqueue_style('cfseo-bulk-edit', CFSEO_URL . 'assets/css/admin-style.css', [], CFSEO_VERSION);
    wp_enqueue_script('cfseo-bulk-edit', CFSEO_URL . 'assets/js/bulk-edit.js', ['jquery'], CFSEO_VERSION, true);
    
    wp_localize_script('cfseo-bulk-edit', 'gscseoBulkEdit', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('CFSEO_bulk_edit'),
    ]);
  }
  
  /**
   * Render bulk edit page
   */
  public static function render_page() {
    $post_types = get_post_types(['public' => true], 'objects');
    $selected_post_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : 'post';
    $indexing_filter = isset($_GET['indexing']) ? sanitize_text_field($_GET['indexing']) : 'all';
    
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
      $args['meta_query'] = [
        'relation' => 'OR',
        ['key' => '_CFSEO_robots_index', 'compare' => 'NOT EXISTS'],
        ['key' => '_CFSEO_robots_index', 'value' => 'index'],
      ];
    } elseif ($indexing_filter === 'noindex') {
      $args['meta_query'] = [
        ['key' => '_CFSEO_robots_index', 'value' => 'noindex'],
      ];
    }
    
    $posts_query = new WP_Query($args);
    ?>
    <div class="wrap cfseo-admin-wrap">
      <h1>
        <span class="dashicons dashicons-edit"></span>
        <?php _e('SEO Bulk Edit', 'clarity-first-seo'); ?>
        <?php CFSEO_Help_Modal::render_help_icon('bulk-overview', 'Learn about bulk edit'); ?>
      </h1>
      <p class="cfseo-subtitle"><?php _e('Update SEO titles, descriptions, and indexing settings for multiple posts at once.', 'clarity-first-seo'); ?></p>
      
      <div class="cfseo-settings-form">
        <div class="cfseo-tab-content">
      
      <!-- Filters -->
      <div class="cfseo-card">
        <h2>
          <span class="dashicons dashicons-filter"></span> <?php _e('Filter Content', 'clarity-first-seo'); ?>
          <?php CFSEO_Help_Modal::render_help_icon('filter-content', 'Learn about filtering content'); ?>
        </h2>
        <p class="description" style="margin-top: 0;"><?php _e('Filter which content appear in the table below. Use these filters to find specific content you want to edit.', 'clarity-first-seo'); ?></p>
        <form method="get" action="">
          <input type="hidden" name="page" value="cfseo-bulk-edit">
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="filter_type"><?php _e('Content Type', 'clarity-first-seo'); ?></label>
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
                <label for="indexing"><?php _e('Indexing Status', 'clarity-first-seo'); ?></label>
              </th>
              <td>
                <select name="indexing" id="indexing">
                  <option value="all" <?php selected($indexing_filter, 'all'); ?>><?php _e('All', 'clarity-first-seo'); ?></option>
                  <option value="indexed" <?php selected($indexing_filter, 'indexed'); ?>><?php _e('Indexed', 'clarity-first-seo'); ?></option>
                  <option value="noindex" <?php selected($indexing_filter, 'noindex'); ?>><?php _e('NoIndex', 'clarity-first-seo'); ?></option>
                </select>
              </td>
              
              <td>
                <button type="submit" class="button"><?php _e('Filter', 'clarity-first-seo'); ?></button>
              </td>
            </tr>
          </table>
        </form>
      </div>
      
      <!-- Bulk Actions -->
      <div class="cfseo-card" style="max-width: 100%; margin-top: 20px;">
        <h2>
          <span class="dashicons dashicons-admin-generic"></span> <?php _e('Quick Bulk Actions', 'clarity-first-seo'); ?>
          <?php CFSEO_Help_Modal::render_help_icon('quick-bulk-actions', 'Learn about quick bulk actions'); ?>
        </h2>
        <p style="color: #646970;"><?php _e('Changes are previewed in the table. Nothing is saved until you click \'Save All Changes\'.', 'clarity-first-seo'); ?></p>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
          <button type="button" id="cfseo-bulk-set-index" class="button">
            <?php _e('✓ Allow in Search (Index)', 'clarity-first-seo'); ?>
          </button>
          <button type="button" id="cfseo-bulk-set-noindex" class="button">
            <?php _e('✗ Hide from Search (NoIndex)', 'clarity-first-seo'); ?>
          </button>
          <button type="button" id="cfseo-bulk-clear-title" class="button">
            <?php _e('Clear Content Titles', 'clarity-first-seo'); ?>
          </button>
          <button type="button" id="cfseo-bulk-clear-description" class="button">
            <?php _e('Clear Descriptions', 'clarity-first-seo'); ?>
          </button>
        </div>
      </div>
      
      <!-- Posts Table -->
      <div class="cfseo-card" style="max-width: 100%; margin-top: 20px; overflow-x: auto;">
        <style>
          #cfseo-bulk-edit-table {
            width: 100%;
            border-collapse: collapse;
          }
          #cfseo-bulk-edit-table th,
          #cfseo-bulk-edit-table td {
            padding: 8px 10px;
            vertical-align: top;
          }
          #cfseo-bulk-edit-table .col-checkbox { width: 30px; min-width: 30px; }
          #cfseo-bulk-edit-table .col-title { width: 200px; min-width: 180px; }
          #cfseo-bulk-edit-table .col-seo-title { width: 250px; min-width: 200px; }
          #cfseo-bulk-edit-table .col-description { width: 400px; min-width: 300px; }
          #cfseo-bulk-edit-table .col-robots { width: 80px; min-width: 80px; }
          #cfseo-bulk-edit-table .col-actions { width: 50px; min-width: 50px; }
          #cfseo-bulk-edit-table input[type="text"],
          #cfseo-bulk-edit-table textarea,
          #cfseo-bulk-edit-table select {
            width: 100%;
            box-sizing: border-box;
          }
        </style>
        <form id="cfseo-bulk-edit-form">
          <table id="cfseo-bulk-edit-table" class="wp-list-table widefat striped">
            <thead>
              <tr>
                <td class="check-column col-checkbox">
                  <input type="checkbox" id="cfseo-select-all" title="<?php esc_attr_e('Select/deselect all posts', 'clarity-first-seo'); ?>">
                </td>
                <th class="col-title"><?php _e('Post/Page', 'clarity-first-seo'); ?></th>
                <th class="col-seo-title">
                  <?php _e('Custom Content Title', 'clarity-first-seo'); ?>
                  <?php CFSEO_Help_Modal::render_help_icon('seo-title-field', 'Leave blank to use auto-generated values.'); ?>
                  <br><small style="font-weight:normal;color:#666;"><?php _e('(leave blank for auto)', 'clarity-first-seo'); ?></small>
                </th>
                <th class="col-description">
                  <?php _e('Custom Content Description', 'clarity-first-seo'); ?>
                  <?php CFSEO_Help_Modal::render_help_icon('meta-description-field', 'Leave blank to use auto-generated values.'); ?>
                  <br><small style="font-weight:normal;color:#666;"><?php _e('(leave blank for auto)', 'clarity-first-seo'); ?></small>
                </th>
                <th class="col-robots">
                  <?php _e('Search Visibility', 'clarity-first-seo'); ?>
                  <?php CFSEO_Help_Modal::render_help_icon('indexing-status', 'Learn about search visibility'); ?>
                </th>
                <th class="col-actions"></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($posts_query->have_posts()): ?>
                <?php while ($posts_query->have_posts()): $posts_query->the_post(); 
                  $post_id = get_the_ID();
                  $seo_title = get_post_meta($post_id, '_CFSEO_title', true);
                  $seo_desc = get_post_meta($post_id, '_CFSEO_description', true);
                  $robots_index = get_post_meta($post_id, '_CFSEO_robots_index', true) ?: 'index';
                ?>
                  <tr>
                    <th class="check-column">
                      <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post_id); ?>" class="cfseo-post-checkbox">
                    </th>
                    <td class="col-title">
                      <strong><?php the_title(); ?></strong>
                      <div class="row-actions">
                        <span><a href="<?php echo get_permalink(); ?>" target="_blank"><?php _e('View', 'clarity-first-seo'); ?></a></span>
                      </div>
                    </td>
                    <td class="col-seo-title">
                      <input 
                        type="text" 
                        name="seo_title[<?php echo esc_attr($post_id); ?>]" 
                        value="<?php echo esc_attr($seo_title); ?>" 
                        placeholder="<?php _e('Auto-generated from post title', 'clarity-first-seo'); ?>"
                      >
                    </td>
                    <td class="col-description">
                      <textarea 
                        name="seo_description[<?php echo esc_attr($post_id); ?>]" 
                        rows="2" 
                        placeholder="<?php _e('Auto-generated from excerpt or content', 'clarity-first-seo'); ?>"
                      ><?php echo esc_textarea($seo_desc); ?></textarea>
                    </td>
                    <td class="col-robots">
                      <select name="robots_index[<?php echo esc_attr($post_id); ?>]">
                        <option value="index" <?php selected($robots_index, 'index'); ?>><?php _e('Index', 'clarity-first-seo'); ?></option>
                        <option value="noindex" <?php selected($robots_index, 'noindex'); ?>><?php _e('NoIndex', 'clarity-first-seo'); ?></option>
                      </select>
                    </td>
                    <td class="col-actions">
                      <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-small cfseo-edit-post-link" title="<?php esc_attr_e('Edit full post in WordPress editor', 'clarity-first-seo'); ?>">
                        <span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align: center; padding: 40px;">
                    <?php _e('No posts found.', 'clarity-first-seo'); ?>
                  </td>
                </tr>
              <?php endif; ?>
              <?php wp_reset_postdata(); ?>
            </tbody>
          </table>
          
          <?php if ($posts_query->have_posts()): ?>
            <div style="margin-top: 20px;">
              <button type="submit" class="button button-primary button-large" title="<?php esc_attr_e('Applies all selected edits to the filtered posts.', 'clarity-first-seo'); ?>">
                <?php _e('Save All Changes', 'clarity-first-seo'); ?>
              </button>
              <span id="cfseo-bulk-status" style="margin-left: 15px;"></span>
            </div>
          <?php endif; ?>
        </form>
      </div>
      
        </div><!-- .cfseo-tab-content -->
      </div><!-- .cfseo-settings-form -->
        
      <?php // CFSEO_Help_Content::render_sidebar('bulk-edit'); ?>
    </div>
    <?php CFSEO_Help_Modal::render_modals('bulk-edit'); ?>
    
    <!-- Custom Modal for Confirmations -->
    <div id="cfseo-confirm-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:100000; align-items:center; justify-content:center;">
      <div style="background:#fff; padding:0; border-radius:8px; max-width:500px; box-shadow:0 10px 40px rgba(0,0,0,0.2); overflow:hidden; animation:slideDown 0.3s ease-out;">
        <div style="background:linear-gradient(135deg, #2271b1 0%, #1a5a8a 100%); padding:20px 24px; border-bottom:1px solid #e0e0e0;">
          <h2 style="margin:0; font-size:20px; color:#fff; font-weight:600; display:flex; align-items:center; gap:10px;">
            <span class="dashicons dashicons-chart-line" style="font-size:24px;"></span>
            <?php _e('Clarity-First SEO', 'clarity-first-seo'); ?>
          </h2>
        </div>
        <div style="padding:24px;">
          <p id="cfseo-confirm-message" style="margin:0 0 24px 0; font-size:15px; line-height:1.6; color:#3c434a;"></p>
          <div style="display:flex; justify-content:flex-end; gap:12px;">
            <button type="button" class="button" id="cfseo-confirm-cancel" style="padding:6px 16px; font-size:13px;"><?php _e('Cancel', 'clarity-first-seo'); ?></button>
            <button type="button" class="button button-primary" id="cfseo-confirm-ok" style="padding:6px 16px; font-size:13px; background:#2271b1; border-color:#2271b1;"><?php _e('Confirm', 'clarity-first-seo'); ?></button>
          </div>
        </div>
      </div>
    </div>
    
    <style>
      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateY(-20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    </style>
    
    <?php
  }
  
  /**
   * AJAX handler for bulk save
   */
  public static function ajax_bulk_save() {
    check_ajax_referer('CFSEO_bulk_edit', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied', 'clarity-first-seo')]);
      return;
    }
    
    $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
    $titles = isset($_POST['seo_title']) ? $_POST['seo_title'] : [];
    $descriptions = isset($_POST['seo_description']) ? $_POST['seo_description'] : [];
    $robots = isset($_POST['robots_index']) ? $_POST['robots_index'] : [];
    
    $updated = 0;
    
    foreach ($post_ids as $post_id) {
      if (!current_user_can('edit_post', $post_id)) continue;
      
      if (isset($titles[$post_id])) {
        update_post_meta($post_id, '_CFSEO_title', sanitize_text_field($titles[$post_id]));
      }
      
      if (isset($descriptions[$post_id])) {
        update_post_meta($post_id, '_CFSEO_description', sanitize_textarea_field($descriptions[$post_id]));
      }
      
      if (isset($robots[$post_id])) {
        update_post_meta($post_id, '_CFSEO_robots_index', sanitize_text_field($robots[$post_id]));
      }
      
      $updated++;
    }
    
    wp_send_json_success([
      'message' => sprintf(__('%d posts updated successfully!', 'clarity-first-seo'), $updated),
      'updated' => $updated
    ]);
  }
}
