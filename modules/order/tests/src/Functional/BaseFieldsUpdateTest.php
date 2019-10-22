<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path from field config to base fields.
 */
class BaseFieldsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../tests/fixtures/8b2d9f0e-base-field-upgrade-feature.php.gz',
    ];
  }

  /**
   * Tests the existing product is accessible after the upgrades.
   */
  public function testProductBaseFieldsUpdate() {
    $this->runUpdates();

    $entity_type_manager = \Drupal::entityTypeManager();
    $order_storage = $entity_type_manager->getStorage('commerce_order');

    $order = $order_storage->load(1);
    assert($order instanceof Order);

    $items = $order->getItems();
    $this->assertEquals([1], array_map(static function (OrderItem $order_item) {
      return $order_item->id();
    }, $items));
    $this->assertEquals([1], array_map(static function (OrderItem $order_item) {
      return $order_item->getPurchasedEntityId();
    }, $items));

    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('commerce_order');
    $this->assertNotInstanceOf(FieldStorageConfigInterface::class, $field_storage_definitions['order_items']);
    $this->assertInstanceOf(BaseFieldDefinition::class, $field_storage_definitions['order_items']);
  }

}
