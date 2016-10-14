<?php

namespace Drupal\commerce_order;

use Drupal\commerce_order\Plugin\Commerce\AdjustmentType\AdjustmentType;
use Drupal\commerce_order\PluginForm\AdjustmentTypeDefaultForm;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

class AdjustmentTypeManager extends DefaultPluginManager {

  /**
   * Default values for each adjustment type plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'label' => '',
    'class' => AdjustmentType::class,
    'forms' => [
      'default' => AdjustmentTypeDefaultForm::class,
    ]
  ];

  /**
   * Constructs a new PaymentGatewayManager object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->alterInfo('commerce_order_adjustment_type_info');
    $this->setCacheBackend($cache_backend, 'commerce_order_adjustment_type_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('adjustment_type', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('label');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    $definition['id'] = $plugin_id;
    foreach (['label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The adjustment type %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}
