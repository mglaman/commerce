<?php

namespace Drupal\Tests\commerce_checkout\Unit;

use Drupal\commerce_checkout\CheckoutGuard\ChainCheckoutGuard;
use Drupal\commerce_checkout\CheckoutGuard\CheckoutGuardInterface;
use Drupal\commerce_checkout\CheckoutGuard\DefaultCheckoutGuard;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the checkout guard system.
 *
 * @group commerce
 */
class CheckoutGuardTest extends UnitTestCase {

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
   * Test checkout guards.
   */
  public function testCheckoutGuard() {
    $container = new ContainerBuilder();
    $chain_checkout_guard = new ChainCheckoutGuard();
    $container->set('commerce_checkout.default_checkout_guard', new DefaultCheckoutGuard());
    $mock_checkout_guard = $this->prophesize(CheckoutGuardInterface::class);
    $mock_checkout_guard->allowed(
      Argument::type(OrderInterface::class),
      Argument::type(CheckoutFlowInterface::class),
      Argument::type('string')
    )->willReturn(FALSE);
    $container->set('commerce_checkout.mocked_checkout_guard', $mock_checkout_guard->reveal());

    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $guards = [
      'commerce_checkout.default_checkout_guard' => -100,
      // First set it to a lower priority, ensure default is TRUE.
      'commerce_checkout.mocked_checkout_guard' => -200,
    ];
    arsort($guards, SORT_NUMERIC);
    foreach ($guards as $id => $priority) {
      $chain_checkout_guard->add($container->get($id));
    }

    $result = $chain_checkout_guard->allowed(
      $this->prophesize(OrderInterface::class)->reveal(),
      $this->prophesize(CheckoutFlowInterface::class)->reveal(),
      CheckoutGuardInterface::PHASE_START
    );
    $this->assertTrue($result);


    $chain_checkout_guard = new ChainCheckoutGuard();
    // Mimic how the container would add the services.
    // @see \Drupal\Core\DependencyInjection\Compiler\TaggedHandlersPass::process
    $guards = [
      'commerce_checkout.default_checkout_guard' => -100,
      'commerce_checkout.mocked_checkout_guard' => 100,
    ];
    arsort($guards, SORT_NUMERIC);
    foreach ($guards as $id => $priority) {
      $chain_checkout_guard->add($container->get($id));
    }
    $result = $chain_checkout_guard->allowed(
      $this->prophesize(OrderInterface::class)->reveal(),
      $this->prophesize(CheckoutFlowInterface::class)->reveal(),
      CheckoutGuardInterface::PHASE_START
    );
    $this->assertFalse($result);
  }

}
