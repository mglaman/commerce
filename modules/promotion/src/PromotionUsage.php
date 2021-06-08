<?php

namespace Drupal\commerce_promotion;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\commerce_promotion\Entity\PromotionInterface;
use Drupal\Core\Database\Connection;

class PromotionUsage implements PromotionUsageInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Static cache of usage for promotions.
   *
   * @var array
   */
  protected $promotionUsage = [];

  /**
   * Static cache of usage for coupons.
   *
   * @var array
   */
  protected $couponUsage = [];

  /**
   * Constructs a PromotionUsage object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function register(OrderInterface $order, PromotionInterface $promotion, CouponInterface $coupon = NULL) {
    $this->connection->insert('commerce_promotion_usage')
      ->fields([
        'promotion_id' => $promotion->id(),
        'coupon_id' => $coupon ? $coupon->id() : 0,
        'order_id' => $order->id(),
        'mail' => $order->getEmail(),
      ])
      ->execute();

    // Clear static cache for usage count of promotion/coupon.
    unset($this->promotionUsage[$promotion->id()]);
    if ($coupon) {
      unset($this->couponUsage[$coupon->id()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reassign($old_mail, $new_mail) {
    $this->connection->update('commerce_promotion_usage')
      ->fields(['mail' => $new_mail])
      ->condition('mail', $old_mail)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $promotions) {
    $promotion_ids = $this->extractIds($promotions);
    // Invalidate the promotion usage static cache for the passed promotions.
    $this->clearCachedUsage($promotion_ids);
    $this->connection->delete('commerce_promotion_usage')
      ->condition('promotion_id', $promotion_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteByCoupon(array $coupons) {
    $coupon_ids = $this->extractIds($coupons);
    // Invalidate the coupon usage static cache for the passed coupons.
    $this->clearCachedCouponUsage($coupon_ids);
    $this->connection->delete('commerce_promotion_usage')
      ->condition('coupon_id', $coupon_ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load(PromotionInterface $promotion, $mail = NULL) {
    $usages = $this->loadMultiple([$promotion], $mail);
    return $usages[$promotion->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadByCoupon(CouponInterface $coupon, $mail = NULL) {
    $usages = $this->loadMultipleByCoupon([$coupon], $mail);
    return $usages[$coupon->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $promotions, $mail = NULL) {
    if (empty($promotions)) {
      return [];
    }

    $promotion_ids = $this->extractIds($promotions);
    // Always fetch the usage afresh when specifying the email.
    $usage_to_load = $mail ? $promotion_ids : array_diff_key($promotion_ids, $this->promotionUsage);

    if ($usage_to_load) {
      // Removes the cached usage for the promotion usage we're about to fetch.
      $this->clearCachedUsage($usage_to_load);
      $query = $this->connection->select('commerce_promotion_usage', 'cpu');
      $query->addField('cpu', 'promotion_id');
      $query->addExpression('COUNT(promotion_id)', 'count');
      $query->condition('promotion_id', $usage_to_load, 'IN');
      if (!empty($mail)) {
        $query->condition('mail', $mail);
      }
      $query->groupBy('promotion_id');
      $this->promotionUsage += $query->execute()->fetchAllAssoc('promotion_id', \PDO::FETCH_ASSOC);
    }

    // Ensure that each promotion ID gets a count, even if it's not present
    // in the query due to non-existent usage.
    $counts = [];
    foreach ($promotion_ids as $promotion_id) {
      $counts[$promotion_id] = 0;
      if (isset($this->promotionUsage[$promotion_id])) {
        $counts[$promotion_id] = $this->promotionUsage[$promotion_id]['count'];
      }
    }

    return $counts;

  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleByCoupon(array $coupons, $mail = NULL) {
    if (empty($coupons)) {
      return [];
    }

    $coupon_ids = $this->extractIds($coupons);
    // Always fetch the usage afresh when specifying the email.
    $usage_to_load = $mail ? $coupon_ids : array_diff_key($coupon_ids, $this->couponUsage);

    if ($usage_to_load) {
      // Removes the cached usage for the coupon usage we're about to fetch.
      $this->clearCachedCouponUsage($coupon_ids);
      $query = $this->connection->select('commerce_promotion_usage', 'cpu');
      $query->addField('cpu', 'coupon_id');
      $query->addExpression('COUNT(coupon_id)', 'count');
      $query->condition('coupon_id', $usage_to_load, 'IN');
      if (!empty($mail)) {
        $query->condition('mail', $mail);
      }
      $query->groupBy('coupon_id');
      $this->couponUsage += $query->execute()->fetchAllAssoc('coupon_id', \PDO::FETCH_ASSOC);
    }

    // Ensure that each coupon ID gets a count, even if it's not present
    // in the query due to non-existent usage.
    $counts = [];
    foreach ($coupon_ids as $coupon_id) {
      $counts[$coupon_id] = 0;
      if (isset($this->couponUsage[$coupon_id])) {
        $counts[$coupon_id] = $this->couponUsage[$coupon_id]['count'];
      }
    }

    return $counts;
  }

  /**
   * Clears the statically cached usage the given promotion IDS.
   *
   * @param array $promotion_ids
   *   An array of promotion IDS, keyed by promotion IDS.
   */
  protected function clearCachedUsage(array $promotion_ids) {
    $this->promotionUsage = array_diff_key($this->promotionUsage, $promotion_ids);
  }

  /**
   * Clears the statically cached usage the given coupon IDS.
   *
   * @param array $coupon_ids
   *   An array of coupon IDS, keyed by coupon IDS.
   */
  protected function clearCachedCouponUsage(array $coupon_ids) {
    $this->couponUsage = array_diff_key($this->couponUsage, $coupon_ids);
  }

  /**
   * Wrapper around EntityHelper::extractIds().
   *
   * This wrapper returns an array keyed by entity IDS as well.
   *
   * @param array $entities
   *   An array of promotions/coupons entities.
   *
   * @return array
   *   The entity IDs, keyed by IDS.
   */
  protected function extractIds(array $entities) {
    $ids = EntityHelper::extractIds($entities);
    return array_combine($ids, $ids);
  }

}
