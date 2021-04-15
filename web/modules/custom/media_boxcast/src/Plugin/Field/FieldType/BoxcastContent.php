<?php

namespace Drupal\media_boxcast\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of boxcast_content.
 *
 * @FieldType(
 *   id = "boxcast_content",
 *   label = @Translation("Boxcast Content"),
 *   default_formatter = "boxcast_content_formatter",
 *   default_widget = "boxcast_content_widget",
 * )
 */
class BoxcastContent extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'url' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'show_title' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'show_description' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'show_highlights' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'show_related' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'default_video' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'market' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'show_countdown' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'show_documents' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'show_index' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'show_donations' => [
          'type' => 'int',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'layout' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $url = $this->get('url')->getValue();
    return empty($url);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'url';
  }

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['url'] = DataDefinition::create('string')->setRequired(TRUE);
    $properties['show_title'] = DataDefinition::create('integer');
    $properties['show_description'] = DataDefinition::create('integer');
    $properties['show_highlights'] = DataDefinition::create('integer');
    $properties['show_related'] = DataDefinition::create('integer');
    $properties['default_video'] = DataDefinition::create('string');
    $properties['market'] = DataDefinition::create('string');
    $properties['show_countdown'] = DataDefinition::create('integer');
    $properties['show_documents'] = DataDefinition::create('integer');
    $properties['show_index'] = DataDefinition::create('integer');
    $properties['show_donations'] = DataDefinition::create('integer');
    $properties['layout'] = DataDefinition::create('string');

    return $properties;
  }

}
