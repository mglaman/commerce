<?php

namespace Drupal\commerce_checkout_test\CheckoutValidator;

use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraint;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Session\AccountInterface;

/**
 * Checkout validator used for testing.
 */
class TestValidator implements CheckoutValidatorInterface {

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, AccountInterface $account, $phase = self::PHASE_ENTER) {
    $list = new CheckoutValidatorConstraintList();
    if ($phase == self::PHASE_ENTER) {
      foreach ($order->getItems() as $item) {
        if ($item->getQuantity() > 10) {
          $list->add(new CheckoutValidatorConstraint(t('You cannot buy more than ten items.')));
        }
      }
    }
    elseif ($phase == self::PHASE_END) {
      if ($order->getTotalPrice()->greaterThan(new Price('100.00', $order->getTotalPrice()->getCurrencyCode()))) {
        $list->add(new CheckoutValidatorConstraint('Your order total is too high, we cannot charge orders over $100.'));
      }
    }
    return $list;
  }

}
