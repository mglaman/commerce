<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\Entity\User;

/**
 * @group commerce
 */
class BillingProfileConvertSubscriberTest extends CommerceKernelTestBase {

  /**
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installConfig(['commerce_product', 'commerce_order']);

    OrderItemType::create([
      'id' => 'test',
      'label' => 'Test',
      'orderType' => 'default',
    ])->save();

    $this->profileStorage = $this->container->get('entity_type.manager')->getStorage('profile');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return ['commerce_order.place.post_transition' => ['didEventTrigger', 0]];
  }

  /**
   * Tests that a missing billing profile does not cause a crash.
   *
   * @dataProvider dataForBillingProfileConversion
   */
  public function testBillingProfileConversion($authenticated, $convert) {
    $user = $authenticated ? $this->createUser() : User::getAnonymousUser();

    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
    ]);
    $profile->get('address')->setValue([
      'country_code' => 'US',
      'postal_code' => '53177',
      'locality' => 'Milwaukee',
      'address_line1' => 'Pabst Blue Ribbon Dr',
      'administrative_area' => 'WI',
      'given_name' => 'Frederick',
      'family_name' => 'Pabst',
    ]);
    if ($convert) {
      $profile->get('data')->__set('add_to_addressbook', TRUE);
    }
    $profile->save();
    $order = Order::create([
      'type' => 'default',
      'state' => 'draft',
      'mail' => 'text@example.com',
      'ip_address' => '127.0.0.1',
    ]);
    $order->setCustomer($user);
    $order->addItem($order_item);
    $order->setBillingProfile($profile);
    $order->setStore($this->store);
    $order->save();

    $order->getState()->applyTransitionById('place');
    $order->save();

    // If we did not convert, there should only be the one profile created for
    // the order.
    $base_profile_query = $this->profileStorage->getQuery()->accessCheck(FALSE);
    if ($convert === FALSE) {
      $profile_ids = (clone $base_profile_query)->execute();
      $this->assertCount(1, $profile_ids);
    }
    // If we did convert, but the user did not become authenticated, there
    // should not be a copied profile.
    elseif ($convert === TRUE && $authenticated === FALSE) {
      $profile_ids = (clone $base_profile_query)->execute();
      $this->assertCount(1, $profile_ids);
    }
    elseif ($convert === TRUE && $authenticated === TRUE) {
      $profile_ids = (clone $base_profile_query)->execute();
      $this->assertCount(2, $profile_ids);
      $profile_ids = (clone $base_profile_query)->condition('uid', $user->id())->execute();
      $this->assertCount(1, $profile_ids);
    }

    // If the user is anonymous, act as if they registered at the end of
    // checkout or the order was later assigned to an existing user.
    if ($authenticated === FALSE) {
      $new_user = $this->createUser();
      $order_assignment = $this->container->get('commerce_order.order_assignment');
      $order_assignment->assign($order, $new_user);
      if ($convert === TRUE) {
        $profile_ids = (clone $base_profile_query)->execute();
        $this->assertCount(2, $profile_ids);
        $profile_ids = (clone $base_profile_query)->condition('uid', $user->id())->execute();
        $this->assertCount(1, $profile_ids);
      }
      else {
        $profile_ids = (clone $base_profile_query)->execute();
        $this->assertCount(1, $profile_ids);
        $profile_ids = (clone $base_profile_query)->condition('uid', $new_user->id())->execute();
        $this->assertCount(0, $profile_ids);
      }
    }
  }

  public function dataForBillingProfileConversion() {
    // Anonymous, do not convert.
    yield [FALSE, FALSE];
    // Anonymous, convert. Should not if order_id is 0.
    yield [FALSE, TRUE];
    // Logged in, do not convert.
    yield [TRUE, FALSE];
    // Logged in, convert.
    yield [TRUE, TRUE];
  }

}
