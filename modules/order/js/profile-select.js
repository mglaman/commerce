(function ($, Drupal) {
  Drupal.behaviors.profileSelect = {
    attach: function (context) {
      var $selects = $(context).find('.profile-select').once();
      if ($selects.length > 0) {
        $selects.each(function (index, el) {
          var $profileSelect = $(el);
          $profileSelect.find('[name="edit_profile"]').click(function (event) {
            event.preventDefault();
            $profileSelect.find('.profile-view-wrapper').toggleClass('editing');
            $profileSelect.find('.profile-form-wrapper').toggleClass('editing');
          })
        });
      }
    }
  };
})(jQuery, Drupal);
