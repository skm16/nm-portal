/**
 * Field Approval Workflow JavaScript
 *
 * Handles approve/reject interactions for pending field changes
 *
 * @package NMDA_Understrap_Child
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Only run on business edit screen
        if ($('#nmda-pending-changes-container').length === 0) {
            return;
        }

        /**
         * Approve Change Button Click
         */
        $(document).on('click', '.nmda-approve-change', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $changeContainer = $button.closest('.nmda-pending-change');
            var businessId = $button.data('business-id');
            var field = $button.data('field');

            // Confirm action
            if (!confirm('Are you sure you want to approve this change? This will update the business profile immediately.')) {
                return;
            }

            // Disable buttons and show processing state
            $changeContainer.addClass('processing');
            $button.prop('disabled', true);
            $button.siblings('.nmda-reject-change').prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_approve_field_change',
                    business_id: businessId,
                    field: field,
                    nonce: $('#nmda_field_approval_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showApprovalMessage($changeContainer, 'success', response.data.message);

                        // Add approved state
                        $changeContainer.removeClass('processing').addClass('approved');

                        // Change badge
                        $changeContainer.find('.nmda-change-badge')
                            .text('Approved')
                            .css({
                                'background': '#28a745',
                                'color': '#fff'
                            });

                        // Hide action buttons
                        $changeContainer.find('.nmda-change-actions').fadeOut();

                        // Remove after 3 seconds
                        setTimeout(function() {
                            $changeContainer.slideUp(function() {
                                $(this).remove();

                                // Check if any changes remain
                                checkRemainingChanges();
                            });
                        }, 3000);

                    } else {
                        // Show error message
                        showApprovalMessage($changeContainer, 'error', response.data.message || 'Failed to approve change.');
                        $changeContainer.removeClass('processing');
                        $button.prop('disabled', false);
                        $button.siblings('.nmda-reject-change').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showApprovalMessage($changeContainer, 'error', 'An error occurred. Please try again.');
                    $changeContainer.removeClass('processing');
                    $button.prop('disabled', false);
                    $button.siblings('.nmda-reject-change').prop('disabled', false);
                    console.error('Approval error:', error);
                }
            });
        });

        /**
         * Reject Change Button Click
         */
        $(document).on('click', '.nmda-reject-change', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $changeContainer = $button.closest('.nmda-pending-change');
            var $rejectionSection = $changeContainer.find('.nmda-rejection-reason');

            // Show rejection reason textarea
            $rejectionSection.slideDown();

            // Hide action buttons
            $button.closest('.nmda-change-actions').hide();

            // Focus on textarea
            $rejectionSection.find('.nmda-rejection-textarea').focus();
        });

        /**
         * Confirm Rejection Button Click
         */
        $(document).on('click', '.nmda-confirm-rejection', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $changeContainer = $button.closest('.nmda-pending-change');
            var $textarea = $changeContainer.find('.nmda-rejection-textarea');
            var rejectionReason = $textarea.val().trim();

            // Validate rejection reason
            if (rejectionReason === '') {
                alert('Please provide a reason for rejecting this change.');
                $textarea.focus();
                return;
            }

            var businessId = $changeContainer.find('.nmda-reject-change').data('business-id');
            var field = $changeContainer.find('.nmda-reject-change').data('field');

            // Disable buttons and show processing state
            $changeContainer.addClass('processing');
            $button.prop('disabled', true);
            $button.siblings('.nmda-cancel-rejection').prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_reject_field_change',
                    business_id: businessId,
                    field: field,
                    reason: rejectionReason,
                    nonce: $('#nmda_field_approval_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showApprovalMessage($changeContainer, 'success',
                            'Change rejected. User has been notified.');

                        // Add rejected state
                        $changeContainer.removeClass('processing').addClass('rejected');

                        // Change badge
                        $changeContainer.find('.nmda-change-badge')
                            .text('Rejected')
                            .css({
                                'background': '#dc3545',
                                'color': '#fff'
                            });

                        // Hide rejection section
                        $changeContainer.find('.nmda-rejection-reason').fadeOut();

                        // Remove after 3 seconds
                        setTimeout(function() {
                            $changeContainer.slideUp(function() {
                                $(this).remove();

                                // Check if any changes remain
                                checkRemainingChanges();
                            });
                        }, 3000);

                    } else {
                        // Show error message
                        showApprovalMessage($changeContainer, 'error',
                            response.data.message || 'Failed to reject change.');
                        $changeContainer.removeClass('processing');
                        $button.prop('disabled', false);
                        $button.siblings('.nmda-cancel-rejection').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showApprovalMessage($changeContainer, 'error',
                        'An error occurred. Please try again.');
                    $changeContainer.removeClass('processing');
                    $button.prop('disabled', false);
                    $button.siblings('.nmda-cancel-rejection').prop('disabled', false);
                    console.error('Rejection error:', error);
                }
            });
        });

        /**
         * Cancel Rejection Button Click
         */
        $(document).on('click', '.nmda-cancel-rejection', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $changeContainer = $button.closest('.nmda-pending-change');
            var $rejectionSection = $changeContainer.find('.nmda-rejection-reason');
            var $actionButtons = $changeContainer.find('.nmda-change-actions');

            // Clear textarea
            $rejectionSection.find('.nmda-rejection-textarea').val('');

            // Hide rejection section
            $rejectionSection.slideUp();

            // Show action buttons again
            $actionButtons.show();
        });

        /**
         * Show approval/rejection message
         *
         * @param {jQuery} $container The change container
         * @param {string} type       Message type ('success' or 'error')
         * @param {string} message    Message text
         */
        function showApprovalMessage($container, type, message) {
            var $messageDiv = $container.find('.nmda-approval-message');

            $messageDiv
                .removeClass('success error')
                .addClass(type)
                .html('<i class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></i> ' + message)
                .slideDown();
        }

        /**
         * Check if any changes remain, show message if none
         */
        function checkRemainingChanges() {
            var $container = $('#nmda-pending-changes-container');
            var remainingChanges = $container.find('.nmda-pending-change').length;

            if (remainingChanges === 0) {
                $container.html('<p class="nmda-no-pending">No pending changes at this time.</p>');
            }
        }

        console.log('Field approval workflow JavaScript loaded successfully');
    });

})(jQuery);
