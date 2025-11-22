/**
 * User Management JavaScript
 *
 * Handles user invitation and management interactions
 *
 * @package NMDA_Understrap_Child
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Only run on manage users page
        if ($('#users-list').length === 0) {
            return;
        }

        var businessId = $('#business-id-invite').val();

        /**
         * Business selector change
         */
        $('#business-selector').on('change', function() {
            var selectedBusinessId = $(this).val();
            window.location.href = window.location.pathname + '?business_id=' + selectedBusinessId;
        });

        /**
         * Open invite modal
         */
        $('#invite-user-btn').on('click', function(e) {
            e.preventDefault();

            // Reset form
            $('#invite-user-form')[0].reset();

            // Show modal
            $('#inviteUserModal').modal('show');
        });

        /**
         * Send invitation
         */
        $('#invite-user-form').on('submit', function(e) {
            e.preventDefault();

            var $submitBtn = $('#send-invite-btn');
            var originalText = $submitBtn.html();

            // Validate email
            var email = $('#invite-email').val();
            if (!validateEmail(email)) {
                showMessage('error', 'Please enter a valid email address.');
                return;
            }

            // Disable submit button
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            // Get form data
            var formData = $(this).serialize();
            formData += '&action=nmda_invite_user&nonce=' + nmdaAjax.nonce;

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('#inviteUserModal').modal('hide');

                        // Show success message
                        showMessage('success', response.data.message);

                        // Refresh users list
                        refreshUsersList(response.data.users);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while sending the invitation.');
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        /**
         * Change user role
         */
        $(document).on('change', '.role-select', function() {
            var $select = $(this);
            var userId = $select.data('user-id');
            var newRole = $select.val();
            var oldRole = $select.data('original-role') || $select.find('option:selected').data('original');

            // Store original role if not already stored
            if (!$select.data('original-role')) {
                $select.data('original-role', oldRole || newRole);
            }

            if (!confirm('Change this user\'s role to ' + capitalizeFirst(newRole) + '?')) {
                // Revert selection
                $select.val($select.data('original-role'));
                return;
            }

            // Disable select
            $select.prop('disabled', true);

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_update_user_role',
                    business_id: businessId,
                    user_id: userId,
                    role: newRole,
                    nonce: nmdaAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update stored original role
                        $select.data('original-role', newRole);

                        // Show success message
                        showMessage('success', response.data.message);

                        // Optionally refresh list
                        if (response.data.users) {
                            refreshUsersList(response.data.users);
                        }
                    } else {
                        showMessage('error', response.data.message);
                        // Revert selection
                        $select.val($select.data('original-role'));
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while updating the user role.');
                    // Revert selection
                    $select.val($select.data('original-role'));
                },
                complete: function() {
                    $select.prop('disabled', false);
                }
            });
        });

        /**
         * Remove user
         */
        $(document).on('click', '.remove-user-btn', function(e) {
            e.preventDefault();

            var $button = $(this);
            var userId = $button.data('user-id');
            var userName = $button.data('name');

            if (!confirm('Are you sure you want to remove ' + userName + ' from this business? They will lose all access.')) {
                return;
            }

            // Disable button
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Removing...');

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_remove_user',
                    business_id: businessId,
                    user_id: userId,
                    nonce: nmdaAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row from table
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();

                            // Check if any users remain
                            if ($('#users-list tbody tr').length === 0) {
                                $('#users-list').html(
                                    '<div class="alert alert-info">' +
                                    '<i class="fa fa-info-circle"></i> No users found. Invite someone to get started!' +
                                    '</div>'
                                );
                            }
                        });

                        // Show success message
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data.message);
                        $button.prop('disabled', false).html('<i class="fa fa-trash"></i> Remove');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while removing the user.');
                    $button.prop('disabled', false).html('<i class="fa fa-trash"></i> Remove');
                }
            });
        });

        /**
         * Resend invitation
         */
        $(document).on('click', '.resend-invite-btn', function(e) {
            e.preventDefault();

            var $button = $(this);
            var email = $button.data('email');

            // Disable button
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_resend_invitation',
                    business_id: businessId,
                    email: email,
                    nonce: nmdaAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while resending the invitation.');
                },
                complete: function() {
                    $button.prop('disabled', false).html('<i class="fa fa-envelope"></i> Resend');
                }
            });
        });

        /**
         * Refresh users list
         *
         * @param {Array} users Array of user objects
         */
        function refreshUsersList(users) {
            if (!users || users.length === 0) {
                $('#users-list').html(
                    '<div class="alert alert-info">' +
                    '<i class="fa fa-info-circle"></i> No users found. Invite someone to get started!' +
                    '</div>'
                );
                return;
            }

            var tableHtml = '<div class="table-responsive"><table class="table table-hover">' +
                '<thead><tr>' +
                '<th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th class="text-right">Actions</th>' +
                '</tr></thead><tbody>';

            users.forEach(function(user) {
                var statusBadge = 'secondary';
                var statusText = capitalizeFirst(user.status);

                if (user.status === 'active') {
                    statusBadge = 'success';
                } else if (user.status === 'pending') {
                    statusBadge = 'warning';
                } else if (user.status === 'inactive') {
                    statusBadge = 'danger';
                }

                var joinedText = '—';
                if (user.status === 'pending' && user.invited_date) {
                    joinedText = 'Invited ' + formatDate(user.invited_date);
                } else if (user.accepted_date) {
                    joinedText = formatDate(user.accepted_date);
                }

                tableHtml += '<tr data-user-id="' + user.user_id + '">';
                tableHtml += '<td><strong>' + escapeHtml(user.display_name) + '</strong></td>';
                tableHtml += '<td>' + escapeHtml(user.user_email) + '</td>';
                tableHtml += '<td>';

                // Role select
                if (user.role === 'owner' && user.is_current_user) {
                    tableHtml += '<span class="badge badge-success">Owner</span>';
                } else {
                    tableHtml += '<select class="form-control form-control-sm role-select" data-user-id="' + user.user_id + '">';
                    tableHtml += '<option value="owner"' + (user.role === 'owner' ? ' selected' : '') + '>Owner</option>';
                    tableHtml += '<option value="manager"' + (user.role === 'manager' ? ' selected' : '') + '>Manager</option>';
                    tableHtml += '<option value="viewer"' + (user.role === 'viewer' ? ' selected' : '') + '>Viewer</option>';
                    tableHtml += '</select>';
                }

                tableHtml += '</td>';
                tableHtml += '<td><span class="badge badge-' + statusBadge + '">' + statusText + '</span></td>';
                tableHtml += '<td>' + joinedText + '</td>';
                tableHtml += '<td class="text-right">';

                if (user.status === 'pending') {
                    tableHtml += '<button type="button" class="btn btn-sm btn-outline-primary resend-invite-btn" ' +
                        'data-email="' + escapeHtml(user.user_email) + '">' +
                        '<i class="fa fa-envelope"></i> Resend</button> ';
                }

                if (!user.is_current_user) {
                    tableHtml += '<button type="button" class="btn btn-sm btn-outline-danger remove-user-btn" ' +
                        'data-user-id="' + user.user_id + '" data-name="' + escapeHtml(user.display_name) + '">' +
                        '<i class="fa fa-trash"></i> Remove</button>';
                }

                tableHtml += '</td></tr>';
            });

            tableHtml += '</tbody></table></div>';

            $('#users-list').html(tableHtml);
        }

        /**
         * Validate email address
         */
        function validateEmail(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        /**
         * Capitalize first letter
         */
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        /**
         * Format date
         */
        function formatDate(dateString) {
            var date = new Date(dateString);
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
        }

        /**
         * Escape HTML
         */
        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        /**
         * Show alert message
         */
        function showMessage(type, message) {
            var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            var icon = type === 'success' ? 'check-circle' : 'exclamation-triangle';

            var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                '<i class="fa fa-' + icon + '"></i> ' + message +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span>' +
                '</button>' +
                '</div>');

            // Insert at top of content
            $alert.prependTo('#content').hide().slideDown();

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $alert.slideUp(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        console.log('User management JavaScript loaded successfully');
    });

})(jQuery);
