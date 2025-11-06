/**
 * NMDA Reimbursement Forms JavaScript
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    // Track if form is being submitted to prevent duplicates
    var isSubmitting = false;

    // Handle AJAX form submissions for all reimbursement forms
    // Use .off() first to prevent duplicate bindings
    $('.nmda-reimbursement-form').off('submit').on('submit', function (e) {
      e.preventDefault();
      e.stopPropagation(); // Stop event from bubbling up

      // Prevent duplicate submissions
      if (isSubmitting) {
        return false;
      }

      var $form = $(this);
      var $submitButton = $form.find('button[type="submit"]');
      var $messages = $('#form-messages'); // Select messages div directly (it's outside the form)

      // Ensure messages div exists
      if ($messages.length === 0) {
        console.error('form-messages div not found');
        alert('Error: Form messages container not found. Please refresh the page.');
        return false;
      }

      var formData = new FormData(this);

      // Mark as submitting and disable submit button
      isSubmitting = true;
      $submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');

      // Clear previous messages
      $messages.empty().show();

      $.ajax({
        url: nmdaData.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            // Hide the form
            $form.hide();

            // Show success message with action buttons
            $messages.html(
              '<div class="alert alert-success" style="padding: 30px; text-align: center;">' +
                '<i class="fa fa-check-circle" style="font-size: 48px; color: #5cb85c; display: block; margin-bottom: 20px;"></i>' +
                '<h3 style="margin-bottom: 15px;">Success!</h3>' +
                '<p style="font-size: 16px; margin-bottom: 25px;">' + response.data.message + '</p>' +
                '<div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">' +
                  '<a href="' + nmdaData.dashboardUrl + '" class="btn btn-primary btn-lg">' +
                    '<i class="fa fa-home"></i> Return to Dashboard' +
                  '</a>' +
                  '<button type="button" class="btn btn-outline-primary btn-lg" onclick="location.reload()">' +
                    '<i class="fa fa-plus"></i> Submit Another Request' +
                  '</button>' +
                '</div>' +
              '</div>'
            );

            // Scroll to message
            $('html, body').animate(
              {
                scrollTop: $messages.offset().top - 100,
              },
              300
            );
          } else {
            // Show error message
            $messages.html(
              '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' +
                response.data.message +
                '</div>'
            );

            // Re-enable submit button and reset submission flag
            isSubmitting = false;
            $submitButton.prop('disabled', false).html('<i class="fa fa-check"></i> Submit Reimbursement Request');

            // Scroll to message
            $('html, body').animate(
              {
                scrollTop: $messages.offset().top - 100,
              },
              300
            );
          }
        },
        error: function (xhr, status, error) {
          // Show error message
          $messages.html(
            '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> An error occurred. Please try again.</div>'
          );

          // Re-enable submit button and reset submission flag
          isSubmitting = false;
          $submitButton.prop('disabled', false).html('<i class="fa fa-check"></i> Submit Reimbursement Request');

          // Scroll to message
          $('html, body').animate(
            {
              scrollTop: $messages.offset().top - 100,
            },
            300
          );

          console.error('Form submission error:', error);
        },
      });
    });

    // File input validation
    $('input[type="file"]').on('change', function () {
      var files = this.files;
      var maxSize = 5242880; // 5MB in bytes
      var allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
      var errors = [];

      for (var i = 0; i < files.length; i++) {
        var file = files[i];

        // Check file size
        if (file.size > maxSize) {
          errors.push(file.name + ' exceeds 5MB limit.');
        }

        // Check file type
        if (allowedTypes.indexOf(file.type) === -1) {
          errors.push(file.name + ' is not an allowed file type.');
        }
      }

      if (errors.length > 0) {
        alert('File validation errors:\n\n' + errors.join('\n'));
        $(this).val(''); // Clear the file input
      }
    });
  });
})(jQuery);
