<?php

namespace Drupal\commerce_payment_test\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\Core\State\StateInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPlaceTestSubscriber implements EventSubscriberInterface {

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
      'commerce_order.place.post_transition' => 'onPost',
      'commerce_order.place.pre_transition' => 'onPre',
    ];
  }

  public function onPost(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $state_key = 'order_place_test_post_transition_' . $order->id();
    $existing_count = $this->state->get($state_key, 0);
    $existing_count++;
    $this->state->set($state_key, $existing_count);
  }

  public function onPre(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $state_key = 'order_place_test_pre_transition_' . $order->id();
    $existing_count = $this->state->get($state_key, 0);
    $existing_count++;
    $this->state->set($state_key, $existing_count);
  }

}
