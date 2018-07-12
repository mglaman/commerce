<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

/**
 * Defines the interface for checkout guard factories.
 */
interface ChainCheckoutValidatorInterface extends CheckoutValidatorInterface {

  /**
   * Adds a checkout guard.
   *
   * @param \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface $validator
   *   The checkout guard.
   */
  public function add(CheckoutValidatorInterface $validator);

}
