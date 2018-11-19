<?php

namespace Drupal\commerce_order\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a controller to render a single order.
 */
class UserOrderViewController extends EntityViewController {

  use StringTranslationTrait;

/**
   * The _title_callback for the user order page.
   *
   * @param string $user
   *   The user uid.
   * @param \Drupal\Core\Entity\EntityInterface $commerce_order
   *   The current order.
   *
   * @return string
   *   The page title.
   */
  public function title($user, EntityInterface $commerce_order) {
    $vars = [
      '@number' => $this->entityManager->getTranslationFromContext($commerce_order)->label(),
    ];
    return $this->t('Order @number', $vars);
  }

}
