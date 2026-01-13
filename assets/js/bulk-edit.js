jQuery(document).ready(function($) {
  
  let hasUnsavedChanges = false;
  
  // Custom confirm function
  function customConfirm(message) {
    return new Promise(function(resolve) {
      $('#cfseo-confirm-message').text(message);
      $('#cfseo-confirm-modal').css('display', 'flex');
      
      $('#cfseo-confirm-ok').off('click').on('click', function() {
        $('#cfseo-confirm-modal').hide();
        resolve(true);
      });
      
      $('#cfseo-confirm-cancel').off('click').on('click', function() {
        $('#cfseo-confirm-modal').hide();
        resolve(false);
      });
    });
  }
  
  // Custom alert function (OK only)
  function customAlert(message) {
    return new Promise(function(resolve) {
      $('#cfseo-confirm-message').text(message);
      $('#cfseo-confirm-modal').css('display', 'flex');
      $('#cfseo-confirm-cancel').hide();
      
      $('#cfseo-confirm-ok').off('click').on('click', function() {
        $('#cfseo-confirm-modal').hide();
        $('#cfseo-confirm-cancel').show();
        resolve();
      });
    });
  }
  
  $('#cfseo-bulk-edit-form').on('change', 'input, textarea, select', function() {
    hasUnsavedChanges = true;
  });
  
  $(document).on('click', '.cfseo-edit-post-link', function(e) {
    if (hasUnsavedChanges) {
      e.preventDefault();
      const href = $(this).attr('href');
      customConfirm('You have unsaved changes. Navigating to the post editor will discard these changes. Continue?').then(function(confirmed) {
        if (confirmed) {
          hasUnsavedChanges = false;
          window.location.href = href;
        }
      });
    }
  });
  
  $(window).on('beforeunload', function(e) {
    if (hasUnsavedChanges) {
      const message = 'You have unsaved changes.';
      e.returnValue = message;
      return message;
    }
  });
  
  $('#cfseo-select-all').on('change', function() {
    $('.cfseo-post-checkbox').prop('checked', $(this).prop('checked'));
  });
  
  $('#cfseo-bulk-set-index').on('click', function() {
    const checked = $('.cfseo-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post.');
      return;
    }
    
    checked.each(function() {
      const postId = $(this).val();
      $('select[name="robots_index[' + postId + ']"]').val('index');
    });
    
    customAlert(checked.length + ' posts set to Index. Click Save All Changes.');
  });
  
  $('#cfseo-bulk-set-noindex').on('click', function() {
    const checked = $('.cfseo-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post.');
      return;
    }
    
    customConfirm('Set ' + checked.length + ' posts to NoIndex?').then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('select[name="robots_index[' + postId + ']"]').val('noindex');
        });
        customAlert(checked.length + ' posts set to NoIndex. Click Save All Changes.');
      }
    });
  });
  
  $('#cfseo-bulk-clear-title').on('click', function() {
    const checked = $('.cfseo-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post.');
      return;
    }
    
    customConfirm('Clear SEO titles for ' + checked.length + ' posts?').then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('input[name="seo_title[' + postId + ']"]').val('');
        });
        customAlert('SEO titles cleared. Click Save All Changes.');
      }
    });
  });
  
  $('#cfseo-bulk-clear-description').on('click', function() {
    const checked = $('.cfseo-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post.');
      return;
    }
    
    customConfirm('Clear descriptions for ' + checked.length + ' posts?').then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('textarea[name="seo_description[' + postId + ']"]').val('');
        });
        customAlert('Descriptions cleared. Click Save All Changes.');
      }
    });
  });
  
  $('#cfseo-bulk-edit-form').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $status = $('#cfseo-bulk-status');
    const $button = $form.find('button[type="submit"]');
    
    const data = {
      action: 'CFSEO_bulk_save',
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
          hasUnsavedChanges = false;
          $status.html('<span style="color: #46b450;">Success: ' + response.data.message + '</span>');
          customAlert(response.data.message).then(function() {
            location.reload();
          });
        } else {
          $status.html('<span style="color: #d63638;">Error: ' + response.data.message + '</span>');
          customAlert('Error: ' + response.data.message);
          $button.prop('disabled', false).text('Save All Changes');
        }
      },
      error: function() {
        $status.html('<span style="color: #d63638;">Save failed. Please try again.</span>');
        customAlert('Save failed. Please try again.');
        $button.prop('disabled', false).text('Save All Changes');
      }
    });
  });
  
});
