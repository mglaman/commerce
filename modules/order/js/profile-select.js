(function ($, Drupal) {
  Drupal.behaviors.profileSelect = {
    attach: function (context) {
      var $selects = $(context).find('.profile-select').once();
      if ($selects.length > 0) {
        $selects.each(function (index, el) {
          var $profileSelect = $(el);
          $profileSelect.find('[name="edit_profile"]').once().click(function (event) {
            event.preventDefault();
            $profileSelect.toggleClass('editing');
            $profileSelect.find('.cancel-edit-profile').once().click(function (event) {
              event.preventDefault();
              $profileSelect.toggleClass('editing');
            });
          });
          $profileSelect.find('.cancel-new-profile').once().click(function (event) {
            event.preventDefault();
            var $availableProfiles = $profileSelect.find('.available-profiles');
            $availableProfiles.val($availableProfiles.find('option:first').val()).trigger('change');
          });
        });
      }
    }
  };
})(jQuery, Drupal);
