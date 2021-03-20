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
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $config) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->config = $config;
    $this->iconsPath = $this->config->get('jcc_courtyard_icons.settings')->get('icons_path');
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
      '#options' => $this->getIconList(),
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
    foreach ($this->getIconList() as $name) {
      $buttons[] = "<button class='jcc-courtyard-icons__button' data-icon-name='$name'>
        <svg width='35' height='35' role='img' aria-label='$name'>
          <use xlink:href='/$this->iconsPath#$name'></use>
        </svg>
      </button>";
    }

    if (!empty($buttons)) {
      return "<div class='jcc-courtyard-icons'>" . implode(' ', $buttons) . "</div>";
    }
    else {
      return '<h3>' . $this->t('Icons Not Configured') . '</h3>
        <p>' .
          $this->t('See <a href="/admin/config/system/jcc-courtyard-icons">JCC Courtyard Icons settings</a>') . '
        </p>
      ';
    }
  }

}
