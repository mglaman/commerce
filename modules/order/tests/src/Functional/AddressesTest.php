<?php

namespace Drupal\Tests\commerce_order\Functional;

use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileType;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * @group commerce
 * @group commerce_order
 */
class AddressesTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Log out of the admin user.
    $this->drupalLogout();
  }

  /**
   * Gets the permissions for the admin user.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
      'administer profile',
      'administer profile types',
    ];
  }

  public function testAddressesLocalTask() {
    $customer = $this->createUser();
    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->assertSession()->linkExists('View');
    $this->assertSession()->linkExists('Edit');
    $this->assertSession()->linkExists('Addresses');
    $this->getSession()->getPage()->clickLink('Addresses');
    $this->assertSession()->pageTextContains('Your addresses');
    $this->getSession()->getPage()->clickLink('View');
    $this->assertSession()->pageTextContains($customer->getDisplayName());
  }

  public function testAddressesActions() {
    $customer = $this->createUser([
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->assertSession()->linkExists('Add new Address');
    $this->getSession()->getPage()->clickLink('Add new Address');
    $this->assertSession()->pageTextContains('Create Address');
    $this->assertSession()->buttonExists('Save');
    $this->assertSession()->buttonExists('Save and make default');
  }

  public function testAddressesActionsAdditionalTypes() {
    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $customer_profile_type = ProfileType::load('customer');
    $bundle_entity_duplicator->duplicate($customer_profile_type, [
      'id' => 'shipping',
      'label' => 'Shipping',
    ]);
    $test_profile_type = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $test_profile_type->save();
    drupal_flush_all_caches();

    $customer = $this->createUser([
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
      'create shipping profile',
      'update own shipping profile',
      'view own shipping profile',
      'create test profile',
      'update own test profile',
      'view own test profile',
    ]);


    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->drupalGet($customer->toUrl());
    $this->assertSession()->linkExists('Test');
    $this->assertSession()->linkNotExists('Shipping');

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->assertSession()->linkExists('Add new Address');
    $this->assertSession()->linkExists('Add new Shipping');
  }

  public function testAddressesAvailable() {
    $customer = $this->createUser([
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
    ]);
    $customer_profile = Profile::create([
      'type' => 'customer',
      'uid' => $customer->id(),
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $customer_profile->save();
    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->assertSession()->elementNotExists('css', 'details');
    $this->assertSession()->pageTextContains($customer_profile->label());
  }

  public function testAddressesDisplayMultipleTypes() {
    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $customer_profile_type = ProfileType::load('customer');
    $bundle_entity_duplicator->duplicate($customer_profile_type, [
      'id' => 'shipping',
      'label' => 'Shipping',
    ]);
    $test_profile_type = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $test_profile_type->save();
    drupal_flush_all_caches();

    $customer = $this->createUser([
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
      'create shipping profile',
      'update own shipping profile',
      'view own shipping profile',
      'create test profile',
      'update own test profile',
      'view own test profile',
    ]);
    $customer_profile = Profile::create([
      'type' => 'customer',
      'uid' => $customer->id(),
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
    ]);
    $customer_profile->save();
    $shipping_profile = Profile::create([
      'type' => 'shipping',
      'uid' => $customer->id(),
      'address' => [
        'country_code' => 'US',
        'administrative_area' => 'CA',
        'locality' => 'Mountain View',
        'postal_code' => '94043',
        'address_line1' => '1098 Alta Ave',
        'organization' => 'Google Inc.',
        'given_name' => 'John',
        'family_name' => 'Smith',
      ],
    ]);
    $shipping_profile->save();


    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->assertSession()->elementTextContains('xpath', '//details[1]', 'Address');
    $this->assertSession()->pageTextContains($customer_profile->label());
    $this->assertSession()->elementTextContains('xpath', '//details[2]', 'Shipping');
    $this->assertSession()->pageTextContains($shipping_profile->label());
  }

}
