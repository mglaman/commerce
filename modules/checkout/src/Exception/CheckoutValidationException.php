<?php

namespace Drupal\commerce_checkout\Exception;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Exception thrown when an order fails checkout flow validation.
 */
class CheckoutValidationException extends \Exception {

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The checkout flow.
   *
   * @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface
   */
  protected $checkoutFlow;

  /**
   * {@inheritdoc}
   */
  public function __construct(OrderInterface $order, CheckoutFlowInterface $checkout_flow, $message = "", $code = 0, \Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->order = $order;
    $this->checkoutFlow = $checkout_flow;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Gets the checkout flow.
   *
   * @return \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface
   *   The checkout flow.
   */
  public function getCheckoutFlow() {
    return $this->checkoutFlow;
  }

}
