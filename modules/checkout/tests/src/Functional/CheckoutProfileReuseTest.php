<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Tests the checkout of an order and profile reuse.
 *
 * @group commerce
 */
class CheckoutProfileReuseTest extends CommerceBrowserTestBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The test customer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testCustomer;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'commerce_checkout_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer commerce_checkout_flow',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    $this->testCustomer = $this->createUser();
    // Make sure the test customer has an existing default profile.
    /** @var \Drupal\profile\Entity\Profile $profile */
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => $this->testCustomer->id(),
    ]);
    $profile->get('address')->setValue([
      'country_code' => 'US',
      'postal_code' => '53177',
      'locality' => 'Milwaukee',
      'address_line1' => 'Pabst Blue Ribbon Dr',
      'administrative_area' => 'WI',
      'given_name' => 'Frederick',
      'family_name' => 'Pabst',
    ]);
    $profile->setDefault(TRUE);
    $profile->save();
    $this->drupalLogin($this->testCustomer);
  }

  /**
   * Tests that the billing information address is prefilled.
   */
  public function testProfileReusedAndCopied() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $profile = $profile_storage->loadDefaultByUser($this->testCustomer, 'customer');
    $this->assertNotEmpty($profile);

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_cart.page'));
    $this->submitForm([], 'Checkout');
    $this->assertSession()->fieldValueEquals('billing_information[profile][address][0][address][given_name]', 'Frederick');
    $this->assertSession()->fieldValueEquals('billing_information[profile][address][0][address][family_name]', 'Pabst');
  }

}
