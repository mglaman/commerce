<?php

namespace Drupal\Tests\commerce_order\Functional;

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
    $this->assertSession()->linkExists('Add new Customer');
    $this->getSession()->getPage()->clickLink('Add new Customer');
    $this->assertSession()->pageTextContains('Create Customer');
    $this->assertSession()->buttonExists('Save');
    $this->assertSession()->buttonExists('Save and make default');
  }

  public function testAddressesActionsAdditionalTypes() {
    $shipping_profile_type = ProfileType::create([
      'id' => 'shipping',
      'label' => 'Shipping',
    ]);
    $shipping_profile_type->setThirdPartySetting('commerce_order', 'commerce_profile_type', TRUE);
    $shipping_profile_type->save();
    $test_profile_type = ProfileType::create([
      'id' => 'test',
      'label' => 'Test',
    ]);
    $test_profile_type->save();

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
    // @todo this should show.
    // $this->assertSession()->linkExists('Test');
    $this->assertSession()->linkNotExists('Shipping');

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->assertSession()->linkExists('Add new Customer');
    $this->assertSession()->linkExists('Add new Shipping');
  }

}
