<?php
/**
 * Page Footer - CSS and JavaScript
 */
if (!defined('ABSPATH')) exit;
?>
    
    </div><!-- .cfseo-tab-content -->
  </div><!-- .cfseo-settings-form -->
    
  <!-- Sidebar Info -->
  <div class="cfseo-sidebar">
      <div class="cfseo-info-box">
        <h3><span class="dashicons dashicons-info"></span> Quick Tips</h3>
        <ul>
          <li>Test your homepage and key pages regularly to ensure proper SEO setup</li>
          <li>Critical checks (HTTP 200, no noindex) determine if your page can be indexed (60% weight)</li>
          <li>Recommended checks (title, description, H1) improve search visibility (30% weight)</li>
          <li>Optimization checks (social, schema) enhance search result appearance (10% weight)</li>
          <li>Fix critical issues first, then work on recommended and optimization items</li>
        </ul>
      </div>
      
      <div class="cfseo-info-box cfseo-success-box">
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
  $('.cfseo-quick-test').on('click', function() {
    var url = $(this).data('url');
    $('#test_url').val(url);
  });
  
  // Page selector dropdown
  $('#CFSEO_page_selector').on('change', function() {
    var selectedUrl = $(this).val();
    if (selectedUrl) {
      $('#test_url').val(selectedUrl);
    }
  });
  
  // Collapsible function groups
  $('.cfseo-group-header').on('click', function() {
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
  
  // Indexing Validation - HTTP Test functionality
  $('#CFSEO_run_http_test').on('click', function() {
    const url = $('#CFSEO_test_url').val();
    const $button = $(this);
    const $results = $('#CFSEO_http_results');
    const $tbody = $('#CFSEO_http_results_body');
    
    if (!url) {
      alert('Please enter a URL to test');
      return;
    }
    
    $button.prop('disabled', true).text('Testing...');
    $tbody.html('<tr><td colspan="3">Running validation...</td></tr>');
    $results.show();
    
    $.ajax({
      url: ajaxurl,
      method: 'POST',
      data: {
        action: 'CFSEO_http_test',
        url: url,
        nonce: '<?php echo wp_create_nonce('CFSEO_http_test'); ?>'
      },
      success: function(response) {
        if (response.success) {
          let html = '';
          response.data.checks.forEach(function(check) {
            const statusColor = check.status === 'pass' ? '#46b450' : (check.status === 'warning' ? '#f0ad4e' : '#dc3232');
            const statusIcon = check.status === 'pass' ? '✓' : (check.status === 'warning' ? '⚠' : '✗');
            html += '<tr>';
            html += '<td><strong>' + check.label + '</strong></td>';
            html += '<td><span style="color: ' + statusColor + ';">' + statusIcon + ' ' + check.result + '</span></td>';
            html += '<td>' + check.details + '</td>';
            html += '</tr>';
          });
          $tbody.html(html);
        } else {
          $tbody.html('<tr><td colspan="3" style="color: #dc3232;">Error: ' + response.data + '</td></tr>');
        }
      },
      error: function() {
        $tbody.html('<tr><td colspan="3" style="color: #dc3232;">Request failed. Please try again.</td></tr>');
      },
      complete: function() {
        $button.prop('disabled', false).text('Run Indexing Validation');
      }
    });
  });
});
</script>
