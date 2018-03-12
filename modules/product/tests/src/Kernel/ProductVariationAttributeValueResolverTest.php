<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the product variation title generation.
 *
 * @group commerce
 */
class ProductVariationAttributeValueResolverTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
    'language',
    'content_translation',
  ];

  /**
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $colorAttributes;

  /**
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $sizeAttributes;

  /**
   * @var \Drupal\commerce_product\ProductVariationAttributeValueResolverInterface
   */
  protected $resolver;

  /**
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig(['commerce_product']);
    $this->attributeFieldManager = $this->container->get('commerce_product.attribute_field_manager');
    $this->resolver = $this->container->get('commerce_product.variation_attribute_value_resolver');

    $variation_type = ProductVariationType::load('default');

    // Create attributes.
    $color_attributes = $this->createAttributeSet($variation_type, 'color', [
      'red' => 'Red',
      'blue' => 'Blue',
    ]);
    $size_attributes = $this->createAttributeSet($variation_type, 'size', [
      'small' => 'Small',
      'medium' => 'Medium',
      'large' => 'Large',
    ]);

    $this->colorAttributes = $color_attributes;
    $this->sizeAttributes = $size_attributes;
  }

  public function testResolveWithNoAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $resolved_variation = $this->resolver->resolve($product->getVariations());
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());
  }

  public function testResolveWithWithOneAttribute() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
    ]);
    $this->assertEquals($variations[3]->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_size' => $this->sizeAttributes['large']->id()
    ]);
    // This fails because we target in a cascading logic check. The first
    // attribute must pass a value check, then the second. In this case
    // the default variation was returned.
    $this->assertNotEquals($variations[2]->id(), $resolved_variation->id());
  }

  /**
   * @group debug
   */
  public function testResolveWithWithTwoAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['red']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id()
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id()
    ]);
    // An invalid arrangement was passed, so the default variation is resolved.
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());
  }

  protected function generateThreeByTwoScenario() {
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $attribute_values_matrix = [
      ['red', 'small'],
      ['red', 'medium'],
      ['red', 'large'],
      ['blue', 'small'],
      ['blue', 'medium'],
    ];
    $variations = [];
    foreach ($attribute_values_matrix as $key => $value) {
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => $this->randomMachineName(),
        'price' => [
          'number' => 999,
          'currency_code' => 'USD',
        ],
        'attribute_color' => $this->colorAttributes[$value[0]],
        'attribute_size' => $this->sizeAttributes[$value[1]],
      ]);
      $variation->save();
      $variations[] = $variation;
      $product->addVariation($variation);
    }
    $product->save();

    return $product;
  }

  /**
   * Creates an attribute field and set of attribute values.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationTypeInterface $variation_type
   *   The variation type.
   * @param string $name
   *   The attribute field name.
   * @param array $options
   *   Associative array of key name values. [red => Red].
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface[]
   *   Array of attribute entities.
   */
  protected function createAttributeSet(ProductVariationTypeInterface $variation_type, $name, array $options) {
    $attribute = ProductAttribute::create([
      'id' => $name,
      'label' => ucfirst($name),
    ]);
    $attribute->save();
    $this->attributeFieldManager->createField($attribute, $variation_type->id());

    $attribute_set = [];
    foreach ($options as $key => $value) {
      $attribute_set[$key] = $this->createAttributeValue($name, $value);
    }

    return $attribute_set;
  }

  /**
   * Creates an attribute value.
   *
   * @param string $attribute
   *   The attribute ID.
   * @param string $name
   *   The attribute value name.
   *
   * @return \Drupal\commerce_product\Entity\ProductAttributeValueInterface
   *   The attribute value entity.
   */
  protected function createAttributeValue($attribute, $name) {
    $attribute_value = ProductAttributeValue::create([
      'attribute' => $attribute,
      'name' => $name,
    ]);
    $attribute_value->save();

    return $attribute_value;
  }


}
