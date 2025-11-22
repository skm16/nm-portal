/**
 * User Directory JavaScript
 *
 * Handles interactions for the user directory page
 */

(function($) {
    'use strict';

    /**
     * User Directory Manager
     */
    class UserDirectory {
        constructor() {
            this.selectedUsers = new Set();
            this.init();
        }

        /**
         * Initialize the directory
         */
        init() {
            this.bindEvents();
            this.addDataLabels();
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Select all users checkbox
            $('#select-all-users').on('change', (e) => {
                this.toggleSelectAll(e.target.checked);
            });

            // Individual user checkboxes
            $('.user-checkbox').on('change', (e) => {
                this.toggleUserSelection(e.target);
            });

            // Export users button
            $('#export-users-btn').on('click', () => {
                this.exportUsers();
            });

            // Bulk action apply
            $('#bulk-action-apply').on('click', () => {
                this.applyBulkAction();
            });

            // Handle form submission to preserve filters
            $('.user-filter-form').on('submit', function(e) {
                // Remove empty fields to clean up URL
                $(this).find('input[type="text"], select').each(function() {
                    if (!$(this).val()) {
                        $(this).prop('disabled', true);
                    }
                });
            });
        }

        /**
         * Add data labels for responsive table
         */
        addDataLabels() {
            $('.user-directory-table tbody tr').each(function() {
                $(this).find('td').each(function(index) {
                    const headerText = $('.user-directory-table thead th').eq(index).text().trim();
                    if (headerText && index > 0) {
                        $(this).attr('data-label', headerText);
                    }
                });
            });
        }

        /**
         * Toggle select all users
         */
        toggleSelectAll(checked) {
            $('.user-checkbox').prop('checked', checked);

            if (checked) {
                $('.user-checkbox').each((index, checkbox) => {
                    this.selectedUsers.add($(checkbox).val());
                });
            } else {
                this.selectedUsers.clear();
            }

            this.updateBulkActionsBar();
        }

        /**
         * Toggle individual user selection
         */
        toggleUserSelection(checkbox) {
            const userId = $(checkbox).val();

            if (checkbox.checked) {
                this.selectedUsers.add(userId);
            } else {
                this.selectedUsers.delete(userId);
                $('#select-all-users').prop('checked', false);
            }

            // Update select all checkbox if all are selected
            const allChecked = $('.user-checkbox:checked').length === $('.user-checkbox').length;
            $('#select-all-users').prop('checked', allChecked);

            this.updateBulkActionsBar();
        }

        /**
         * Update bulk actions bar visibility and count
         */
        updateBulkActionsBar() {
            const count = this.selectedUsers.size;
            const $bar = $('.bulk-actions-bar');

            if (count > 0) {
                $bar.slideDown();
                $bar.find('.selected-count').text(count);
            } else {
                $bar.slideUp();
            }
        }

        /**
         * Export users to CSV
         */
        exportUsers(selectedOnly = false) {
            const rows = [];

            // Add header row
            rows.push([
                'User ID',
                'Name',
                'Email',
                'Businesses',
                'Primary Role',
                'Status',
                'Last Login'
            ]);

            // Get table rows
            const $rows = selectedOnly
                ? $('.user-checkbox:checked').closest('tr')
                : $('.user-directory-table tbody tr');

            $rows.each(function() {
                const $row = $(this);
                const $checkbox = $row.find('.user-checkbox');

                if (!$checkbox.length) return; // Skip empty state row

                const userId = $checkbox.val();
                const name = $row.find('.user-name-link').text().trim();
                const email = $row.find('td').eq(2).text().trim();
                const businessCount = $row.find('.badge-info').text().trim();
                const role = $row.find('td').eq(4).text().trim();
                const status = $row.find('td').eq(5).text().trim();
                const lastLogin = $row.find('td').eq(6).text().trim();

                rows.push([
                    userId,
                    name,
                    email,
                    businessCount,
                    role,
                    status,
                    lastLogin
                ]);
            });

            // Convert to CSV
            const csv = rows.map(row =>
                row.map(cell => `"${cell}"`).join(',')
            ).join('\n');

            // Download CSV
            this.downloadCSV(csv, 'nmda-users-export.csv');
        }

        /**
         * Download CSV file
         */
        downloadCSV(content, filename) {
            const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');

            if (navigator.msSaveBlob) { // IE 10+
                navigator.msSaveBlob(blob, filename);
            } else {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            // Show success message
            this.showNotification('CSV exported successfully!', 'success');
        }

        /**
         * Apply bulk action
         */
        applyBulkAction() {
            const action = $('#bulk-action-select').val();

            if (!action) {
                this.showNotification('Please select an action', 'warning');
                return;
            }

            if (this.selectedUsers.size === 0) {
                this.showNotification('Please select at least one user', 'warning');
                return;
            }

            switch (action) {
                case 'export':
                    this.exportUsers(true);
                    break;

                case 'message':
                    this.sendBulkMessage();
                    break;

                default:
                    this.showNotification('Invalid action', 'error');
            }
        }

        /**
         * Send bulk message (placeholder)
         */
        sendBulkMessage() {
            const userIds = Array.from(this.selectedUsers);

            // This would typically open a modal or redirect to a messaging interface
            alert(`Send message to ${userIds.length} user(s):\n${userIds.join(', ')}`);

            // In production, this might make an AJAX call or redirect:
            /*
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_send_bulk_message',
                    nonce: nmdaAjax.nonce,
                    user_ids: userIds
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Messages sent successfully', 'success');
                    }
                }
            });
            */
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            const alertClass = `alert-${type === 'error' ? 'danger' : type}`;
            const $alert = $(`
                <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `);

            $alert.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 9999,
                minWidth: '300px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
            });

            $('body').append($alert);

            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                $alert.fadeOut(() => $alert.remove());
            }, 3000);
        }
    }

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize if we're on the user directory page
        if ($('.user-directory-table').length) {
            new UserDirectory();
        }

        // Add tooltips to buttons if Bootstrap tooltip is available
        if (typeof $.fn.tooltip === 'function') {
            $('[title]').tooltip();
        }

        // Smooth scroll to top when clicking pagination
        $('.pagination a').on('click', function() {
            $('html, body').animate({
                scrollTop: $('#page-wrapper').offset().top - 20
            }, 300);
        });
    });

})(jQuery);
