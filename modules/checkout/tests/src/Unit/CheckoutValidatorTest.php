<?php

namespace Drupal\Tests\commerce_checkout\Unit;

use Drupal\commerce_checkout\CheckoutValidator\ChainCheckoutValidator;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface;
use Drupal\commerce_checkout\CheckoutValidator\DefaultCheckoutValidator;
use Drupal\commerce_checkout\CheckoutOrderManager;
use Drupal\commerce_checkout\Entity\CheckoutFlowInterface as EntityCheckoutFlowInterface;
use Drupal\commerce_checkout\Exception\CheckoutValidationException;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface as PluginCheckoutFlowInterface;
use Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Field\FieldItemListInterface;
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
   * Test checkout validators.
   */
  public function testCheckoutValidator() {
    $container = new ContainerBuilder();
    $chain_checkout_validator = new ChainCheckoutValidator();
    $container->set('commerce_checkout.default_checkout_validator', new DefaultCheckoutValidator());
    $mock_checkout_validator = $this->prophesize(CheckoutValidatorInterface::class);
    $mock_checkout_validator->allowed(
      Argument::type(OrderInterface::class),
      Argument::type(PluginCheckoutFlowInterface::class),
      Argument::type('string')
    )->willReturn(FALSE);
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
      $chain_checkout_validator->add($container->get($id));
    }

    $result = $chain_checkout_validator->allowed(
      $this->prophesize(OrderInterface::class)->reveal(),
      $this->prophesize(PluginCheckoutFlowInterface::class)->reveal(),
      CheckoutValidatorInterface::PHASE_ENTER
    );
    $this->assertTrue($result);


    $chain_checkout_validator = new ChainCheckoutValidator();
    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $validators = [
      'commerce_checkout.default_checkout_validator' => -100,
      'commerce_checkout.mocked_checkout_validator' => 100,
    ];
    arsort($validators, SORT_NUMERIC);
    foreach ($validators as $id => $priority) {
      $chain_checkout_validator->add($container->get($id));
    }
    $result = $chain_checkout_validator->allowed(
      $this->prophesize(OrderInterface::class)->reveal(),
      $this->prophesize(PluginCheckoutFlowInterface::class)->reveal(),
      CheckoutValidatorInterface::PHASE_ENTER
    );
    $this->assertFalse($result);
  }

  /**
   * Tests the checkout order manager throws exception when validation fails.
   */
  public function testValidationException() {
    $container = new ContainerBuilder();
    $chain_checkout_validator = new ChainCheckoutValidator();
    $container->set('commerce_checkout.default_checkout_validator', new DefaultCheckoutValidator());
    $mock_checkout_validator = $this->prophesize(CheckoutValidatorInterface::class);
    $mock_checkout_validator->allowed(
      Argument::type(OrderInterface::class),
      Argument::type(PluginCheckoutFlowInterface::class),
      Argument::type('string')
    )->willReturn(FALSE);
    $container->set('commerce_checkout.mocked_checkout_validator', $mock_checkout_validator->reveal());
    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $validators = [
      'commerce_checkout.default_checkout_validator' => -100,
      'commerce_checkout.mocked_checkout_validator' => 100,
    ];
    arsort($validators, SORT_NUMERIC);
    foreach ($validators as $id => $priority) {
      $chain_checkout_validator->add($container->get($id));
    }

    $this->setExpectedException(
      CheckoutValidationException::class,
      'Checkout flow validation_test_flow failed to validate with order 1234'
    );


    $chain_checkout_flow_resolver = $this->prophesize(ChainCheckoutFlowResolverInterface::class);
    $checkout_flow = $this->prophesize(EntityCheckoutFlowInterface::class);
    $checkout_flow->getPlugin()->willReturn(
      $this->prophesize(PluginCheckoutFlowInterface::class)->reveal()
    );
    $checkout_flow->id()->willReturn('validation_test_flow');
    $chain_checkout_flow_resolver->resolve(Argument::type(OrderInterface::class))->willReturn($checkout_flow->reveal());

    $checkout_order_manager = new CheckoutOrderManager(
      $chain_checkout_flow_resolver->reveal(),
      $chain_checkout_validator
    );

    $order = $this->prophesize(OrderInterface::class);
    $order->id()->willReturn(1234);
    $checkout_flow_field_item_list = $this->prophesize(FieldItemListInterface::class);
    $checkout_flow_field_item_list->isEmpty()->willReturn(TRUE);
    $order->get('checkout_flow')->willReturn($checkout_flow_field_item_list->reveal());
    $checkout_order_manager->getCheckoutFlow($order->reveal());

  }

}
