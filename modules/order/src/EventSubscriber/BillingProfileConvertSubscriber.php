<?php

namespace Drupal\commerce_order\EventSubscriber;

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
    return ['commerce_order.place.post_transition' => ['convertProfiles', 100]];
  }

  /**
   * Converts the order's billing profile.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The event.
   */
  public function convertProfiles(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $billing_profile = $order->getBillingProfile();

    // @todo when Profile has data field + methods, use those.
    if (!$billing_profile || $billing_profile->get('data')->isEmpty()) {
      return;
    }

    $data = $billing_profile->get('data')->first()->getValue();
    $add_to_addressbook = isset($data['add_to_addressbook']);
    if ($add_to_addressbook) {
      // @todo inject storage.
      $addressbook_profile = Profile::create([
        'type' => 'customer',
        'uid' => $order->getCustomerId(),
      ]);

      // Copy any non-basefield values to addressbook entity.
      $selected_profile_values = $billing_profile->toArray();
      foreach ($billing_profile->getFieldDefinitions() as $field_name => $definition) {
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
    }
  }

}
