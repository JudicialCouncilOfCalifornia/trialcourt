<?php

namespace Drupal\jcc_jrn_contact\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\jcc_jrn_contact\JccStaffInterface;
use Drupal\user\UserInterface;

/**
 * Defines the jcc ajp entity class.
 *
 * @ContentEntityType(
 *   id = "jcc_ajp",
 *   label = @Translation("Temporary Assigned Judge"),
 *   label_collection = @Translation("Temporary Assigned Judges"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jcc_jrn_contact\JccAjpListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\jcc_jrn_contact\Form\JccAjpForm",
 *       "edit" = "Drupal\jcc_jrn_contact\Form\JccAjpForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "jcc_ajp",
 *   admin_permission = "access jcc ajp overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/jcc-ajp/add",
 *     "canonical" = "/jcc-ajp/{jcc_ajp}",
 *     "edit-form" = "/admin/content/jcc-ajp/{jcc_ajp}/edit",
 *     "delete-form" = "/admin/content/jcc-ajp/{jcc_ajp}/delete",
 *     "collection" = "/admin/content/jcc-ajp"
 *   },
 * )
 */
class JccAjp extends ContentEntityBase implements JccStaffInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new jcc ajp entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
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
  public function getOwner() {
    return $this->get('uid')->entity;
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['person_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Person ID'))
      ->setDescription(t('Person ID.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the AJP entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t("Email"))
      ->setDescription(t('The email of the ajp.'))
      ->addConstraint('UniqueField', [])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => 9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'email_mailto',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['program_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Program Status'))
      ->setDescription(t('Program status of the jcc ajp.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
        'allowed_values' => [
          'Deceased' => 'Deceased',
          'Left Program' => 'Left Program',
          'Approved' => 'Approved',
          'On Hold' => 'On Hold',
          'Not In Program' => 'Not In Program',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 11,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fy'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Fiscal Year'))
      ->setDescription(t('Fiscal year of the jcc ajp.'))
      ->setDisplayOptions('form', [
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fy_service_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Service Days'))
      ->setDescription(t('Service Days of the jcc ajp.'))
      ->setDisplayOptions('form', [
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fy_pro_bono_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pro Bono Days'))
      ->setDescription(t('Pro Bono Days of the jcc ajp.'))
      ->setDisplayOptions('form', [
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['life_service_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Life Service Days'))
      ->setDescription(t('Life Service Days of the jcc ajp.'))
      ->setDisplayOptions('form', [
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['life_pro_bono_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Life Pro Bono Days'))
      ->setDescription(t('Life pro bono days of the jcc ajp.'))
      ->setDisplayOptions('form', [
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['jud_exp_end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Judicial Experience End Date'))
      ->setDescription(t('Judicial Experience End Date.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
        'weight' => 15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the jcc staff is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 100,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the jcc staff author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 150,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 150,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the jcc staff was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 200,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 200,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the jcc staff was last edited.'));

    return $fields;
  }

}
