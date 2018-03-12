<?php

namespace Drupal\commerce_product;

class ProductVariationAttributeValueResolver implements ProductVariationAttributeValueResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(array $variations, array $attribute_values = []) {
    $current_variation = reset($variations);
    if (empty($attribute_values)) {
      return $current_variation;
    }
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    foreach ($variations as $variation) {
      $match = TRUE;
      foreach ($attribute_values as $attribute_field_name => $attribute_value) {
        // If any one of the attributes do not match, this is not a valid
        // candidate for the resolved variation.
        if ($variation->getAttributeValueId($attribute_field_name) != $attribute_value) {
          $match = FALSE;
        }
      }
      if ($match) {
        $current_variation = $variation;
        break;
      }
    }

    return $current_variation;
  }

}
