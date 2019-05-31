<?php

namespace Drupal\commerce_order\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\views\Element\View;

class Addresses implements ContainerInjectionInterface {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

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

  public function addressBook(UserInterface $user) {
    $cacheability = new CacheableMetadata();
    $build = [];
    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    $profile_types = array_filter($profile_type_storage->loadMultiple(), static function (ProfileTypeInterface $profile_type) {
      return $profile_type->getThirdPartySetting('commerce_order', 'commerce_profile_type', FALSE);
    });
    foreach ($profile_types as $profile_type_id => $profile_type) {
      $cacheability->addCacheableDependency($profile_type);
      // Render the active profiles.
      // @todo test multiple profile types and that they appear.
      // @todo decide to avoid using `details` if there is only one profile type.
      $build[$profile_type_id . '_profiles'] = [
        '#type' => 'details',
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