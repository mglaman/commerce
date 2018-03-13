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
   * The color attributes values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $colorAttributes;

  /**
   * The size attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $sizeAttributes;

  /**
   * The variation attribute value resolver.
   *
   * @var \Drupal\commerce_product\ProductVariationAttributeValueResolverInterface
   */
  protected $resolver;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * The RAM attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $ramAttributes;

  /**
   * The Disk 1 attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $disk1Attributes;

  /**
   * The Disk 2 attribute values.
   *
   * @var \Drupal\commerce_product\Entity\ProductAttributeValue[]
   */
  protected $disk2Attributes;

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

    $ram_attributes = $this->createAttributeSet($variation_type, 'ram', [
      '4gb' => '4GB',
      '8gb' => '8GB',
      '16gb' => '16GB',
      '32gb' => '32GB',
    ]);

    $disk1_attributes = $this->createAttributeSet($variation_type, 'disk1', [
      '1tb' => '1TB',
      '2tb' => '2TB',
      '3tb' => '3TB',
    ]);
    $disk2_attributes = $this->createAttributeSet($variation_type, 'disk2', [
      '1tb' => '1TB',
      '2tb' => '2TB',
      '3tb' => '3TB',
    ]);

    $this->colorAttributes = $color_attributes;
    $this->sizeAttributes = $size_attributes;

    $this->ramAttributes = $ram_attributes;
    $this->disk1Attributes = $disk1_attributes;
    $this->disk2Attributes = $disk2_attributes;
  }

  /**
   * Tests that if no attributes are passed, the default variation is returned.
   */
  public function testResolveWithNoAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $resolved_variation = $this->resolver->resolve($product->getVariations());
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($product->getVariations(), [
      'attribute_color' => '',
    ]);
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($product->getVariations(), [
      'attribute_color' => '',
      'attribute_size' => '',
    ]);
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());
  }

  /**
   * Tests that if one attribute passed, the proper variation is returned.
   */
  public function testResolveWithWithOneAttribute() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
    ]);
    $this->assertEquals($variations[3]->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());
  }

  /**
   * Tests that if two attributes are passed, the proper variation is returned.
   */
  public function testResolveWithWithTwoAttributes() {
    $product = $this->generateThreeByTwoScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['red']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    // An invalid arrangement was passed, so the default variation is resolved.
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => '',
      'attribute_size' => $this->sizeAttributes['large']->id(),
    ]);
    // A missing attribute was passed for first option.
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_color' => $this->colorAttributes['blue']->id(),
      'attribute_size' => $this->sizeAttributes['small']->id(),
    ]);
    // An empty second option defaults to first variation option.
    $this->assertEquals($variations[3]->id(), $resolved_variation->id());
  }

  /**
   * Tests optional attributes.
   */
  public function testResolveWithOptionalAttributes() {
    $product = $this->generateThreeByTwoOptionalScenario();
    $variations = $product->getVariations();

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
    ]);
    $this->assertEquals($variations[1]->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
      'attribute_disk1' => $this->disk1Attributes['1tb']->id(),
      'attribute_disk2' => $this->disk2Attributes['1tb']->id(),
    ]);
    $this->assertEquals($variations[2]->id(), $resolved_variation->id());

    $resolved_variation = $this->resolver->resolve($variations, [
      'attribute_ram' => $this->ramAttributes['16gb']->id(),
      'attribute_disk1' => $this->disk1Attributes['1tb']->id(),
      'attribute_disk2' => $this->disk2Attributes['2tb']->id(),
    ]);
    $this->assertEquals($product->getDefaultVariation()->id(), $resolved_variation->id());
  }

  /**
   * Generates a three by two secenario.
   *
   * This generates a product and variations in 3x2 scenario. There are three
   * sizes and two colors. Missing one color option.
   *
   * [ RS, RM, RL ]
   * [ BS, BM, X  ]
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product.
   */
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
   * Generates a three by two (optional) secenario.
   *
   * This generates a product and variations in 3x2 scenario.
   *
   * https://www.drupal.org/project/commerce/issues/2730643#comment-11216983
   *
   * [ 8GBx1TB,    X        , X ]
   * [    X   , 16GBx1TB    , X ]
   * [    X   , 16GBx1TBx1TB, X ]
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product.
   */
  protected function generateThreeByTwoOptionalScenario() {
    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomMachineName(),
      'stores' => [$this->store],
      'variations' => [],
    ]);
    $attribute_values_matrix = [
      ['8gb', '1tb', ''],
      ['16gb', '1tb', ''],
      ['16gb', '1tb', '1tb'],
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
        'attribute_ram' => $this->ramAttributes[$value[0]],
        'attribute_disk1' => $this->disk1Attributes[$value[1]],
        'attribute_disk2' => isset($this->disk2Attributes[$value[2]]) ? $this->disk2Attributes[$value[2]] : NULL,
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
