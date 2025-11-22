/**
 * Messages Page JavaScript
 * Handles messaging interface interactions
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // New message form submission
        $('#new-message-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var formData = $form.serialize();

            // Add action and nonce
            formData += '&action=nmda_send_message';
            formData += '&nonce=' + nmdaAjax.nonce;

            // Disable submit button
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        showAlert('Message sent successfully!', 'success');

                        // Close modal
                        $('#newMessageModal').modal('hide');

                        // Reset form
                        $form[0].reset();

                        // Reload page to show new message
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert(response.data.message || 'Failed to send message.', 'error');
                        $submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Message');
                    }
                },
                error: function () {
                    showAlert('A network error occurred. Please try again.', 'error');
                    $submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Message');
                }
            });
        });

        // Reply button click
        $('.reply-btn').on('click', function () {
            $('#reply-form-container').slideDown();
            $('textarea[name="message"]', '#reply-form').focus();
        });

        // Cancel reply button
        $('.cancel-reply-btn').on('click', function () {
            $('#reply-form-container').slideUp();
            $('#reply-form')[0].reset();
        });

        // Reply form submission
        $('#reply-form').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var formData = $form.serialize();

            // Add subject as "Re: original subject" (only if not already prefixed)
            var originalSubject = $('.message-detail-card .card-header h5').first().text().trim() || '';

            // Remove any existing "Re:" prefix and clean up
            originalSubject = originalSubject.replace(/^(Re:\s*)+/i, '').trim();

            // Remove "(No subject)" text if present
            if (originalSubject === '(No subject)' || originalSubject === '') {
                originalSubject = '';
            }

            // Add "Re:" prefix
            var replySubject = originalSubject ? 'Re: ' + originalSubject : 'Re: (No subject)';
            formData += '&subject=' + encodeURIComponent(replySubject);

            // Add action and nonce
            formData += '&action=nmda_send_message';
            formData += '&nonce=' + nmdaAjax.nonce;

            // Disable submit button
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function (response) {
                    if (response.success) {
                        // Show success message
                        showAlert('Reply sent successfully!', 'success');

                        // Reload page to show reply
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        showAlert(response.data.message || 'Failed to send reply.', 'error');
                        $submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Reply');
                    }
                },
                error: function () {
                    showAlert('A network error occurred. Please try again.', 'error');
                    $submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Reply');
                }
            });
        });

        // Delete message button
        $('.delete-message-btn').on('click', function () {
            if (!confirm('Are you sure you want to delete this message?')) {
                return;
            }

            var messageId = $(this).data('message-id');
            var $btn = $(this);

            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_delete_message',
                    nonce: nmdaAjax.nonce,
                    message_id: messageId
                },
                success: function (response) {
                    if (response.success) {
                        showAlert('Message deleted successfully.', 'success');

                        // Redirect to messages page
                        setTimeout(function () {
                            window.location.href = window.location.pathname;
                        }, 1000);
                    } else {
                        showAlert(response.data.message || 'Failed to delete message.', 'error');
                        $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                    }
                },
                error: function () {
                    showAlert('A network error occurred. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                }
            });
        });

        // Mark message as read when viewing
        var urlParams = new URLSearchParams(window.location.search);
        var messageId = urlParams.get('id');
        if (messageId && $('.message-item.active.unread').length) {
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_mark_message_read',
                    nonce: nmdaAjax.nonce,
                    message_id: messageId
                },
                success: function (response) {
                    if (response.success) {
                        // Update unread badge
                        updateUnreadBadge(response.data.unread_count);

                        // Remove unread class from message item
                        $('.message-item[data-message-id="' + messageId + '"]').removeClass('unread');
                    }
                }
            });
        }

        // Update unread count in badge
        function updateUnreadBadge(count) {
            var $headerBadge = $('.dashboard-header .badge-danger');
            var $inboxBadge = $('.nav-link .badge-danger');

            if (count > 0) {
                $headerBadge.text(count + ' Unread');
                $inboxBadge.text(count);
            } else {
                $headerBadge.remove();
                $inboxBadge.remove();
            }

            // Update navigation badge if it exists
            var $navBadge = $('#nav-messages-badge, .messages-badge');
            if ($navBadge.length) {
                if (count > 0) {
                    $navBadge.text(count).show();
                } else {
                    $navBadge.hide();
                }
            }
        }

        // Show alert message
        function showAlert(message, type) {
            var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show message-alert" role="alert">' +
                '<i class="fa ' + icon + '"></i> ' + message +
                '<button type="button" class="close" data-dismiss="alert">' +
                '<span>&times;</span>' +
                '</button>' +
                '</div>');

            // Remove existing alerts
            $('.message-alert').remove();

            // Prepend to message detail card
            if ($('.message-detail-card .card-body').length) {
                $('.message-detail-card .card-body').prepend($alert);
            } else {
                $('.message-detail-card').prepend($alert);
            }

            // Scroll to alert
            $('html, body').animate({
                scrollTop: $alert.offset().top - 100
            }, 300);

            // Auto-dismiss after 5 seconds
            setTimeout(function () {
                $alert.fadeOut(function () {
                    $(this).remove();
                });
            }, 5000);
        }

        // Refresh unread count periodically (every 60 seconds)
        setInterval(function () {
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_get_unread_count',
                    nonce: nmdaAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        updateUnreadBadge(response.data.unread_count);
                    }
                }
            });
        }, 60000);
    });

})(jQuery);
