<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Default implementation of the chained checkout guard.
 */
class ChainCheckoutValidator implements ChainCheckoutValidatorInterface {

  /**
   * Array of checkout guards.
   *
   * @var \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface[]
   */
  protected $guards = [];

  /**
   * {@inheritdoc}
   */
  public function add(CheckoutValidatorInterface $guard) {
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
