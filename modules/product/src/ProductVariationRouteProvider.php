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
    $route->addRequirements(['_entity_access' => 'commerce_product.update']);

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
    // If a user has the ability to see the product overview, they should also
    // be able to view the variations that belong to a product.
    $route->addRequirements([
      '_permission' => 'access commerce_product overview',
    ]);

    return $route;
  }

}
