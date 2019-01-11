<?php

namespace Drupal\commerce_payment_test\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\OffsiteRedirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "test_double_save_offsite_redirect",
 *   label = "Test Double Save (Off-site redirect)",
 *   display_label = "Example",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_example\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class TestDoubleSaveOffsiteRedirect extends OffsiteRedirect {

  /**
   * {@inheritdoc}
   *
   * Adds data to the order and saves it. This should not cause a double trigger
   * of the "placed" event. An order saved in this method should not cause
   * errors when the order is finally saved in PaymentCheckoutController::returnPage.
   *
   * @see \Drupal\commerce_payment\Controller\PaymentCheckoutController::returnPage
   * @link https://www.drupal.org/project/commerce/issues/3011667
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $order->setData('test_double_save_offsite_redirect', [
      'test' => TRUE,
    ]);
    $order->save();
    parent::onReturn($order, $request);
  }

}
