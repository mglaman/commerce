<?php

namespace Drupal\commerce_log\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the log entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_log",
 *   label = @Translation("Log"),
 *   label_singular = @Translation("log"),
 *   label_plural = @Translation("logs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count log",
 *     plural = "@count logs",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce\EntityAccessControlHandler",
 *     "list_builder" = "Drupal\commerce_log\LogListBuilder",
 *     "storage" = "Drupal\commerce_log\LogStorage",
 *     "view_builder" = "Drupal\commerce_log\LogViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_log",
 *   admin_permission = "administer commerce_log",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "log_id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/logs",
 *     "canonical" = "/admin/commerce/logs/{commerce_log}",
 *   },
 * )
 */
class Log extends ContentEntityBase implements LogInterface {

  /**
   * {@inheritdoc}
   */
  public function getUserId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getCategoryPlugin()->getLabel() . ': ' . $this->getTemplatePlugin()->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoryId() {
    return $this->get('log_category_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategoryPlugin() {
    $log_category_manager = \Drupal::service('plugin.manager.log_category');
    return $log_category_manager->createInstance($this->getCategoryId());
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateId() {
    return $this->get('log_template_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplatePlugin() {
    $log_template_manager = \Drupal::service('plugin.manager.log_template');
    return $log_template_manager->createInstance($this->getTemplateId());
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityId() {
    return $this->get('source_entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityType() {
    return $this->get('source_entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return \Drupal::entityTypeManager()
      ->getStorage($this->getSourceEntityType())
      ->load($this->getSourceEntityId());
  }

  /**
   * {@inheritdoc}
   */
  public function getParams() {
    return $this->get('params')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setParams(array $params) {
    $this->set('params', $params);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('The user for the log.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_log\Entity\Log::getCurrentUserId');

    $fields['log_template_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Log template ID'))
      ->setDescription(t('The log template plugin ID'));

    $fields['log_category_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Log category ID'))
      ->setDescription(t('The log category plugin ID'));

    $fields['source_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Source entity ID'))
      ->setDescription(t('The source entity ID'));

    $fields['source_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source entity type'))
      ->setDescription(t('The source entity type'));

    $fields['params'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Params'))
      ->setDescription(t('A serialized array of parameters for the log template.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the payment method was created.'));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
