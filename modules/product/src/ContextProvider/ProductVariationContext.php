<?php

namespace Drupal\commerce_product\ContextProvider;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;

/**
 * Provides a product variation context.
 */
class ProductVariationContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductVariationContext object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $context_definition = new EntityContextDefinition('entity:commerce_product_variation', new TranslatableMarkup('Product variation'));
    $value = $this->routeMatch->getParameter('commerce_product_variation');
    /** @var \Drupal\commerce_product\ProductVariationStorageInterface $product_variation_storage */
    $product_variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    if ($value === NULL) {
      $product = $this->routeMatch->getParameter('commerce_product');
      if ($product) {
        // If commerce_product has an entity reference to a custom translatable
        // entity that has its translations overview available under
        // product/{commerce_product}/custom_entity/{custom_entity}/translations
        // $product passed by the breadcrumb builder when testing route
        // candidates is just the product ID as string not the loaded entity.
        // @see PathBasedBreadcrumbBuilder::getRequestForPath().
        if (is_scalar($product)) {
          $product_storage = $this->entityTypeManager->getStorage('commerce_product');
          $product = $product_storage->load($product);
        }

        if ($product instanceof ProductInterface) {
          $value = $product_variation_storage->loadFromContext($product);
          if ($value === NULL) {
            $product_type = ProductType::load($product->bundle());
            $value = $product_variation_storage->create([
              'type' => $product_type->getVariationTypeId(),
            ]);
          }
        }
      }
      /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
      elseif ($product_type = $this->routeMatch->getParameter('commerce_product_type')) {
        if (is_string($product_type)) {
          $product_type = ProductType::load($product_type);
        }
        $value = $product_variation_storage->createWithSampleValues($product_type->getVariationTypeId());
      }
      // @todo Simplify this logic once EntityTargetInterface is available
      // @see https://www.drupal.org/project/drupal/issues/3054490
      elseif (strpos($this->routeMatch->getRouteName(), 'layout_builder') !== FALSE) {
        /** @var \Drupal\layout_builder\SectionStorageInterface $section_storage */
        $section_storage = $this->routeMatch->getParameter('section_storage');
        if ($section_storage instanceof DefaultsSectionStorageInterface) {
          $context = $section_storage->getContextValue('display');
          assert($context instanceof EntityDisplayInterface);
          if ($context->getTargetEntityTypeId() === 'commerce_product') {
            $product_type = ProductType::load($context->getTargetBundle());
            $value = $product_variation_storage->createWithSampleValues($product_type->getVariationTypeId());
          }
        }
        elseif ($section_storage instanceof OverridesSectionStorageInterface) {
          $context = $section_storage->getContextValue('entity');
          if ($context instanceof ProductInterface) {
            $value = $context->getDefaultVariation();
            if ($value === NULL) {
              $product_type = ProductType::load($context->bundle());
              $value = $product_variation_storage->createWithSampleValues($product_type->getVariationTypeId());
            }
          }
        }
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);
    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);

    return ['commerce_product_variation' => $context];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return $this->getRuntimeContexts([]);
  }

}
