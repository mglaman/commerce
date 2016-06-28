<?php

namespace Drupal\Tests\commerce_cart\Functional;

use Blackfire\Bridge\PhpUnit\TestCaseTrait as BlackfireTrait;
use Blackfire\Profile\Configuration;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the add to cart form.
 *
 * @group commerce
 */
class AddToCartFormTest extends CartBrowserTestBase {

  use BlackfireTrait;

  /**
   * Test adding a product to the cart.
   */
  public function testProductAddToCartForm() {
    // Confirm that the initial add to cart submit works.
    $this->postAddToCart($this->variation->getProduct());
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertLineItemInOrder($this->variation, $line_items[0]);

    // Confirm that the second add to cart submit increments the quantity
    // of the first line item..
    $this->postAddToCart($this->variation->getProduct());
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    \Drupal::entityTypeManager()->getStorage('commerce_line_item')->resetCache();
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertTrue(count($line_items) == 1, 'No additional line items were created');
    $this->assertLineItemInOrder($this->variation, $line_items[0], 2);
  }

  /**
   * Tests add to cart performance.
   *
   * @group blackfire
   */
  public function testAddToCartPerformance() {
    $config = new Configuration();
    $config->assert('main.wall_time < 1s', 'Wall time');

    $this->drupalGet('product/' . $this->variation->getProduct()->id());
    $this->assertBlackfire($config, function () {
      $this->submitForm([], 'Add to cart');
    });
  }

  /**
   * Tests ability to expose line item fields on the add to cart form.
   */
  public function testExposedLineItemFields() {
    /** @var \Drupal\Core\Entity\Entity\EntityFormDisplay $line_item_form_display */
    $line_item_form_display = EntityFormDisplay::load('commerce_line_item.product_variation.add_to_cart');
    $line_item_form_display->setComponent('quantity', [
      'type' => 'number',
    ]);
    $line_item_form_display->save();
    // Get the existing product page and submit Add to cart form.
    $this->postAddToCart($this->variation->getProduct(), [
      'quantity[0][value]' => 3,
    ]);
    // Check if the quantity was increased for the existing line item.
    $this->cart = Order::load($this->cart->id());
    $line_items = $this->cart->getLineItems();
    $this->assertLineItemInOrder($this->variation, $line_items[0], 3);
  }

  /**
   * Tests that an attribute field is disabled if there's only one value.
   */
  public function testProductAttributeDisabledIfOne() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
    ]);

    // Reload the variation since we have new fields.
    $this->variation = ProductVariation::load($this->variation->id());
    $product = $this->variation->getProduct();

    // Update first variation to have the attribute's value.
    $this->variation->attribute_size = $size_attributes['small']->id();
    $this->variation->attribute_color = $color_attributes['red']->id();
    $this->variation->save();

    $attribute_values_matrix = [
      ['medium', 'red'],
      ['large', 'red'],
    ];
    $variations = [
      $this->variation,
    ];
    // Generate variations off of the attributes values matrix.
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'amount' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_size' => $size_attributes[$value[0]]->id(),
        'attribute_color' => $color_attributes[$value[1]]->id(),
      ]);
      $variations[] = $variation;
      $product->variations->appendItem($variation);
    }
    $product->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->elementExists('xpath', '//select[@id="edit-purchased-entity-0-attributes-attribute-color" and @disabled]');
  }

  /**
   * Tests that the add to cart form renders an attribute entity.
   */
  public function testRenderedAttributeElement() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $color_attribute_values = $this->createAttributeSet($variation_type, 'color', [
      'cyan' => 'Cyan',
      'magenta' => 'Magenta',
    ], TRUE);
    $color_attribute_values['cyan']->set('rendered_test', 'Cyan (Rendered)')->save();
    $color_attribute_values['cyan']->save();
    $color_attribute_values['magenta']->set('rendered_test', 'Magenta (Rendered)')->save();
    $color_attribute_values['magenta']->save();

    $color_attribute = ProductAttribute::load($color_attribute_values['cyan']->getAttributeId());

    $variation1 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['cyan'],
    ]);
    $variation2 = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => $this->randomMachineName(),
      'price' => [
        'amount' => 999,
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_attribute_values['magenta'],
    ]);
    $product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [$variation1, $variation2],
    ]);

    $this->drupalGet($product->toUrl());
    $this->assertAttributeExists('purchased_entity[0][attributes][attribute_color]', $color_attribute_values['cyan']->id());

    $color_attribute->set('elementType', 'commerce_product_rendered_attribute')->save();

    $this->drupalGet($product->toUrl());
    $this->assertSession()->pageTextContains('Cyan (Rendered)');
    $this->assertSession()->pageTextContains('Magenta (Rendered)');
  }

  /**
   * Tests the behavior of optional product attributes.
   */
  public function testOptionalProductAttribute() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type */
    $variation_type = ProductVariationType::load($this->variation->bundle());

    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
    ]);
    // Make the color attribute optional.
    $color_field = \Drupal::entityManager()->getStorage('field_config')->load('commerce_product_variation.default.attribute_color');
    $color_field->setRequired(TRUE);
    $color_field->save();

    // Reload the variation since we have new fields.
    $this->variation = ProductVariation::load($this->variation->id());
    $product = $this->variation->getProduct();
    // Update the first variation to have the attribute values.
    $this->variation->attribute_size = $size_attributes['small']->id();
    $this->variation->attribute_color = $color_attributes['red']->id();
    $this->variation->save();

    $attribute_values_matrix = [
      ['medium', 'red'],
      ['large', 'red'],
    ];
    $variations = [
      $this->variation,
    ];
    // Generate variations off of the attributes values matrix.
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = $this->createEntity('commerce_product_variation', [
        'type' => $variation_type->id(),
        'sku' => $this->randomMachineName(),
        'price' => [
          'amount' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_size' => $size_attributes[$value[0]]->id(),
        'attribute_color' => $color_attributes[$value[1]]->id(),
      ]);
      $variations[] = $variation;
      $product->variations->appendItem($variation);
    }
    $product->save();

    // The color element should be required because each variation has a color.
    $this->drupalGet($product->toUrl());
    $this->assertSession()->fieldExists('purchased_entity[0][attributes][attribute_size]');
    $this->assertSession()->elementExists('xpath', '//select[@id="edit-purchased-entity-0-attributes-attribute-color" and @required]');

    // Remove the color value from all variations.
    // The color element should now be hidden.
    foreach ($variations as $variation) {
      $variation->attribute_color = NULL;
      $this->variation->save();
    }
    $this->drupalGet($product->toUrl());
    $this->assertSession()->fieldExists('purchased_entity[0][attributes][attribute_size]');
    $this->assertSession()->fieldNotExists('purchased_entity[0][attributes][attribute_color]');
  }

}
