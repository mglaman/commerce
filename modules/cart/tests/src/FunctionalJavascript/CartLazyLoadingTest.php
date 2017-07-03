<?php

namespace Drupal\Tests\commerce_cart\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_cart\Functional\CartBrowserTestBase;

/**
 * Tests the add to cart form with Big Pipe and caching enabled.
 *
 * @group commerce
 * @group commerce_cart
 */
class CartLazyLoadingTest extends CartBrowserTestBase {

  use JavascriptTestTrait;

  public static $modules = [
    'page_cache',
    'dynamic_page_cache',
    'big_pipe',
    'big_pipe_test',
    'commerce_cart_test',
    'commerce_cart_big_pipe',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductInterface[]
   */
  protected $products = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // @see \Drupal\big_pipe\Tests\BigPipeTest::setUp.
    $this->maximumMetaRefreshCount = 0;
    for ($i = 1; $i < 5; $i++) {
      // Create a product variation.
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => (string) 3 * $i,
          'currency_code' => 'USD',
        ],
      ]);
      $this->products[] = $this->createEntity('commerce_product', [
        'type' => 'default',
        'title' => $this->randomMachineName(),
        'stores' => [$this->store],
        'variations' => [$variation],
      ]);
    }
  }

  /**
   * Tests the add to cart form on a product page when lazy loading occurs.
   */
  public function testAddToCartWithBigPipe() {
    $this->drupalLogout();
    $test_product = $this->products[0];
    // Hit once to prime cache.
    $this->drupalGet($test_product->toUrl());

    // Revisit and assert no session, full cache.
    $this->drupalGet($test_product->toUrl('canonical', ['query' => ['trigger_session' => 1]]));
    $this->submitForm([], 'Add to cart');
    $this->assertSession()->pageTextContains(sprintf('%s added to your cart.', $test_product->getDefaultVariation()->getTitle()));
  }

  /**
   * Tests that the form IDs are unique.
   */
  public function testUniqueAddToCartFormIds() {
    $this->drupalGet('/test-multiple-cart-forms', ['query' => ['trigger_session' => 1]]);
    $seen_ids = [];
    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $this->assertCount(5, $forms);
    foreach ($forms as $form) {
      $this->assertFalse(in_array($form->getAttribute('id'), $seen_ids));
      $seen_ids[] = $form->getAttribute('id');
    }

    // Submit the form, ensure form rebuilds and proper product added.
    $this->submitForm([], 'Add to cart', $forms[2]->getAttribute('id'));
    $seen_ids = [];
    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $this->assertCount(5, $forms);
    foreach ($forms as $form) {
      $this->assertFalse(in_array($form->getAttribute('id'), $seen_ids));
      $seen_ids[] = $form->getAttribute('id');
    }
    $this->assertSession()->pageTextContains(sprintf('%s added to your cart.', $this->products[1]->getDefaultVariation()->getTitle()));
  }

  /**
   * Tests that a page with multiple add to cart forms works properly.
   */
  public function testMultipleCartsOnPage() {
    // View of rendered products, each containing an add to cart form.
    $this->drupalGet('/test-multiple-cart-forms', ['query' => ['trigger_session' => 1]]);
    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $this->assertEquals(5, count($forms));
    $this->submitForm([], 'Add to cart', $forms[2]->getAttribute('id'));

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertEquals(new Price('6', 'USD'), $order_items[0]->getTotalPrice());

    // View of fields, one of which is the variations field
    // rendered via the "commerce_add_to_cart" formatter.
    $this->drupalGet('/test-multiple-cart-forms-fields');
    /** @var \Behat\Mink\Element\NodeElement[] $forms */
    $forms = $this->getSession()->getPage()->findAll('css', '.commerce-order-item-add-to-cart-form');
    $this->assertEquals(5, count($forms));
    $this->submitForm([], 'Add to cart', $forms[3]->getAttribute('id'));

    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertEquals(2, count($order_items));
    $this->assertEquals(new Price('9', 'USD'), $order_items[1]->getTotalPrice());
  }

}
