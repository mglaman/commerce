<?php

namespace Drupal\Tests\commerce_payment\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the integration between payments and checkout and profile reuse.
 *
 * @group commerce
 */
class PaymentCheckoutProfileReuseTest extends CommerceWebDriverTestBase {

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
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'commerce_payment_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge([
      'administer profile',
    ], parent::getAdministratorPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->createUser([
      'view own customer profile',
    ]);
    $this->drupalLogin($this->account);

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '39.99',
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

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'onsite',
      'label' => 'On-site',
      'plugin' => 'example_onsite',
      'configuration' => [
        'api_key' => '2342fewfsfs',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'example_offsite_redirect',
      'configuration' => [
        'redirect_method' => 'post',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $gateway->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'manual',
      'label' => 'Manual',
      'plugin' => 'manual',
      'configuration' => [
        'display_label' => 'Cash on delivery',
        'instructions' => [
          'value' => 'Sample payment instructions.',
          'format' => 'plain_text',
        ],
      ],
    ]);
    $gateway->save();
  }

  /**
   * Tests that the billing information is not copied to the addressbook.
   */
  public function testProfileNotCopiedToAddressbookPaymentMethodAddForm() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => 1]));
    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4012888888881881',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2020',
      'payment_information[add_payment_method][payment_details][security_code]' => '123',
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    // The order's billing address should have no owner.
    $order = Order::load(1);
    /** @var \Drupal\profile\Entity\ProfileInterface $order_billing_profile */
    $order_billing_profile = $order->getBillingProfile();
    $this->assertEquals(0, $order_billing_profile->getOwnerId());

    // The user should not have any profiles in their addressbook.
    $profiles = $profile_storage->loadMultipleByUser($this->account, 'customer', TRUE);
    $this->assertCount(0, $profiles);
  }

  /**
   * Tests that the billing information is copied to the addressbook.
   */
  public function testProfileCopiedToAddressbookPaymentMethodAddForm() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => 1]));
    $this->submitForm([
      'payment_information[add_payment_method][payment_details][number]' => '4012888888881881',
      'payment_information[add_payment_method][payment_details][expiration][month]' => '02',
      'payment_information[add_payment_method][payment_details][expiration][year]' => '2020',
      'payment_information[add_payment_method][payment_details][security_code]' => '123',
      'payment_information[add_payment_method][billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[add_payment_method][billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[add_payment_method][billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[add_payment_method][billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[add_payment_method][billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[add_payment_method][billing_information][address][0][address][postal_code]' => '10001',
      'payment_information[add_payment_method][billing_information][add_to_addressbook]' => 1,
    ], 'Continue to review');
    $this->submitForm([], 'Pay and complete purchase');
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
    $this->assertEquals('Johnny', $address->getGivenName());
    $this->assertEquals('Appleseed', $address->getFamilyName());
  }

  /**
   * Tests that the billing information is not copied to the addressbook.
   */
  public function testProfileNotCopiedToAddressbookPaymentInformationForm() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => 1]));
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    // The order's billing address should have no owner.
    $order = Order::load(1);
    $order_billing_profile = $order->getBillingProfile();
    $this->assertEquals(0, $order_billing_profile->getOwnerId());

    // The user should not have any profiles in their addressbook.
    $profiles = $profile_storage->loadMultipleByUser($this->account, 'customer', TRUE);
    $this->assertCount(0, $profiles);
  }

  /**
   * Tests that the billing information is not copied to the addressbook.
   */
  public function testProfileCopiedToAddressbookPaymentInformationForm() {
    /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');

    $this->drupalGet($this->product->toUrl());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet(Url::fromRoute('commerce_checkout.form', ['commerce_order' => 1]));
    $radio_button = $this->getSession()->getPage()->findField('Example');
    $radio_button->click();
    $this->waitForAjaxToFinish();
    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
      'payment_information[billing_information][add_to_addressbook]' => 1,
    ], 'Continue to review');
    $this->submitForm([], 'Pay and complete purchase');
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
    $this->assertEquals('Johnny', $address->getGivenName());
    $this->assertEquals('Appleseed', $address->getFamilyName());
  }

}
