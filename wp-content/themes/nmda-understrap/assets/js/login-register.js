/**
 * NMDA Portal - Login & Registration Form Enhancements
 *
 * Provides UX enhancements without blocking form functionality
 *
 * @package NMDA_Understrap_Child
 */

(function($) {
    'use strict';

    /**
     * Password Toggle Functionality
     * Shows/hides password in password fields
     */
    function initPasswordToggle() {
        // Use event delegation for better reliability
        $(document).on('click', '.password-toggle', function(e) {
            e.preventDefault();

            const $button = $(this);
            const targetId = $button.data('target');

            if (!targetId) {
                console.warn('Password toggle button missing data-target attribute');
                return;
            }

            const $passwordField = $('#' + targetId);

            if ($passwordField.length === 0) {
                console.warn('Password field not found: ' + targetId);
                return;
            }

            const $icon = $button.find('i');

            if ($passwordField.attr('type') === 'password') {
                $passwordField.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                $button.attr('aria-label', 'Hide password');
            } else {
                $passwordField.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                $button.attr('aria-label', 'Show password');
            }
        });
    }

    /**
     * Password Strength Checker
     * Returns strength score and feedback
     */
    function checkPasswordStrength(password) {
        let strength = 0;
        const feedback = [];

        if (password.length >= 8) strength++;
        else feedback.push('At least 8 characters');

        if (/[a-z]/.test(password)) strength++;
        else feedback.push('lowercase letters');

        if (/[A-Z]/.test(password)) strength++;
        else feedback.push('uppercase letters');

        if (/[0-9]/.test(password)) strength++;
        else feedback.push('numbers');

        if (/[^a-zA-Z0-9]/.test(password)) strength++;
        else feedback.push('special characters');

        let level = 'weak';
        let text = 'Weak';
        let color = '#dc3545';

        if (strength >= 5) {
            level = 'strong';
            text = 'Strong';
            color = '#28a745';
        } else if (strength >= 3) {
            level = 'medium';
            text = 'Medium';
            color = '#ffc107';
        }

        return { strength, level, text, color, feedback };
    }

    /**
     * Initialize Password Strength Indicator
     */
    function initPasswordStrength() {
        const $passwordFields = $('#nmda_password, #nmda_new_password');

        if ($passwordFields.length === 0) {
            return; // No password fields on this page
        }

        $passwordFields.each(function() {
            const $field = $(this);
            const $formGroup = $field.closest('.form-group');

            // Check if strength indicator already exists
            if ($formGroup.find('.password-strength-indicator').length > 0) {
                return;
            }

            // Create strength indicator HTML
            const $indicator = $('<div class="password-strength-indicator mt-2"></div>');
            const $bar = $('<div class="strength-bar-container"><div class="strength-bar"></div></div>');
            const $text = $('<div class="strength-text small mt-1"></div>');

            $indicator.append($bar).append($text);

            // Insert after form-text or after field
            const $insertAfter = $formGroup.find('.form-text').length > 0
                ? $formGroup.find('.form-text')
                : $field.closest('.password-field-wrapper');

            $insertAfter.after($indicator);

            // Add event listener
            $field.on('input', function() {
                const password = $(this).val();
                const $strengthBar = $indicator.find('.strength-bar');
                const $strengthText = $indicator.find('.strength-text');

                if (password.length === 0) {
                    $strengthBar.css({ 'width': '0%', 'background-color': '#e9ecef' });
                    $strengthText.html('');
                    return;
                }

                const result = checkPasswordStrength(password);
                const percentage = (result.strength / 5) * 100;

                $strengthBar.css({
                    'width': percentage + '%',
                    'background-color': result.color,
                    'transition': 'all 0.3s ease'
                });

                let feedbackText = '<strong>Strength:</strong> <span style="color: ' + result.color + ';">' + result.text + '</span>';

                if (result.feedback.length > 0) {
                    feedbackText += '<br><small class="text-muted">Add: ' + result.feedback.join(', ') + '</small>';
                }

                $strengthText.html(feedbackText);
            });
        });

        // Add CSS if not already present
        if ($('#password-strength-styles').length === 0) {
            $('head').append(`
                <style id="password-strength-styles">
                    .password-strength-indicator { margin-top: 0.5rem; }
                    .strength-bar-container {
                        width: 100%;
                        height: 6px;
                        background-color: #e9ecef;
                        border-radius: 3px;
                        overflow: hidden;
                    }
                    .strength-bar {
                        height: 100%;
                        width: 0%;
                        background-color: #e9ecef;
                        transition: all 0.3s ease;
                    }
                    .strength-text { color: #6c757d; }
                </style>
            `);
        }
    }

    /**
     * Password Match Validation (Visual Only)
     * Does NOT block form submission
     */
    function initPasswordMatch() {
        const $password = $('#nmda_password, #nmda_new_password');
        const $confirmPassword = $('#nmda_password_confirm, #nmda_confirm_password');

        if ($password.length === 0 || $confirmPassword.length === 0) {
            return;
        }

        // Add visual feedback only - don't block submission
        $confirmPassword.on('input', function() {
            const password = $password.val();
            const confirm = $(this).val();

            if (confirm.length === 0) {
                $(this).removeClass('is-invalid is-valid');
                return;
            }

            if (password === confirm) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        });

        $password.on('input', function() {
            if ($confirmPassword.val().length > 0) {
                $confirmPassword.trigger('input');
            }
        });
    }

    /**
     * Visual Field Validation
     * Adds is-invalid class to empty required fields on blur
     * Does NOT block form submission
     */
    function initVisualValidation() {
        $('form input[required], form select[required], form textarea[required]').on('blur', function() {
            const $field = $(this);

            if ($field.val().trim() === '') {
                $field.addClass('is-invalid');
            } else {
                $field.removeClass('is-invalid');
            }
        });

        // Remove invalid class on input
        $('form input, form select, form textarea').on('input change', function() {
            $(this).removeClass('is-invalid');
        });
    }

    /**
     * Auto-dismiss Success Alerts
     */
    function initAutoDismiss() {
        $('.alert-success, .alert-info').each(function() {
            const $alert = $(this);
            setTimeout(function() {
                $alert.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 8000);
        });
    }

    /**
     * Focus First Input
     */
    function focusFirstInput() {
        $('form input:visible:not([type="hidden"]):first').focus();
    }

    /**
     * Initialize All Functions
     */
    $(document).ready(function() {
        try {
            initPasswordToggle();
            initPasswordStrength();
            initPasswordMatch();
            initVisualValidation();
            initAutoDismiss();
            focusFirstInput();

            console.log('NMDA Login/Register enhancements loaded successfully');
        } catch (error) {
            console.error('Error initializing login/register enhancements:', error);
            // Don't throw - let forms work even if enhancements fail
        }
    });

})(jQuery);
