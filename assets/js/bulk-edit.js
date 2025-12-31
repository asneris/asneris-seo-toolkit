jQuery(document).ready(function($) {
  
  // Select all checkbox
  $('#gscseo-select-all').on('change', function() {
    $('.gscseo-post-checkbox').prop('checked', $(this).prop('checked'));
  });
  
  // Bulk action: Set to Index
  $('#gscseo-bulk-set-index').on('click', function() {
    const checked = $('.gscseo-post-checkbox:checked');
    if (checked.length === 0) {
      alert('Please select at least one post.');
      return;
    }
    
    checked.each(function() {
      const postId = $(this).val();
      $('select[name="robots_index[' + postId + ']"]').val('index');
    });
    
    alert(checked.length + ' posts set to Index. Click "Save All Changes" to apply.');
  });
  
  // Bulk action: Set to NoIndex
  $('#gscseo-bulk-set-noindex').on('click', function() {
    const checked = $('.gscseo-post-checkbox:checked');
    if (checked.length === 0) {
      alert('Please select at least one post.');
      return;
    }
    
    if (!confirm('Are you sure you want to set ' + checked.length + ' posts to NoIndex? They will be hidden from search engines.')) {
      return;
    }
    
    checked.each(function() {
      const postId = $(this).val();
      $('select[name="robots_index[' + postId + ']"]').val('noindex');
    });
    
    alert(checked.length + ' posts set to NoIndex. Click "Save All Changes" to apply.');
  });
  
  // Bulk action: Clear Titles
  $('#gscseo-bulk-clear-title').on('click', function() {
    const checked = $('.gscseo-post-checkbox:checked');
    if (checked.length === 0) {
      alert('Please select at least one post.');
      return;
    }
    
    if (!confirm('Clear SEO titles for ' + checked.length + ' posts? Defaults will be used.')) {
      return;
    }
    
    checked.each(function() {
      const postId = $(this).val();
      $('input[name="seo_title[' + postId + ']"]').val('');
    });
    
    alert('SEO titles cleared. Click "Save All Changes" to apply.');
  });
  
  // Bulk action: Clear Descriptions
  $('#gscseo-bulk-clear-description').on('click', function() {
    const checked = $('.gscseo-post-checkbox:checked');
    if (checked.length === 0) {
      alert('Please select at least one post.');
      return;
    }
    
    if (!confirm('Clear descriptions for ' + checked.length + ' posts? Defaults will be used.')) {
      return;
    }
    
    checked.each(function() {
      const postId = $(this).val();
      $('textarea[name="seo_description[' + postId + ']"]').val('');
    });
    
    alert('Descriptions cleared. Click "Save All Changes" to apply.');
  });
  
  // Form submission
  $('#gscseo-bulk-edit-form').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $status = $('#gscseo-bulk-status');
    const $button = $form.find('button[type="submit"]');
    
    // Collect only changed rows
    const data = {
      action: 'gscseo_bulk_save',
      nonce: gscseoBulkEdit.nonce,
      post_ids: [],
      seo_title: {},
      seo_description: {},
      robots_index: {}
    };
    
    $form.find('input[name="post_ids[]"]').each(function() {
      const postId = $(this).val();
      data.post_ids.push(postId);
      data.seo_title[postId] = $('input[name="seo_title[' + postId + ']"]').val();
      data.seo_description[postId] = $('textarea[name="seo_description[' + postId + ']"]').val();
      data.robots_index[postId] = $('select[name="robots_index[' + postId + ']"]').val();
    });
    
    $button.prop('disabled', true).text('Saving...');
    $status.html('<span style="color: #666;">Processing...</span>');
    
    $.ajax({
      url: gscseoBulkEdit.ajaxUrl,
      type: 'POST',
      data: data,
      success: function(response) {
        if (response.success) {
          $status.html('<span style="color: #46b450;">✓ ' + response.data.message + '</span>');
          setTimeout(function() {
            location.reload();
          }, 1500);
        } else {
          $status.html('<span style="color: #d63638;">✗ ' + response.data.message + '</span>');
          $button.prop('disabled', false).text('Save All Changes');
        }
      },
      error: function() {
        $status.html('<span style="color: #d63638;">✗ Save failed. Please try again.</span>');
        $button.prop('disabled', false).text('Save All Changes');
      }
    });
  });
  
});
