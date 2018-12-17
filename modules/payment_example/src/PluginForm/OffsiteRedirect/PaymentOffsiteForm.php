<?php

namespace Drupal\commerce_payment_example\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $redirect_method = $payment_gateway_plugin->getConfiguration()['redirect_method'];
    $remove_js = ($redirect_method == 'post_manual');
    if (in_array($redirect_method, ['post', 'post_manual'])) {
      // If an off-site payment gateway supports multiple payment methods types,
      // the payment method type selected will be stored in the order data.
      // This allows passing additional parameters to the payment gateway
      // service when generating the redirect URL.
      $order = $payment->getOrder();
      $payment_method_type = $order->getData('payment_method_type');
      $redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_post', [], [
        'query' => [
          'payment_method_type' => $payment_method_type
        ]
      ])->toString();
      $redirect_method = 'post';
    }
    else {
      // Gateways that use the GET redirect method usually perform an API call
      // that prepares the remote payment and provides the actual url to
      // redirect to. Any params received from that API call that need to be
      // persisted until later payment creation can be saved in $order->data.
      // Example: $order->setData('my_gateway', ['test' => '123']), followed
      // by an $order->save().
      $order = $payment->getOrder();
      // Simulate an API call failing and throwing an exception, for test purposes.
      // See PaymentCheckoutTest::testFailedCheckoutWithOffsiteRedirectGet().
      if ($order->getBillingProfile()->get('address')->family_name == 'FAIL') {
        throw new PaymentGatewayException('Could not get the redirect URL.');
      }
      $redirect_url = Url::fromRoute('commerce_payment_example.dummy_redirect_302', [], ['absolute' => TRUE])->toString();
    }
    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
      'total' => $payment->getAmount()->getNumber(),
    ];

    $form = $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
    if ($remove_js) {
      // Disable the javascript that auto-clicks the Submit button.
      unset($form['#attached']['library']);
    }

    return $form;
  }

}
