<?php

namespace Drupal\commerce_checkout\Event;

/**
 * Defines events for the Commerce checkout module.
 */
final class CheckoutEvents {

  /**
   * Name of the event fired when an account gets created during checkout.
   *
   * @Event
   *
   * @see \Drupal\commerce_checkout\Event\CheckoutAccountCreateEvent
   */
  const ACCOUNT_CREATE = 'commerce_checkout.account_create';

}
