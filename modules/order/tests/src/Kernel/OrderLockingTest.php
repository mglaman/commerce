<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests optimistic locking for orders.
 *
 * @group commerce
 */
class OrderLockingTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_order',
  ];

  /**
   * A test user to be used as orders customer.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installConfig(['commerce_order']);
    $this->user = $this->createUser(['mail' => 'test@example.com']);


    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();
  }

  /**
   * Tests OrderItem::postSave breaking versioning.
   *
   * @dataProvider providerSetOrderitemOrderId
   *
   * CartManager sets the order item ID on the order item, ensuring that
   * a reference is populated on the order to the order item, which forces a
   * save and breaks versioning.
   *
   * @see \Drupal\commerce_cart\CartManager::addOrderItem
   * @see \Drupal\commerce_order\Entity\OrderItem::postSave
   */
  public function testOrderItemPostSave($set_order_item_order_id) {
    // Create the new cart order.
    $cart = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'uid' => $this->user->id(),
      'cart' => TRUE,
    ]);
    $cart->save();

    $this->assertEquals(1, $cart->getVersion());

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
      'order_id' => $set_order_item_order_id ? $cart->id() : NULL
    ]);
    $order_item->save();

    $cart->addItem($order_item);
    $cart->save();
    $this->assertEquals(2, $cart->getVersion());
  }

  /**
   * @return array
   */
  public function providerSetOrderitemOrderId() {
    return [
      [FALSE],
      [TRUE]
    ];
  }


}
