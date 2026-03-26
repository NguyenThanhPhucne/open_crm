<?php

namespace Drupal\crm_advanced_filters\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the SavedFilter entity.
 *
 * @ContentEntityType(
 *   id = "saved_filter",
 *   label = @Translation("Saved Filter"),
 *   label_collection = @Translation("Saved Filters"),
 *   label_count = @PluralTranslation(
 *     singular = "@count saved filter",
 *     plural = "@count saved filters"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\crm_advanced_filters\SavedFilterListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\crm_advanced_filters\Form\SavedFilterForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\crm_advanced_filters\SavedFilterAccessControlHandler",
 *   },
 *   base_table = "saved_filter",
 *   data_table = "saved_filter_field_data",
 *   revision_table = "saved_filter_revision",
 *   revision_data_table = "saved_filter_field_revision",
 *   translatable = TRUE,
 *   show_revision_ui = FALSE,
 *   admin_permission = "administer saved_filter",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "published" = "published_at",
 *     "created" = "created",
 *     "revision_default" = "revision_default",
 *   },
 *   links = {
 *     "canonical" = "/api/crm/filters/{saved_filter}",
 *     "edit-form" = "/admin/crm/saved-filters/{saved_filter}/edit",
 *     "delete-form" = "/admin/crm/saved-filters/{saved_filter}/delete",
 *     "collection" = "/admin/crm/saved-filters",
 *   },
 *   field_ui_base_route = "entity.saved_filter.admin_form",
 * )
 */
class SavedFilter extends ContentEntityBase implements SavedFilterInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);

    // Set default owner to current user if not provided.
    if (empty($values['uid'])) {
      $values['uid'] = \Drupal::currentUser()->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entity_type) {
    $this->set('entity_type', $entity_type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterDefinition() {
    return $this->get('filter_definition')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilterDefinition(array $definition) {
    $this->set('filter_definition', $definition);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublic() {
    return $this->get('is_public')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublic($public = TRUE) {
    $this->set('is_public', $public);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
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
  public function getLastUsedTime() {
    return $this->get('last_used')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastUsedTime($timestamp) {
    $this->set('last_used', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filter Name'))
      ->setDescription(t('The name of the saved filter'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of what this filter finds'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -4,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The entity type being filtered'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filter_definition'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Filter Definition'))
      ->setDescription(t('Serialized filter definition'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['is_public'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is Public'))
      ->setDescription(t('Whether this filter is shareable with the team'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -2,
      ])
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => -2,
        'settings' => [
          'format' => 'custom',
          'format_custom_true' => t('Public'),
          'format_custom_false' => t('Private'),
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user that owns this filter'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultUid')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'match_limit' => 10,
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the filter was created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the filter was last modified'));

    $fields['last_used'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Used'))
      ->setDescription(t('The time that the filter was last used'))
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('Whether the filter is active'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 99,
      ])
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Gets the default user ID for a new filter.
   */
  public static function getDefaultUid() {
    return [\Drupal::currentUser()->id()];
  }

}
