jQuery(document).ready(function($) {
  
  // Track if any changes have been made
  let hasUnsavedChanges = false;
  
  // Mark as changed when the textarea is modified
  $('textarea[name="robots_content"]').on('input', function() {
    hasUnsavedChanges = true;
  });
  
  // Warn before leaving page with unsaved changes
  $(window).on('beforeunload', function(e) {
    if (hasUnsavedChanges) {
      const message = 'You have unsaved changes to robots.txt. Are you sure you want to leave this page?';
      e.returnValue = message; // Standard for most browsers
      return message; // For some older browsers
    }
  });
  
  // Clear flag when form is submitted
  $('form').on('submit', function() {
    hasUnsavedChanges = false;
  });
  
});
