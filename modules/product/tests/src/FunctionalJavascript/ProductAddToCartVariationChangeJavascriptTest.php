<?php

namespace Drupal\Tests\commerce_product\FunctionalJavascript;

use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_product\Functional\ProductBrowserTestBase;
use Drupal\commerce_price\Price;

/**
 * Consequent product variation field view mode in add to cart form.
 *
 * @group commerce
 */
class ProductAddToCartVariationChangeJavascriptTest extends ProductBrowserTestBase {

  use JavascriptTestTrait;

  /**
   * The 'low' product variant.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $low_bulb;

  /**
   * The 'high' product variant.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariation
   */
  protected $high_bulb;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_cart',
    'commerce_product_add_to_cart_variation_change',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $defaultCurrency = $this->store->getDefaultCurrencyCode();

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
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'Bulb',
    ]);
    $product->setStores([$this->store]);

    // Create test commerce_product_variations.
    $low_bulb = $this->createEntity('commerce_product_variation', [
      'title' => 'Bulb - Low',
      'type' => 'default',
      'sku' => 'bulb-low',
      'product_id' => $product->id(),
    ]);
    $low_bulb
      ->set('price', new Price('10', $defaultCurrency))
      ->set('attribute_brightness', $low->id())
      ->save();

    $high_bulb = $this->createEntity('commerce_product_variation', [
      'title' => 'Bulb - High',
      'type' => 'default',
      'sku' => 'bulb-high',
      'product_id' => $product->id(),
    ]);
    $high_bulb
      ->set('price', new Price('20', $defaultCurrency))
      ->set('attribute_brightness', $high->id())
      ->save();

    $product->setVariations([$low_bulb, $high_bulb])->save();

    $this->product = $product;
    $this->low_bulb = $low_bulb;
    $this->high_bulb = $high_bulb;
  }

  /**
   * Tests managing product attribute values.
   */
  public function testAddToCartVariationChange() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();
    $page = $session->getPage();

    $product_id = $this->product->id();
    $product_url = $this->product->toUrl()->toString();
    $low_bulb_id = $this->low_bulb->id();
    $low_bulb_price = implode(' ', [
      number_format($this->low_bulb->getPrice()->getNumber(), 2, '.', ','),
      $this->low_bulb->getPrice()->getCurrencyCode(),
    ]);
    $high_bulb_id = $this->high_bulb->id();
    $high_bulb_price = implode(' ', [
      number_format($this->high_bulb->getPrice()->getNumber(), 2, '.', ','),
      $this->high_bulb->getPrice()->getCurrencyCode(),
    ]);
    $price_field_selector = '.product--variation-field--variation_price__' . $product_id;

    $this->drupalGet($product_url);
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
