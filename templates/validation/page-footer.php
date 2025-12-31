<?php
/**
 * Page Footer - CSS and JavaScript
 */
if (!defined('ABSPATH')) exit;
?>
    </div><!-- .gscseo-tab-content -->
  </div><!-- .gscseo-settings-form -->
    
  <!-- Sidebar Info -->
  <div class="gscseo-sidebar">
      <div class="gscseo-info-box">
        <h3><span class="dashicons dashicons-info"></span> Quick Tips</h3>
        <ul>
          <li>Test your homepage and key pages regularly to ensure proper SEO setup</li>
          <li>Critical checks (HTTP 200, no noindex) determine if your page can be indexed (60% weight)</li>
          <li>Recommended checks (title, description, H1) improve search visibility (30% weight)</li>
          <li>Optimization checks (social, schema) enhance search result appearance (10% weight)</li>
          <li>Fix critical issues first, then work on recommended and optimization items</li>
        </ul>
      </div>
      
      <div class="gscseo-info-box gscseo-success-box">
        <h3><span class="dashicons dashicons-yes"></span> Need Help?</h3>
        <p>Learn more about clarity-first SEO approach:</p>
        <ul>
          <li><a href="https://clarityfirstseo.com/docs/validation/" target="_blank">Validation Guide</a></li>
          <li><a href="https://clarityfirstseo.com/docs/scoring/" target="_blank">Scoring Method</a></li>
          <li><a href="https://clarityfirstseo.com/docs/critical-checks/" target="_blank">Critical vs Recommended</a></li>
        </ul>
      </div>
    </div>
</div><!-- .wrap -->

<script>
jQuery(document).ready(function($) {
  // Quick test buttons
  $('.gscseo-quick-test').on('click', function() {
    var url = $(this).data('url');
    $('#test_url').val(url);
  });
  
  // Page selector dropdown
  $('#gscseo_page_selector').on('change', function() {
    var selectedUrl = $(this).val();
    if (selectedUrl) {
      $('#test_url').val(selectedUrl);
    }
  });
  
  // Collapsible function groups
  $('.gscseo-group-header').on('click', function() {
    var group = $(this).data('group');
    var content = $('#group-' + group);
    
    if (content.is(':visible')) {
      content.slideUp();
      $(this).removeClass('expanded');
    } else {
      content.slideDown();
      $(this).addClass('expanded');
    }
  });
});
</script>
