<?php

namespace Drupal\commerce_checkout\CheckoutGuard;

/**
 * Defines the interface for checkout guard factories.
 */
interface ChainCheckoutGuardInterface extends CheckoutGuardInterface {

  /**
   * Adds a checkout guard.
   *
   * @param \Drupal\commerce_checkout\CheckoutGuard\CheckoutGuardInterface $guard
   *   The checkout guard.
   */
  public function add(CheckoutGuardInterface $guard);

}
