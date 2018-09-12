<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines the list builder for products.
 */
class ProductListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Title');
    $header['type'] = t('Type');
    $header['status'] = t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $entity */
    $product_type = ProductType::load($entity->bundle());

    $row['title']['data'] = [
      '#type' => 'link',
      '#title' => $entity->label(),
    ] + $entity->toUrl()->toRenderArray();
    $row['type'] = $product_type->label();
    $row['status'] = $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('update')) {
      $operations['variations'] = [
        'title' => $this->t('Variations'),
        'weight' => 20,
        'url' => new Url('entity.commerce_product_variation.collection', [
          'commerce_product' => $entity->id(),
        ]),
        // The entity_operations generates a destination query parameter that
        // brings the user back to the products collection. This is a jarring
        // UX experience if the user had just re-ordered the variations, saved
        // the form, and then saw a list of all the products in their store.
        // Setting this to null prevents EntityOperations::render from affecting
        // the UX.
        'query' => ['destination' => NULL],
      ];
    }

    return $operations;
  }

}
