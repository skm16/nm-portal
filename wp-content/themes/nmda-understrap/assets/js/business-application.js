/**
 * NMDA Business Application Form JavaScript
 *
 * @package NMDA_Understrap_Child
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Only run on business application page
        if ($('#nmda-business-application').length === 0) {
            return;
        }

        // Draft restoration functionality
        if (typeof savedDraftData !== 'undefined') {
            $('#restore-draft-btn').on('click', function() {
                // Populate form fields with saved data
                $.each(savedDraftData, function(key, value) {
                    var $field = $('[name="' + key + '"]');

                    if ($field.length) {
                        if ($field.is(':checkbox') || $field.is(':radio')) {
                            // Handle checkboxes and radios
                            if (Array.isArray(value)) {
                                // Multiple checkboxes (like products[], classification[])
                                value.forEach(function(val) {
                                    $('[name="' + key + '"][value="' + val + '"]').prop('checked', true);
                                });
                            } else {
                                $('[name="' + key + '"][value="' + value + '"]').prop('checked', true);
                            }
                        } else {
                            // Handle regular inputs, textareas, selects
                            $field.val(value);
                        }
                    }
                });

                // Trigger change events to update conditional fields
                $('#primary_address_type, #is_primary_contact, .classification-check').trigger('change');
                $('#has_facebook, #has_instagram, #has_twitter, #assoc_other').trigger('change');

                // Update product counter
                $('.nmda-product-selection input[type=checkbox]').trigger('change');

                // Hide notification
                $('#draft-notification').fadeOut();

                // Show success message
                $('<div class="alert alert-success alert-dismissible fade show">' +
                  '<i class="fa fa-check-circle"></i> Draft restored successfully! Continue where you left off.' +
                  '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                  '</div>').insertBefore('#nmda-business-application').delay(5000).fadeOut();
            });

            $('#clear-draft-btn').on('click', function() {
                if (confirm('Are you sure you want to delete your saved draft? This cannot be undone.')) {
                    // Clear draft via AJAX
                    if (typeof nmdaAjax === 'undefined') {
                        console.error('nmdaAjax is not defined');
                        return;
                    }

                    $.ajax({
                        url: nmdaAjax.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'nmda_clear_draft',
                            nonce: nmdaAjax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#draft-notification').fadeOut();
                                $('<div class="alert alert-success alert-dismissible fade show">' +
                                  '<i class="fa fa-check-circle"></i> Draft cleared. Starting with a fresh form.' +
                                  '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                                  '</div>').insertBefore('#nmda-business-application').delay(5000).fadeOut();
                            }
                        }
                    });
                }
            });
        }

        // Address type conditional fields
        $('#primary_address_type').on('change', function() {
            $('#reservation_instructions_group').toggle($(this).val() === 'public_reservation');
            $('#other_instructions_group').toggle($(this).val() === 'other');
        });

        // Primary contact toggle
        $('#is_primary_contact').on('change', function() {
            $('#primary-contact-fields').toggle(!$(this).is(':checked'));
            if ($(this).is(':checked')) {
                $('#contact_first_name, #contact_last_name').removeAttr('required');
            } else {
                $('#contact_first_name, #contact_last_name').attr('required', 'required');
            }
        });

        // Social media field toggles
        $('#has_facebook').on('change', function() {
            $('#facebook').prop('disabled', !$(this).is(':checked'));
        });
        $('#has_instagram').on('change', function() {
            $('#instagram').prop('disabled', !$(this).is(':checked'));
        });
        $('#has_twitter').on('change', function() {
            $('#twitter').prop('disabled', !$(this).is(':checked'));
        });

        // Associate member type toggle
        $('#class_associate').on('change', function() {
            $('#associate-type-fields').toggle($(this).is(':checked'));
        });

        // Associate other text
        $('#assoc_other').on('change', function() {
            $('#assoc_other_text_group').toggle($(this).is(':checked'));
        });

        // Classification validation
        $('.classification-check').on('change', function() {
            var anyChecked = $('.classification-check:checked').length > 0;
            $('.classification-check').each(function() {
                if (anyChecked) {
                    $(this).removeAttr('required');
                } else {
                    $(this).attr('required', 'required');
                }
            });
        });

        // Application summary population - hook into multi-step form
        $(document).on('click', '.nmda-btn-next', function() {
            setTimeout(function() {
                var currentStep = $('.nmda-form-step.active').data('step');
                if (currentStep === 5) {
                    populateApplicationSummary();
                }
            }, 100);
        });

        function populateApplicationSummary() {
            var summary = '<div class="application-summary-content">';

            // Personal Contact Information
            summary += '<h5 class="border-bottom pb-2 mb-3">Personal Contact Information</h5>';
            summary += '<div class="row mb-3">';
            summary += '<div class="col-md-6"><strong>Name:</strong> ' + $('#owner_first_name').val() + ' ' + $('#owner_last_name').val() + '</div>';
            summary += '<div class="col-md-6"><strong>Phone:</strong> ' + $('#contact_phone').val() + '</div>';
            summary += '<div class="col-md-6"><strong>Email:</strong> ' + $('#contact_email').val() + '</div>';
            summary += '<div class="col-md-6"><strong>Address:</strong> ' + $('#contact_address').val() + ', ' + $('#contact_city').val() + ', ' + $('#contact_state').val() + ' ' + $('#contact_zip').val() + '</div>';
            summary += '</div>';

            // Business Information
            summary += '<h5 class="border-bottom pb-2 mb-3 mt-4">Business Information</h5>';
            summary += '<div class="row mb-3">';
            summary += '<div class="col-md-12"><strong>Business Name:</strong> ' + $('#business_name').val() + '</div>';
            if ($('#dba').val()) {
                summary += '<div class="col-md-12"><strong>DBA:</strong> ' + $('#dba').val() + '</div>';
            }
            summary += '<div class="col-md-6"><strong>Phone:</strong> ' + $('#business_phone').val() + '</div>';
            summary += '<div class="col-md-6"><strong>Email:</strong> ' + $('#business_email').val() + '</div>';
            if ($('#website').val()) {
                summary += '<div class="col-md-12"><strong>Website:</strong> ' + $('#website').val() + '</div>';
            }
            summary += '<div class="col-md-12"><strong>Address:</strong> ' + $('#primary_address').val() + ', ' + $('#primary_city').val() + ', ' + $('#primary_state').val() + ' ' + $('#primary_zip').val() + '</div>';
            summary += '<div class="col-md-12"><strong>Address Type:</strong> ' + $('#primary_address_type option:selected').text() + '</div>';
            summary += '</div>';

            // Classification
            summary += '<h5 class="border-bottom pb-2 mb-3 mt-4">Logo Program Classification</h5>';
            summary += '<div class="mb-3">';
            var classifications = [];
            $('.classification-check:checked').each(function() {
                classifications.push($(this).next('label').find('strong').text());
            });
            summary += '<p>' + (classifications.length > 0 ? classifications.join(', ') : 'None selected') + '</p>';
            summary += '</div>';

            // Products
            summary += '<h5 class="border-bottom pb-2 mb-3 mt-4">Products</h5>';
            summary += '<div class="mb-3">';
            var productCount = $('.nmda-product-selection input[type=checkbox]:checked').length;
            summary += '<p><strong>' + productCount + ' product type(s) selected</strong></p>';
            if (productCount > 0) {
                summary += '<ul class="list-unstyled">';
                $('.nmda-product-selection input[type=checkbox]:checked').each(function() {
                    summary += '<li><i class="fa fa-check text-success"></i> ' + $(this).next('label').text() + '</li>';
                });
                summary += '</ul>';
            }
            summary += '</div>';

            summary += '</div>';

            $('#application-summary').html(summary);
        }

        // Track selected products count
        $('.nmda-product-selection input[type=checkbox]').on('change', function() {
            var count = $('.nmda-product-selection input[type=checkbox]:checked').length;
            if (count > 0) {
                if (!$('#product-count-badge').length) {
                    $('.nmda-product-selection').prepend('<div id="product-count-badge" class="alert alert-info">' + count + ' product(s) selected</div>');
                } else {
                    $('#product-count-badge').text(count + ' product(s) selected');
                }
            } else {
                $('#product-count-badge').remove();
            }
        });

        console.log('Business application form JavaScript loaded successfully');
    });

})(jQuery);
