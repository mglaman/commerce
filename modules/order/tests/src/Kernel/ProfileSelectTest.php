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

    // Create a uid1 so permissions don't get bypassed later on.
    $uid1 = $this->createUser();
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If the form is being validated.
    // The value has been set on the "profile" key of the element.
    $profile = $form_state->getValue(['profile', 'profile']);
    $this->assertInstanceOf(Profile::class, $profile);
  }

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
    $user = $this->createUser([], [
      'create customer profile',
    ]);
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
   * Tests that the #profile attribute contains the profile value.
   */
  public function testProfilePropertyOnElement() {
    $form = $this->buildTestForm();
    $this->assertInstanceOf(
      Profile::class,
      $form['profile']['#profile']
    );
    $this->assertInstanceOf(
      Profile::class,
      $form['profile']['#default_value']
    );
  }

  /**
   * Tests using a previous revision with the profile select element.
   *
   * This asserts that a previous revision passed into the element will not
   * be allowed to be modified.
   */
  public function testLatestRevision() {
    $user = $this->createUser([], [
      'create customer profile',
    ]);
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

    $test_profile2_revision_id = $test_profile2->getRevisionId();

    // Mark it as default, and create a new revision.
    $test_profile2 = $this->reloadEntity($test_profile2);
    $test_profile2->setDefault(TRUE);
    $test_profile2->setNewRevision();
    $test_profile2->save();

    $this->assertNotEquals(
      $test_profile2_revision_id,
      $test_profile2->getRevisionId()
    );

    $original_test_profile2 = $this->container->get('entity_type.manager')
      ->getStorage('profile')
      ->loadRevision($test_profile2_revision_id);

    $this->assertFalse($original_test_profile2->isDefaultRevision());
    $this->assertTrue($test_profile2->isDefaultRevision());

    // Pass the second profile to form, so it is the one being modified.
    $form = $this->buildTestForm([
      'profile' => $original_test_profile2,
    ]);
    $this->assertCount(4, $form['profile']['available_profiles']['#options']);
    $this->assertEquals([
      '_existing' => t(':label (Original)', [':label' => $original_test_profile2->label()]),
      $test_profile1->id() => $test_profile1->label(),
      $test_profile2->id() => $test_profile2->label(),
      '_new' => '+ Enter a new address',
    ], $form['profile']['available_profiles']['#options']);
    $this->assertEquals('_existing', $form['profile']['available_profiles']['#default_value']);
    $this->assertFalse($form['profile']['profile_view']['edit']['#access']);
  }

  /**
   * Tess the element when passing values from the select list.
   */
  public function testAvailableProfilesFormStateValue() {
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

    $test_profile2_revision_id = $test_profile2->getRevisionId();

    // Mark it as default, and create a new revision.
    $test_profile2 = $this->reloadEntity($test_profile2);
    $test_profile2->setDefault(TRUE);
    $test_profile2->setNewRevision();
    $test_profile2->save();

    $this->assertNotEquals(
      $test_profile2_revision_id,
      $test_profile2->getRevisionId()
    );

    $original_test_profile2 = $this->container->get('entity_type.manager')
      ->getStorage('profile')
      ->loadRevision($test_profile2_revision_id);

    $this->assertFalse($original_test_profile2->isDefaultRevision());
    $this->assertTrue($test_profile2->isDefaultRevision());

    // Pass a previous revision to the form, but specify we want a new profile.
    $form = $this->buildTestForm([
      'profile' => $original_test_profile2,
      'values' => [
        'profile' => [
          'available_profiles' => '_new',
        ],
      ],
      'input' => [
        'profile' => [
          'available_profiles' => '_new',
        ],
      ],
    ]);
    $this->assertEquals('_new', $form['profile']['available_profiles']['#value']);
    $this->assertTrue($form['profile']['#profile']->isNew());

    // Pass a previous revision to the form, but specify the first test profile.
    $form = $this->buildTestForm([
      'profile' => $original_test_profile2,
      'values' => [
        'profile' => [
          'available_profiles' => $test_profile1->id(),
        ],
      ],
      'input' => [
        'profile' => [
          'available_profiles' => $test_profile1->id(),
        ],
      ],
    ]);
    $this->assertEquals($test_profile1->id(), $form['profile']['available_profiles']['#value']);
    $this->assertEquals($test_profile1->id(), $form['profile']['#profile']->id());

    // Pass in the latest revision for the default value, and ensure that we
    // do not receive `_existing` as the option.
    $form = $this->buildTestForm([
      'profile' => $original_test_profile2,
      'values' => [
        'profile' => [
          'available_profiles' => $test_profile2->id(),
        ],
      ],
      'input' => [
        'profile' => [
          'available_profiles' => $test_profile2->id(),
        ],
      ],
    ]);
    $this->assertEquals($test_profile2->id(), $form['profile']['available_profiles']['#value']);
    $this->assertEquals($test_profile2->id(), $form['profile']['#profile']->id());
  }

  /**
   * Tests that the `_new` option is controlled by permission access.
   */
  public function testCreateAccess() {
    // Create a user who has profiles, but does not have the ability to create
    // new. This replicates sites where users have a set of profiles to select
    // from based on custom logic but cannot create new ones.
    $user = $this->createUser([], []);
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

    $this->assertCount(2, $form['profile']['available_profiles']['#options']);
    $this->assertEquals([
      $test_profile1->id() => $test_profile1->label(),
      $test_profile2->id() => $test_profile2->label(),
    ], $form['profile']['available_profiles']['#options']);
  }

  /**
   * Tests editing an available profile is based on permissions.
   */
  public function testEditAccess() {
    // Create a user who has profiles, but does not have the ability to create
    // new. This replicates sites where users have a set of profiles to select
    // from based on custom logic but cannot create new ones.
    $user = $this->createUser([], [
      'create customer profile',
    ]);
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
      'profile' => $test_profile1,
    ]);

    $this->assertCount(3, $form['profile']['available_profiles']['#options']);
    $this->assertEquals($test_profile1->id(), $form['profile']['available_profiles']['#default_value']);

    $this->assertFalse($form['profile']['profile_view']['edit']['#access']);

    $user = $this->createUser([], [
      'create customer profile',
      'update own profile',
      'update own customer profile',
    ]);
    $test_profile1->setOwner($user);
    $test_profile1->save();
    $test_profile2->setOwner($user);
    $test_profile2->save();

    $form = $this->buildTestForm([
      'user' => $user,
      'profile' => $test_profile1,
    ]);
    $this->assertTrue($form['profile']['profile_view']['edit']['#access']);
  }

  /**
   * Tests the validation of the element.
   *
   * Assertions are made in this forms validate method. The main assertion
   * is that the proposed profile is set as the element value during the
   * validation process, so that other elements can validate against that
   * selected profile.
   *
   * @see \Drupal\Tests\commerce_order\Kernel\ProfileSelectTest::validateForm
   */
  public function testElementValidation() {
    $form = $this->buildTestForm();

    $form_validator = $this->container->get('form_validator');

    $form_state = new FormState();
    $form_state->setProgrammed();
    $form_validator->validateForm($this->getFormId(), $form, $form_state);
  }

  /**
   * Tests the submission of the element.
   */
  public function testElementSubmission() {
    $user = $this->createUser([], [
      'create customer profile',
    ]);
    $form_state = new FormState();
    $form_state->setFormState([
      'user' => $user,
      'values' => [
        'profile' => [
          'available_profiles' => '_new',
          'address' => [
            0 => [
              'address' => [
                'country_code' => 'US',
                'postal_code' => '53177',
                'locality' => 'Milwaukee',
                'address_line1' => 'Pabst Blue Ribbon Dr',
                'administrative_area' => 'WI',
                'given_name' => 'Frederick',
                'family_name' => 'Pabst',
              ],
            ],
          ],
        ],
      ],
      'input' => [
        'profile' => [
          'available_profiles' => '_new',
          'address' => [
            0 => [
              'address' => [
                'country_code' => 'US',
                'postal_code' => '53177',
                'locality' => 'Milwaukee',
                'address_line1' => 'Pabst Blue Ribbon Dr',
                'administrative_area' => 'WI',
                'given_name' => 'Frederick',
                'family_name' => 'Pabst',
              ],
            ],
          ],
        ],
      ],
    ]);
    $this->formBuilder->submitForm($this, $form_state);

    $complete_form = $form_state->getCompleteForm();
    // Assert the profile is stored on the element's #profile property and
    // has been saved.
    $profile = $complete_form['profile']['#profile'];
    $this->assertInstanceOf(Profile::class, $profile);
    $this->assertFalse($profile->isNew());

    // Assert the profile was set to be the element value as well.
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $form_state->getValue(['profile', 'profile']);
    $this->assertInstanceOf(Profile::class, $profile);
    $this->assertFalse($profile->isNew());

    $profile_storage = $this->container->get('entity_type.manager')->getStorage('profile');
    /** @var \Drupal\profile\Entity\ProfileInterface $initial_profile_revision */
    $initial_profile_revision = $profile_storage->loadRevision($profile->getRevisionId());

    // Resubmit the form with the profile we created. However, we're going to
    // modify the address. This should cause a new revision to be created.
    $form_state = new FormState();
    $form_state->setFormState([
      'user' => $user,
      'profile' => $profile,
      'values' => [
        'profile' => [
          'available_profiles' => $profile->id(),
          'address' => [
            0 => [
              'address' => [
                'country_code' => 'US',
                'postal_code' => '53177',
                'locality' => 'Milwaukee',
                'address_line1' => 'Pabst Blue Ribbon Dr',
                'administrative_area' => 'WI',
                'given_name' => 'Joseph',
                'family_name' => 'Schlitz',
              ],
            ],
          ],
        ],
      ],
      'input' => [
        'profile' => [
          'available_profiles' => $profile->id(),
          'address' => [
            0 => [
              'address' => [
                'country_code' => 'US',
                'postal_code' => '53177',
                'locality' => 'Milwaukee',
                'address_line1' => 'Pabst Blue Ribbon Dr',
                'administrative_area' => 'WI',
                'given_name' => 'Joseph',
                'family_name' => 'Schlitz',
              ],
            ],
          ],
        ],
      ],
    ]);
    $this->formBuilder->submitForm($this, $form_state);

    /** @var \Drupal\profile\Entity\ProfileInterface $updated_profile */
    $updated_profile = $form_state->getValue(['profile', 'profile']);
    $this->assertInstanceOf(Profile::class, $updated_profile);
    $this->assertEquals($initial_profile_revision->id(), $updated_profile->id());
    $this->assertNotEquals(
      $initial_profile_revision->getRevisionId(),
      $updated_profile->getRevisionId()
    );
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
    $form_state->setProgrammed();
    $form_state->setProcessInput();
    $form_state->setFormState($form_state_additions);
    $form = $this->formBuilder->buildForm($this, $form_state);

    // If form values were passed, rebuild the form to simulate AJAX.
    if (!empty($form_state_additions['values'])) {
      $form_state->setMethod('GET');
      $form_state->setValues($form_state_additions['values']);
      $form = $this->formBuilder->rebuildForm($this->getFormId(), $form_state, $form);
    }
    return $form;
  }

}
