<?php

namespace Drupal\commerce_order\Plugin\Field\FieldWidget;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\AdjustmentTypeManager;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of 'commerce_adjustment_default'.
 *
 * @FieldWidget(
 *   id = "commerce_adjustment_default",
 *   label = @Translation("Adjustment"),
 *   field_types = {
 *     "commerce_adjustment"
 *   }
 * )
 */
class AdjustmentDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The adjustment type manager.
   *
   * @var \Drupal\commerce_order\AdjustmentTypeManager
   */
  protected $adjustmentTypeManager;

  /**
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormManager;

  /**
   * Constructs a new AdjustmentDefaultWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\commerce_order\AdjustmentTypeManager $adjustment_type_manager
   *   The adjustment type manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AdjustmentTypeManager $adjustment_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->adjustmentTypeManager = $adjustment_type_manager;
    $this->pluginFormManager = \Drupal::service('plugin_form.factory');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.commerce_adjustment_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    /** @var \Drupal\commerce_order\Adjustment $adjustment */
    $adjustment = $items[$delta]->value;

    if (!$adjustment) {
      if (!empty($user_input)) {
        $adjustment_type = $user_input[$this->fieldDefinition->getName()][$delta]['adjustment']['type'];
      }
      else {
        $adjustment_type = '_none';
      }
    }
    else {
      $adjustment_type = $adjustment->getType();
    }

    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    // Prefix and suffix used for Ajax replacement.
    $element['adjustment'] = $element + [
      '#type' => 'container',
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#tree' => TRUE,
    ];

    $element['adjustment']['type'] = $element +[
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => [
        '_none' => $this->t('- Select -'),
      ],
      '#default_value' => $adjustment_type,
      '#ajax' => [
        'callback' => [get_called_class(), 'pluginFormAjax'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];

    foreach ($this->adjustmentTypeManager->getDefinitions() as $definition) {
      $element['adjustment']['type']['#options'][$definition['id']] = $definition['label'];
    }

    // This is never not _none.
    if ($adjustment_type != '_none') {
      // @todo this fails because the adjustment is null.
      $element['adjustment']['definition'] = $element +[
        '#type' => 'commerce_order_adjustment_type_form',
        '#default_value' => $adjustment,
      ];
    }

    return $element;
  }

  /**
   * Ajax callback.
   */
  public static function pluginFormAjax(&$form, FormStateInterface &$form_state, Request $request) {
    // Retrieve the element to be rendered.
    $triggering_element = $form_state->getTriggeringElement();
    array_pop($triggering_element['#array_parents']);
    $form_element = NestedArray::getValue($form, $triggering_element['#array_parents']);
    return $form_element;
  }

  /**
   * {@inheritdoc}
   *
   * @todo the problem is here. We're massaging the values but on rebuild its empty
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if ($value['adjustment']['type'] == '_none' || !isset($value['adjustment']['definition'])) {
        continue;
      }
      $stop = null;
      $values[$key] = new Adjustment([
        'type' => $value['adjustment']['definition']['type'],
        'label' => $value['adjustment']['definition']['label'],
        'amount' => new Price($value['adjustment']['definition']['amount']['number'], $value['adjustment']['definition']['amount']['currency_code']),
        'source_id' => $value['adjustment']['definition']['source_id'],
      ]);
    }
    return $values;
  }

}
