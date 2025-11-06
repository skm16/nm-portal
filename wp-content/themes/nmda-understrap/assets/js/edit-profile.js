/**
 * Edit Profile JavaScript
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    var $form = $('#profile-edit-form');
    var $saveBtn = $form.find('button[type="submit"]');
    var $cancelBtn = $('#cancel-btn');
    var $businessSelector = $('#business-selector');
    var formChanged = false;
    var autosaveTimeout = null;

    // Check for success message in sessionStorage
    var savedMessage = sessionStorage.getItem('nmda_profile_success');
    if (savedMessage) {
      try {
        var messageData = JSON.parse(savedMessage);
        showMessage(messageData.message, messageData.type, messageData.html);
        sessionStorage.removeItem('nmda_profile_success');
      } catch (e) {
        console.error('Error parsing saved message:', e);
      }
    }

    // Initialize Bootstrap tabs manually
    if (typeof $.fn.tab !== 'undefined') {
      // Bootstrap 4 syntax
      console.log('Initializing Bootstrap 4 tabs');
      var $tabs = $('#profileTabs a[data-toggle="tab"]');
      console.log('Found ' + $tabs.length + ' tabs');

      $tabs.on('click', function (e) {
        e.preventDefault();
        console.log('Tab clicked:', $(this).attr('href'));
        $(this).tab('show');
      });
    } else if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
      // Bootstrap 5 syntax
      console.log('Initializing Bootstrap 5 tabs');
      var triggerTabList = [].slice.call(document.querySelectorAll('#profileTabs a[data-bs-toggle="tab"]'));
      triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl);
        triggerEl.addEventListener('click', function (event) {
          event.preventDefault();
          tabTrigger.show();
        });
      });
    } else {
      console.error('Bootstrap tabs not available');
    }

    // Business selector change
    if ($businessSelector.length) {
      console.log('Business selector found, attaching change handler');
      $businessSelector.on('change', function () {
        console.log('Business selector changed to:', $(this).val());
        if (formChanged) {
          if (confirm('You have unsaved changes. Do you want to discard them?')) {
            window.location.href = window.location.pathname + '?business_id=' + $(this).val();
          } else {
            // Revert selector
            $(this).val($('input[name="business_id"]').val());
          }
        } else {
          window.location.href = window.location.pathname + '?business_id=' + $(this).val();
        }
      });
    } else {
      console.log('Business selector not found (single business account)');
    }

    // Track form changes
    $form.on('change', 'input, select, textarea', function () {
      formChanged = true;
      // Trigger autosave after 2 seconds of inactivity
      clearTimeout(autosaveTimeout);
      autosaveTimeout = setTimeout(function () {
        autosaveForm();
      }, 2000);
    });

    // Form submission
    $form.on('submit', function (e) {
      e.preventDefault();

      if (!validateForm()) {
        return false;
      }

      saveProfile(false);
    });

    // Cancel button
    $cancelBtn.on('click', function () {
      if (formChanged) {
        if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
          window.location.href = nmdaAjax.dashboardUrl || '/dashboard';
        }
      } else {
        window.location.href = nmdaAjax.dashboardUrl || '/dashboard';
      }
    });

    // Warn on page leave
    $(window).on('beforeunload', function () {
      if (formChanged) {
        return 'You have unsaved changes. Are you sure you want to leave?';
      }
    });

    /**
     * Validate form
     */
    function validateForm() {
      var isValid = true;
      var firstInvalidField = null;

      // Check required fields
      $form.find('[required]').each(function () {
        var $field = $(this);
        if (!$field.val() || $field.val().trim() === '') {
          $field.addClass('is-invalid');
          if (!firstInvalidField) {
            firstInvalidField = $field;
          }
          isValid = false;
        } else {
          $field.removeClass('is-invalid');
        }
      });

      // Focus first invalid field
      if (firstInvalidField) {
        firstInvalidField.focus();
        showMessage('Please fill in all required fields.', 'error');
      }

      return isValid;
    }

    /**
     * Save profile via AJAX
     */
    function saveProfile(isAutosave) {
      isAutosave = isAutosave || false;

      var formData = $form.serialize();

      // Add autosave flag
      if (isAutosave) {
        formData += '&autosave=1';
      }

      // Show loading state
      if (!isAutosave) {
        $saveBtn.addClass('loading').prop('disabled', true);
      } else {
        showAutosaveIndicator('saving');
      }

      $.ajax({
        url: nmdaAjax.ajaxurl,
        type: 'POST',
        data: formData,
        success: function (response) {
          if (response.success) {
            formChanged = false;

            if (!isAutosave) {
              // Store message in sessionStorage for display after reload
              var messageToStore = {
                message: response.data.message,
                html: response.data.message_html || null,
                type: 'success'
              };
              sessionStorage.setItem('nmda_profile_success', JSON.stringify(messageToStore));

              $saveBtn.removeClass('loading').prop('disabled', false);

              // Reload page to show updated data
              window.location.reload();
            } else {
              showAutosaveIndicator('saved');
            }
          } else {
            var errorMsg = response.data && response.data.message ? response.data.message : 'An error occurred while saving.';

            if (!isAutosave) {
              showMessage(errorMsg, 'error', null);
              $saveBtn.removeClass('loading').prop('disabled', false);
            } else {
              showAutosaveIndicator('error');
            }
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX Error:', error);

          if (!isAutosave) {
            showMessage('A network error occurred. Please try again.', 'error', null);
            $saveBtn.removeClass('loading').prop('disabled', false);
          } else {
            showAutosaveIndicator('error');
          }
        },
      });
    }

    /**
     * Autosave form
     */
    function autosaveForm() {
      // Don't autosave if form is invalid
      var hasRequiredEmpty = false;
      $form.find('[required]').each(function () {
        if (!$(this).val() || $(this).val().trim() === '') {
          hasRequiredEmpty = true;
          return false;
        }
      });

      if (hasRequiredEmpty) {
        return;
      }

      saveProfile(true);
    }

    /**
     * Show message to user
     * @param {string} message - Plain text message
     * @param {string} type - 'success' or 'error'
     * @param {string} html - Optional HTML formatted message
     */
    function showMessage(message, type, html) {
      // Remove existing alerts
      $('.profile-alert').remove();

      var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
      var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

      // Use HTML message if provided, otherwise use plain text
      var messageContent = html || ('<i class="fa ' + icon + '"></i> ' + message);

      // Create alert with close button
      var $alert = $(
        '<div class="alert ' + alertClass + ' profile-alert alert-dismissible fade show" role="alert">' +
          '<div class="d-flex align-items-start">' +
            '<div class="flex-grow-1">' +
              '<i class="fa ' + icon + ' mr-2"></i>' +
              '<span class="alert-message">' + messageContent + '</span>' +
            '</div>' +
            '<button type="button" class="close ml-3" data-dismiss="alert" aria-label="Close">' +
              '<span aria-hidden="true">&times;</span>' +
            '</button>' +
          '</div>' +
        '</div>'
      );

      $form.prepend($alert);

      // Scroll to top
      $('html, body').animate(
        {
          scrollTop: $form.offset().top - 100,
        },
        300
      );

      // Don't auto-dismiss - let user close it manually
    }

    /**
     * Show autosave indicator
     */
    function showAutosaveIndicator(status) {
      var $indicator = $('#autosave-indicator');

      // Create if doesn't exist
      if ($indicator.length === 0) {
        $indicator = $('<div id="autosave-indicator"></div>');
        $('body').append($indicator);
      }

      // Set status
      $indicator.removeClass('saving saved error').addClass(status);

      // Set message
      var message = '';
      var icon = '';
      if (status === 'saving') {
        icon = '<i class="fa fa-refresh fa-spin"></i>';
        message = 'Saving...';
      } else if (status === 'saved') {
        icon = '<i class="fa fa-check"></i>';
        message = 'Changes saved';
      } else if (status === 'error') {
        icon = '<i class="fa fa-exclamation-triangle"></i>';
        message = 'Save failed';
      }

      $indicator.html(icon + message);

      // Show
      $indicator.addClass('show');

      // Hide after delay
      setTimeout(function () {
        $indicator.removeClass('show');
      }, 3000);
    }

    /**
     * Remove invalid class on input
     */
    $form.on('input change', '.is-invalid', function () {
      $(this).removeClass('is-invalid');
    });
  });
})(jQuery);
