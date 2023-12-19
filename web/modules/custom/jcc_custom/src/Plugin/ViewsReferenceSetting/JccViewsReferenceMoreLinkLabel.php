<?php

namespace Drupal\jcc_custom\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting more link override.
 *
 * @ViewsReferenceSetting(
 *   id = "use_more_text",
 *   label = @Translation("More link label"),
 *   default_value = "",
 * )
 */
class JccViewsReferenceMoreLinkLabel extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'textfield';
    $form_field['#title'] = $this->t('More link label');
    $form_field['#description'] = $this->t('Override the "Read More" link label for this view.');
    $form_field['#required'] = FALSE;
    $form_field['#weight'] = 98;
  }

  /**
   * {@inheritDoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (!empty($value)) {
      $view->display_handler->setOption('use_more_text', $value);
    }
  }

}
