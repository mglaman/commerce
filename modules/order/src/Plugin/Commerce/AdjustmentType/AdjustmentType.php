<?php

namespace Drupal\commerce_order\Plugin\Commerce\AdjustmentType;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;

class AdjustmentType extends PluginBase implements AdjustmentTypeInterface {

  use PluginWithFormsTrait;

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

}
