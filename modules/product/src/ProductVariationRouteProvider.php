<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the Product variation entity.
 */
class ProductVariationRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_product' => [
        'type' => 'entity:commerce_product',
      ],
    ]);
    // Product variations can be created if the parent product can be updated.
    $requirements = $route->getRequirements();
    unset($requirements['_entity_create_access']);
    $requirements['_entity_access'] = 'commerce_product.update';
    $route->setRequirements($requirements);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);

    $route->setOption('parameters', [
      'commerce_product' => [
        'type' => 'entity:commerce_product',
      ],
    ]);
    $route->setOption('_admin_route', TRUE);
    // Product variations can be viewed if the parent product can be updated.
    $requirements = $route->getRequirements();
    $requirements['_entity_access'] = 'commerce_product.update';
    $requirements['_commerce_product_variation_collection_access'] = 'TRUE';
    $route->setRequirements($requirements);

    return $route;
  }

}
