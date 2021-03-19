<?php

namespace Drupal\jcc_courtyard_icons\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type of courtyard_icons.
 *
 * @FieldType(
 *   id = "courtyard_icons",
 *   label = @Translation("Courtyard Icons"),
 *   default_formatter = "courtyard_icons_formatter",
 *   default_widget = "courtyard_icons_widget",
 * )
 */
class CourtyardIcons extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Here, columns contains the values that the field will store.
      'columns' => [
        // List the values that the field will save. This
        // field will only save a single value, 'value'.
        'value' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
