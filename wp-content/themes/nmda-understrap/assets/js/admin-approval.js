/**
 * NMDA Admin Approval Interface JavaScript
 */

(function ($) {
  'use strict';

  /**
   * Initialize on document ready
   */
  $(document).ready(function () {
    // Initialize modal
    var $modal = $('#nmda-application-modal');
    var $modalContent = $('#nmda-application-detail');

    // View application button
    $(document).on('click', '.nmda-view-application', function () {
      var businessId = $(this).data('business-id');
      loadApplicationDetails(businessId);
    });

    // Close modal
    $('.nmda-modal-close').on('click', function () {
      $modal.hide();
    });

    // Close modal on outside click
    $(window).on('click', function (e) {
      if ($(e.target).is($modal)) {
        $modal.hide();
      }
    });

    // Approve application
    $(document).on('click', '.nmda-approve-application', function () {
      var businessId = $(this).data('business-id');

      if (confirm('Are you sure you want to approve this application? The business will be published and the applicant will receive an approval email.')) {
        approveApplication(businessId);
      }
    });

    // Reject application
    $(document).on('click', '.nmda-reject-application', function () {
      var businessId = $(this).data('business-id');

      if (confirm('Are you sure you want to reject this application? The applicant will be notified via email.')) {
        rejectApplication(businessId);
      }
    });

    // Save admin notes
    $(document).on('click', '.nmda-save-notes', function () {
      var businessId = $(this).data('business-id');
      var notes = $('#admin-notes-' + businessId).val();
      saveAdminNotes(businessId, notes);
    });

    // Select all checkbox
    $('#cb-select-all-1').on('change', function () {
      $('input[name="business[]"]').prop('checked', $(this).prop('checked'));
    });

    // Bulk actions
    $('#doaction').on('click', function () {
      var action = $('#bulk-action-selector-top').val();
      var selected = $('input[name="business[]"]:checked')
        .map(function () {
          return $(this).val();
        })
        .get();

      if (action === '-1') {
        alert('Please select a bulk action.');
        return;
      }

      if (selected.length === 0) {
        alert('Please select at least one application.');
        return;
      }

      if (action === 'approve') {
        if (confirm('Are you sure you want to approve ' + selected.length + ' application(s)?')) {
          bulkApprove(selected);
        }
      } else if (action === 'reject') {
        if (confirm('Are you sure you want to reject ' + selected.length + ' application(s)?')) {
          bulkReject(selected);
        }
      }
    });

    /**
     * Load application details via AJAX
     */
    function loadApplicationDetails(businessId) {
      $modalContent.html('<div style="padding: 50px; text-align: center;">Loading...</div>');
      $modal.show();

      $.ajax({
        url: nmdaAdmin.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_get_application_details',
          nonce: nmdaAdmin.nonce,
          business_id: businessId,
        },
        success: function (response) {
          if (response.success) {
            $modalContent.html(response.data.html);
          } else {
            $modalContent.html('<div class="error"><p>' + response.data.message + '</p></div>');
          }
        },
        error: function () {
          $modalContent.html('<div class="error"><p>An error occurred. Please try again.</p></div>');
        },
      });
    }

    /**
     * Approve application
     */
    function approveApplication(businessId) {
      var $button = $('.nmda-approve-application[data-business-id="' + businessId + '"]');
      $button.prop('disabled', true).text('Approving...');

      $.ajax({
        url: nmdaAdmin.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_approve_application',
          nonce: nmdaAdmin.nonce,
          business_id: businessId,
        },
        success: function (response) {
          if (response.success) {
            // Show success message
            showNotice('success', response.data.message);

            // Close modal and reload page
            $modal.hide();
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            showNotice('error', response.data.message);
            $button.prop('disabled', false).text('Approve Application');
          }
        },
        error: function () {
          showNotice('error', 'An error occurred. Please try again.');
          $button.prop('disabled', false).text('Approve Application');
        },
      });
    }

    /**
     * Reject application
     */
    function rejectApplication(businessId) {
      var $button = $('.nmda-reject-application[data-business-id="' + businessId + '"]');
      $button.prop('disabled', true).text('Rejecting...');

      $.ajax({
        url: nmdaAdmin.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_reject_application',
          nonce: nmdaAdmin.nonce,
          business_id: businessId,
        },
        success: function (response) {
          if (response.success) {
            // Show success message
            showNotice('success', response.data.message);

            // Close modal and reload page
            $modal.hide();
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            showNotice('error', response.data.message);
            $button.prop('disabled', false).text('Reject Application');
          }
        },
        error: function () {
          showNotice('error', 'An error occurred. Please try again.');
          $button.prop('disabled', false).text('Reject Application');
        },
      });
    }

    /**
     * Save admin notes
     */
    function saveAdminNotes(businessId, notes) {
      var $button = $('.nmda-save-notes[data-business-id="' + businessId + '"]');
      $button.prop('disabled', true).text('Saving...');

      $.ajax({
        url: nmdaAdmin.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_save_admin_notes',
          nonce: nmdaAdmin.nonce,
          business_id: businessId,
          notes: notes,
        },
        success: function (response) {
          if (response.success) {
            showNotice('success', response.data.message);
            $button.prop('disabled', false).text('Save Notes');
          } else {
            showNotice('error', response.data.message);
            $button.prop('disabled', false).text('Save Notes');
          }
        },
        error: function () {
          showNotice('error', 'An error occurred. Please try again.');
          $button.prop('disabled', false).text('Save Notes');
        },
      });
    }

    /**
     * Bulk approve applications
     */
    function bulkApprove(businessIds) {
      var processed = 0;
      var total = businessIds.length;

      businessIds.forEach(function (businessId) {
        $.ajax({
          url: nmdaAdmin.ajaxurl,
          type: 'POST',
          data: {
            action: 'nmda_approve_application',
            nonce: nmdaAdmin.nonce,
            business_id: businessId,
          },
          success: function () {
            processed++;
            if (processed === total) {
              showNotice('success', processed + ' application(s) approved successfully!');
              setTimeout(function () {
                location.reload();
              }, 1500);
            }
          },
        });
      });
    }

    /**
     * Bulk reject applications
     */
    function bulkReject(businessIds) {
      var processed = 0;
      var total = businessIds.length;

      businessIds.forEach(function (businessId) {
        $.ajax({
          url: nmdaAdmin.ajaxurl,
          type: 'POST',
          data: {
            action: 'nmda_reject_application',
            nonce: nmdaAdmin.nonce,
            business_id: businessId,
          },
          success: function () {
            processed++;
            if (processed === total) {
              showNotice('success', processed + ' application(s) rejected.');
              setTimeout(function () {
                location.reload();
              }, 1500);
            }
          },
        });
      });
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
      var $notice = $(
        '<div class="notice notice-' +
          type +
          ' is-dismissible"><p>' +
          message +
          '</p></div>'
      );

      $('.wrap').prepend($notice);

      // Auto-dismiss after 5 seconds
      setTimeout(function () {
        $notice.fadeOut(function () {
          $(this).remove();
        });
      }, 5000);

      // Scroll to top
      $('html, body').animate({ scrollTop: 0 }, 300);
    }
  });
})(jQuery);
