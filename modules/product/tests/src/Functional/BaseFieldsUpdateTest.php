<?php

namespace Drupal\Tests\commerce_product\Functional;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

class BaseFieldsUpdateTest extends UpdatePathTestBase {

  use StoreCreationTrait;

  /**
   * @inheritDoc
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../tests/fixtures/8b2d9f0e-base-field-upgrade-feature.php.gz',
    ];
  }

  public function testProductBaseFieldsUpdate() {
    $this->runUpdates();

    $entity_type_manager = \Drupal::entityTypeManager();
    $product_storage = $entity_type_manager->getStorage('commerce_product');

    $product = $product_storage->load(1);
    assert($product instanceof Product);
    $this->assertEquals([1], $product->getVariationIds());
    $this->assertEquals([1, 2], $product->getStoreIds());
  }

}
