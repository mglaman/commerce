(function ($, Drupal) {
  Drupal.behaviors.profileSelect = {
    attach: function (context) {
      function toggleRequired($profileSelect, required) {
        $profileSelect.find('.required').each(function (key, el) {
          el.required = required;
        });
      }

      var $selects = $(context).find('.profile-select').once();
      if ($selects.length > 0) {
        $selects.each(function (index, el) {
          var $profileSelect = $(el);
          var $inputs = $profileSelect.find('input:not([type=submit]):not([type=button]),select,textarea');
          $profileSelect.data('originalValues', $inputs.serializeArray());
          toggleRequired($profileSelect, false);

          $profileSelect.find('.edit-profile').once().click(function (event) {
            event.preventDefault();
            toggleRequired($profileSelect, true);
            $profileSelect.toggleClass('editing');

            $profileSelect.find('.cancel-edit-profile').once().click(function (event) {
              event.preventDefault();
              $profileSelect.toggleClass('editing');
              toggleRequired($profileSelect, false);
              var originalValues = $profileSelect.data('originalValues');
              $.each(originalValues, function (key, field) {
                console.log(field);
                console.log($profileSelect.find('[name="' + field['name'] + '"]').val());
                $profileSelect.find('[name="' + field['name'] + '"]').val(field['value']);
              });
            });
          });
        });
      }
    }
  };
})(jQuery, Drupal);
