<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce\CredentialsCheckFloodInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Event\CheckoutAccountCreateEvent;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the registration pane.
 *
 * @CommerceCheckoutPane(
 *   id = "completion_register",
 *   label = @Translation("Guest registration after checkout"),
 *   display_label = @Translation("Account information"),
 *   wrapper_element = "fieldset",
 * )
 */
class CompletionRegister extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The credentials check flood controller.
   *
   * @var \Drupal\commerce\CredentialsCheckFloodInterface
   */
  protected $credentialsCheckFlood;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The client IP address.
   *
   * @var string
   */
  protected $clientIp;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new CompletionRegistration object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\commerce\CredentialsCheckFloodInterface $credentials_check_flood
   *   The credentials check flood controller.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, CredentialsCheckFloodInterface $credentials_check_flood, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, UserAuthInterface $user_auth, RequestStack $request_stack, EventDispatcherInterface $event_dispatcher, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->credentialsCheckFlood = $credentials_check_flood;
    $this->currentUser = $current_user;
    $this->userAuth = $user_auth;
    $this->clientIp = $request_stack->getCurrentRequest()->getClientIp();
    $this->eventDispatcher = $event_dispatcher;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('commerce.credentials_check_flood'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('user.auth'),
      $container->get('request_stack'),
      $container->get('event_dispatcher'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // Registering is only possible for anonymous users and this pane can only
    // be shown at the end of checkout. Ensure that there is no user on the site
    // with the same email address.
    return $this->currentUser->isAnonymous()
      && $this->order->getState()->value != 'draft'
      && !$this->userStorage->loadByProperties(['mail' => $this->order->getEmail()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => FALSE,
      '#attributes' => [
        'class' => ['username'],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#default_value' => '',
    ];

    $pane_form['password'] = [
      '#type' => 'password_confirm',
      '#size' => 60,
      '#description' => $this->t('Provide a password for the new account.'),
      '#required' => TRUE,
    ];

    $pane_form['actions'] = ['#type' => 'actions'];
    $pane_form['actions']['register'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create my account'),
      '#name' => 'commerce_checkout_completion_register_submit',
    ];

    // Additional fields.
    $user = $this->userStorage->create([]);
    $form_display = EntityFormDisplay::collectRenderDisplay($user, 'register');
    $form_display->buildForm($user, $pane_form, $form_state);

    return [
      '#theme' => 'commerce_checkout_completion_register',
      'form' => $pane_form,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Validate the entity. This will ensure that the username and email are in
    // the right format and not already taken. The pane should only appear for
    // a non-existent email, but users can modify the email for their account.
    $values = $form_state->getValue($pane_form['#parents']);
    $account = $this->userStorage->create([
      'pass' => $values['password'],
      'mail' => $this->order->getEmail(),
      'name' => $values['name'],
      'status' => TRUE,
    ]);

    $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
    $form_display->extractFormValues($account, $pane_form, $form_state);
    $form_display->validateFormValues($account, $pane_form, $form_state);

    // Manually flag violations of fields not handled by the form display. This
    // is necessary as entity form displays only flag violations for fields
    // contained in the display.
    // @see \Drupal\user\AccountForm::flagViolations
    $violations = $account->validate();
    foreach ($violations->getByFields(['name', 'pass', 'mail']) as $violation) {
      list($field_name) = explode('.', $violation->getPropertyPath(), 2);
      $form_state->setError($pane_form['form'][$field_name], $violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\user\UserInterface $account */
    $values = $form_state->getValue($pane_form['#parents']);
    $account = $this->userStorage->create([
      'pass' => $values['password'],
      'mail' => $this->order->getEmail(),
      'name' => $values['name'],
      'status' => TRUE,
    ]);
    $form_display = EntityFormDisplay::collectRenderDisplay($account, 'register');
    $form_display->extractFormValues($account, $pane_form, $form_state);
    $account->activate();
    $account->save();

    user_login_finalize($account);
    $this->messenger->addStatus($this->t('Registration successful. You are now logged in.'));
    $this->order->setCustomer($account);

    // Add the billing profile to the user's address book.
    $profile = $this->order->getBillingProfile();
    if ($profile) {
      $profile->setOwner($account);
      $profile->save();
    }

    // Normally advancing steps in the checkout automatically saves the order.
    // Since this pane occurs on the last step, manual order saving is needed.
    $this->order->save();

    $this->credentialsCheckFlood->clearAccount($this->clientIp, $account->getAccountName());

    // Notify other modules about the account creation.
    $event = new CheckoutAccountCreateEvent($account, $this->order);
    $this->eventDispatcher->dispatch(CheckoutEvents::ACCOUNT_CREATE, $event);
    // Redirect the user to a different page, if a redirect has been set.
    if ($url = $event->getRedirectUrl()) {
      $form_state->setRedirectUrl($url);
    }
  }

}
