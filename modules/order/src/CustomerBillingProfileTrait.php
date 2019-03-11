<?php

namespace Drupal\commerce_order;

use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Trait to reuse billing profile ownership manipulation.
 *
 * Bikeshed. Needs work. This was created just so the logic could be abstracted.
 * In Commerce it is used three separate times and would be needed by Shipping
 * or others as well.
 *
 * @see \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\BillingInformation::buildPaneForm
 * @see \Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation::buildBillingProfileForm
 * @see \Drupal\commerce_payment\PluginForm\PaymentMethodAddForm::buildConfigurationForm
 */
trait CustomerBillingProfileTrait {

  protected $profileStorage;

  /**
   * @param $profile_type
   * @param \Drupal\user\UserInterface $user
   * @param \Drupal\profile\Entity\ProfileInterface|null $existing
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   */
  protected function ensureParentProfileCopy($profile_type, UserInterface $user, ProfileInterface $existing = NULL) {
    if (!$existing) {
      $profile_storage = $this->getProfileStorage();
      $existing_profile = $profile_storage->loadDefaultByUser($user, $profile_type);
      if (!$existing_profile) {
        $existing_profile = $profile_storage->create([
          'type' => $profile_type,
          'uid' => $user->id(),
        ]);
      }

      $existing = $existing_profile->createDuplicate();
      $existing->setOwner(User::getAnonymousUser());
    }
    return $existing;
  }

  /**
   * @return \Drupal\profile\ProfileStorageInterface
   */
  protected function getProfileStorage() {
    return $this->profileStorage ?: \Drupal::entityTypeManager()->getStorage('profile');
  }

}
