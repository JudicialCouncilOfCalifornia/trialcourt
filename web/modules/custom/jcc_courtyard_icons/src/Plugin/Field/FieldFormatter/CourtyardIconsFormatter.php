<?php

namespace Drupal\jcc_courtyard_icons\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formatter that shows and icon from Courtyard.
 *
 * @FieldFormatter(
 *   id = "courtyard_icons_formatter",
 *   label = @Translation("Courtyard Icons"),
 *   field_types = {
 *     "courtyard_icons"
 *   }
 * )
 */
class CourtyardIconsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $config) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config;
    $this->iconsPath = $this->config->get('jcc_courtyard_icons.settings')->get('icons_path');
    $sets = $this->config->get('jcc_courtyard_icons.settings')->get('icon_sets');
    $sets = explode(PHP_EOL, $sets);
    foreach ($sets as $set) {
      $this->sets[] = trim($set);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $name = '';

    foreach ($items as $delta => $item) {
      foreach ($item->toArray() as $name) {
        foreach ($this->sets as $set) {
          if (strpos($name, "icon-$set-") !== FALSE) {
            $set_name = $set;
            $icon_name = str_replace("icon-$set-", '', $name);
          }
        }
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#format' => 'full_html',
          '#text' => "<svg role='img' aria-label='$name'><use xlink:href='/$this->iconsPath#$name'></use></svg>",
          '#icon_set' => $set_name,
          '#icon_name' => $icon_name,
          '#attached' => [
            'library' => [
              'jcc_courtyard_icons/widget',
            ],
          ],
        ];
      }
    }

    return $elements;
  }

}
