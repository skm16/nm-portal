/**
 * NMDA Admin Reimbursements Interface JavaScript
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    var $modal = $('#nmda-reimbursement-modal');
    var $modalContent = $('#nmda-reimbursement-detail');

    /**
     * View reimbursement details
     */
    $(document).on('click', '.nmda-view-reimbursement', function () {
      var reimbursementId = $(this).data('reimbursement-id');
      loadReimbursementDetails(reimbursementId);
    });

    /**
     * Close modal
     */
    $('.nmda-modal-close').on('click', function () {
      $modal.hide();
    });

    /**
     * Close modal on outside click
     */
    $(window).on('click', function (e) {
      if ($(e.target).is($modal)) {
        $modal.hide();
      }
    });

    /**
     * Approve reimbursement
     */
    $(document).on('click', '.nmda-approve-reimbursement', function () {
      var reimbursementId = $(this).data('reimbursement-id');
      var approvedAmount = $('#approved-amount').val();

      if (!approvedAmount || approvedAmount <= 0) {
        alert('Please enter a valid approved amount.');
        return;
      }

      if (confirm('Are you sure you want to approve this reimbursement request for $' + parseFloat(approvedAmount).toFixed(2) + '? The business owner will be notified via email.')) {
        approveReimbursement(reimbursementId, approvedAmount);
      }
    });

    /**
     * Reject reimbursement
     */
    $(document).on('click', '.nmda-reject-reimbursement', function () {
      var reimbursementId = $(this).data('reimbursement-id');
      var reason = $('#rejection-reason').val();

      if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
      }

      if (confirm('Are you sure you want to reject this reimbursement request? The business owner will be notified via email.')) {
        rejectReimbursement(reimbursementId, reason);
      }
    });

    /**
     * Load reimbursement details via AJAX
     */
    function loadReimbursementDetails(reimbursementId) {
      $modalContent.html('<div style="padding: 50px; text-align: center;"><span class="dashicons dashicons-update-alt"></span> Loading...</div>');
      $modal.show();

      $.ajax({
        url: nmdaReimbursements.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_get_reimbursement_details',
          nonce: nmdaReimbursements.nonce,
          reimbursement_id: reimbursementId,
        },
        success: function (response) {
          if (response.success) {
            $modalContent.html(response.data.html);
          } else {
            $modalContent.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
          }
        },
        error: function () {
          $modalContent.html('<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>');
        },
      });
    }

    /**
     * Approve reimbursement
     */
    function approveReimbursement(reimbursementId, approvedAmount) {
      var $button = $('.nmda-approve-reimbursement[data-reimbursement-id="' + reimbursementId + '"]');
      $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Approving...');

      $.ajax({
        url: nmdaReimbursements.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_approve_reimbursement',
          nonce: nmdaReimbursements.nonce,
          reimbursement_id: reimbursementId,
          approved_amount: approvedAmount,
        },
        success: function (response) {
          if (response.success) {
            showNotice('success', response.data.message);

            // Close modal and reload page
            $modal.hide();
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            showNotice('error', response.data.message);
            $button.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Approve Request');
          }
        },
        error: function () {
          showNotice('error', 'An error occurred. Please try again.');
          $button.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Approve Request');
        },
      });
    }

    /**
     * Reject reimbursement
     */
    function rejectReimbursement(reimbursementId, reason) {
      var $button = $('.nmda-reject-reimbursement[data-reimbursement-id="' + reimbursementId + '"]');
      $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Rejecting...');

      $.ajax({
        url: nmdaReimbursements.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_reject_reimbursement',
          nonce: nmdaReimbursements.nonce,
          reimbursement_id: reimbursementId,
          reason: reason,
        },
        success: function (response) {
          if (response.success) {
            showNotice('success', response.data.message);

            // Close modal and reload page
            $modal.hide();
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            showNotice('error', response.data.message);
            $button.prop('disabled', false).html('<span class="dashicons dashicons-no"></span> Reject Request');
          }
        },
        error: function () {
          showNotice('error', 'An error occurred. Please try again.');
          $button.prop('disabled', false).html('<span class="dashicons dashicons-no"></span> Reject Request');
        },
      });
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
      var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

      $('.wrap h1').after($notice);

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
