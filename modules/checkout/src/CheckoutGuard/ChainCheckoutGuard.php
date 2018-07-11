<?php

namespace Drupal\commerce_checkout\CheckoutGuard;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Default implementation of the chained checkout guard.
 */
class ChainCheckoutGuard implements ChainCheckoutGuardInterface {

  /**
   * Array of checkout guards.
   *
   * @var \Drupal\commerce_checkout\CheckoutGuard\CheckoutGuardInterface[]
   */
  protected $guards = [];

  /**
   * {@inheritdoc}
   */
  public function add(CheckoutGuardInterface $guard) {
    $this->guards[] = $guard;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(OrderInterface $order, CheckoutFlowInterface $checkout_flow, $phase) {
    foreach ($this->guards as $guard) {
      $result = $guard->allowed($order, $checkout_flow, $phase);
      if ($result !== NULL) {
        return $result;
      }
    }
  }

}
