/**
 * Asneris SEO Toolkit - Admin JavaScript
 * Handles media uploads, import/export, and dynamic UI interactions
 */

(function($) {
    'use strict';

    const ASNERISSEO_Admin = {
        
        /**
         * Initialize all admin features
         */
        init: function() {
            this.mediaUploader();
            this.conditionalFields();
            this.importExport();
            this.resetSettings();
            this.formValidation();
            this.imagePreview();
        },

        /**
         * WordPress Media Uploader Integration
         */
        mediaUploader: function() {
            $(document).on('click', '.ASNERISSEO-upload-button', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const targetInput = $(button.data('target'));
                const previewContainer = button.siblings('.ASNERISSEO-image-preview');

                // Create a new media uploader for each button click
                const mediaUploader = wp.media({
                    title: 'Select or Upload Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                // When an image is selected
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    // Set the image URL in the input field
                    targetInput.val(attachment.url);
                    
                    // Update preview
                    previewContainer.html(
                        '<img src="' + attachment.url + '" style="max-width: 400px; margin-top: 10px;">'
                    );
                    
                    // Trigger change event
                    targetInput.trigger('change');
                });

                // Open the media uploader
                mediaUploader.open();
            });

            // Remove image functionality
            $(document).on('click', '.ASNERISSEO-remove-image', function(e) {
                e.preventDefault();
                const button = $(this);
                const targetInput = button.data('target');
                
                $(targetInput).val('');
                button.closest('.ASNERISSEO-image-preview').empty();
            });
        },

        /**
         * Handle conditional field visibility
         */
        conditionalFields: function() {
            const toggleConditionalFields = function() {
                $('[data-depends]').each(function() {
                    const field = $(this);
                    const dependsOn = field.data('depends');
                    const checkbox = $('input[name*="[' + dependsOn + ']"]');
                    
                    if (checkbox.is(':checked')) {
                        field.addClass('active').show();
                    } else {
                        field.removeClass('active').hide();
                    }
                });
            };

            // Initial check
            toggleConditionalFields();

            // On checkbox change
            $(document).on('change', 'input[type="checkbox"]', function() {
                toggleConditionalFields();
            });
        },

        /**
         * Export Settings
         */
        importExport: function() {
            const self = this;

            // Export settings
            $('#ASNERISSEO-export-settings').on('click', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: gscseoAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ASNERISSEO_export_settings',
                        nonce: gscseoAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const dataStr = JSON.stringify(response.data, null, 2);
                            const dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);
                            
                            const exportName = 'ASNERISSEO-settings-' + self.getFormattedDate() + '.json';
                            
                            const linkElement = document.createElement('a');
                            linkElement.setAttribute('href', dataUri);
                            linkElement.setAttribute('download', exportName);
                            linkElement.click();
                            
                            self.showNotice('Settings exported successfully!', 'success');
                        } else {
                            self.showNotice('Export failed: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        self.showNotice('Export failed. Please try again.', 'error');
                    }
                });
            });

            // Import settings
            $('#ASNERISSEO-import-settings').on('click', function(e) {
                e.preventDefault();
                $('#ASNERISSEO-import-file').click();
            });

            $('#ASNERISSEO-import-file').on('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                if (file.type !== 'application/json') {
                    self.showNotice('Please select a valid JSON file.', 'error');
                    return;
                }

                if (!confirm('Import will overwrite your current settings. Continue?')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const settings = JSON.parse(e.target.result);
                        
                        $.ajax({
                            url: gscseoAdmin.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'ASNERISSEO_import_settings',
                                nonce: gscseoAdmin.nonce,
                                settings: settings
                            },
                            success: function(response) {
                                if (response.success) {
                                    self.showNotice('Settings imported successfully! Reloading...', 'success');
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    self.showNotice('Import failed: ' + response.data, 'error');
                                }
                            },
                            error: function() {
                                self.showNotice('Import failed. Please try again.', 'error');
                            }
                        });
                    } catch (error) {
                        self.showNotice('Invalid JSON file.', 'error');
                    }
                };
                reader.readAsText(file);
            });
        },

        /**
         * Reset Settings
         */
        resetSettings: function() {
            const self = this;

            $('#ASNERISSEO-reset-settings').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to reset all settings? This action cannot be undone.')) {
                    return;
                }

                if (!confirm('This will delete ALL plugin settings. Are you absolutely sure?')) {
                    return;
                }

                $.ajax({
                    url: gscseoAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'ASNERISSEO_reset_settings',
                        nonce: gscseoAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            self.showNotice('Settings reset successfully! Reloading...', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            self.showNotice('Reset failed: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        self.showNotice('Reset failed. Please try again.', 'error');
                    }
                });
            });
        },

        /**
         * Form Validation
         */
        formValidation: function() {
            $('.ASNERISSEO-settings-form').on('submit', function(e) {
                let hasErrors = false;

                // Validate URLs
                $(this).find('input[type="url"]').each(function() {
                    const input = $(this);
                    const value = input.val().trim();
                    
                    if (value && !ASNERISSEO_Admin.isValidUrl(value)) {
                        input.addClass('error');
                        hasErrors = true;
                    } else {
                        input.removeClass('error');
                    }
                });

                // Validate phone numbers
                $(this).find('input[type="tel"]').each(function() {
                    const input = $(this);
                    const value = input.val().trim();
                    
                    if (value && !ASNERISSEO_Admin.isValidPhone(value)) {
                        input.addClass('error');
                        hasErrors = true;
                    } else {
                        input.removeClass('error');
                    }
                });

                if (hasErrors) {
                    e.preventDefault();
                    ASNERISSEO_Admin.showNotice('Please fix validation errors before saving.', 'error');
                    return false;
                }
            });
        },

        /**
         * Image Preview on URL change
         */
        imagePreview: function() {
            $(document).on('change', '.ASNERISSEO-media-url', function() {
                const input = $(this);
                const url = input.val().trim();
                const previewContainer = input.siblings('.ASNERISSEO-image-preview');
                
                if (url && ASNERISSEO_Admin.isValidUrl(url)) {
                    previewContainer.html(
                        '<img src="' + url + '" style="max-width: 400px; margin-top: 10px;">'
                    );
                } else if (!url) {
                    previewContainer.empty();
                }
            });
        },

        /**
         * Utilities
         */
        showNotice: function(message, type) {
            const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
            const notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.ASNERISSEO-admin-wrap').prepend(notice);
            
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        getFormattedDate: function() {
            const date = new Date();
            return date.getFullYear() + '-' +
                   String(date.getMonth() + 1).padStart(2, '0') + '-' +
                   String(date.getDate()).padStart(2, '0');
        },

        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },

        isValidPhone: function(phone) {
            // Basic phone validation (can be enhanced)
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            return phoneRegex.test(phone);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ASNERISSEO_Admin.init();
    });

})(jQuery);
