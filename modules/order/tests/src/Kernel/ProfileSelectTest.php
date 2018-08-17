<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the commerce_profile_select element.
 *
 * @group commerce
 */
class ProfileSelectTest extends CommerceKernelTestBase implements FormInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_order',
  ];

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The form test cases.
   *
   * @var string
   */
  protected $formTestCase;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['commerce_order']);
    $this->installEntitySchema('profile');
    $this->formBuilder = $this->container->get('form_builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'profile_select_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Default basic element definition.
    $form['profile'] = [
      '#type' => 'commerce_profile_select',
      '#title' => 'Select an address',
      '#create_title' => '+ Enter a new address',
      '#default_value' => Profile::create([
        'type' => 'customer',
        'uid' => $this->createUser(),
      ]),
      '#profile_latest_revision' => TRUE,
      '#default_country' => 'US',
      '#available_countries' => ['HU', 'FR', 'US', 'RS', 'DE'],
    ];


    switch ($this->formTestCase) {
      case 'testValidateElementPropertiesDefaultValueEmpty':
        $form['profile']['#default_value'] = NULL;
        break;

      case 'testValidateElementPropertiesDefaultValueInstance':
        $form['profile']['#default_value'] = '14';
        break;

      case 'testValidateElementPropertiesAvailableCountries':
        $form['profile']['#available_countries'] = 'US';
        break;

      case 'testDefaultCountryIsValid':
        // Do nothing, the default country is valid and in the available list.
        break;

      case 'testDefaultCountryIsNotValid':
        $form['profile']['#default_country'] = 'CA';
        break;

      default:
        throw new \InvalidArgumentException('A valid formTestCase must be specified.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests that the element expects a default value.
   */
  public function testValidateElementPropertiesDefaultValueEmpty() {
    $this->setExpectedException(\InvalidArgumentException::class, 'The commerce_profile_select element requires the #default_value property.');
    $this->formTestCase = __FUNCTION__;
    $this->buildTestForm();
  }

  /**
   * Tests that the element expects a default value of ProfileInterface.
   */
  public function testValidateElementPropertiesDefaultValueInstance() {
    $this->setExpectedException(\InvalidArgumentException::class, 'The commerce_profile_select #default_value property must be a profile entity.');
    $this->formTestCase = __FUNCTION__;
    $this->buildTestForm();
  }

  /**
   * Tests that the element expects a default value of ProfileInterface.
   */
  public function testValidateElementPropertiesAvailableCountries() {
    $this->setExpectedException(\InvalidArgumentException::class, 'The commerce_profile_select #available_countries property must be an array.');
    $this->formTestCase = __FUNCTION__;
    $this->buildTestForm();
  }

  public function testValidateElementPropertiesDefaultCountry() {
    $this->formTestCase = 'testDefaultCountryIsValid';
    $form = $this->buildTestForm();
    $this->assertEquals('US', $form['profile']['#default_country']);

    $this->formTestCase = 'testDefaultCountryIsNotValid';
    $form = $this->buildTestForm();
    $this->assertNull($form['profile']['#default_country']);
  }

  /**
   * Build the test form.
   *
   * @return array
   *   The rendered form.
   */
  protected function buildTestForm() {
    // Programmatically submit the form.
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm($this, $form_state);
    return $form;
  }

}
