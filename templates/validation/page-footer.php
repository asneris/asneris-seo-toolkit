<?php
/**
 * Page Footer - CSS and JavaScript
 */
if (!defined('ABSPATH')) exit;
?>
    
    </div><!-- .cfseo-tab-content -->
  </div><!-- .cfseo-settings-form -->
    
  <?php // CFSEO_Help_Content::render_sidebar('site-diagnostics'); ?>
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

<?php CFSEO_Help_Modal::render_modals('site-diagnostics'); ?>
