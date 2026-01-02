<?php
/**
 * Validation Page Header
 * @var array $data All validation data
 */
if (!defined('ABSPATH')) exit;

$current_validation_tab = isset($_GET['validation_tab']) ? sanitize_text_field($_GET['validation_tab']) : 'seo';
?>
<div class="wrap gscseo-admin-wrap">
  <h1>
    <span class="dashicons dashicons-yes-alt"></span>
    <?php _e('SEO Validation Checker', 'bfseo'); ?>
  </h1>
  <p class="gscseo-subtitle"><?php _e('Automated validation of your SEO implementation', 'bfseo'); ?></p>
  
  <!-- Validation Sub-Tabs -->
  <div class="nav-tab-wrapper" style="margin-bottom: 20px;">
    <a href="?page=gscseo-validation&validation_tab=seo" class="nav-tab <?php echo $current_validation_tab === 'seo' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-admin-generic"></span> SEO Config Validation
    </a>
    <a href="?page=gscseo-validation&validation_tab=diagnostics" class="nav-tab <?php echo $current_validation_tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>">
      <span class="dashicons dashicons-analytics"></span> Site Diagnostics
    </a>
  </div>
  
  <div class="gscseo-settings-form">
    <div class="gscseo-tab-content">
