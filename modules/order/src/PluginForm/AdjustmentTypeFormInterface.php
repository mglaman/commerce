<?php

namespace Drupal\commerce_order\PluginForm;

use Drupal\commerce_order\Adjustment;
use Drupal\Core\Plugin\PluginFormInterface;

interface AdjustmentTypeFormInterface extends PluginFormInterface {
  public function setAdjustment(Adjustment $adjustment);
  public function getAdjustment();
}
