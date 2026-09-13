<?php

namespace Drupal\jcc_custom\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ViewExecutable;

/**
 * Renders case tables while omitting columns empty across the result set.
 *
 * @ViewsStyle(
 *   id = "jcc_case_table",
 *   title = @Translation("JCC Case table"),
 *   help = @Translation("Displays a case table and can hide columns empty across the current results."),
 *   theme = "views_view_table",
 *   display_types = {"normal"}
 * )
 */
class CaseTable extends Table {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['hide_empty_columns'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    // Core's table template preprocess already contains the efficient column
    // removal implementation. Set its per-field switches from our one
    // display-level option so newly added fields are handled automatically.
    $hide = !empty($this->options['hide_empty_columns']);
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $this->options['info'][$field_id]['empty_column'] = $hide;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['hide_empty_columns'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide columns empty across the current results'),
      '#default_value' => !empty($this->options['hide_empty_columns']),
      '#description' => $this->t('Columns are removed only when every displayed row has no value. Fields with data in at least one row remain visible.'),
      '#weight' => -10,
    ];
  }

}
