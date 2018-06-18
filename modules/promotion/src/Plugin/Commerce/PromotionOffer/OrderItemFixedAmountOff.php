<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides the percentage off offer for order items.
 *
 * @CommercePromotionOffer(
 *   id = "order_item_fixed_amount_off",
 *   label = @Translation("Fixed amount off each matching product"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemFixedAmountOff extends FixedAmountOffBase {

  /**
   * {@inheritdoc}
   */
  public function apply(EntityInterface $entity, PromotionInterface $promotion) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    $unit_price = $order_item->getUnitPrice();
    $adjustment_amount = $this->getAmount();
    if ($unit_price->getCurrencyCode() != $adjustment_amount->getCurrencyCode()) {
      return;
    }
    // Don't reduce the order item unit price past zero.
    if ($adjustment_amount->greaterThan($unit_price)) {
      $adjustment_amount = $unit_price;
    }

    // Find out the percentage the adjustment represents from the subtotal
    // price, so we can apply it proportionately across order items.
    $adjustment_percentage = Calculator::divide($adjustment_amount->getNumber(), $unit_price->getNumber());

    $order_item->addAdjustment(new Adjustment([
      'type' => 'promotion',
      // @todo Change to label from UI when added in #2770731.
      'label' => t('Discount'),
      'amount' => $adjustment_amount->multiply('-1'),
      'percentage' => $adjustment_percentage,
      'source_id' => $promotion->id(),
    ]));
  }

}
