<?php

namespace Drupal\commerce_product\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for product variation creation.
 *
 * This differs from `access_check.entity_create` as to provide the correct
 * product variation type from the product type's configuration in the create
 * access check.
 */
class ProductVariationCreateAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ProductVariationCreateAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to create the product variation.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $route_match->getParameter('commerce_product');
    if (!$product) {
      return AccessResult::forbidden();
    }
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $product_type_storage->load($product->bundle());

    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('commerce_product_variation');
    return $access_control_handler->createAccess($product_type->getVariationTypeId(), $account, [], TRUE);
  }

}
