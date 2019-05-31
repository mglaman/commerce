<?php

namespace Drupal\commerce_order\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\profile\Entity\ProfileTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddressesLocalAction extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProfileAddLocalTask.
   *
   * @param string $base_plugin_definition
   *   The base plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($base_plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_definition) {
    return new static(
      $base_plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    // Starting weight for ordering the local tasks.
    $weight = 0;
    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    $profile_types = array_filter($profile_type_storage->loadMultiple(), function (ProfileTypeInterface $profile_type) {
      return $profile_type->getThirdPartySetting('commerce_order', 'commerce_profile_type', FALSE);
    });
    foreach ($profile_types as $profile_type_id => $profile_type) {
      $this->derivatives[$profile_type_id] = [
        'title' => "Add new {$profile_type->label()}",
        'route_name' => 'entity.profile.type.user_profile_form.add',
        'appears_on' => ['commerce_order.user_addresses'],
        'route_parameters' => [
          'profile_type' => $profile_type_id,
        ],
        'weight' => ++$weight,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
