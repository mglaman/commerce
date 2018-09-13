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
    // Ensure access to the update the product as well.
    $requirements = $route->getRequirements();
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
    // Ensure access to the update the product as well.
    $requirements = $route->getRequirements();
    $requirements['_entity_access'] = 'commerce_product.update';
    $route->setRequirements($requirements);

    return $route;
  }

}
