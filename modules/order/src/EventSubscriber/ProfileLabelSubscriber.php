<?php

namespace Drupal\commerce_order\EventSubscriber;

use Drupal\profile\Entity\ProfileType;
use Drupal\profile\Event\ProfileLabelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProfileLabelSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      'profile.label' => 'onLabel',
    ];
    return $events;
  }

  /**
   * Sets the customer profile label to the first address line.
   *
   * @param \Drupal\profile\Event\ProfileLabelEvent $event
   *   The profile label event.
   */
  public function onLabel(ProfileLabelEvent $event) {
    /** @var \Drupal\profile\Entity\ProfileInterface $order */
    $profile = $event->getProfile();
    $profile_type = ProfileType::load($profile->bundle());
    $is_commerce_profile_type = $profile_type->getThirdPartySetting('commerce_order', 'commerce_profile_type', FALSE);
    if ($is_commerce_profile_type && !$profile->address->isEmpty()) {
      $event->setLabel($profile->address->address_line1);
    }
  }

}
