<?php

namespace Drupal\Tests\commerce_payment\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Tests\commerce\FunctionalJavascript\CommerceWebDriverTestBase;

/**
 * Tests the order paid event with double save in an off site redirect return.
 *
 * @link https://www.drupal.org/project/commerce/issues/3011667
 *
 * @group commerce
 */
class OrderPaidDoubleFireTest extends CommerceWebDriverTestBase {

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
    'commerce_payment_test',
  ];


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

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
      'id' => 'offsite',
      'label' => 'Off-site',
      'plugin' => 'test_double_save_offsite_redirect',
      'configuration' => [
        // PayPal uses GET. Testing with POST caused mass failures.
        'redirect_method' => 'get',
        'payment_method_types' => ['credit_card'],
      ],
    ]);
    $gateway->save();
  }

  /**
   * Tests checkout with an off-site gateway (POST redirect method).
   *
   * @dataProvider providerForWhenToSaveOrder
   */
  public function testCheckoutWithOffsiteRedirectPost($when_to_save) {
    $state = $this->container->get('state');
    $state->set('test_double_save_offsite_redirect_when_to_save', $when_to_save);


    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet('checkout/1');

    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Example');
    $this->assertSession()->pageTextContains('Johnny Appleseed');
    $this->assertSession()->pageTextContains('123 New York Drive');
    $this->submitForm([], 'Pay and complete purchase');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');

    $order = Order::load(1);
    $this->assertEquals('offsite', $order->get('payment_gateway')->target_id);
    $this->assertFalse($order->isLocked());
    // Verify that a payment was created.
    $payment = Payment::load(1);
    $this->assertNotNull($payment);
    $this->assertEquals($payment->getAmount(), $order->getTotalPrice());

    // Test the data was set and preserved from our test gateway.
    $this->assertEquals($order->getData('test_double_save_offsite_redirect'), [
      'test' => TRUE,
    ]);

    // The order should be marked as paid, a payment was added and
    // commerce_payment.order_manager updated the order's total paid amount.
    // @see \Drupal\commerce_payment\Entity\Payment::postSave
    //
    // @NOTE: When the order is saved by the gateway _before_ a payment is added
    // this fails.
    //
    // @NOTE: \Drupal\commerce_payment\PaymentOrderManager::updateTotalPaid saves the order.
    $this->assertTrue($order->isPaid());

    // Test the paid_event was dispatched.
    $this->assertTrue($order->getData('paid_event_dispatched'));

    // Paid ran event ran, see if our subscriber executed.
    //
    // This actually does not run, because the order had its "place" transition
    // ran at the end of the checkout. Nothing caused the order payment event
    // to have to trigger while a draft. (ie: IPN.)
    $this->assertEquals('order_not_draft', $state->get('order_paid_test_subscriber_ran'));
    $this->assertEquals(null, $state->get('order_paid_test_subscriber_' . $order->id()));

    // Verify the place transition did not execute twice.
    // Unsure why or how this is possibly happening as the paid_event does
    // not seem to dispatch when there is a double execution.
    $this->assertEquals(1, $state->get('order_place_test_pre_transition_' . $order->id()));
    $this->assertEquals(1, $state->get('order_place_test_post_transition_' . $order->id()));
  }

  public function providerForWhenToSaveOrder() {
    return [
      ['before'],
      ['after'],
    ];
  }

}
