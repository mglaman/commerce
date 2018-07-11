<?php

namespace Drupal\commerce_checkout\CheckoutGuard;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Defines the interface for checkout guards.
 *
 * By default, an order can go through checkout unless at least one guard
 * returns FALSE.
 */
interface CheckoutGuardInterface {

  const PHASE_START = 'start';

  const PHASE_END = 'end';

  /**
   * Determines if an order is allowed to proceed in checkout.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The checkout flow.
   * @param string $phase
   *   The phase.
   *
   * @return bool
   *   Returns TRUE if allowed, FALSE otherwise.
   */
  public function allowed(OrderInterface $order, CheckoutFlowInterface $checkout_flow, $phase);

}
