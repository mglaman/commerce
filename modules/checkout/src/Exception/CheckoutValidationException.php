<?php

namespace Drupal\commerce_checkout\Exception;

use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList;
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
   * The constraint list.
   *
   * @var \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList
   */
  protected $constraintList;

  /**
   * {@inheritdoc}
   */
  public function __construct(OrderInterface $order, CheckoutValidatorConstraintList $constraint_list, $message = "", $code = 0, \Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
    $this->order = $order;
    $this->constraintList = $constraint_list;
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
   * Gets the constraint list.
   *
   * @return \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList
   *   The constraint list.
   */
  public function getConstraintList() {
    return $this->constraintList;
  }

}
