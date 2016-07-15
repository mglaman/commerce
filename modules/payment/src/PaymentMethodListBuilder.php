<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines the list builder for payment methods.
 */
class PaymentMethodListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'methods';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_payment_methods';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Payment methods');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $entity */
    $row['label'] = $entity->label();
    $row['type'] = $entity->getType()->getLabel();

    return $row + parent::buildRow($entity);
  }

}
