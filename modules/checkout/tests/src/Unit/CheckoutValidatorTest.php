<?php

namespace Drupal\Tests\commerce_checkout\Unit;

use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraint;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface;
use Drupal\commerce_checkout\CheckoutValidator\DefaultCheckoutValidator;
use Drupal\commerce_checkout\CheckoutOrderManager;
use Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the checkout validator system.
 *
 * @group commerce
 */
class CheckoutValidatorTest extends UnitTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_order',
    'commerce_checkout',
  ];

  /**
   * Tests CheckoutValidatorConstraintList.
   */
  public function testCheckoutValidatorConstraintList() {
    $list = new CheckoutValidatorConstraintList([
      new CheckoutValidatorConstraint('A random condition failed!'),
      new CheckoutValidatorConstraint('An excuse not to enter checkout!'),
    ]);
    $this->assertCount(2, $list);
    $this->assertEquals(2, $list->count());

    $empty_list = new CheckoutValidatorConstraintList();

    $merge = new CheckoutValidatorConstraintList();
    $merge->addAll($empty_list);
    $merge->addAll($list);

    $this->assertCount(2, $list);
    $this->assertEquals(2, $list->count());
  }

  /**
   * Test checkout validators.
   */
  public function testCheckoutValidator() {
    $container = new ContainerBuilder();
    $checkout_order_manager = new CheckoutOrderManager(
      $this->prophesize(ChainCheckoutFlowResolverInterface::class)->reveal()
    );
    $container->set('commerce_checkout.default_checkout_validator', new DefaultCheckoutValidator());
    $mock_checkout_validator = $this->prophesize(CheckoutValidatorInterface::class);
    $mock_checkout_validator->validate(
      Argument::type(OrderInterface::class),
      Argument::type(AccountInterface::class),
      Argument::type('string')
    )->willReturn(new CheckoutValidatorConstraintList());
    $container->set('commerce_checkout.mocked_checkout_validator', $mock_checkout_validator->reveal());

    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $validators = [
      'commerce_checkout.default_checkout_validator' => -100,
      // First set it to a lower priority, ensure default is TRUE.
      'commerce_checkout.mocked_checkout_validator' => -200,
    ];
    arsort($validators, SORT_NUMERIC);
    foreach ($validators as $id => $priority) {
      $checkout_order_manager->addValidator($container->get($id));
    }

    $account = $this->prophesize(AccountInterface::class);
    $account->isAuthenticated()->willReturn(TRUE);
    $account->id()->willReturn(3);
    $account->hasPermission('access checkout')->willReturn(TRUE);

    $order = $this->prophesize(OrderInterface::class);
    $order->getState()->willReturn((object) [
      'value' => 'draft',
    ]);
    $order->hasItems()->willReturn(TRUE);
    $order->getCustomerId()->willReturn(3);

    $result = $checkout_order_manager->validate(
      $order->reveal(),
      $account->reveal(),
      CheckoutValidatorInterface::PHASE_ENTER
    );
    $this->assertEquals(0, $result->count());

    $mock_checkout_validator = $this->prophesize(CheckoutValidatorInterface::class);
    $mock_checkout_validator->validate(
      Argument::type(OrderInterface::class),
      Argument::type(AccountInterface::class),
      Argument::type('string')
    )->willReturn(new CheckoutValidatorConstraintList([
      new CheckoutValidatorConstraint('A random condition failed!'),
      new CheckoutValidatorConstraint('An excuse not to enter checkout!'),
    ]));
    $container->set('commerce_checkout.mocked_checkout_validator', $mock_checkout_validator->reveal());

    $checkout_order_manager = new CheckoutOrderManager(
      $this->prophesize(ChainCheckoutFlowResolverInterface::class)->reveal()
    );
    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $validators = [
      'commerce_checkout.default_checkout_validator' => -100,
      'commerce_checkout.mocked_checkout_validator' => 100,
    ];
    arsort($validators, SORT_NUMERIC);
    foreach ($validators as $id => $priority) {
      $checkout_order_manager->addValidator($container->get($id));
    }
    $result = $checkout_order_manager->validate(
      $order->reveal(),
      $account->reveal(),
      CheckoutValidatorInterface::PHASE_ENTER
    );
    $this->assertEquals(2, $result->count());
  }

}
