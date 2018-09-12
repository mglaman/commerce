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
  protected function prepareEntity() {
    if ($this->entity->isNew()) {
      $product = $this->getRouteMatch()->getParameter('commerce_product');
      $this->entity->set('product_id', $product);
    }
  }

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
      $product = $route_match->getParameter('commerce_product');
      $product_type = $this->entityTypeManager->getStorage('commerce_product_type')->load($product->bundle());
      $values['type'] = $product_type->getVariationTypeId();
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->create($values);
    }
    return $entity;
  }

}
