<?php

namespace Drupal\commerce_cart_big_pipe\Form;

use Drupal\commerce_cart\Form\AddToCartForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a slow to build add to cart form, to test streaming.
 */
class BigPipeAddToCartForm extends AddToCartForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    sleep(1);
    return parent::buildForm($form, $form_state);
  }

}
