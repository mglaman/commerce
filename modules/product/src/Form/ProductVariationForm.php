<?php

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines the add/edit form for product variations.
 */
class ProductVariationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage($this->t('Saved the %label variation.', ['%label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      $entity = $route_match->getParameter($entity_type_id);
    }
    else {
      /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
      $product = $route_match->getParameter('commerce_product');
      /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
      $product_type = $this->entityTypeManager->getStorage('commerce_product_type')->load($product->bundle());
      $values['type'] = $product_type->getVariationTypeId();
      $values = [
        'type' => $product_type->getVariationTypeId(),
        'product_id' => $product->id(),
      ];
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    }
    return $entity;
  }

}
