<?php

namespace Drupal\commerce_order\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  public function checkAccess(AccountInterface $account, UserInterface $user) {
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

  public function addressBook() {
    // @todo list each profile type.
    // @todo we need local actions for adding addresses.
    // @todo this needs a local task on user pages.
    return [];
  }

}
