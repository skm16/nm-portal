/**
 * NMDA Portal Custom JavaScript
 *
 * @package NMDA_Understrap_Child
 */

(function ($) {
  'use strict';

  /**
   * Multi-step form handler
   */
  class NMDAMultiStepForm {
    constructor(formSelector) {
      this.$form = $(formSelector);
      this.currentStep = 0;
      this.$steps = this.$form.find('.nmda-form-step');
      this.totalSteps = this.$steps.length;
      this.init();
    }

    init() {
      this.showStep(0);
      this.bindEvents();
    }

    bindEvents() {
      const self = this;

      // Next button
      this.$form.on('click', '.nmda-btn-next', function (e) {
        e.preventDefault();
        if (self.validateStep(self.currentStep)) {
          self.nextStep();
        }
      });

      // Previous button
      this.$form.on('click', '.nmda-btn-prev', function (e) {
        e.preventDefault();
        self.prevStep();
      });

      // Save draft button
      this.$form.on('click', '.nmda-btn-save-draft', function (e) {
        e.preventDefault();
        self.saveDraft();
      });
    }

    showStep(step) {
      this.$steps.removeClass('active');
      this.$steps.eq(step).addClass('active');
      this.updateProgressIndicator(step);
      this.currentStep = step;

      // Update button visibility
      this.$form.find('.nmda-btn-prev').toggle(step > 0);
      this.$form.find('.nmda-btn-next').toggle(step < this.totalSteps - 1);
      this.$form.find('.nmda-btn-submit').toggle(step === this.totalSteps - 1);
    }

    nextStep() {
      if (this.currentStep < this.totalSteps - 1) {
        this.showStep(this.currentStep + 1);
      }
    }

    prevStep() {
      if (this.currentStep > 0) {
        this.showStep(this.currentStep - 1);
      }
    }

    validateStep(step) {
      const $currentStep = this.$steps.eq(step);
      let isValid = true;

      // Validate required fields in current step
      $currentStep.find('[required]').each(function () {
        if (!this.checkValidity()) {
          $(this).addClass('is-invalid');
          isValid = false;
        } else {
          $(this).removeClass('is-invalid');
        }
      });

      return isValid;
    }

    updateProgressIndicator(step) {
      const $indicator = this.$form.find('.nmda-progress-indicator');
      $indicator.find('.nmda-progress-step').each(function (index) {
        $(this).toggleClass('completed', index <= step);
      });
    }

    saveDraft() {
      const formData = this.$form.serialize();

      $.ajax({
        url: nmdaAjax.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_save_draft',
          nonce: nmdaAjax.nonce,
          formData: formData,
        },
        success: function (response) {
          if (response.success) {
            alert('Draft saved successfully!');
          } else {
            alert('Error saving draft. Please try again.');
          }
        },
        error: function () {
          alert('Error saving draft. Please try again.');
        },
      });
    }
  }

  /**
   * Document upload handler
   */
  class NMDADocumentUpload {
    constructor(uploadAreaSelector) {
      this.$uploadArea = $(uploadAreaSelector);
      this.$input = this.$uploadArea.find('input[type="file"]');
      this.$documentList = $('.nmda-uploaded-documents');
      this.documents = [];
      this.init();
    }

    init() {
      this.bindEvents();
    }

    bindEvents() {
      const self = this;

      // Click to upload
      this.$uploadArea.on('click', function () {
        self.$input.click();
      });

      // File input change
      this.$input.on('change', function () {
        self.handleFiles(this.files);
      });

      // Drag and drop
      this.$uploadArea.on('dragover', function (e) {
        e.preventDefault();
        $(this).addClass('drag-over');
      });

      this.$uploadArea.on('dragleave', function () {
        $(this).removeClass('drag-over');
      });

      this.$uploadArea.on('drop', function (e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        self.handleFiles(e.originalEvent.dataTransfer.files);
      });

      // Remove document
      this.$documentList.on('click', '.remove-doc', function () {
        const index = $(this).data('index');
        self.removeDocument(index);
      });
    }

    handleFiles(files) {
      const self = this;

      Array.from(files).forEach(function (file) {
        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
          alert('File ' + file.name + ' is too large. Maximum size is 10MB.');
          return;
        }

        // Upload file via AJAX
        self.uploadFile(file);
      });
    }

    uploadFile(file) {
      const self = this;
      const formData = new FormData();
      formData.append('file', file);
      formData.append('action', 'nmda_upload_document');
      formData.append('nonce', nmdaAjax.nonce);

      $.ajax({
        url: nmdaAjax.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            self.addDocument({
              id: response.data.id,
              name: file.name,
              url: response.data.url,
            });
          } else {
            alert('Error uploading file: ' + file.name);
          }
        },
        error: function () {
          alert('Error uploading file: ' + file.name);
        },
      });
    }

    addDocument(doc) {
      this.documents.push(doc);
      this.renderDocuments();
    }

    removeDocument(index) {
      this.documents.splice(index, 1);
      this.renderDocuments();
    }

    renderDocuments() {
      const self = this;
      this.$documentList.empty();

      this.documents.forEach(function (doc, index) {
        const $item = $('<div class="nmda-document-item">')
          .append(
            $('<span class="doc-name">').text(doc.name),
            $('<span class="remove-doc">').attr('data-index', index).html('&times;')
          );
        self.$documentList.append($item);
      });
    }

    getDocuments() {
      return this.documents;
    }
  }

  /**
   * AJAX form handler
   */
  function handleAjaxForm(formSelector, successCallback) {
    $(formSelector).on('submit', function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitBtn = $form.find('[type="submit"]');
      const formData = new FormData(this);
      formData.append('nonce', nmdaAjax.nonce);

      // Disable submit button
      $submitBtn.prop('disabled', true).text('Processing...');

      $.ajax({
        url: nmdaAjax.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            if (typeof successCallback === 'function') {
              successCallback(response.data);
            } else {
              alert('Form submitted successfully!');
              $form[0].reset();
            }
          } else {
            alert('Error: ' + (response.data.message || 'Unknown error'));
          }
        },
        error: function () {
          alert('An error occurred. Please try again.');
        },
        complete: function () {
          $submitBtn.prop('disabled', false).text('Submit');
        },
      });
    });
  }

  /**
   * Editable field handler
   */
  function initEditableFields() {
    $('.nmda-editable-field .nmda-edit-icon').on('click', function () {
      const $field = $(this).closest('.nmda-editable-field');
      const $display = $field.find('.field-display');
      const $input = $field.find('.field-input');

      $display.hide();
      $input.show().focus();
    });

    $('.nmda-editable-field .field-input').on('blur', function () {
      const $input = $(this);
      const $field = $input.closest('.nmda-editable-field');
      const $display = $field.find('.field-display');
      const fieldName = $field.data('field');
      const businessId = $field.data('business-id');
      const newValue = $input.val();

      // Update via AJAX
      $.ajax({
        url: nmdaAjax.ajaxurl,
        type: 'POST',
        data: {
          action: 'nmda_update_field',
          nonce: nmdaAjax.nonce,
          business_id: businessId,
          field_name: fieldName,
          value: newValue,
        },
        success: function (response) {
          if (response.success) {
            $display.text(newValue).show();
            $input.hide();

            if (response.data.pending_approval) {
              $field.append(
                '<div class="nmda-pending-approval">Change pending admin approval</div>'
              );
            }
          } else {
            alert('Error updating field: ' + response.data.message);
          }
        },
        error: function () {
          alert('Error updating field. Please try again.');
        },
      });
    });
  }

  /**
   * Notification center
   */
  function initNotifications() {
    // Mark notification as read
    $('.nmda-notification-item').on('click', function () {
      const $item = $(this);
      const notificationId = $item.data('id');

      if ($item.hasClass('unread')) {
        $.ajax({
          url: nmdaAjax.ajaxurl,
          type: 'POST',
          data: {
            action: 'nmda_mark_notification_read',
            nonce: nmdaAjax.nonce,
            notification_id: notificationId,
          },
          success: function (response) {
            if (response.success) {
              $item.removeClass('unread');
              updateNotificationBadge();
            }
          },
        });
      }
    });
  }

  function updateNotificationBadge() {
    const unreadCount = $('.nmda-notification-item.unread').length;
    const $badge = $('.nmda-notification-badge');

    if (unreadCount > 0) {
      $badge.text(unreadCount).show();
    } else {
      $badge.hide();
    }
  }

  /**
   * Handle business application form submission with redirect
   */
  function handleBusinessApplicationForm() {
    $('#nmda-business-application').on('submit', function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitBtn = $form.find('.nmda-btn-submit');
      const formData = new FormData(this);

      // Disable submit button
      $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');

      $.ajax({
        url: nmdaAjax.ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
          if (response.success) {
            // Show success message
            $form.html(
              '<div class="alert alert-success">' +
              '<h4><i class="fa fa-check-circle"></i> Application Submitted Successfully!</h4>' +
              '<p>' + (response.data.message || 'Your business application has been submitted.') + '</p>' +
              '<p>Application ID: #' + response.data.business_id + '</p>' +
              '<p>You will receive a confirmation email shortly. We will review your application and notify you once it has been processed.</p>' +
              '<a href="' + (response.data.redirect || '/dashboard') + '" class="btn btn-primary">Go to Dashboard</a>' +
              '</div>'
            );

            // Scroll to top
            $('html, body').animate({ scrollTop: 0 }, 500);
          } else {
            alert('Error: ' + (response.data.message || 'Unknown error. Please try again.'));
            $submitBtn.prop('disabled', false).html('<i class="fa fa-check"></i> Submit Application');
          }
        },
        error: function () {
          alert('An error occurred while submitting your application. Please try again.');
          $submitBtn.prop('disabled', false).html('<i class="fa fa-check"></i> Submit Application');
        },
      });
    });
  }

  /**
   * Initialize on document ready
   */
  $(document).ready(function () {
    // Initialize multi-step forms
    if ($('.nmda-reimbursement-form').length) {
      new NMDAMultiStepForm('.nmda-reimbursement-form');
    }

    // Handle business application form specially
    if ($('#nmda-business-application').length) {
      handleBusinessApplicationForm();
    }

    // Initialize document upload
    if ($('.nmda-document-upload-area').length) {
      window.nmdaDocUpload = new NMDADocumentUpload('.nmda-document-upload-area');
    }

    // Initialize editable fields
    initEditableFields();

    // Initialize notifications
    initNotifications();
    updateNotificationBadge();

    // Handle other AJAX forms (not business application)
    if ($('.nmda-ajax-form').not('#nmda-business-application').length) {
      handleAjaxForm('.nmda-ajax-form:not(#nmda-business-application)');
    }
  });
})(jQuery);
