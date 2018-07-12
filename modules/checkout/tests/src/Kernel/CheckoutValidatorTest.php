<?php

namespace Drupal\Tests\commerce_checkout\Kernel;

use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the checkout validators.
 *
 * @group commerce
 */
class CheckoutValidatorTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
    'commerce_checkout',
    'commerce_checkout_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_order');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_product');
    $this->installConfig('commerce_checkout');
  }

  /**
   * Tests checkout validation.
   */
  public function testCheckoutValidation() {
    $order = Order::create([
      'type' => 'default',
      'mail' => $this->randomMachineName() . '@example.com',
      'store_id' => $this->store->id(),
    ]);
    $order_item = OrderItem::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'unit_price' => new Price('10.00', 'USD'),
    ]);
    $order->addItem($order_item);

    $chain_validator = $this->container->get('commerce_checkout.chain_checkout_validator');

    $result = $chain_validator->validate($order, CheckoutValidatorInterface::PHASE_ENTER);
    $this->assertCount(0, $result);

    $order_item->setQuantity(20);
    $result = $chain_validator->validate($order, CheckoutValidatorInterface::PHASE_ENTER);
    $this->assertCount(1, $result);
  }

}
