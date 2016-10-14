<?php

namespace Drupal\commerce_order\Plugin\Commerce\AdjustmentType;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

interface AdjustmentTypeInterface extends PluginWithFormsInterface, DerivativeInspectionInterface {

  /**
   * Gets the adjustment type label.
   *
   * @return mixed
   *   The adjustment type label.
   */
  public function getLabel();


}
