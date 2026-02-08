jQuery(document).ready(function($) {
  
  let hasUnsavedChanges = false;
  
  // Custom confirm function
  function customConfirm(message) {
    return new Promise(function(resolve) {
      $('#ASNERISSEO-confirm-message').text(message);
      $('#ASNERISSEO-confirm-modal').css('display', 'flex');
      
      $('#ASNERISSEO-confirm-ok').off('click').on('click', function() {
        $('#ASNERISSEO-confirm-modal').hide();
        resolve(true);
      });
      
      $('#ASNERISSEO-confirm-cancel').off('click').on('click', function() {
        $('#ASNERISSEO-confirm-modal').hide();
        resolve(false);
      });
    });
  }
  
  // Custom alert function (OK and Cancel)
  function customAlert(message) {
    return new Promise(function(resolve) {
      $('#ASNERISSEO-confirm-message').text(message);
      $('#ASNERISSEO-confirm-modal').css('display', 'flex');
      $('#ASNERISSEO-confirm-cancel').text('Cancel').show();
      
      $('#ASNERISSEO-confirm-ok').off('click').on('click', function() {
        $('#ASNERISSEO-confirm-modal').hide();
        resolve(true);
      });
      
      $('#ASNERISSEO-confirm-cancel').off('click').on('click', function() {
        $('#ASNERISSEO-confirm-modal').hide();
        resolve(false);
      });
    });
  }
  
  $('#ASNERISSEO-bulk-edit-form').on('change', 'input, textarea, select', function() {
    hasUnsavedChanges = true;
  });
  
  $(document).on('click', '.ASNERISSEO-edit-post-link', function(e) {
    if (hasUnsavedChanges) {
      e.preventDefault();
      const href = $(this).attr('href');
      const message = 'You have unsaved changes on this page.\n\n' +
                     'If you navigate to the WordPress post editor now, all changes you\'ve made in the bulk edit table will be lost.\n\n' +
                     'Would you like to:\n' +
                     '• Click Cancel to stay here and save your changes first\n' +
                     '• Click Confirm to discard changes and go to the post editor';
      
      customConfirm(message).then(function(confirmed) {
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
  
  $('#ASNERISSEO-select-all').on('change', function() {
    $('.ASNERISSEO-post-checkbox').prop('checked', $(this).prop('checked'));
  });
  
  $('#ASNERISSEO-bulk-set-index').on('click', function() {
    const checked = $('.ASNERISSEO-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post to update.');
      return;
    }
    
    const message = 'Allow ' + checked.length + ' post' + (checked.length > 1 ? 's' : '') + ' in search results?\n\n' +
                    'This will set the indexing status to "Index", allowing search engines like Google to show ' +
                    (checked.length > 1 ? 'these pages' : 'this page') + ' in search results. You can review the changes in the table before saving.';
    
    customConfirm(message).then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('select[name="robots_index[' + postId + ']"]').val('index');
        });
        hasUnsavedChanges = true;
        customAlert(checked.length + ' post' + (checked.length > 1 ? 's' : '') + ' updated to "Allow Indexing". Review the changes below, then click "Save All Changes" to apply.');
      }
    });
  });
  
  $('#ASNERISSEO-bulk-set-noindex').on('click', function() {
    const checked = $('.ASNERISSEO-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post to update.');
      return;
    }
    
    const message = 'Hide ' + checked.length + ' post' + (checked.length > 1 ? 's' : '') + ' from search results?\n\n' +
                    'This will set the indexing status to "NoIndex", preventing search engines like Google from showing ' +
                    (checked.length > 1 ? 'these pages' : 'this page') + ' in search results. This is useful for draft content, private pages, or content you don\'t want publicly discoverable.\n\n' +
                    'You can review the changes in the table before saving.';
    
    customConfirm(message).then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('select[name="robots_index[' + postId + ']"]').val('noindex');
        });
        hasUnsavedChanges = true;
        customAlert(checked.length + ' post' + (checked.length > 1 ? 's' : '') + ' updated to "Prevent Indexing". Review the changes below, then click "Save All Changes" to apply.');
      }
    });
  });
  
  $('#ASNERISSEO-bulk-clear-title').on('click', function() {
    const checked = $('.ASNERISSEO-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post to update.');
      return;
    }
    
    const message = 'Clear custom SEO titles for ' + checked.length + ' post' + (checked.length > 1 ? 's' : '') + '?\n\n' +
                    'This will remove any custom SEO titles you\'ve set. After saving, ' +
                    (checked.length > 1 ? 'these posts' : 'this post') + ' will use auto-generated titles based on the post title and site name.\n\n' +
                    'This action cannot be undone, but you can set new custom titles anytime.';
    
    customConfirm(message).then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('input[name="seo_title[' + postId + ']"]').val('');
        });
        hasUnsavedChanges = true;
        customAlert('Custom SEO titles cleared for ' + checked.length + ' post' + (checked.length > 1 ? 's' : '') + '. Review the changes below, then click "Save All Changes" to apply.');
      }
    });
  });
  
  $('#ASNERISSEO-bulk-clear-description').on('click', function() {
    const checked = $('.ASNERISSEO-post-checkbox:checked');
    if (checked.length === 0) {
      customAlert('Please select at least one post to update.');
      return;
    }
    
    const message = 'Clear custom meta descriptions for ' + checked.length + ' post' + (checked.length > 1 ? 's' : '') + '?\n\n' +
                    'This will remove any custom meta descriptions you\'ve set. After saving, ' +
                    (checked.length > 1 ? 'these posts' : 'this post') + ' will use auto-generated descriptions based on the post excerpt or content.\n\n' +
                    'This action cannot be undone, but you can set new custom descriptions anytime.';
    
    customConfirm(message).then(function(confirmed) {
      if (confirmed) {
        checked.each(function() {
          const postId = $(this).val();
          $('textarea[name="seo_description[' + postId + ']"]').val('');
        });
        hasUnsavedChanges = true;
        customAlert('Custom meta descriptions cleared for ' + checked.length + ' post' + (checked.length > 1 ? 's' : '') + '. Review the changes below, then click "Save All Changes" to apply.');
      }
    });
  });
  
  $('#ASNERISSEO-bulk-edit-form').on('submit', function(e) {
    e.preventDefault();
    
    const $form = $(this);
    const $status = $('#ASNERISSEO-bulk-status');
    const $button = $form.find('button[type="submit"]');
    
    const data = {
      action: 'ASNERISSEO_bulk_save',
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
          customAlert(response.data.message).then(function(confirmed) {
            if (confirmed) {
              location.reload();
            }
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
