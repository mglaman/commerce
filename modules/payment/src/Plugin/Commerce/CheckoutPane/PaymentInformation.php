<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethods;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_information",
 *   label = @Translation("Payment information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class PaymentInformation extends CheckoutPaneBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new BillingInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway;
    if (!$payment_gateway) {
      return '';
    }

    $payment_method = $this->order->payment_method;
    if ($payment_gateway instanceof SupportsStoredPaymentMethods && $payment_method) {
      $view_builder = $this->entityTypeManager->getViewBuilder('commerce_payment_method');
      $payment_method_view = $view_builder->view($payment_method, 'default');
      $summary = $this->renderer->render($payment_method_view);
    }
    else {
      $billing_profile = $this->order->getBillingProfile();
      $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
      $profile_view = $profile_view_builder->view($billing_profile, 'default');
      $summary = $payment_gateway->getPlugin()->getDisplayLabel();
      $summary .= $this->renderer->render($profile_view);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_method');
    $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');

    $payment_gateways = $payment_gateway_storage->loadMultiple();
    $options = [];
    $default_option = NULL;
    foreach ($payment_gateways as $payment_gateway) {
      $payment_method_types = $payment_gateway->getPlugin()->getPaymentMethodTypes();
      foreach ($payment_method_types as $payment_method_type) {
        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface $payment_method_type */
        $payment_methods = $payment_method_storage->loadByProperties([
          'type' => $payment_method_type->getPluginId(),
          'payment_gateway' => $payment_gateway->id(),
          'uid' => $this->order->getOwnerId(),
        ]);
        $options = [];
        foreach ($payment_methods as $payment_method) {
          $options[$payment_method->id()] = $payment_method->label();
        }
        $options['new'] = $payment_method_type->getCreateLabel();
      }
    }

    $pane_form['payment_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment method'),
      '#options' => $options,
      '#default_value' => $default_option,
    ];
    $payment_method = $payment_method_storage->create([
      'type' => 'credit_card',
      'payment_gateway' => $payment_gateway->id(),
      'uid' => $this->order->getOwnerId(),
    ]);
    $plugin_form = $payment_gateway->getForm('add-payment-method');
    $pane_form['add'] = $plugin_form->buildForm($pane_form['add'], $form_state, $payment_method);

    return $pane_form;
  }

  /**
   * Loads the stored payment methods for the given payment gateway.
   *
   * @todo Move this method to the payment method storage.
   *
   * @param array $payment_gateway
   *   The payment gateway.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentMethodInterface[]
   */
  protected function loadPaymentMethods(array $payment_gateway) {
    if (!($payment_gateway instanceof SupportsStoredPaymentMethods)) {
      return [];
    }

    $query = $this->entityTypeManager->getStorage('commerce_payment_method')->getQuery();
    $query
      ->condition('payment_gateway', $payment_gateway->id())
      ->condition('reusable', TRUE)
      ->sort('created', 'DESC');
    $result = $query->execute();

  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {

  }

}
