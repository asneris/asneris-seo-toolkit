<?php
/**
 * URL Selector Component
 * @var array $published_posts List of published posts/pages
 * @var string $test_url Current test URL
 */
if (!defined('ABSPATH')) exit;
?>
<div class="cfseo-card">
  <h2><span class="dashicons dashicons-search"></span> <?php esc_html_e('Test Any Page', 'clarity-first-seo'); ?></h2>
  
  <form method="post" action="" id="cfseo-validation-form">
    <?php wp_nonce_field('CFSEO_validation'); ?>
    
    <!-- Quick Actions -->
    <div class="cfseo-quick-actions" style="margin-bottom: 15px;">
      <button type="button" class="button cfseo-quick-test" data-url="<?php echo esc_url(home_url('/')); ?>">
        🏠 <?php esc_html_e('Test Homepage', 'clarity-first-seo'); ?>
      </button>
      <?php if (!empty($published_posts)):
        $cfseo_latest_post = null;
        $cfseo_latest_page = null;
        foreach ($published_posts as $post) {
          if ($post->post_type === 'post' && !$cfseo_latest_post) $cfseo_latest_post = $post;
          if ($post->post_type === 'page' && !$cfseo_latest_page) $cfseo_latest_page = $post;
        }
        if ($cfseo_latest_post):
      ?>
      <button type="button" class="button cfseo-quick-test" data-url="<?php echo esc_url(get_permalink($cfseo_latest_post->ID)); ?>">
        📝 <?php esc_html_e('Test Latest Post', 'clarity-first-seo'); ?>
      </button>
      <?php endif; if ($latest_page): ?>
      <button type="button" class="button cfseo-quick-test" data-url="<?php echo esc_url(get_permalink($latest_page->ID)); ?>">
        📄 <?php esc_html_e('Test Latest Page', 'clarity-first-seo'); ?>
      </button>
      <?php endif; endif; ?>
    </div>
    
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="CFSEO_page_selector"><?php esc_html_e('Select Published Content', 'clarity-first-seo'); ?></label>
        </th>
        <td>
          <select id="CFSEO_page_selector" class="large-text" style="margin-bottom: 10px;">
            <option value=""><?php esc_html_e('-- Select a page to test --', 'clarity-first-seo'); ?></option>
            <optgroup label="<?php esc_html_e('Homepage', 'clarity-first-seo'); ?>">
              <option value="<?php echo esc_url(home_url('/')); ?>">🏠 <?php esc_html_e('Homepage', 'clarity-first-seo'); ?></option>
            </optgroup>
            
            <?php if (!empty($published_posts)): ?>
            <optgroup label="<?php esc_html_e('Recent Posts & Pages (100)', 'clarity-first-seo'); ?>">
              <?php foreach ($published_posts as $post):
                $cfseo_icon = $post->post_type === 'post' ? '📝' : '📄';
                $cfseo_permalink = get_permalink($post->ID);
              ?>
              <option value="<?php echo esc_url($cfseo_permalink); ?>">
                <?php echo esc_html($cfseo_icon); ?> <?php echo esc_html($post->post_title); ?> 
                (<?php echo esc_html($post->post_type); ?>)
              </option>
              <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="test_url"><?php esc_html_e('Or Enter Custom URL', 'clarity-first-seo'); ?></label>
        </th>
        <td>
          <input type="url" id="test_url" name="test_url" class="regular-text" 
                 value="<?php echo esc_attr($test_url); ?>" 
                 placeholder="<?php esc_html_e('https://yoursite.com/any-page/', 'clarity-first-seo'); ?>">
          <p class="description"><?php esc_html_e('Enter any URL from your site to validate SEO implementation', 'clarity-first-seo'); ?></p>
        </td>
      </tr>
    </table>
    
    <button type="submit" name="run_validation" class="button button-primary button-large">
      <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
      <?php esc_html_e('Run Validation', 'clarity-first-seo'); ?>
    </button>
    <p class="description" style="margin-top: 8px; color: #646970;">
      <?php esc_html_e('This inspection does not modify the page.', 'clarity-first-seo'); ?>
    </p>
  </form>
</div>
