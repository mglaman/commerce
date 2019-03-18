<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Event\OrderAssignEvent;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\profile\Entity\Profile;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Converts flagged billing profiles to the user's addressbook.
 */
class BillingProfileConvertSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => ['convertProfilesOnPlacedOrder', 100],
      'commerce_order.order.assign' => ['convertProfilesOnOrderAssignment', 100],
    ];
  }

  /**
   * Converts the order's billing profile.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function convertProfilesOnPlacedOrder(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    if ($this->shouldConvertProfile($order)) {
      $this->convertProfile($order);
    }
  }

  /**
   * Converts the order's billing profile.
   *
   * @param \Drupal\commerce_order\Event\OrderAssignEvent $event
   *   The event.
   */
  public function convertProfilesOnOrderAssignment(OrderAssignEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getOrder();
    if ($this->shouldConvertProfile($order)) {
      $this->convertProfile($order);
    }
  }

  /**
   * Checks if the order's billing profile should be converted.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   Returns TRUE if the profile should be converted, FALSE otherwise.
   */
  protected function shouldConvertProfile(OrderInterface $order) {
    // Skip anonymous users.
    if ($order->getCustomerId() === 0) {
      return FALSE;
    }
    $billing_profile = $order->getBillingProfile();
    // @todo when Profile has data field + methods, use those.
    if (!$billing_profile || $billing_profile->get('data')->isEmpty()) {
      return FALSE;
    }
    $data = $billing_profile->get('data')->first()->getValue();
    return !empty($data['add_to_addressbook']);
  }

  /**
   * Converts the order's billing profile.
   *
   * @todo refactor params to be source profile and target user.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\profile\Entity\Profile
   *   The converted profile.
   */
  protected function convertProfile(OrderInterface $order) {
    $profile_to_copy = $order->getBillingProfile();
    // @todo inject storage.
    $addressbook_profile = Profile::create([
      'type' => 'customer',
      'uid' => $order->getCustomerId(),
    ]);

    // Copy any non-basefield values to addressbook entity.
    $selected_profile_values = $profile_to_copy->toArray();
    foreach ($profile_to_copy->getFieldDefinitions() as $field_name => $definition) {
      if ($definition instanceof BaseFieldDefinition) {
        unset($selected_profile_values[$field_name]);
      }
    }
    foreach ($selected_profile_values as $field_name => $value) {
      if ($addressbook_profile->hasField($field_name)) {
        $addressbook_profile->set($field_name, $value);
      }
    }

    // Save the profile, exposing it in their addresbook.
    $addressbook_profile->save();
    return $addressbook_profile;
  }

}
