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

    $this->applyEmptyColumnOptions();
  }

  /**
   * Enables core's global-empty-column handling for every displayed field.
   */
  protected function applyEmptyColumnOptions(): void {
    // Core's table preprocess already contains the column removal logic. Set
    // its per-field switches from our display-level option so newly added
    // fields are handled automatically.
    $hide = !empty($this->options['hide_empty_columns']);
    if (!isset($this->options['info']) || !is_array($this->options['info'])) {
      $this->options['info'] = [];
    }
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $this->options['info'][$field_id]['empty_column'] = $hide;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Views may rebuild style options after init (for example after a
    // configuration import). Apply the flags again at the final render point.
    $this->applyEmptyColumnOptions();

    if (!empty($this->options['hide_empty_columns'])) {
      $this->removeEmptyColumns();
    }
    return parent::render();
  }

  /**
   * Removes columns whose displayed fields are empty for every result row.
   *
   * Core performs a similar operation in its theme preprocess, but doing it
   * here ensures the style works for inherited display options and block
   * displays that do not retain the per-field table settings.
   */
  protected function removeEmptyColumns(): void {
    $fields = $this->displayHandler->getHandlers('field');
    $columns = $this->sanitizeColumns($this->options['columns'], $fields);
    $has_value = [];

    foreach ($columns as $field_id => $column_id) {
      if (!isset($fields[$field_id]) || !empty($fields[$field_id]->options['exclude'])) {
        continue;
      }
      $has_value[$column_id] = $has_value[$column_id] ?? FALSE;
      foreach ($this->view->result as $row) {
        if (trim((string) $fields[$field_id]->advancedRender($row)) !== '') {
          $has_value[$column_id] = TRUE;
          break;
        }
      }
    }

    $this->options['columns'] = array_filter(
      $columns,
      static fn(string $column_id): bool => !empty($has_value[$column_id])
    );
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
