<?php

namespace Drupal\commerce_order\PluginForm;

use Drupal\commerce_order\Adjustment;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class AdjustmentTypeDefaultForm extends PluginFormBase implements AdjustmentTypeFormInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\commerce_order\Adjustment
   */
  protected $adjustment;

  public function setAdjustment(Adjustment $adjustment) {
    $this->adjustment = $adjustment;
  }

  public function getAdjustment() {
    return $this->adjustment;
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'commerce_price/admin';
    $form['type'] = [
      '#type' => 'value',
      '#value' => $this->adjustment->getType(),
    ];
    $form['source_id'] = [
      '#type' => 'value',
      '#value' => $this->adjustment->getSourceId(),
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->adjustment->getLabel(),
    ];
    $form['amount'] = [
      '#type' => 'commerce_price',
      '#title' => t('Amount'),
      '#default_value' => $this->adjustment->getAmount(),
      '#required' => TRUE,
    ];
    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->setAdjustment(new Adjustment($values));
  }

}
