<?php
/**
 * SEO Config Validation Tab
 * @var array $results Validation results
 * @var string $test_url Test URL
 * @var array $published_posts Published content
 */
if (!defined('ABSPATH')) exit;
?>

<!-- URL Selector Form -->
<?php include __DIR__ . '/url-selector.php'; ?>

<!-- Show results if validation was run -->
<?php if ($results && !isset($results['error'])): ?>
  
  <!-- Overall Score -->
  <?php include __DIR__ . '/overall-score.php'; ?>
  
  <!-- Function Groups -->
  <?php include __DIR__ . '/group-identity.php'; ?>
  <?php include __DIR__ . '/group-indexing.php'; ?>
  <?php include __DIR__ . '/group-discovery.php'; ?>
  <?php include __DIR__ . '/group-social.php'; ?>
  <?php include __DIR__ . '/group-schema.php'; ?>
  <?php include __DIR__ . '/group-content.php'; ?>
  <?php include __DIR__ . '/group-console.php'; ?>
  <?php include __DIR__ . '/group-performance.php'; ?>

<?php elseif ($results && isset($results['error'])): ?>
  
  <!-- Show Error -->
  <div class="notice notice-error">
    <p><strong><?php esc_html_e('Error:', 'asneris-seo-toolkit'); ?></strong> <?php echo esc_html($results['error']); ?></p>
  </div>

<?php endif; ?>
