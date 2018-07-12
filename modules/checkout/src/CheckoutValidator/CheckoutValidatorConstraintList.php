<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

/**
 * Contains a list of constraints.
 */
final class CheckoutValidatorConstraintList implements \IteratorAggregate, \Countable {

  /**
   * The constraints.
   *
   * @var \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraint[]
   */
  private $constraints = [];

  /**
   * Constructs a new CheckoutValidatorConstraintList object.
   *
   * @param array $constraints
   *   The constraints.
   */
  public function __construct(array $constraints = []) {
    foreach ($constraints as $constraint) {
      $this->add($constraint);
    }
  }

  /**
   * Adds a constraint.
   *
   * @param \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraint $constraint
   *   The constraint.
   */
  public function add(CheckoutValidatorConstraint $constraint) {
    $this->constraints[] = $constraint;
  }

  /**
   * Adds all constraints from another list.
   *
   * @param self $list
   *   The list.
   */
  public function addAll(self $list) {
    foreach ($list as $constraint) {
      $this->add($constraint);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->constraints);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->constraints);
  }

}
