<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\Core\Url;

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
    $this->saveHtmlOutput();
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
    // @todo should this say "Address %s has been created"?
    $this->assertSession()->pageTextContains('Pabst Blue Ribbon Dr has been created.');
    // @todo override the redirect of the form to go to address book.
    $url = Url::fromRoute('commerce_order.user_addressbook', ['user' => $customer->id()]);
    $this->assertSession()->addressEquals($url);
  }

}
