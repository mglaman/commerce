<?php

namespace Drupal\commerce_order\Controller;

use Drupal\commerce_order\AddressBookInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileTypeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AddressBookController implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The address book service.
   *
   * @var \Drupal\commerce_order\AddressBookInterface
   */
  protected $addressBook;

  /**
   * Constructs a new Addresses object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AddressBookInterface $address_book) {
    $this->entityTypeManager = $entity_type_manager;
    $this->addressBook = $address_book;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_order.address_book')
    );
  }

  /**
   * Checks access to the addresses.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $definition = $this->entityTypeManager->getDefinition('profile');
    if ($account->hasPermission($definition->getAdminPermission())) {
      return AccessResult::allowed()->cachePerUser();
    }
    $user = $route_match->getParameter('user');
    if ($account->id() !== $user->id()) {
      return AccessResult::forbidden()->cachePerUser();
    }
    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types */
    $profile_types = $this->addressBook->getProfileTypes();
    $profile_types = array_filter($profile_types, function (ProfileTypeInterface $profile_type) use ($user) {
      // Create a stub profile to pass to entity access.
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      // Create a stubbed Profile entity for access checks.
      $profile_stub = $profile_storage->create([
        'type' => $profile_type->id(),
        'uid' => $user->id(),
      ]);
      $access_handler = $this->entityTypeManager->getAccessControlHandler('profile');
      return $access_handler->access($profile_stub, 'view');
    });

    return AccessResult::allowedIf(!empty($profile_types))->cachePerUser();
  }

  public function checkCreateAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    $definition = $this->entityTypeManager->getDefinition('profile');
    if ($account->hasPermission($definition->getAdminPermission())) {
      return AccessResult::allowed()->cachePerUser();
    }
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $route_match->getParameter('profile_type');
    $access_handler = $this->entityTypeManager->getAccessControlHandler('profile');
    return $access_handler->createAccess($profile_type->id(), $account, [], TRUE);
  }

  /**
   * Add profile page.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The render array, or redirect response.
   *
   * @see \Drupal\Core\Entity\Controller\EntityController::addPage
   */
  public function addProfile(UserInterface $user) {
    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types */
    $profile_types = $this->addressBook->getProfileTypes();
    $profile_types = array_filter($profile_types, function (ProfileTypeInterface $profile_type) {
      $access_handler = $this->entityTypeManager->getAccessControlHandler('profile');
      return $access_handler->createAccess($profile_type->id());
    });
    if (count($profile_types) === 1) {
      $bundle_names = array_keys($profile_types);
      $bundle_name = reset($bundle_names);
      $url = Url::fromRoute(
        'commerce_order.user_addressbook.add_form',
        [
          'user' => $user->id(),
          'profile_type' => $bundle_name,
        ],
        ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    $build = [
      '#theme' => 'entity_add_list',
      '#bundles' => [],
    ];
    $profile_type_definition = $this->entityTypeManager->getDefinition('profile_type');
    $build['#cache']['tags'] = $profile_type_definition->getListCacheTags();
    foreach ($profile_types as $profile_type_id => $profile_type) {
      $build['#bundles'][$profile_type_id] = [
        'label' => $profile_type->label(),
        'description' => '',
        'add_link' => Link::createFromRoute(
          $profile_type->label(),
          'commerce_order.user_addressbook.add_form',
          [
            'user' => $user->id(),
            'profile_type' => $profile_type_id,
          ]
        ),
      ];
    }

    return $build;
  }

  /**
   * Renders the addresses for the user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   *
   * @return array
   *   The response.
   */
  public function listProfiles(UserInterface $user) {
    $cacheability = new CacheableMetadata();
    $build = [];
    $profile_types = $this->addressBook->getProfileTypes();
    $profile_types = array_filter($profile_types, function (ProfileTypeInterface $profile_type) use ($user) {
      // Create a stub profile to pass to entity access.
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      // Create a stubbed Profile entity for access checks.
      $profile_stub = $profile_storage->create([
        'type' => $profile_type->id(),
        'uid' => $user->id(),
      ]);
      $access_handler = $this->entityTypeManager->getAccessControlHandler('profile');
      return $access_handler->access($profile_stub, 'view');
    });
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
