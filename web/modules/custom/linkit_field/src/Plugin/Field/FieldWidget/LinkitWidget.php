<?php

namespace Drupal\linkit_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\linkit_field\Utility\LinkitHelper;

/**
 * Defines the 'linkit_field_linkit' field widget.
 *
 * @FieldWidget(
 *   id = "linkit_field_linkit",
 *   label = @Translation("Linkit"),
 *   field_types = {"link"},
 * )
 */
class LinkitWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkit_profile' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $linkit_profiles = \Drupal::entityTypeManager()
      ->getStorage('linkit_profile')
      ->loadMultiple();

    $options = [];
    foreach ($linkit_profiles as $linkit_profile) {
      $options[$linkit_profile->id()] = $linkit_profile->label();
    }

    $elements['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#options' => $options,
      '#default_value' => $this->getSetting('linkit_profile'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $linkit_profile_id = $this->getSetting('linkit_profile');
    $linkit_profile = \Drupal::entityTypeManager()->getStorage('linkit_profile')->load($linkit_profile_id);

    if ($linkit_profile) {
      $summary[] = $this->t('Linkit profile: @linkit_profile', ['@linkit_profile' => $linkit_profile->label()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $uri = $item->uri;
    $uri_scheme = parse_url($uri, PHP_URL_SCHEME);
    $is_nolink = substr($uri, 0, 14) === 'route:<nolink>';
    if (!empty($uri) && empty($uri_scheme) && $is_nolink) {
      $uri = LinkitHelper::uriFromUserInput($uri);
      $uri_scheme = parse_url($uri, PHP_URL_SCHEME);
    }
    if ($is_nolink) {
      $uri_as_url = $uri;
    }
    else {
      $uri_as_url = !empty($uri) ? Url::fromUri($uri)->toString() : '';
    }
    $linkit_profile_id = $this->getSetting('linkit_profile');

    // The current field value could have been entered by a different user.
    // However, if it is inaccessible to the current user, do not display it
    // to them.
    $default_allowed = !$item->isEmpty() && (\Drupal::currentUser()->hasPermission('link to any page') || $item->getUrl()->access());

    if ($default_allowed && $uri_scheme == 'entity') {
      $entity = LinkitHelper::getEntityFromUri($uri);
    }

    $element['uri'] = [
      '#type' => 'linkit',
      '#title' => $this->t('URL'),
      '#placeholder' => $this->getSetting('placeholder_url'),
      '#default_value' => $default_allowed ? $uri_as_url : NULL,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#description' => $this->t('Start typing to find content or paste a URL and click on the suggestion below.'),
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => $linkit_profile_id,
      ],
      '#error_no_message' => TRUE,
    ];

    $element['attributes']['href'] = [
      '#type' => 'hidden',
      '#default_value' => $default_allowed ? $uri : '',
    ];

    $element['attributes']['data-entity-type'] = [
      '#type' => 'hidden',
      '#default_value' => $default_allowed && isset($entity) ? $entity->getEntityTypeId() : '',
    ];

    $element['attributes']['data-entity-uuid'] = [
      '#type' => 'hidden',
      '#default_value' => $default_allowed && isset($entity) ? $entity->uuid() : '',
    ];

    $element['attributes']['data-entity-substitution'] = [
      '#type' => 'hidden',
      '#default_value' => $default_allowed && isset($entity) ? $entity->getEntityTypeId() == 'file' ? 'file' : 'canonical' : '',
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#placeholder' => $this->getSetting('placeholder_title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#maxlength' => 255,
      '#access' => $this->getFieldSetting('title') != DRUPAL_DISABLED,
      '#required' => $this->getFieldSetting('title') === DRUPAL_REQUIRED && $element['#required'],
      '#attributes' => [
        'class' => ['linkit-widget-title'],
      ],
      '#error_no_message' => TRUE,
    ];
    // Post-process the title field to make it conditionally required if URL is
    // non-empty. Omit the validation on the field edit form, since the field
    // settings cannot be saved otherwise.
    if (!$this->isDefaultValueWidget($form_state) && $this->getFieldSetting('title') == DRUPAL_REQUIRED) {
      $element['#element_validate'][] = [
        get_called_class(),
        'validateTitleElement',
      ];
    }

    // If cardinality is 1, ensure a proper label is output for the field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      // If the link title is disabled, use the field definition label as the
      // title of the 'uri' element.
      if ($this->getFieldSetting('title') == DRUPAL_DISABLED) {
        $element['uri']['#title'] = $element['#title'];
      }
      // Otherwise wrap everything in a details element.
      else {
        $element += [
          '#type' => 'fieldset',
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value['uri'] = LinkitHelper::uriFromUserInput($value['uri']);
      $value += ['options' => []];
    }
    return $values;
  }

}
