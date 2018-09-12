<?php

namespace Drupal\commerce_product\Access;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an access checker for product variations.
 */
class ProductVariationCollectionAccess implements AccessInterface {

  /**
   * Checks access to the product variation for the given account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   Parent product of the variation.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, ProductInterface $commerce_product) {
    $product_type = ProductType::load($commerce_product->bundle());
    return $commerce_product->access('update', $account, TRUE)
      ->andIf(AccessResult::allowedIf($product_type->shouldShowVariationsTab()));
  }

}
