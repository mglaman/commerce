<?php

namespace Drupal\commerce_product;

interface  ProductVariationAttributeValueResolverInterface {

  /**
   * Resolves an available variation by the attributes.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The variations.
   * @param array $attribute_values
   *   An array of attribute values, keyed by the attribute name.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The variation.
   */
  public function resolve(array $variations, array $attribute_values = []);

}
