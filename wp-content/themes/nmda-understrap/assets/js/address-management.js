/**
 * Address Management JavaScript
 *
 * Handles CRUD operations for business addresses
 *
 * @package NMDA_Understrap_Child
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Only run on edit profile page
        if ($('#addresses-list').length === 0) {
            return;
        }

        /**
         * Add New Address Button
         */
        $('#add-address-btn').on('click', function(e) {
            e.preventDefault();

            // Reset form
            $('#address-form')[0].reset();
            $('#address-index').val('');
            $('#modal-title-text').text('Add New Address');

            // Show modal
            $('#addressModal').modal('show');
        });

        /**
         * Edit Address Button
         */
        $(document).on('click', '.edit-address-btn', function(e) {
            e.preventDefault();

            var index = $(this).data('index');
            var businessId = $('#business-id-address').val();

            // Show loading state
            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

            // Fetch address data via AJAX
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_get_address',
                    business_id: businessId,
                    index: index,
                    nonce: nmdaAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var address = response.data.address;

                        // Populate form with ACF field names
                        $('#address-index').val(address.index);
                        $('#location-name').val(address.location_name || '');
                        $('#location-type').val(address.location_type || '');
                        $('#address').val(address.address || '');
                        $('#address-2').val(address.address_2 || '');
                        $('#city').val(address.city || '');
                        $('#state').val(address.state || '');
                        $('#zip').val(address.zip || '');
                        $('#county').val(address.county || '');
                        $('#phone').val(address.phone || '');
                        $('#email').val(address.email || '');
                        $('#country').val(address.country || 'USA');

                        // Update modal title
                        $('#modal-title-text').text('Edit Address');

                        // Show modal
                        $('#addressModal').modal('show');
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while loading the address.');
                },
                complete: function() {
                    // Reset button state
                    $('.edit-address-btn').prop('disabled', false).html('<i class="fa fa-edit"></i> Edit');
                }
            });
        });

        /**
         * Save Address (Add or Update)
         */
        $('#address-form').on('submit', function(e) {
            e.preventDefault();

            var $submitBtn = $('#save-address-btn');
            var originalText = $submitBtn.html();

            // Disable submit button
            $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            // Get form data
            var formData = $(this).serialize();
            formData += '&action=nmda_save_address&nonce=' + nmdaAjax.nonce;

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('#addressModal').modal('hide');

                        // Show success message
                        showMessage('success', response.data.message);

                        // Refresh address list
                        refreshAddressList(response.data.addresses);
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while saving the address.');
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        /**
         * Delete Address Button
         */
        $(document).on('click', '.delete-address-btn', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this address? This action cannot be undone.')) {
                return;
            }

            var index = $(this).data('index');
            var businessId = $('#business-id-address').val();
            var $button = $(this);
            var $addressItem = $button.closest('.address-item');

            // Disable button
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deleting...');

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_delete_address',
                    business_id: businessId,
                    index: index,
                    nonce: nmdaAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove address item with animation
                        $addressItem.fadeOut(300, function() {
                            $(this).remove();

                            // Check if any addresses remain
                            if ($('.address-item').length === 0) {
                                $('#addresses-list').html(
                                    '<div class="alert alert-info">' +
                                    '<i class="fa fa-info-circle"></i> No additional addresses found. Add your first address above.' +
                                    '</div>'
                                );
                            }
                        });

                        // Show success message
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data.message);
                        $button.prop('disabled', false).html('<i class="fa fa-trash"></i> Delete');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while deleting the address.');
                    $button.prop('disabled', false).html('<i class="fa fa-trash"></i> Delete');
                }
            });
        });

        /**
         * Set as Primary Address Button
         */
        $(document).on('click', '.set-primary-btn', function(e) {
            e.preventDefault();

            if (!confirm('Set this as the primary address? This will copy this address to your primary business location.')) {
                return;
            }

            var index = $(this).data('index');
            var businessId = $('#business-id-address').val();
            var $button = $(this);

            // Disable button
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Setting...');

            // Send AJAX request
            $.ajax({
                url: nmdaAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'nmda_set_primary_address',
                    business_id: businessId,
                    index: index,
                    nonce: nmdaAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showMessage('success', response.data.message);

                        // Reload page to show updated primary address
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showMessage('error', response.data.message);
                        $button.prop('disabled', false).html('<i class="fa fa-star"></i> Set as Primary');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while setting primary address.');
                    $button.prop('disabled', false).html('<i class="fa fa-star"></i> Set as Primary');
                }
            });
        });

        /**
         * Refresh address list display
         *
         * @param {Array} addresses Array of address objects
         */
        function refreshAddressList(addresses) {
            var $list = $('#addresses-list');
            $list.empty();

            if (!addresses || addresses.length === 0) {
                $list.html(
                    '<div class="alert alert-info">' +
                    '<i class="fa fa-info-circle"></i> No additional addresses found. Add your first address above.' +
                    '</div>'
                );
                return;
            }

            addresses.forEach(function(address, index) {
                var locationType = address.location_type || '';
                var locationTypeLabel = locationType.replace(/_/g, ' ');
                locationTypeLabel = locationTypeLabel.charAt(0).toUpperCase() + locationTypeLabel.slice(1);
                var locationName = address.location_name || locationTypeLabel || 'Address';

                var addressHtml =
                    '<div class="address-item card mb-3" data-index="' + index + '">' +
                    '    <div class="card-body">' +
                    '        <div class="row align-items-center">' +
                    '            <div class="col-md-8">' +
                    '                <div class="address-details">' +
                    '                    <h6 class="mb-1">' + locationName + '</h6>';

                if (locationType) {
                    addressHtml +=
                        '                    <p class="mb-1 text-muted">' +
                        '                        <i class="fa fa-tag"></i> <strong>Type:</strong> ' + locationTypeLabel +
                        '                    </p>';
                }

                addressHtml +=
                    '                    <p class="mb-0">' +
                    '                        <i class="fa fa-map-marker"></i> ' +
                                        (address.address || '');

                if (address.address_2) {
                    addressHtml += ', ' + address.address_2;
                }

                addressHtml += '<br>' + (address.city || '') + ', ' + (address.state || '') + ' ' + (address.zip || '');

                if (address.county) {
                    addressHtml += ' (' + address.county + ' County)';
                }

                addressHtml += '                    </p>';

                if (address.phone) {
                    addressHtml +=
                        '                    <p class="mb-0 mt-1">' +
                        '                        <i class="fa fa-phone"></i> ' + address.phone +
                        '                    </p>';
                }

                if (address.email) {
                    addressHtml +=
                        '                    <p class="mb-0 mt-1">' +
                        '                        <i class="fa fa-envelope"></i> ' + address.email +
                        '                    </p>';
                }

                addressHtml +=
                    '                </div>' +
                    '            </div>' +
                    '            <div class="col-md-4 text-right">' +
                    '                <div class="address-actions">' +
                    '                    <button type="button" class="btn btn-sm btn-outline-success set-primary-btn" data-index="' + index + '">' +
                    '                        <i class="fa fa-star"></i> Set as Primary' +
                    '                    </button>' +
                    '                    <button type="button" class="btn btn-sm btn-outline-primary edit-address-btn" data-index="' + index + '">' +
                    '                        <i class="fa fa-edit"></i> Edit' +
                    '                    </button>' +
                    '                    <button type="button" class="btn btn-sm btn-outline-danger delete-address-btn" data-index="' + index + '">' +
                    '                        <i class="fa fa-trash"></i> Delete' +
                    '                    </button>' +
                    '                </div>' +
                    '            </div>' +
                    '        </div>' +
                    '    </div>' +
                    '</div>';

                $list.append(addressHtml);
            });
        }

        /**
         * Show alert message
         *
         * @param {string} type Message type ('success' or 'error')
         * @param {string} message Message text
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

            // Insert before addresses section
            $alert.insertBefore('#addresses-list').hide().slideDown();

            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $alert.slideUp(function() {
                    $(this).remove();
                });
            }, 5000);
        }

        console.log('Address management JavaScript loaded successfully');
    });

})(jQuery);
