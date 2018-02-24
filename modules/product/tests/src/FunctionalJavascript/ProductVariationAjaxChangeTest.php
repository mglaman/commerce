<?php

namespace Drupal\Tests\commerce_product\FunctionalJavascript;

use Drupal\commerce_price\Entity\Currency;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_product\Functional\ProductBrowserTestBase;
use Drupal\commerce_price\Price;

/**
 * Consequent product variation field view mode in add to cart form.
 *
 * @group commerce
 */
class ProductVariationAjaxChangeTest extends ProductBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The 'low' product variant.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $lowBulb;

  /**
   * The 'high' product variant.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $highBulb;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_cart',
    'commerce_product_variation_ajax_change_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test attribute values.
    $low = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => 'brightness',
      'name' => 'Low',
    ]);
    $high = $this->createEntity('commerce_product_attribute_value', [
      'attribute' => 'brightness',
      'name' => 'High',
    ]);

    // Create test commerce_product.
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Bulb',
      'stores' => [$this->store],
    ]);

    // Create test commerce_product_variations.
    $this->lowBulb  = $this->createEntity('commerce_product_variation', [
      'title' => 'Bulb - Low',
      'type' => 'default',
      'sku' => 'bulb-low',
      'product_id' => $this->product->id(),
      'price' => [
        'number' => 10,
        'currency_code' => $this->store->getDefaultCurrencyCode(),
      ],
      'attribute_brightness' => $low->id()
    ]);

    $this->highBulb = $this->createEntity('commerce_product_variation', [
      'title' => 'Bulb - High',
      'type' => 'default',
      'sku' => 'bulb-high',
      'product_id' => $this->product->id(),
      'price' => [
        'number' => 20,
        'currency_code' => $this->store->getDefaultCurrencyCode(),
      ],
      'attribute_brightness' => $high->id()
    ]);

    $this->product->setVariations([
      $this->lowBulb,
      $this->highBulb
    ])->save();
  }

  /**
   * Tests managing product attribute values.
   *
   * The `commerce_product_variation.default.full` configuration uses the
   * `commerce_price_plain` formatter, but the default view mode still uses the
   * `commerce_price_default` formatter. The AJAX refresh should return currency
   *  in the plain format of ##.00 USD and not $##.00.
   */
  public function testAddToCartVariationChange() {
    $this->drupalGet($this->product->toUrl());

    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();
    $render = $this->container->get('renderer');

    $low_bulb_id = $this->lowBulb->id();
    $low_bulb_price = [
      '#theme' => 'commerce_price_plain',
      '#number' => $this->lowBulb->getPrice()->getNumber(),
      '#currency' => Currency::load($this->lowBulb->getPrice()->getCurrencyCode()),
    ];
    $low_bulb_price = trim($render->renderPlain($low_bulb_price));
    $high_bulb_id = $this->highBulb->id();
    $high_bulb_price = [
      '#theme' => 'commerce_price_plain',
      '#number' => $this->highBulb->getPrice()->getNumber(),
      '#currency' => Currency::load($this->highBulb->getPrice()->getCurrencyCode()),
    ];
    $high_bulb_price = trim($render->renderPlain($high_bulb_price));
    $price_field_selector = '.product--variation-field--variation_price__' . $this->product->id();

    $assert_session->elementExists('css', $price_field_selector);
    $assert_session->elementTextContains('css', $price_field_selector . ' .field__item', $low_bulb_price);
    $assert_session->selectExists('purchased_entity[0][attributes][attribute_brightness]');
    $assert_session->optionExists('purchased_entity[0][attributes][attribute_brightness]', $low_bulb_id);
    $assert_session->optionExists('purchased_entity[0][attributes][attribute_brightness]', $high_bulb_id);
    $assert_session->fieldValueEquals('purchased_entity[0][attributes][attribute_brightness]', $low_bulb_id);
    $page->selectFieldOption('purchased_entity[0][attributes][attribute_brightness]', $high_bulb_id);
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', $price_field_selector);
    $assert_session->elementTextContains('css', $price_field_selector . ' .field__item', $high_bulb_price);

    $page->selectFieldOption('purchased_entity[0][attributes][attribute_brightness]', $low_bulb_id);
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->elementExists('css', $price_field_selector);
    $assert_session->elementTextContains('css', $price_field_selector . ' .field__item', $low_bulb_price);
  }

}
