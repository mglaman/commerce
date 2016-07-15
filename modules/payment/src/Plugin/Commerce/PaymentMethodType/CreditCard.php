<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\commerce_payment\CreditCard as CreditCardHelper;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;

/**
 * Provides the credit card payment method type.
 *
 * @CommercePaymentMethodType(
 *   id = "credit_card",
 *   label = @Translation("Credit card"),
 *   create_label = @Translation("New credit card"),
 * )
 */
class CreditCard extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    $card_type = CreditCardHelper::getType($payment_method->card_type->value);
    $args = [
      '@card_type' => $card_type->getLabel(),
      '@card_number' => $payment_method->card_number->value,
    ];
    return $this->t('@card_type ending in @card_number', $args);
  }

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = parent::buildFieldDefinitions();

    $fields['card_type'] = BundleFieldDefinition::create('list_string')
      ->setLabel(t('Card type'))
      ->setDescription(t('The credit card type.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values_function', ['\Drupal\commerce_payment\CreditCard', 'getTypeLabels'])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['card_number'] = BundleFieldDefinition::create('string')
      ->setLabel(t('Card number'))
      ->setDescription(t('The last 4 digits of the credit card number'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['card_exp_month'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Card expiration month'))
      ->setDescription(t('The credit card expiration month.'))
      ->setRequired(TRUE)
      ->setSetting('size', 'tiny')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ]);

    $fields['card_exp_year'] = BundleFieldDefinition::create('integer')
      ->setLabel(t('Card expiration year'))
      ->setDescription(t('The credit card expiration year.'))
      ->setRequired(TRUE)
      ->setSetting('size', 'tiny')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ]);

    return $fields;
  }

}
