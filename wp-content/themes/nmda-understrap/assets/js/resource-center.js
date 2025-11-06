/**
 * Resource Center JavaScript
 */

(function ($) {
  'use strict';

  $(document).ready(function () {
    var $searchInput = $('#search');
    var $categorySelect = $('#category');
    var $clearButton = $('.clear-filters');
    var $resourceCards = $('.resource-card').parent(); // Get the col-* wrapper
    var $resourcesGrid = $('.card-body .row').last(); // The resources grid row

    // Prevent form submission - handle filtering client-side
    $('form').on('submit', function (e) {
      e.preventDefault();
      filterResources();
      return false;
    });

    // Filter on input change
    $searchInput.on('input', function () {
      filterResources();
    });

    // Filter on category change
    $categorySelect.on('change', function () {
      filterResources();
    });

    // Clear filters
    $clearButton.on('click', function (e) {
      e.preventDefault();
      $searchInput.val('');
      $categorySelect.val('');
      filterResources();
      $(this).parent().hide();
    });

    function filterResources() {
      var searchTerm = $searchInput.val().toLowerCase();
      var categoryFilter = $categorySelect.val();
      var visibleCount = 0;

      // Get selected category name without the count (e.g., "Marketing (5)" becomes "Marketing")
      var selectedCategoryName = '';
      if (categoryFilter) {
        selectedCategoryName = $categorySelect.find('option:selected').text().split('(')[0].trim().toLowerCase();
      }

      $resourceCards.each(function () {
        var $card = $(this);
        var title = $card.find('.resource-title').text().toLowerCase();
        var description = $card.find('.resource-description').text().toLowerCase();
        var categories = [];

        // Get all category badges - clone and remove icon to get clean text
        $card.find('.resource-categories .badge').each(function () {
          var $badge = $(this).clone();
          $badge.find('i').remove(); // Remove the icon
          categories.push($badge.text().trim().toLowerCase());
        });

        var matchesSearch = !searchTerm || title.indexOf(searchTerm) > -1 || description.indexOf(searchTerm) > -1;

        var matchesCategory = !categoryFilter || categories.some(function (cat) {
          return cat === selectedCategoryName;
        });

        if (matchesSearch && matchesCategory) {
          $card.show();
          visibleCount++;
        } else {
          $card.hide();
        }
      });

      // Show/hide "no results" message
      if (visibleCount === 0) {
        if ($('.no-results-message').length === 0) {
          $resourcesGrid.after(
            '<div class="alert alert-info no-results-message">' +
              '<i class="fa fa-info-circle"></i> No resources found matching your search criteria.' +
              '</div>'
          );
        }
        $resourcesGrid.hide();
      } else {
        $('.no-results-message').remove();
        $resourcesGrid.show();
      }

      // Show/hide clear button
      if (searchTerm || categoryFilter) {
        $clearButton.parent().show();
      } else {
        $clearButton.parent().hide();
      }
    }

    // Initial filter check
    filterResources();
  });
})(jQuery);
