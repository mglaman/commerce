<?php

namespace Drupal\commerce_payment_test\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPaidTestSubscriber implements EventSubscriberInterface {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new OrderPaidTestSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.order.paid' => 'onPaid',
    ];
  }

  /**
   * Tracks the number of times this event was triggered per order.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   The event.
   */
  public function onPaid(OrderEvent $event) {
    $order = $event->getOrder();
    $this->state->set('order_paid_test_subscriber_ran', true);

    if ($order->getState()->getId() != 'draft') {
      // The order has already been placed.
      return;
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $order->get('payment_gateway')->entity;
    if (!$payment_gateway) {
      // The payment gateway is unknown.
      return;
    }

    $state_key = 'order_paid_test_subscriber_' . $order->id();
    $existing_count = $this->state->get($state_key, 0);
    $existing_count++;
    $this->state->set($state_key, $existing_count);
  }

}
