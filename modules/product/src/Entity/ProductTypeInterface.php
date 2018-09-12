<?php

namespace Drupal\commerce_product\Entity;

use Drupal\commerce\Entity\CommerceBundleEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for product types.
 */
interface ProductTypeInterface extends CommerceBundleEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the product type's matching variation type ID.
   *
   * @return string
   *   The variation type ID.
   */
  public function getVariationTypeId();

  /**
   * Sets the product type's matching variation type ID.
   *
   * @param string $variation_type_id
   *   The variation type ID.
   *
   * @return $this
   */
  public function setVariationTypeId($variation_type_id);

  /**
   * Gets whether variation fields should be injected into the rendered product.
   *
   * @return bool
   *   TRUE if the variation fields should be injected into the rendered
   *   product, FALSE otherwise.
   */
  public function shouldInjectVariationFields();

  /**
   * Sets whether variation fields should be injected into the rendered product.
   *
   * @param bool $inject
   *   Whether variation fields should be injected into the rendered product.
   *
   * @return $this
   */
  public function setInjectVariationFields($inject);

  /**
   * Gets whether the variations tab should be shown for this product type.
   *
   * @return bool
   *   TRUE if the variations tab is shown for this product type,
   *   FALSE otherwise.
   */
  public function shouldShowVariationsTab();

  /**
   * Sets whether the variations tab should be shown with the edit form.
   *
   * @param bool $show
   *   Whether the variations tab should be shown with the edit form.
   *
   * @return $this
   */
  public function setShowVariationsTab($show);

}
