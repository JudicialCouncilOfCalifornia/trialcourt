<?php

namespace Drupal\jcc_custom\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting more link label override.
 *
 * @ViewsReferenceSetting(
 *   id = "link_display",
 *   label = @Translation("More link url"),
 *   default_value = "",
 * )
 */
class JccViewsReferenceMoreLinkUrl extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'entity_autocomplete';
    $form_field['#target_type'] = 'node';
    $form_field['#title'] = $this->t('More link URL');
    $form_field['#description'] = $this->t('Override the "Read More" link URL for this View. Internal or external urls are allowed.');
    $form_field['#required'] = FALSE;
    $form_field['#process_default_value'] = FALSE;
    $form_field['#weight'] = 99;
    $form_field['#attributes'] = [
      'data-autocomplete-first-character-blacklist' => '/#?',
    ];
    $form_field['#element_validate'] = [
      [
        'Drupal\link\Plugin\Field\FieldWidget\LinkWidget',
        'validateUriElement',
      ],
    ];

    // Process the url for an entity or an external URL.
    if (strpos($form_field['#default_value'], 'entity:') === 0) {
      $value = explode('/', $form_field['#default_value']);
      $entity_id = end($value);
      $entity = \Drupal::entityTypeManager()->getStorage($form_field['#target_type'])->load($entity_id);
      $form_field['#default_value'] = $entity_id ? $entity : '';
      $form_field['#process_default_value'] = TRUE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (!empty($value)) {
      $view->display_handler->setOption('link_display', $value);
    }
  }

}
