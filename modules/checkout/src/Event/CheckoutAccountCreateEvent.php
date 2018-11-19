<?php

namespace Drupal\commerce_checkout\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for creating an account during checkout.
 */
class CheckoutAccountCreateEvent extends Event {

  /**
   * The account created during checkout.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The checkout order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * The URL to redirect to after creating an account.
   *
   * @var \Drupal\Core\Url|null
   */
  protected $redirect;

  /**
   * Constructs a new CheckoutAccountCreateEvent object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account created during checkout.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The checkout order.
   */
  public function __construct(AccountInterface $account, OrderInterface $order) {
    $this->account = $account;
    $this->order = $order;
  }

  /**
   * Gets the created account.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The account created during checkout.
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets the checkout order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The checkout order.
   */
  public function getOrder() {
    return $this->order;
  }

  /**
   * Sets the redirect for redirecting after the account event has finished.
   *
   * @param string $route_name
   *   The name of the route.
   * @param array $route_parameters
   *   (optional) An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options. See
   *   \Drupal\Core\Url for the available keys.
   *
   * @return $this
   */
  public function setRedirect($route_name, array $route_parameters = [], array $options = []) {
    $url = new Url($route_name, $route_parameters, $options);
    return $this->setRedirectUrl($url);
  }

  /**
   * Sets the redirect URL for redirecting after the account event has finished.
   *
   * @param \Drupal\Core\Url $url
   *   The URL to redirect to.
   *
   * @return $this
   */
  public function setRedirectUrl(Url $url) {
    $this->redirect = $url;
    return $this;
  }

  /**
   * Gets the value to use for redirecting after the account event has finished.
   *
   * @return \Drupal\Core\Url|null
   *   A redirect url, if set. Null otherwise.
   */
  public function getRedirectUrl() {
    return $this->redirect;
  }

}
