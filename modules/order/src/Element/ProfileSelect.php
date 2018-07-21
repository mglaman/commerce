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
use Drupal\profile\Entity\ProfileInterface;

/**
 * Provides a form element for selecting a customer profile.
 *
 * Usage example:
 * @code
 * $form['billing_profile'] = [
 *   '#type' => 'commerce_profile_select',
 *   '#default_value' => $profile,
 *   '#default_country' => 'FR',
 *   '#available_countries' => ['US', 'FR'],
 * ];
 * @endcode
 * To access the profile in validation or submission callbacks, use
 * $form['billing_profile']['#profile']. Due to Drupal core limitations the
 * profile can't be accessed via $form_state->getValue('billing_profile').
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
      // The country to select if the address widget doesn't have a default.
      '#default_country' => NULL,
      // A list of country codes. If empty, all countries will be available.
      '#available_countries' => [],

      // The profile entity operated on. Required.
      '#default_value' => NULL,
      '#process' => [
        [$class, 'attachElementSubmit'],
        [$class, 'processForm'],
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
   * Builds the element form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when #default_value is empty or not an entity, or when
   *   #available_countries is not an array of country codes.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm(array $element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#default_value'])) {
      throw new \InvalidArgumentException('The commerce_profile_select element requires the #default_value property.');
    }
    elseif (isset($element['#default_value']) && !($element['#default_value'] instanceof ProfileInterface)) {
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
    $element['#attached']['library'][] = 'commerce_order/profile_select';
    $element['#attributes']['class'][] = 'profile-select';

    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');

    $profile = $element['#default_value'];

    $available_profiles = [];
    if (!$profile->getOwner()->isAnonymous()) {
      $available_profiles = $profile_storage->loadMultipleByUser($profile->getOwner(), $profile->bundle(), TRUE);
    }
    if ($profile->isNew()) {
      foreach ($available_profiles as $available_profile) {
        if ($available_profile->isDefault()) {
          $element['#default_value'] = $available_profile;
          $profile = $available_profile;
          break;
        }
      }
    }

    $selected_available_profile = $form_state->getValue(array_merge($element['#parents'], ['available_profiles']));
    if ($selected_available_profile) {
      if ($selected_available_profile == '_new') {
        $selected_available_profile = $profile_storage->create([
          'type' => $profile->bundle(),
          'uid' => $profile->getOwnerId(),
        ]);
      }
      else {
        $selected_available_profile = $profile_storage->load($selected_available_profile);
      }
      $element['#default_value'] = $selected_available_profile;
      $profile = $selected_available_profile;
    }

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

    $element['available_profiles'] = [
      '#type' => 'select',
      '#title' => t('Address'),
      '#options' => EntityHelper::extractLabels($available_profiles) + ['_new' => t('+ New billing address')],
      '#default_value' => $profile->id() ?: '_new',
      '#access' => !empty($available_profiles),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];

    $view_display = EntityViewDisplay::collectRenderDisplay($element['#default_value'], 'default');
    $element['profile_view'] = $view_display->build($element['#default_value']);
    $element['profile_view']['#prefix'] = '<div class="profile-view-wrapper">';
    $element['profile_view']['#suffix'] = '</div>';
    $element['profile_view']['#access'] = !$profile->isNew();
    $element['profile_view']['edit'] = [
      '#type' => 'button',
      '#value' => t('Edit address'),
      '#name' => 'edit_profile',
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['edit-profile'],
      ],
    ];

    $form_display = EntityFormDisplay::collectRenderDisplay($element['#default_value'], 'default');
    $element['profile_form'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['profile-form-wrapper'],
      ],
      '#parents' => $element['#parents'],
    ];
    if ($profile->isNew()) {
      $element['profile_form']['#attributes']['class'][] = 'editing';
    }

    $form_display->buildForm($element['#default_value'], $element['profile_form'], $form_state);
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
    $form_display = EntityFormDisplay::collectRenderDisplay($element['#default_value'], 'default');
    $form_display->extractFormValues($element['#default_value'], $element, $form_state);
    $form_display->validateFormValues($element['#default_value'], $element, $form_state);
  }

  /**
   * Submits the element form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitForm(array &$element, FormStateInterface $form_state) {
    $form_display = EntityFormDisplay::collectRenderDisplay($element['#default_value'], 'default');
    $form_display->extractFormValues($element['#default_value'], $element, $form_state);
    $element['#default_value']->save();
    // @todo use setValueForElement().
    // $form_state->setValueForElement($element, $element['#default_value']);
    $element['#profile'] = $element['#default_value'];
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
   * Clears dependent form values when the country or subdivision changes.
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
