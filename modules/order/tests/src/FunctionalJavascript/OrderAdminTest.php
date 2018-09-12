<?php

namespace Drupal\Tests\commerce_order\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\Tests\commerce\FunctionalJavascript\JavascriptTestTrait;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;

class OrderAdminTest extends OrderBrowserTestBase {

  use JavascriptTestTrait;

  public function testManageOrderForNewCustomer() {
    $this->drupalGet(Url::fromRoute('entity.commerce_order.add_page'));
    $this->getSession()->getPage()->checkField('New customer');
    $this->waitForAjaxToFinish();
    $this->getSession()->getPage()->fillField('Email', 'email@example.com');
    $this->getSession()->getPage()->pressButton('Create');

    $this->getSession()->getPage()->fillField('First name', 'Celia');
    $this->getSession()->getPage()->fillField('Last name', 'Engeseth');
    $this->getSession()->getPage()->fillField('Street address', '8502 Pilgrim St.');
    $this->getSession()->getPage()->fillField('City', 'Mokena');
    $this->getSession()->getPage()->fillField('State', 'IL');
    $this->getSession()->getPage()->fillField('Zip code', '60448');

    $product_variation_field = $this->getSession()->getPage()->find('named', ['field', 'Product variation']);
    $product_variation_field->setValue($this->variation->getTitle());
    $this->getSession()->getDriver()->keyDown($product_variation_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
    /** @var \Behat\Mink\Element\NodeElement[] $results */
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    $results[0]->click();
    $this->getSession()->getPage()->checkField('Override the unit price');
    $this->getSession()->getPage()->fillField('Unit price', '12.00');
    $this->getSession()->getPage()->pressButton('Create order item');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->pressButton('Add new order item');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldExists('Product variation');
    // Order item IEF does not affect profile_select for billing.
    $this->assertSession()->fieldValueEquals('Street address', '8502 Pilgrim St.');

    $open_ief = $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-order-items-form-inline-entity-form"]');
    $open_ief->pressButton('Cancel');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->pressButton('Save');

    $this->assertSession()->pageTextContains('The order has been successfully saved.');
    $this->getSession()->getPage()->clickLink('Edit');
    $this->saveHtmlOutput();

    $this->assertSession()->fieldExists('Select an address');
    $this->assertSession()->optionExists('Select an address', '8502 Pilgrim St.');
    $this->assertSession()->optionExists('Select an address', '+ Enter a new address');
  }

}
