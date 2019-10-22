<?php

namespace Drupal\Tests\commerce\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;
use Drupal\views\ViewEntityInterface;

/**
 * @group commerce
 */
class BaseFieldConversionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/8b2d9f0e-base-field-upgrade-feature.php.gz',
    ];
  }

  /**
   * Tests the existing product is accessible after the upgrades.
   *
   * @see commerce_product_update_8208
   */
  public function testProductBaseFieldsUpdate() {
    $this->runUpdates();

    $entity_type_manager = \Drupal::entityTypeManager();
    $product_storage = $entity_type_manager->getStorage('commerce_product');

    $product = $product_storage->load(1);
    assert($product instanceof Product);
    $this->assertEquals([1], $product->getVariationIds());
    $this->assertEquals([1, 2], $product->getStoreIds());

    $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('commerce_product');
    $this->assertNotInstanceOf(FieldStorageConfigInterface::class, $field_storage_definitions['variations']);
    $this->assertNotInstanceOf(FieldStorageConfigInterface::class, $field_storage_definitions['stores']);
    $this->assertInstanceOf(BaseFieldDefinition::class, $field_storage_definitions['variations']);
    $this->assertInstanceOf(BaseFieldDefinition::class, $field_storage_definitions['stores']);
  }

  /**
   * Tests the existing order is accessible after the upgrades.
   *
   * @see commerce_order_update_8207
   */
  public function testOrdertBaseFieldsUpdate() {
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

  /**
   * Test commerce_cart views are updated and query properly.
   */
  public function testViewsAdjusted() {
    $this->runUpdates();

    /** @var \Drupal\views\ViewEntityInterface $order_item_relationship_views */
    $order_item_relationship_views = View::loadMultiple([
      'commerce_cart_block',
      'commerce_cart_form',
      'commerce_checkout_order_summary',
    ]);
    foreach ($order_item_relationship_views as $view) {
      assert($view instanceof ViewEntityInterface);
      $display = $view->getDisplay('default');
      $this->assertEquals('order_items_target_id', $display['display_options']['relationships']['order_items']['field']);
      $this->assertEquals('commerce_order', $display['display_options']['relationships']['order_items']['entity_type']);
      $this->assertEquals('order_items', $display['display_options']['relationships']['order_items']['entity_field']);

      $executable = $view->getExecutable();
      $executable->execute();
      $this->assertNotEmpty($view->result);
      $this->assertCount(1, $view->result, var_export($view->result, TRUE));
    }
  }

}
