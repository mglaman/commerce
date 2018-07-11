<?php

namespace Drupal\commerce_checkout\CheckoutGuard;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * The default checkout guard.
 *
 * This is the default implementation for checkout guards and always allows
 * an order to proceed through checkout.
 */
class DefaultCheckoutGuard implements CheckoutGuardInterface {

  /**
   * {@inheritdoc}
   */
  public function allowed(OrderInterface $order, CheckoutFlowInterface $checkout_flow, $phase) {
    return TRUE;
  }

}
