<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\profile\Entity\ProfileType;

/**
 * @group commerce
 * @group commerce_order
 */
class AddressBookTest extends OrderWebDriverTestBase {

  protected function setUp() {
    parent::setUp();
    $this->drupalLogout();
  }

  public function testManagingAddresses() {
    $customer = $this->createUser([
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->getSession()->getPage()->clickLink('Add address');
    $this->getSession()->getPage()->fillField('address[0][address][country_code]', 'US');
    $this->waitForAjaxToFinish();
    $this->submitForm([
      'address[0][address][given_name]' => 'Frederick',
      'address[0][address][family_name]' => 'Pabst',
      'address[0][address][address_line1]' => 'Pabst Blue Ribbon Dr',
      'address[0][address][postal_code]' => '53177',
      'address[0][address][locality]' => 'Milwaukee',
      'address[0][address][administrative_area]' => 'WI',
    ], 'Save and make default');
    $this->assertSession()->pageTextContains('Address Pabst Blue Ribbon Dr has been created.');
    $url = Url::fromRoute('commerce_order.user_address_book', ['user' => $customer->id()]);
    $this->assertSession()->addressEquals($url);
    $this->assertSession()->pageTextContains('Frederick Pabst');
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr');
    $this->assertSession()->pageTextContains('Milwaukee, WI 53177');
    $this->assertSession()->pageTextContains('United States');

    $this->getSession()->getPage()->find('css', '.views-field-operations')->findLink('Edit')->click();
    $this->saveHtmlOutput();
    $this->getSession()->getPage()->fillField('Zip code', '53212');
    $this->getSession()->getPage()->pressButton('Save');
    $this->saveHtmlOutput();
//    $this->assertSession()->pageTextContains('Address Pabst Blue Ribbon Dr has been updated. ');
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr has been updated. ');
    $url = Url::fromRoute('commerce_order.user_address_book', ['user' => $customer->id()]);
    $this->assertSession()->addressEquals($url);
  }

  public function testManagingMultipleAddressTypes() {
    $bundle_entity_duplicator = $this->container->get('entity.bundle_entity_duplicator');
    $customer_profile_type = ProfileType::load('customer');
    $bundle_entity_duplicator->duplicate($customer_profile_type, [
      'id' => 'shipping',
      'label' => 'Shipping',
    ]);

    $customer = $this->createUser([
      'create customer profile',
      'update own customer profile',
      'view own customer profile',
      'create shipping profile',
      'update own shipping profile',
      'view own shipping profile',
    ]);
    $this->drupalLogin($customer);
    $this->drupalGet($customer->toUrl());

    $this->getSession()->getPage()->clickLink('Addresses');
    $this->getSession()->getPage()->clickLink('Add address');
    $this->getSession()->getPage()->clickLink('Shipping');
    $this->getSession()->getPage()->fillField('address[0][address][country_code]', 'US');
    $this->waitForAjaxToFinish();
    $this->submitForm([
      'address[0][address][given_name]' => 'Frederick',
      'address[0][address][family_name]' => 'Pabst',
      'address[0][address][address_line1]' => 'Pabst Blue Ribbon Dr',
      'address[0][address][postal_code]' => '53177',
      'address[0][address][locality]' => 'Milwaukee',
      'address[0][address][administrative_area]' => 'WI',
    ], 'Save and make default');
    $this->assertSession()->pageTextContains('Address Pabst Blue Ribbon Dr has been created.');
    $url = Url::fromRoute('commerce_order.user_address_book', ['user' => $customer->id()]);
    $this->assertSession()->addressEquals($url);

    $this->assertSession()->elementTextContains('xpath', '//details[1]', 'Customer');
    $this->assertSession()->elementTextContains('xpath', '//details[2]', 'Shipping');
    $this->assertSession()->elementTextContains('xpath', '//details[2]', 'Frederick Pabst');
    $this->assertSession()->elementTextContains('xpath', '//details[2]', 'Pabst Blue Ribbon Dr');
    $this->assertSession()->elementTextContains('xpath', '//details[2]', 'Milwaukee, WI 53177');
    $this->assertSession()->elementTextContains('xpath', '//details[2]', 'United States');
  }

}
