<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * The default checkout guard.
 *
 * This is the default implementation for checkout guards and always allows
 * an order to proceed through checkout.
 *
 * @todo we probably don't even need this.
 */
class DefaultCheckoutValidator implements CheckoutValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, $phase = self::PHASE_ENTER) {
    return new CheckoutValidatorConstraintList();
  }

}
