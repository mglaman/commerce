<?php

namespace Drupal\commerce_order\Element;

use Drupal\commerce\Element\CommerceElementTrait;
use Drupal\commerce\EntityHelper;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides a form element for selecting a customer profile.
 *
 * Usage example:
 * @code
 * $form['billing_profile'] = [
 *   '#type' => 'commerce_profile_select',
 *   '#default_value' => $profile,
 *   '#profile_type' => 'customer',
 *   '#profile_uid' => \Drupal::currentUser()->id(),
 *   '#default_country' => 'FR',
 *   '#available_countries' => ['US', 'FR'],
 * ];
 * @endcode
 *
 * To access the profile in validation or submission callbacks, use
 *   - $form_state->getValue('billing_profile')
 * Or, (kept for backwards compatibility)
 *   - $form['billing_profile']['#profile'].
 *
 * @RenderElement("commerce_profile_select")
 */
class ProfileSelect extends RenderElement {

  use CommerceElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#title' => t('Select a profile'),
      '#create_title' => t('+ Enter a new profile'),

      // Needed for creating new profiles, since #default_value may be empty.
      // @todo need to implement.
      '#profile_type' => NULL,
      '#profile_uid' => NULL,

      // Whether profiles should always be loaded in the latest revision.
      // Disable when editing historical data, such as placed orders.
      '#profile_latest_revision' => TRUE,

      // The country to select if the address widget doesn't have a default.
      '#default_country' => NULL,
      // A list of country codes. If empty, all countries will be available.
      '#available_countries' => [],

      // The profile entity operated on.
      '#default_value' => NULL,
      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElementSubmit'],
        [$class, 'validateForm'],
      ],
      '#commerce_element_submit' => [
        [$class, 'submitForm'],
      ],
      '#after_build' => [
        [$class, 'clearValues'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Validates the element properties.
   *
   * This also provides support breaking changes made that added the
   * profile_type and profile_uid values instead of passing a default
   * value.
   *
   * @param array $element
   *   The form element.
   *
   * @throws \InvalidArgumentException
   *   Thrown if an element property is invalid, or empty but required.
   */
  public static function validateElementProperties(array &$element) {
    if (empty($element['#default_value'])) {
      throw new \InvalidArgumentException('The commerce_profile_select element requires the #default_value property.');
    }
    elseif (!($element['#default_value'] instanceof ProfileInterface)) {
      throw new \InvalidArgumentException('The commerce_profile_select #default_value property must be a profile entity.');
    }
    if (!is_array($element['#available_countries'])) {
      throw new \InvalidArgumentException('The commerce_profile_select #available_countries property must be an array.');
    }
    // Make sure that the specified default country is available.
    if (!empty($element['#default_country']) && !empty($element['#available_countries'])) {
      if (!in_array($element['#default_country'], $element['#available_countries'])) {
        $element['#default_country'] = NULL;
      }
    }
  }

  /**
   * Builds the element form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed form element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function processElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    self::validateElementProperties($element);

    $element['#attached']['library'][] = 'commerce_order/profile_select';
    $element['#attributes']['class'][] = 'profile-select';

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    /** @var \Drupal\profile\Entity\ProfileInterface $default_profile */
    $default_profile = $element['#default_value'];

    // This is the latest revision if reports that is the default revision,
    // and the element allows editing the current revision through the
    // #profile_latest_revision flag.
    $default_value_is_latest_revision = $default_profile->isDefaultRevision() && $element['#profile_latest_revision'];
    $default_profile_label = $default_profile->label();
    $owner = $default_profile->getOwner();
    $profile_type = $default_profile->bundle();

    // If the owner is a registered user, load their other active profiles for
    // selection and reuse.
    $available_profiles = static::getAvailableProfiles($owner, $profile_type);
    // If the default value is a new profile, automatically select their
    // default profile.
    if ($default_profile->isNew()) {
      foreach ($available_profiles as $available_profile) {
        if ($available_profile->isDefault()) {
          $element['#default_value'] = $available_profile;
          $default_profile = $available_profile;
          break;
        }
      }
    }
    // Handle a form rebuild and grab the selected profile value.
    $selected_available_profile = $form_state->getValue(array_merge($element['#parents'], ['available_profiles']));
    if ($selected_available_profile) {
      if ($selected_available_profile == '_new') {
        $selected_available_profile = $profile_storage->create([
          'type' => $default_profile->bundle(),
          'uid' => $default_profile->getOwnerId(),
        ]);
      }
      // We are still going to use the existing profile, which is referenced at
      // a previous revision.
      elseif ($selected_available_profile == '_existing') {
        $selected_available_profile = $default_profile;
      }
      else {
        $selected_available_profile = $profile_storage->load($selected_available_profile);
      }
      $element['#default_value'] = $selected_available_profile;
      $default_profile = $selected_available_profile;
    }

    // Set #profile to keep BC.
    $element['#profile'] = $default_profile;

    $id_prefix = implode('-', $element['#parents']);
    $wrapper_id = Html::getId($id_prefix . '-ajax-wrapper');
    $element = [
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      // Pass the id along to other methods.
      '#wrapper_id' => $wrapper_id,
      '#element_mode' => $form_state->get('element_mode') ?: 'view',
    ] + $element;

    // If the profile is new, apply the `creating` class so that the form is
    // displayed automatically.
    if ($default_profile->isNew()) {
      $element['#attributes']['class'][] = 'creating';
    }

    $available_profiles_default_value = $default_profile->id() ?: '_new';
    $available_profiles_options = EntityHelper::extractLabels($available_profiles);

    if ($owner->hasPermission('create profile')) {
      $available_profiles_options += ['_new' => $element['#create_title']];
    }

    // If the original default value is not the default revision, ensure it
    // persists as an option to prevent unexpected changes in data.
    if (!$default_value_is_latest_revision) {
      $available_profiles_options = [
        '_existing' => t(':label (Original)', [':label' => $default_profile_label]),
      ] + $available_profiles_options;
      $available_profiles_default_value = '_existing';
    }

    $element['available_profiles'] = [
      '#type' => 'select',
      '#title' => $element['#title'],
      '#options' => $available_profiles_options,
      '#default_value' => $available_profiles_default_value,
      '#access' => !empty($available_profiles),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
      '#prefix' => '<div class="hidden-on-edit">',
      '#suffix' => '</div>',
      '#attributes' => [
        'class' => ['available-profiles'],
      ],
    ];

    $view_display = EntityViewDisplay::collectRenderDisplay($default_profile, 'default');
    $element['profile_view'] = $view_display->build($element['#default_value']);
    $element['profile_view']['#prefix'] = '<div class="hidden-on-edit">';
    $element['profile_view']['#suffix'] = '</div>';
    $element['profile_view']['#access'] = !$default_profile->isNew();
    $element['profile_view']['edit'] = [
      '#type' => 'button',
      '#value' => t('Edit'),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['edit-profile'],
      ],
      '#access' => $default_profile->isDefaultRevision() && $default_profile->access('update', $owner),
      // Ensure this edit button shows below any other fields.
      '#weight' => 100,
    ];

    $form_display = EntityFormDisplay::collectRenderDisplay($default_profile, 'default');
    $element['profile_form'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['visible-on-edit visible-on-create'],
      ],
      '#access' => $default_profile->isDefaultRevision(),
      '#parents' => $element['#parents'],
      'cancel' => [
        '#type' => 'button',
        '#value' => t('Cancel changes'),
        '#limit_validation_errors' => [],
        '#weight' => 100,
        '#attributes' => [
          'class' => [
            'cancel-edit-profile',
            'hidden-on-create',
          ],
        ],
      ],
    ];

    $form_display->buildForm($default_profile, $element['profile_form'], $form_state);

    // Adjust the address widget on the profile, if present.
    if (!empty($element['profile_form']['address']['widget'][0])) {
      $widget_element = &$element['profile_form']['address']['widget'][0];
      // Remove the details wrapper from the address widget.
      $widget_element['#type'] = 'container';
      // Provide a default country.
      if (!empty($element['#default_country']) && empty($widget_element['address']['#default_value']['country_code'])) {
        $widget_element['address']['#default_value']['country_code'] = $element['#default_country'];
      }
      // Limit the available countries.
      if (!empty($element['#available_countries'])) {
        $widget_element['address']['#available_countries'] = $element['#available_countries'];
      }
    }
    return $element;
  }

  /**
   * Validates the element form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Exception
   *   Thrown if button-level #validate handlers are detected on the parent
   *   form, as a protection against buggy behavior.
   */
  public static function validateForm(array &$element, FormStateInterface $form_state) {
    $selected_available_profile = self::getSelectedAvailableProfile($element, $form_state);
    $form_display = EntityFormDisplay::collectRenderDisplay($element['#default_value'], 'default');
    $form_display->extractFormValues($selected_available_profile, $element, $form_state);
    $form_display->validateFormValues($selected_available_profile, $element, $form_state);

    // Set the profile as a value in the `profile` key of the form state.
    $element_clone = $element;
    $element_clone['#parents'][] = 'profile';
    $form_state->setValueForElement($element_clone, $selected_available_profile);
  }

  /**
   * Submits the element form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function submitForm(array &$element, FormStateInterface $form_state) {
    $selected_available_profile = self::getSelectedAvailableProfile($element, $form_state);
    $form_display = EntityFormDisplay::collectRenderDisplay($selected_available_profile, 'default');
    $form_display->extractFormValues($selected_available_profile, $element, $form_state);

    // If the profile was modified, enforce a new revision.
    // When the _existing option is chosen, there will be no changes reported
    // preventing an accidental flag for the revision.
    if ($selected_available_profile->hasTranslationChanges()) {
      // If this is an old revision, we want to save directly to it, and not
      // a new revision. But if it is the latest revision, we want to ensure
      // that changes don't affect references to it.
      if ($selected_available_profile->isLatestRevision()) {
        $selected_available_profile->setNewRevision(TRUE);
      }
      $selected_available_profile->save();
    }

    // Set the profile as a value in the `profile` key of the form state.
    $element_clone = $element;
    $element_clone['#parents'][] = 'profile';
    $form_state->setValueForElement($element_clone, $selected_available_profile);
    $element['#profile'] = $selected_available_profile;
  }

  /**
   * Gets the available profiles for the user that can be selected.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param string $profile_type
   *   The profile type.
   *
   * @return array|\Drupal\profile\Entity\ProfileInterface[]
   *   An array of profiles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getAvailableProfiles(AccountInterface $account, $profile_type) {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $available_profiles = [];
    if ($account->isAuthenticated()) {
      $available_profiles = $profile_storage->loadMultipleByUser($account, $profile_type, TRUE);
    }
    return $available_profiles;
  }

  /**
   * Gets the selected available profile.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The selected profile.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected static function getSelectedAvailableProfile(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
    $selected_available_profile = $form_state->getValue(array_merge($element['#parents'], ['available_profiles']));
    if ($selected_available_profile == '_new') {
      return $profile_storage->create([
        'type' => $element['#default_value']->bundle(),
        'uid' => $element['#default_value']->getOwnerId(),
      ]);
    }
    // We are still going to use the existing profile, which is referenced at
    // a previous revision.
    elseif ($selected_available_profile == '_existing') {
      return $element['#default_value'];
    }
    else {
      return $profile_storage->load($selected_available_profile);
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
    return $element;
  }

  /**
   * Clears dependent form values when the profile changes.
   *
   * Clears all input, so that the default values for a new profile form will
   * be used, instead of the last input.
   */
  public static function clearValues(array $element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if (!$triggering_element) {
      return $element;
    }
    if (end($triggering_element['#array_parents']) != 'available_profiles') {
      return $element;
    }

    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $input = &$form_state->getUserInput();

    if (NestedArray::keyExists($input, $triggering_element_parents)) {
      // Remove any input for profile fields.
      array_walk(NestedArray::getValue($input, $triggering_element_parents), function (&$item, $key) {
        $item = ($key == 'available_profiles') ? $item : NULL;
      });
    }

    return $element;
  }

}
