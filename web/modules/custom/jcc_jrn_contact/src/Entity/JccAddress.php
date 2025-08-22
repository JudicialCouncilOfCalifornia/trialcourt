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
 * Defines the jcc cio entity class.
 *
 * @ContentEntityType(
 *   id = "jcc_address",
 *   label = @Translation("Address"),
 *   label_collection = @Translation("Address"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jcc_jrn_contact\JccAddressListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\jcc_jrn_contact\Form\JccAddressForm",
 *       "edit" = "Drupal\jcc_jrn_contact\Form\JccAddressForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "jcc_address",
 *   admin_permission = "access jcc address overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "addr1",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/jcc-address/add",
 *     "canonical" = "/jcc-address/{jcc_address}",
 *     "edit-form" = "/admin/content/jcc-address/{jcc_address}/edit",
 *     "delete-form" = "/admin/content/jcc-address/{jcc_address}/delete",
 *     "collection" = "/admin/content/jcc-address"
 *   },
 * )
 */
class JccAddress extends ContentEntityBase implements JccStaffInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new jcc staff entity is created, set the uid entity reference to
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

    $fields['addr_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Address ID'))
      ->setDescription(t('Address ID.'))
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

    $fields['loc_num'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Location Number'))
      ->setDescription(t('Location number of the address.'))
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

    foreach ([
      'addr1', 'addr2', 'city', 'state', 'zip',
      'mailing_addr1', 'mailing_addr2', 'mailing_city', 'mailing_state', 'mailing_zip',
      'phone', 'fax',
    ] as $field) {
      $fields[$field] = BaseFieldDefinition::create('string')
        ->setLabel(ucfirst(str_replace('_', ' ', $field)))
        ->setDescription(ucfirst(str_replace('_', ' ', $field)) . ' of the jcc address.')
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
    }

    $fields['court'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Court'))
      ->setDescription(t('Court location related to CIO.'))
      ->setSetting('target_type', 'jcc_court')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 7,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => 'Enter court location name...',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

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
