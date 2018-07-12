<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The default checkout guard.
 *
 * This is the default implementation for checkout guards and always allows
 * an order to proceed through checkout.
 */
class DefaultCheckoutValidator implements CheckoutValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, AccountInterface $account, $phase = self::PHASE_ENTER) {
    $list = new CheckoutValidatorConstraintList();

    if ($order->getState()->value == 'canceled') {
      $list->add(new CheckoutValidatorConstraint(t('The order is cancelled.')));
    }
    if (!$order->hasItems()) {
      $list->add(new CheckoutValidatorConstraint(t('The order has no items.')));
    }
    if ($account->isAuthenticated() && ($account->id() != $order->getCustomerId())) {
      $list->add(new CheckoutValidatorConstraint(t('The order does not belong to this account.')));
    }
    if (!$account->hasPermission('access checkout')) {
      $list->add(new CheckoutValidatorConstraint('The account does not have access to checkout.'));
    }

    return $list;
  }

}
