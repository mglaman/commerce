<?php

namespace Drupal\commerce_payment_test\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\OffsiteRedirect;

/**
 * Provides the test off-site Redirect payment gateway.
 *
 * Copy of the example offsite redirect module with an altered display_label and
 * dummy PayPal payment method type support.
 *
 * @CommercePaymentGateway(
 *   id = "test_offsite_redirect",
 *   label = "Test Offsite (Off-site redirect)",
 *   display_label = "Test Offsite",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payment_example\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card", "paypal"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class TestOffsiteRedirect extends OffsiteRedirect {}
