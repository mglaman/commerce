<?php

namespace Drupal\commerce_cart\CheckoutValidator;

use Drupal\commerce_cart\CartSession;
use Drupal\commerce_cart\CartSessionInterface;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraint;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Cart ownership checkout validator.
 */
class CartCheckoutValidator implements CheckoutValidatorInterface {

  /**
   * The cart session.
   *
   * @var \Drupal\commerce_cart\CartSessionInterface
   */
  protected $cartSession;

  /**
   * Constructs a new CartCheckoutValidator object.
   *
   * @param \Drupal\commerce_cart\CartSessionInterface $cart_session
   *   The cart session.
   */
  public function __construct(CartSessionInterface $cart_session) {
    $this->cartSession = $cart_session;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, AccountInterface $account, $phase = self::PHASE_ENTER) {
    $list = new CheckoutValidatorConstraintList();

    if ($account->isAnonymous()) {
      $active_cart = $this->cartSession->hasCartId($order->id(), CartSession::ACTIVE);
      $completed_cart = $this->cartSession->hasCartId($order->id(), CartSession::COMPLETED);
      $customer_check = $active_cart || $completed_cart;

      if (!$customer_check) {
        $list->add(new CheckoutValidatorConstraint(t('The order does not belong to this account.')));
      }
    }

    return $list;
  }

}
