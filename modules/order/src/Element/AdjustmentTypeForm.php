<?php

namespace Drupal\commerce_order\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;


/**
 * Provides a form element for embedding the payment gateway forms.
 *
 * Usage example:
 * @code
 * $form['adjustments'] = [
 *   '#type' => 'commerce_order_adjustment_type_form',
 *   '#operation' => 'default',
 *   '#adjustment_type' => 'custom'
 *   // An adjustment object or NULL.
 *   '#default_value' => $adjustment,
 * ];
 * @endcode
 *
 * @RenderElement("commerce_order_adjustment_type_form")
 */
class AdjustmentTypeForm extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#operation' => 'default',
      '#default_value' => NULL,
      '#adjustment_type' => NULL,
      '#process' => [
        [$class, 'processForm'],
      ],
      '#element_validate' => [
        [$class, 'validateForm'],
      ],
      '#element_submit' => [
        [$class, 'submitForm'],
      ],
      '#theme_wrappers' => ['container'],
    ];
  }

  /**
   * Builds the payment gateway form.
   *
   * @param array $element
   *   The form element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the #operation or #default_value properties are empty, or
   *   when the #default_value property is not a valid entity.
   *
   * @return array
   *   The processed form element.
   */
  public static function processForm($element, FormStateInterface $form_state, &$complete_form) {
    $plugin_form = static::createPluginForm($element);
    $element = $plugin_form->buildConfigurationForm($element, $form_state);
    return $element;
  }

  /**
   * Validates the payment gateway form.
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
  public static function validateForm(&$element, FormStateInterface $form_state) {
    $plugin_form = self::createPluginForm($element);
    $plugin_form->validateConfigurationForm($element, $form_state);
  }

  /**
   * Submits the payment gateway form.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function submitForm(&$element, FormStateInterface $form_state) {
    $plugin_form = self::createPluginForm($element);

    try {
      $plugin_form->submitConfigurationForm($element, $form_state);
      $form_state->setValueForElement($element, $plugin_form->getAdjustment());
    }
    catch (\Exception $e) {
      $form_state->setError($element, $e->getMessage());
    }
  }

  /**
   * Creates an instance of the plugin form.
   *
   * @param array $element
   *   The form element.
   *
   * @return \Drupal\commerce_order\PluginForm\AdjustmentTypeFormInterface
   *   The plugin form.
   */
  public static function createPluginForm($element) {
    /** @var \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_factory */
    $plugin_form_factory = \Drupal::service('plugin_form.factory');
    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    $adjustment = $element['#default_value'];
    if ($adjustment) {
      $adjustment_type = $adjustment->getType();
    }
    else {
      $adjustment_type = $element['#adjustment_type'];
    }
    $plugin = \Drupal::service('plugin.manager.commerce_adjustment_type')->createInstance($adjustment_type);
    /** @var \Drupal\commerce_order\PluginForm\AdjustmentTypeFormInterface $plugin_form */
    $plugin_form = $plugin_form_factory->createInstance($plugin, $element['#operation']);
    $plugin_form->setAdjustment($adjustment);
    return $plugin_form;
  }

}
