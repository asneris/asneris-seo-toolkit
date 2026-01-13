<?php
/**
 * Site Diagnostics Page Header
 * @var array $data All validation data
 */
if (!defined('ABSPATH')) exit;
?>
<div class="wrap cfseo-admin-wrap">
  <h1>
    <span class="dashicons dashicons-analytics"></span>
    <?php _e('Site Diagnostics', 'clarity-first-seo'); ?>
    <?php CFSEO_Help_Modal::render_help_icon('site-diagnostics-overview', 'Learn about Site Diagnostics'); ?>
  </h1>
  <p class="cfseo-subtitle"><?php _e('Check site-wide SEO settings that affect how search engines find and index your pages.', 'clarity-first-seo'); ?></p>
  
  <div class="cfseo-settings-form">
    <div class="cfseo-tab-content">
