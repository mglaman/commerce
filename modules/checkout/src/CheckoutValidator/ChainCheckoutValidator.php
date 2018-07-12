<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

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
  protected $validators = [];

  /**
   * {@inheritdoc}
   */
  public function add(CheckoutValidatorInterface $validator) {
    $this->validators[] = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, $phase = self::PHASE_ENTER) {
    $constraints = new CheckoutValidatorConstraintList();
    foreach ($this->validators as $validator) {
      $constraints->addAll($validator->validate($order, $phase));
    }
    return $constraints;
  }

}
