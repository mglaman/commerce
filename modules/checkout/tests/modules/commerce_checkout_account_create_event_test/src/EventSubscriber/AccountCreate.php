<?php

namespace Drupal\commerce_checkout_account_create_event_test\EventSubscriber;

use Drupal\commerce_checkout\Event\CheckoutAccountCreateEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for acting upon account creation during checkout.
 */
class AccountCreate implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CheckoutEvents::ACCOUNT_CREATE][] = 'checkoutComplete';
    return $events;
  }

  /**
   * Reacts on accounts being created.
   *
   * @param \Drupal\commerce_checkout\Event\CheckoutAccountCreateEvent $event
   *   The account create event.
   */
  public function checkoutComplete(CheckoutAccountCreateEvent $event) {
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $event->getAccount();

    // Set a redirect to the user edit page.
    $event->setRedirect('entity.user.edit_form', [
      'user' => $account->id(),
    ]);
  }

}
