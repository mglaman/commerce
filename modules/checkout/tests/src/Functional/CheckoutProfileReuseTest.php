<?php

namespace Drupal\Tests\commerce_checkout\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Url;
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

    $this->account = $this->createUser([
      'view own customer profile',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests that the billing information is not copied to the addressbook.
   */
  public function testProfileNotCopiedToAddressbook() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_cart.page'));
    $this->submitForm([], 'Checkout');
    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => 'Frederick',
      'billing_information[profile][address][0][address][family_name]' => 'Pabst',
      'billing_information[profile][address][0][address][address_line1]' => 'Pabst Blue Ribbon Dr',
      'billing_information[profile][address][0][address][postal_code]' => '53177',
      'billing_information[profile][address][0][address][locality]' => 'Milwaukee',
      'billing_information[profile][address][0][address][administrative_area]' => 'WI',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Frederick Pabst');
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    // The user should not have any profiles in their addressbook.
    $profiles = $profile_storage->loadMultipleByUser($this->account, 'customer', TRUE);
    $this->assertCount(0, $profiles);

    $order = Order::load(1);
    /** @var \Drupal\profile\Entity\ProfileInterface $order_billing_profile */
    $order_billing_profile = $order->getBillingProfile();

    // The order's billing address should have no owner.
    $this->assertEquals(0, $order_billing_profile->getOwnerId());
  }

  /**
   * Test copying to addressbook.
   */
  public function testProfileAddToAddressbook() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_cart.page'));
    $this->submitForm([], 'Checkout');
    $this->submitForm([
      'billing_information[profile][address][0][address][given_name]' => 'Frederick',
      'billing_information[profile][address][0][address][family_name]' => 'Pabst',
      'billing_information[profile][address][0][address][address_line1]' => 'Pabst Blue Ribbon Dr',
      'billing_information[profile][address][0][address][postal_code]' => '53177',
      'billing_information[profile][address][0][address][locality]' => 'Milwaukee',
      'billing_information[profile][address][0][address][administrative_area]' => 'WI',
      'billing_information[profile][add_to_addressbook]' => 1,
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Billing information');
    $this->assertSession()->pageTextContains('Frederick Pabst');
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr');
    $this->submitForm([], 'Complete checkout');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    // The order's billing address should have no owner.
    $order = Order::load(1);
    $order_billing_profile = $order->getBillingProfile();
    $this->assertEquals(0, $order_billing_profile->getOwnerId());

    // Verify the profile is now in the user's addressbook.
    $profiles = $profile_storage->loadMultipleByUser($this->account, 'customer', TRUE);
    $this->assertCount(1, $profiles);
    $profile = reset($profiles);
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile->get('address')->first();
    $this->assertEquals('Frederick', $address->getGivenName());
    $this->assertEquals('Pabst', $address->getFamilyName());
  }

}
