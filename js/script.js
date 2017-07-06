/**
 * @file
 * BCELN Theme's default drupal behaviour.
 */
(function ($, Drupal, window, document, undefined) {

Drupal.behaviors.ocls_theme_behaviour = {
  attach: function(context, settings) {
    // Updated the search bar width, to match the main menu width. This
    // Should only be done if not in mobile view (hence the width check
    // On line 15).
    function updateSearchBar() {
      if ($('.nav-inner-wrapper #islandora-solr-simple-search-form').length > 0) {
        // Ensure this is not in mobile mode (by page width).
        if ($(window).width() > 620) {
          $('#islandora-solr-simple-search-form').width($('#superfish-1').width());
        } else {
          $('#islandora-solr-simple-search-form').width('100%');
        }
      }
    }
    $(window).load(function(){
      // On the advanced search page, add another field
      // to query on.
      if ($('#edit-terms-0-add').length > 0) {
        $('#edit-terms-0-add').mousedown();
      }
      // Adjust the search bar width to match that of the menu.
//      updateSearchBar();
    });
    $(window).resize(function() {
      // Adjust the search bar width to match that of the menu.
//      updateSearchBar();
    });
  }
};

})(jQuery, Drupal, this, this.document);
