<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the payment entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_payment",
 *   label = @Translation("Payment"),
 *   label_singular = @Translation("Payment"),
 *   label_plural = @Translation("Payments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment",
 *     plural = "@count payments",
 *   ),
 *   bundle_label = @Translation("Payment type"),
 *   bundle_plugin_type = "commerce_payment_type",
 *   handlers = {
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "commerce_payment",
 *   admin_permission = "administer payments",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "payment_id",
 *     "bundle" = "payment_gateway",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Payment extends ContentEntityBase implements PaymentInterface {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    $payment_type_manager = \Drupal::service('plugin.manager.commerce_payment_type');
    return $payment_type_manager->createInstance($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGateway() {
    return $this->get('payment_gateway')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentGatewayId() {
    return $this->get('payment_gateway')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethod() {
    return $this->get('payment_method')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodId() {
    return $this->get('payment_method')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrder() {
    return $this->get('order_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderId() {
    return $this->get('order_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteId() {
    return $this->get('remote_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteId($remote_id) {
    $this->set('remote_id', $remote_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->get('amount')->first()->toPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function setAmount(Price $amount) {
    $this->set('amount', $amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefundedAmount() {
    return $this->get('refunded_amount')->first()->toPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function setRefundedAmount(Price $refunded_amount) {
    $this->set('refunded_amount', $refunded_amount);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->first();
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizedTime() {
    return $this->get('authorized')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorizedTime($timestamp) {
    $this->set('authorized', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationExpiresTime() {
    return $this->get('authorization_expires')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorizationExpiresTime($timestamp) {
    $this->set('authorization_expires', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCapturedTime() {
    return $this->get('captured')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCapturedTime($timestamp) {
    $this->set('captured', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['payment_method'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Payment method'))
      ->setDescription(t('The payment method.'))
      ->setSetting('target_type', 'commerce_payment_method')
      ->setReadOnly(TRUE);

    // @todo Order should modify and add this?
//    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
//      ->setLabel(t('Order'))
//      ->setDescription(t('The parent order.'))
//      ->setSetting('target_type', 'commerce_order')
//      ->setReadOnly(TRUE);

    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote ID'))
      ->setDescription(t('The remote payment ID.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Amount'))
      ->setDescription(t('The payment amount.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['refunded_amount'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Refunded amount'))
      ->setDescription(t('The refunded payment amount.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The payment state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', ['\Drupal\commerce_payment\Entity\Payment', 'getWorkflowId']);

    $fields['authorized'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Authorized'))
      ->setDescription(t('The time when the payment was authorized.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['authorization_expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Authorization expires'))
      ->setDescription(t('The time when the payment authorization expires.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['captured'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Captured'))
      ->setDescription(t('The time when the payment was captured.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Gets the workflow ID for the state field.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @return string
   *   The workflow ID.
   */
  public static function getWorkflowId(PaymentInterface $payment) {
    return $payment->getType()->getWorkflowId();
  }

}
