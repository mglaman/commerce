<?php

namespace Drupal\commerce_checkout;

use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorConstraintList;
use Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface;
use Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Manages checkout flows for orders.
 */
class CheckoutOrderManager implements CheckoutOrderManagerInterface {

  /**
   * Array of checkout validators.
   *
   * @var \Drupal\commerce_checkout\CheckoutValidator\CheckoutValidatorInterface[]
   */
  protected $validators = [];

  /**
   * The chain checkout flow resolver.
   *
   * @var \Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface
   */
  protected $chainCheckoutFlowResolver;

  /**
   * Constructs a new CheckoutOrderManager object.
   *
   * @param \Drupal\commerce_checkout\Resolver\ChainCheckoutFlowResolverInterface $chain_checkout_flow_resolver
   *   The chain checkout flow resolver.
   */
  public function __construct(ChainCheckoutFlowResolverInterface $chain_checkout_flow_resolver) {
    $this->chainCheckoutFlowResolver = $chain_checkout_flow_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(OrderInterface $order, AccountInterface $account, $phase = CheckoutValidatorInterface::PHASE_ENTER) {
    $constraints = new CheckoutValidatorConstraintList();
    foreach ($this->validators as $validator) {
      $constraints->addAll($validator->validate($order, $account, $phase));
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function addValidator(CheckoutValidatorInterface $validator) {
    $this->validators[] = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckoutFlow(OrderInterface $order) {
    if ($order->get('checkout_flow')->isEmpty()) {
      $checkout_flow = $this->chainCheckoutFlowResolver->resolve($order);
      $order->set('checkout_flow', $checkout_flow);
      $order->save();
    }

    return $order->get('checkout_flow')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckoutStepId(OrderInterface $order, $requested_step_id = NULL) {
    // Customers can't edit orders that have already been placed.
    if ($order->getState()->value != 'draft') {
      return 'complete';
    }
    $checkout_flow = $this->getCheckoutFlow($order);
    $available_step_ids = array_keys($checkout_flow->getPlugin()->getVisibleSteps());
    $selected_step_id = $order->get('checkout_step')->value;
    $selected_step_id = $selected_step_id ?: reset($available_step_ids);
    if (empty($requested_step_id) || $requested_step_id == $selected_step_id) {
      return $selected_step_id;
    }

    if (in_array($requested_step_id, $available_step_ids)) {
      // Allow access to a previously completed step.
      $requested_step_index = array_search($requested_step_id, $available_step_ids);
      $selected_step_index = array_search($selected_step_id, $available_step_ids);
      if ($requested_step_index <= $selected_step_index) {
        $selected_step_id = $requested_step_id;
      }
    }

    return $selected_step_id;
  }

}
