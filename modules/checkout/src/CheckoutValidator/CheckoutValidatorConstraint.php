<?php

namespace Drupal\commerce_checkout\CheckoutValidator;

/**
 * The validator constraint.
 */
final class CheckoutValidatorConstraint {

  /**
   * The message.
   *
   * @var string
   */
  private $message;

  /**
   * Constructs a new CheckoutValidatorConstraint object.
   *
   * @param string $message
   *   The message.
   */
  public function __construct($message) {
    $this->message = $message;
  }

  /**
   * Gets the message.
   *
   * @return string
   *   The message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Returns the message.
   *
   * @return string
   *   The message.
   */
  public function __toString() {
    return (string) $this->getMessage();
  }

}
