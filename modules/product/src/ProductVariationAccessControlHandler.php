<?php

namespace Drupal\commerce_product;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controls product variation access based on the parent product.
 */
class ProductVariationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $entity->getProduct();
      if ($product) {
        $result = $product->access($operation, $account, TRUE);
      }
      else {
        // Assumes the product access control handling has already happened.
        $result = AccessResult::allowed();
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer commerce_product',
      'update commerce_product',
    ], 'OR');
  }

}
