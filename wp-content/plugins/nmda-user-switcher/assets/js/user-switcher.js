/**
 * NMDA User Switcher JavaScript
 * Handles user search and AJAX interactions
 */

(function($) {
    'use strict';

    var NMDAUserSwitcher = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initUserSearch();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle "Switch to Another User" click
            $(document).on('click', '.nmda-switch-to-other', function(e) {
                e.preventDefault();
                NMDAUserSwitcher.showUserSearch();
            });

            // Handle search results click
            $(document).on('click', '.nmda-user-result-item', function(e) {
                e.preventDefault();
                var url = $(this).data('switch-url');
                if (url) {
                    window.location.href = url;
                }
            });

            // Close search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.nmda-user-search-wrapper').length) {
                    $('.nmda-user-search-results').removeClass('has-results').empty();
                }
            });

            // Keyboard navigation for search results
            $(document).on('keydown', '#nmda-user-search-input', function(e) {
                if (e.key === 'Escape') {
                    $('.nmda-user-search-results').removeClass('has-results').empty();
                    $(this).blur();
                }
            });

            // Add keyboard navigation for results
            $(document).on('keydown', '.nmda-user-result-item', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },

        /**
         * Initialize user search autocomplete
         */
        initUserSearch: function() {
            var searchInput = $('#nmda-user-search-input');

            if (searchInput.length === 0) {
                return;
            }

            var searchTimeout = null;

            searchInput.on('input', function() {
                var $input = $(this);
                var searchTerm = $input.val().trim();

                // Clear previous timeout
                clearTimeout(searchTimeout);

                if (searchTerm.length < 2) {
                    $('.nmda-user-search-results').removeClass('has-results').empty();
                    return;
                }

                // Show loading state
                $('.nmda-user-search-results')
                    .addClass('has-results')
                    .html('<div class="nmda-user-search-loading">' +
                          nmdaUserSwitcher.strings.loading +
                          '</div>');

                // Debounce search
                searchTimeout = setTimeout(function() {
                    NMDAUserSwitcher.performSearch(searchTerm);
                }, 300);
            });

            // Focus search input when admin bar item is clicked
            $(document).on('click', '#wp-admin-bar-nmda-user-search', function(e) {
                e.stopPropagation();
                setTimeout(function() {
                    searchInput.focus();
                }, 100);
            });
        },

        /**
         * Perform user search via AJAX
         */
        performSearch: function(searchTerm) {
            $.ajax({
                url: nmdaUserSwitcher.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'nmda_search_users',
                    search: searchTerm,
                    nonce: nmdaUserSwitcher.nonce
                },
                success: function(response) {
                    if (response.success && response.data.users) {
                        NMDAUserSwitcher.displaySearchResults(response.data.users);
                    } else {
                        NMDAUserSwitcher.displayNoResults();
                    }
                },
                error: function() {
                    NMDAUserSwitcher.displayError();
                }
            });
        },

        /**
         * Display search results
         */
        displaySearchResults: function(users) {
            var $results = $('.nmda-user-search-results');

            if (users.length === 0) {
                this.displayNoResults();
                return;
            }

            var html = '';

            users.forEach(function(user) {
                var businessText = user.business_count > 0
                    ? user.business_count + ' ' + (user.business_count === 1 ? 'Business' : 'Businesses')
                    : 'No Businesses';

                html += '<div class="nmda-user-result-item" data-switch-url="' +
                        NMDAUserSwitcher.escapeHtml(user.switch_url) + '" tabindex="0">' +
                        '<span class="nmda-user-result-name">' +
                        NMDAUserSwitcher.escapeHtml(user.display_name) +
                        '</span>' +
                        '<span class="nmda-user-result-email">' +
                        NMDAUserSwitcher.escapeHtml(user.user_email) +
                        '</span>' +
                        '<div class="nmda-user-result-meta">' +
                        '<span class="nmda-user-result-role">' +
                        NMDAUserSwitcher.escapeHtml(user.roles) +
                        '</span>' +
                        '<span class="nmda-user-result-businesses">' +
                        businessText +
                        '</span>' +
                        '</div>' +
                        '</div>';
            });

            $results.addClass('has-results').html(html);
        },

        /**
         * Display no results message
         */
        displayNoResults: function() {
            $('.nmda-user-search-results')
                .addClass('has-results')
                .html('<div class="nmda-user-search-empty">' +
                      nmdaUserSwitcher.strings.noResults +
                      '</div>');
        },

        /**
         * Display error message
         */
        displayError: function() {
            $('.nmda-user-search-results')
                .addClass('has-results')
                .html('<div class="nmda-user-search-empty">Error loading results. Please try again.</div>');
        },

        /**
         * Show user search (for "Switch to Another User" functionality)
         */
        showUserSearch: function() {
            var searchInput = $('#nmda-user-search-input');

            if (searchInput.length > 0) {
                // Clear existing search
                searchInput.val('').focus();
                $('.nmda-user-search-results').removeClass('has-results').empty();

                // Scroll to admin bar
                $('html, body').animate({
                    scrollTop: 0
                }, 300);
            }
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },

        /**
         * Get user info via AJAX (for detailed view)
         */
        getUserInfo: function(userId, callback) {
            $.ajax({
                url: nmdaUserSwitcher.ajaxUrl,
                type: 'GET',
                data: {
                    action: 'nmda_get_user_info',
                    user_id: userId,
                    nonce: nmdaUserSwitcher.nonce
                },
                success: function(response) {
                    if (response.success && response.data.user) {
                        callback(response.data.user);
                    }
                },
                error: function() {
                    console.error('Failed to load user info');
                }
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        NMDAUserSwitcher.init();
    });

    /**
     * Make accessible globally if needed
     */
    window.NMDAUserSwitcher = NMDAUserSwitcher;

})(jQuery);
