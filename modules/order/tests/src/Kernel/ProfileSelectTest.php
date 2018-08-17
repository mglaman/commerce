<?php

namespace Drupal\Tests\commerce_order\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\user\Entity\User;

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
      '#default_value' => $form_state->get('profile') ?: Profile::create([
        'type' => 'customer',
        'uid' => $form_state->get('user') ?: User::getAnonymousUser(),
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

      case 'testDefaultCountryIsNotValid':
        $form['profile']['#default_country'] = 'CA';
        break;

      default:
        // Do nothing, the default definition is enough to test with.
        break;
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

  /**
   * Tests that an invalid default country resets to NULL.
   */
  public function testValidateElementPropertiesDefaultCountry() {
    $this->formTestCase = 'testDefaultCountryIsValid';
    $form = $this->buildTestForm();
    $this->assertEquals('US', $form['profile']['#default_country']);

    $this->formTestCase = 'testDefaultCountryIsNotValid';
    $form = $this->buildTestForm();
    $this->assertNull($form['profile']['#default_country']);
  }

  /**
   * Tests the available profiles select list.
   *
   * Ensures:
   * - Anonymous users do not see the select list
   * - Users without existing profiles do not see the select list
   * - Users with existing profiles see the select list
   * - The select list defaults to the current user's profile.
   * - The element's default value is the user's default profile.
   */
  public function testAvailableProfilesSelectList() {
    $this->formTestCase = __FUNCTION__;

    // Test as anonymous user, which should never show the select list.
    $form = $this->buildTestForm();
    $this->assertFalse($form['profile']['available_profiles']['#access']);

    // Test that a user without previous profiles does not see the select list.
    $user = $this->createUser();
    $form = $this->buildTestForm([
      'user' => $user,
    ]);
    $this->assertFalse($form['profile']['available_profiles']['#access']);

    // Create profiles for the user, assert the select list is available.
    $test_profile1 = Profile::create([
      'type' => 'customer',
      'address' => [
        'organization' => '',
        'country_code' => 'FR',
        'postal_code' => '75002',
        'locality' => 'Paris',
        'address_line1' => 'A french street',
        'given_name' => 'John',
        'family_name' => 'LeSmith',
      ],
      'uid' => $user->id(),
    ]);
    $test_profile1->setDefault(TRUE);
    $test_profile1->save();
    $test_profile2 = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $test_profile2->save();

    $form = $this->buildTestForm([
      'user' => $user,
    ]);
    $this->assertTrue($form['profile']['available_profiles']['#access']);
    $this->assertCount(3, $form['profile']['available_profiles']['#options']);
    $this->assertEquals([
      $test_profile1->id() => $test_profile1->label(),
      $test_profile2->id() => $test_profile2->label(),
      '_new' => '+ Enter a new address',
    ], $form['profile']['available_profiles']['#options']);
    $this->assertEquals($test_profile1->id(), $form['profile']['available_profiles']['#default_value']);
    $this->assertEquals($test_profile1->id(), $form['profile']['#default_value']->id());

    // If we mark the test_profile2 as default, it should be the default option.
    $test_profile2->setDefault(TRUE);
    $test_profile2->save();

    $form = $this->buildTestForm([
      'user' => $user,
    ]);
    $this->assertEquals($test_profile2->id(), $form['profile']['available_profiles']['#default_value']);
    $this->assertEquals($test_profile2->id(), $form['profile']['#default_value']->id());
  }

  /**
   * Tests that the element default value respects provided profile.
   */
  public function testAvailableProfilesListWithProvidedDefaultValue() {
    $user = $this->createUser();
    $test_profile1 = Profile::create([
      'type' => 'customer',
      'address' => [
        'organization' => '',
        'country_code' => 'FR',
        'postal_code' => '75002',
        'locality' => 'Paris',
        'address_line1' => 'A french street',
        'given_name' => 'John',
        'family_name' => 'LeSmith',
      ],
      'uid' => $user->id(),
    ]);
    $test_profile1->setDefault(TRUE);
    $test_profile1->save();
    $test_profile2 = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $test_profile2->save();

    // Pass the second profile to form, so it is the one being modified.
    $form = $this->buildTestForm([
      'profile' => $test_profile2,
    ]);
    $this->assertEquals($test_profile2->id(), $form['profile']['available_profiles']['#default_value']);
    $this->assertEquals($test_profile2->id(), $form['profile']['#default_value']->id());
  }

  /**
   * Build the test form.
   *
   * @param array $form_state_additions
   *   An array of values to add to the form state.
   *
   * @return array
   *   The rendered form.
   *
   * @throws \Drupal\Core\Form\EnforcedResponseException
   * @throws \Drupal\Core\Form\FormAjaxException
   */
  protected function buildTestForm(array $form_state_additions = []) {
    // Programmatically submit the form.
    $form_state = new FormState();
    $form_state->setFormState($form_state_additions);
    $form = $this->formBuilder->buildForm($this, $form_state);
    return $form;
  }

}
