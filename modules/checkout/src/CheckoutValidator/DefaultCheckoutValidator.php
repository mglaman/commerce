<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The default checkout validator.
 *
 * This default checkout validator requires an order to have orders items in
 * order to enter checkout.
 */
class DefaultCheckoutValidator implements CheckoutValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, AccountInterface $account, $phase = self::PHASE_ENTER) {
    $list = new CheckoutValidatorConstraintList();

    if (!$order->hasItems()) {
      $list->add(new CheckoutValidatorConstraint(t('The order has no items.')));
    }

    return $list;
  }

}
