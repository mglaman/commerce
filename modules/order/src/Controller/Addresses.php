<?php

namespace Drupal\commerce_order\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Addresses implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new Addresses object.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks access to the addresses.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param Drupal\Core\Session\AccountInterface $account
   *   The account.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $user = $route_match->getParameter('user');
    if ($account->id() === $user->id()) {
      return AccessResult::allowed()->cachePerUser();
    }
    $definition = $this->entityTypeManager->getDefinition('profile');
    if ($account->hasPermission($definition->getAdminPermission())) {
      return AccessResult::allowed()->cachePerUser();
    }
    // @todo figure out permissions. Do we want more granular for each type?
    // decorate the route with all profile types which are used by commerce
    // and check their specific permissions.
    // @see \Drupal\profile\Access\ProfileAccessCheck.
    return AccessResult::forbidden()->cachePerUser();
  }

  /**
   * Renders the addressbook.
   *
   * @param Drupal\user\UserInterface $user
   *   The user.
   */
  public function list(UserInterface $user) {
    $cacheability = new CacheableMetadata();
    $build = [];
    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types */
    $profile_types = $profile_type_storage->loadByProperties([
      'multiple' => TRUE,
      'third_party_settings.commerce_order.commerce_profile_type' => TRUE,
    ]);
    $wrapper_element_type = count($profile_types) > 1 ? 'details' : 'container';
    foreach ($profile_types as $profile_type_id => $profile_type) {
      $cacheability->addCacheableDependency($profile_type);
      // Render the active profiles.
      $build[$profile_type_id . '_profiles'] = [
        '#type' => $wrapper_element_type,
        '#title' => $profile_type->label(),
        '#open' => TRUE,
        'list' => [
          '#type' => 'view',
          '#name' => 'profiles',
          '#display_id' => 'profile_type_listing',
          '#arguments' => [$user->id(), $profile_type_id, 1],
          '#embed' => TRUE,
        ],
      ];
    }

    $cacheability->applyTo($build);
    return $build;
  }

}
