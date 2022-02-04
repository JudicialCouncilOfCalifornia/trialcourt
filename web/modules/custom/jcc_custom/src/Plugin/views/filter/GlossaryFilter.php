<?php

namespace Drupal\jcc_custom\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Database\Database;

/**
 * Glossary filter.
 *
 * @ViewsFilter("jcc_custom_glossary_filter")
 */
class GlossaryFilter extends StringFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    if ($this->value != 'All') {
      $field = "$this->tableAlias.$this->realField";

      $info = $this->operators();
      if (!empty($info[$this->operator]['method'])) {
        $this->{$info[$this->operator]['method']}($field);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function opEqual($field) {
    $letter = \Drupal::request()->query->get('gl');
    if (!empty($letter)) {
      $this->query->addWhere($this->options['group'], $field, $this->value, '=');
    }
  }

  /**
   * Group filter.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $connection = Database::getConnection();
    $letters_array = [];
    $letters = '';
    $query = $connection->select('node_field_data', 'n')
      ->fields('n', ['title'])
      ->condition('n.status', 1)
      ->condition('n.type', 'importer')
      ->distinct()
      ->orderBy('n.title', 'ASC')
      ->execute();
    $options = [];
    foreach ($query as $row) {
      $first_letter = substr($row->title, 0, 1);
      if (!in_array($first_letter, $letters_array)) {
        $letters_array[] = $first_letter;
        $upper = strtoupper($first_letter);
        $letters .= '<div class="glossary-item"><a href="?gl=' . $upper . '">' . $upper . '</a></div>';
      }
    }
    $form['value'] = [
      '#type' => 'markup',
      '#markup' => Markup::create('<div class="glossary-links">' . $letters . '</div>'),
    ];
  }

}
