<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_order\Exception\OrderVersionMismatchException;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests order locking.
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
   * Tests our OrderVersion constraint is available.
   */
  public function testOrderConstraintDefinition() {
    // Ensure our OrderVersion constraint is available.
    $entity_type_manager = $this->container->get('entity_type.manager');
    $order_type = $entity_type_manager->getDefinition('commerce_order');
    $default_constraints = [
      'OrderVersion' => [],
      // Added to all ContentEntity implementations.
      'EntityUntranslatableFields' => NULL
    ];
    self::assertEquals($default_constraints, $order_type->getConstraints());
  }

  /**
   * Tests order constraints are validated.
   */
  public function testOrderConstraintValidation() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
    ]);
    $contraint_violations = $order->validate()->getEntityViolations();
    self::assertEquals(0, $contraint_violations->count());
    $order->save();
    self::assertEquals(1, $order->getVersion());

    (function($order_id) {
      $order = Order::load($order_id);
      assert($order instanceof OrderInterface);
      $order->addItem(OrderItem::create([
        'type' => 'test',
        'quantity' => 1,
        'unit_price' => new Price('12.00', 'USD'),
      ]));
      $order->save();
      self::assertEquals(2, $order->getVersion());
    })($order->id());

    $contraint_violations = $order->validate()->getEntityViolations();
    self::assertEquals(1, $contraint_violations->count());
    $entity_constraint_violation = $contraint_violations->get(0);
    self::assertEquals('The order has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.', $entity_constraint_violation->getMessage());
  }

  public function testOrderVersionMismatchException() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = Order::create([
      'type' => 'default',
      'mail' => $this->user->getEmail(),
      'uid' => $this->user->id(),
      'store_id' => $this->store->id(),
    ]);
    $order->save();
    self::assertEquals(1, $order->getVersion());

    (function($order_id) {
      $order = Order::load($order_id);
      assert($order instanceof OrderInterface);
      $order->addItem(OrderItem::create([
        'type' => 'test',
        'quantity' => 1,
        'unit_price' => new Price('12.00', 'USD'),
      ]));
      $order->save();
      self::assertEquals(2, $order->getVersion());
    })($order->id());

    $this->setExpectedException(
      OrderVersionMismatchException::class,
      'The order has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.'
    );
    $order->save();
  }

}
