/**
 * NMDA Reimbursement Forms JavaScript
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    // Handle AJAX form submissions for all reimbursement forms
    $('.nmda-reimbursement-form').on('submit', function (e) {
      e.preventDefault();

      var $form = $(this);
      var $submitButton = $form.find('button[type="submit"]');
      var $messages = $form.find('#form-messages');
      var formData = new FormData(this);

      // Disable submit button
      $submitButton.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');

      // Clear previous messages
      $messages.empty();

      $.ajax({
        url: nmdaData.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            // Show success message
            $messages.html(
              '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ' +
                response.data.message +
                '</div>'
            );

            // Reset form
            $form[0].reset();
            $form.find('#amount_requested').val('');

            // Scroll to message
            $('html, body').animate(
              {
                scrollTop: $messages.offset().top - 100,
              },
              300
            );

            // Redirect to dashboard after 3 seconds
            setTimeout(function () {
              window.location.href = nmdaData.dashboardUrl;
            }, 3000);
          } else {
            // Show error message
            $messages.html(
              '<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' +
                response.data.message +
                '</div>'
            );

            // Re-enable submit button
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

          // Re-enable submit button
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
