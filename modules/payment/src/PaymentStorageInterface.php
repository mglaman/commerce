<?php

namespace Drupal\commerce_payment;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines the interface for payment storage.
 */
interface PaymentStorageInterface extends ContentEntityStorageInterface {

  /**
   * Constructs a new payment entity for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $values
   *   (optional) An array of values to set, keyed by property name. If the
   *   entity type has bundles, the bundle key has to be specified.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity object.
   */
  public function createForOrder(OrderInterface $order, array $values = []);

  /**
   * Loads the payment for the given remote ID.
   *
   * @param string $remote_id
   *   The remote ID.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface|null
   *   The payment, or NULL if none found.
   */
  public function loadByRemoteId($remote_id);

  /**
   * Loads all payments for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface[]
   *   The payments.
   */
  public function loadMultipleByOrder(OrderInterface $order);

}
