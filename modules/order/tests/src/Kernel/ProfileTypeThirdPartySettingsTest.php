<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\profile\Entity\ProfileType;

/**
 * @group commerce
 * @group commerce_order
 */
class ProfileTypeThirdPartySettingsTest extends EntityKernelTestBase {

  public static $modules = [
    'options',
    'views',
    'address',
    'entity',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'inline_entity_form',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installConfig(['commerce_order']);
  }

  /**
   * Tests the commerce_order.commerce_profile_type third party setting.
   */
  public function testCustomerProfileTypeIsCommerceProfileType() {
    $customer_profile_type = ProfileType::load('customer');
    $this->assertTrue($customer_profile_type->getThirdPartySetting('commerce_order', 'commerce_profile_type', FALSE));

    $new_profile = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $new_profile->save();
    $this->assertFalse($new_profile->getThirdPartySetting('commerce_order', 'commerce_profile_type', FALSE));
    $new_profile->setThirdPartySetting('commerce_order', 'commerce_profile_type', TRUE);
    $new_profile->save();
    $this->assertTrue($new_profile->getThirdPartySetting('commerce_order', 'commerce_profile_type', FALSE));
  }

  /**
   * Tests that profile types used by Commerce do not have local tasks.
   */
  public function testLocalTasksAlter() {

    $local_tasks_manager = $this->container->get('plugin.manager.menu.local_task');
    $derivative_key = 'entity.profile.user_profile_form:profile.type.%s';

    $customer_profile_type = ProfileType::load('customer');
    $this->assertFalse($local_tasks_manager->hasDefinition(sprintf($derivative_key, $customer_profile_type->id())));

    $new_profile = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $new_profile->save();
    $local_tasks_manager->clearCachedDefinitions();
    $this->assertTrue($local_tasks_manager->hasDefinition(sprintf($derivative_key, $new_profile->id())));

    $new_profile->setThirdPartySetting('commerce_order', 'commerce_profile_type', TRUE);
    $new_profile->save();

    $local_tasks_manager->clearCachedDefinitions();
    $this->assertFalse($local_tasks_manager->hasDefinition(sprintf($derivative_key, $new_profile->id())));
  }

  public function testLocalActions() {
    $local_actions_manager = $this->container->get('plugin.manager.menu.local_action');
    $derivative_key = 'commerce_order.addresses.%s';

    $customer_profile_type = ProfileType::load('customer');
    $this->assertFalse($local_actions_manager->hasDefinition(sprintf($derivative_key, $customer_profile_type->id())));

    $test_account = $this->createUser([], [
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
    ]);
    $this->container->get('current_user')->setAccount($test_account);
    $actions = $local_actions_manager->getActionsForRoute('commerce_order.user_addresses');
    $this->assertNotEmpty($actions);
    $this->assertArrayHasKey('commerce_order.addresses_actions:customer', $actions);
  }

}
