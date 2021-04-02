<?php

namespace Drupal\jcc_courtyard_icons\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * A widget for Courtyard Icons.
 *
 * @FieldWidget(
 *   id = "courtyard_icons_widget",
 *   module = "jcc_courtyard_icons",
 *   label = @Translation("Courtyard Icons Widget"),
 *   field_types = {
 *     "courtyard_icons",
 *   }
 * )
 */
class CourtyardIconsWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Icon types array.
   *
   * Groups icons by type indicated by prefix:
   * 'icon-prefix' => $this->t('Label')
   *
   * Any icons that don't have a prefix match will be placed in the group
   * "Other". Add additional prefix/Label pairs to the array to expand.
   *
   * This groups the Icon Buttons as well as the Select Options.
   *
   * @var array
   */
  private $types = [];

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $config) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->config = $config;
    $this->iconsPath = $this->config->get('jcc_courtyard_icons.settings')->get('icons_path');
    // Any icons that don't have a prefix match will be placed in the group
    // "Other". Add additional prefix/Label pairs to the array to expand.
    $this->types = [
      'icon-fa' => $this->t('Font Awesome'),
      'icon-line-white' => $this->t('Line: White'),
      'icon-line-dark' => $this->t('Line: Dark'),
    ];
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['icons'] = [
      '#type' => 'processed_text',
      '#format' => 'full_html',
      '#text' => $this->getIconButtons(),
    ];
    $element['icons']['#attached']['library'][] = 'jcc_courtyard_icons/widget';

    $element['value'] = [
      '#type' => 'select',
      '#options' => $this->getIconOptions(),
      // Do not allow chosen module to control the field.
      '#chosen' => FALSE,
      '#default_value' => $items[$delta]->value,
    ] + $element;

    return $element;
  }

  /**
   * Validate the courtyard_icons field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];

    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }

    if (!in_array($value, $this->getIconList())) {
      $form_state->setError($element, $this->t("An invalid icon was selected."));
    }
  }

  /**
   * Get the icon list by parsing the courtyard icons.svg.
   *
   * @return array
   *   An array of icon names.
   */
  public function getIconList() {
    if (!empty($this->iconList)) {
      return $this->iconList;
    }

    if (file_exists($this->iconsPath)) {
      $file = file_get_contents($this->iconsPath);
      preg_match_all('/id="([a-z]|-)*"/', $file, $matches);
      foreach ($matches[0] as $id) {
        preg_match('/(?<=")[^"]+(?=")/', $id, $match);
        $name = $match[0];
        $this->iconList[$name] = $name;
      }
    }
    else {
      $this->iconList = [];
    }

    return $this->iconList;
  }

  /**
   * Get icon buttons from icon list.
   *
   * @return string
   *   The markup for courtyard icon buttons.
   */
  public function getIconButtons() {
    $buttons = [];
    $current = '';
    foreach ($this->getIconList() as $name) {
      foreach ($this->types as $prefix => $label) {
        if (strpos($name, $prefix) !== FALSE) {
          if ($current != $prefix) {
            $buttons[$prefix][] = "<h5>" . (string) $label . "</h5>";
            $current = $prefix;
          }
          $buttons[$prefix][] = "<button class='jcc-courtyard-icons__button' data-icon-name='$name'>
            <svg role='img' aria-label='$name'>
              <use xlink:href='/$this->iconsPath#$name'></use>
            </svg>
          </button>";
        }
      }
    }

    foreach ($buttons as $group) {
      $groups[] = implode(' ', $group);
    }

    if (!empty($buttons)) {
      return "<div class='jcc-courtyard-icons'>" . implode(' ', $groups) . "</div>";
    }
    else {
      return '<h3>' . $this->t('Icons Not Configured') . '</h3>
        <p>' .
          $this->t('See <a href="/admin/config/system/jcc-courtyard-icons">JCC Courtyard Icons settings</a>') . '
        </p>
      ';
    }
  }

  /**
   * Get icon options from the icon list and expand to option groups.
   *
   * @return array
   *   Associative array with 2 levels. Group -> options.
   */
  public function getIconOptions() {
    $options = [];
    $grouped = [];
    $list = $this->getIconList();

    foreach ($list as $name) {
      foreach ($this->types as $prefix => $label) {
        if (strpos($name, $prefix) !== FALSE) {
          $options[(string) $label][$name] = $name;
          $grouped[$name] = $name;
        }
      }
    }
    $others = array_diff($list, $grouped);
    $other_label = $this->t('Other');
    foreach ($others as $other) {
      $options[(string) $other_label][$other] = $other;
    }

    return $options;
  }

}
