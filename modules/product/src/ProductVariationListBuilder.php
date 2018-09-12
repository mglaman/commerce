<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for product variations.
 */
class ProductVariationListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The delta values of the variation field items.
   *
   * @var integer[]
   */
  protected $variationDeltas = [];

  /**
   * Whether tabledrag is enabled.
   *
   * @var bool
   */
  protected $hasTableDrag = TRUE;

  /**
   * The attribute field manager.
   *
   * @var \Drupal\commerce_product\ProductAttributeFieldManagerInterface
   */
  protected $attributeFieldManager;

  /**
   * Constructs a new ProductVariationListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\commerce_product\ProductAttributeFieldManagerInterface $attribute_field_manager
   *   The product attribute field manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, FormBuilderInterface $form_builder, ProductAttributeFieldManagerInterface $attribute_field_manager) {
    parent::__construct($entity_type, $storage);

    $this->routeMatch = $route_match;
    $this->formBuilder = $form_builder;
    $this->attributeFieldManager = $attribute_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('form_builder'),
      $container->get('commerce_product.attribute_field_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_variations';
  }

  /**
   * {@inheritdoc}
   *
   * @todo Why not getVariations, delta is not field delta but variation ID?
   */
  public function load() {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $this->routeMatch->getParameter('commerce_product');
    $variations = $product->get('variations')->referencedEntities();
    foreach ($variations as $delta => $variation) {
      $this->variationDeltas[$variation->id()] = $delta;
    }
    return $variations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['sku'] = $this->t('SKU');
    $header['title'] = $this->t('Title');
    $header['price'] = $this->t('Price');
    $header['status'] = $this->t('Status');
    $header += $this->getAttributeList();
    if ($this->hasTableDrag) {
      $header['weight'] = $this->t('Weight');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $this->variationDeltas[$entity->id()];
    $row['sku'] = $entity->getSku();
    $row['title'] = $entity->label();
    $row['price'] = $entity->getPrice();
    $row['status'] = $entity->isActive() ? $this->t('Active') : $this->t('Inactive');

    foreach ($this->getAttributeList() as $field_name => $label) {
      if ($attribute = $entity->getAttributeValue($field_name)) {
        $row[$field_name] = $attribute->label();
      }
      else {
        $row[$field_name] = $this->t('(Missing attribute value)');
      }
    }

    if ($this->hasTableDrag) {
      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $entity->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $this->variationDeltas[$entity->id()],
        '#attributes' => ['class' => ['weight']],
      ];
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = $this->formBuilder->getForm($this);
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $variations = $this->load();
    if (count($variations) <= 1) {
      $this->hasTableDrag = FALSE;
    }
    $delta = 10;
    // Dynamically expand the allowed delta based on the number of entities.
    $count = count($variations);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    $form['variations'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
    ];
    foreach ($variations as $entity) {
      $row = $this->buildRow($entity);
      $row['sku'] = ['#markup' => $row['sku']];
      $row['title'] = ['#markup' => $row['title']];
      $row['price'] = ['#markup' => $row['price']];
      $row['status'] = ['#markup' => $row['status']];
      foreach ($this->getAttributeList() as $field_name => $label) {
        $row[$field_name] = ['#markup' => $row[$field_name]];
      }
      // Handle attribute list.
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      $form['variations'][$entity->id()] = $row;
    }

    if ($this->hasTableDrag) {
      $form['variations']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'weight',
      ];
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $product = $this->routeMatch->getParameter('commerce_product');
    $variations = $product->get('variations')->referencedEntities();
    $new_variations = [];
    foreach ($form_state->getValue('variations') as $id => $value) {
      $new_variations[$value['weight']] = $variations[$this->variationDeltas[$id]];
    }
    $product->setVariations($new_variations);
    $product->save();
  }

  /**
   * Get available attributes for the current product's variations.
   *
   * @return array
   *   The product attributes.
   */
  protected function getAttributeList() {
    $attributes = [];
    $product = $this->routeMatch->getParameter('commerce_product');
    $variation_type_id = ProductType::load($product->bundle())->getVariationTypeId();
    $attribute_definitions = $this->attributeFieldManager->getFieldDefinitions($variation_type_id);
    foreach ($attribute_definitions as $field_name => $attribute) {
      $attributes[$field_name] = $attribute->label();
    }
    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureDestination(Url $url) {
    return $url->mergeOptions(['query' => ['destination' => Url::fromRoute('<current>')->toString()]]);
  }

}
