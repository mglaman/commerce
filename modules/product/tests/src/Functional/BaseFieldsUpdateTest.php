<?php

namespace Drupal\Tests\commerce_product\Functional;

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

}
