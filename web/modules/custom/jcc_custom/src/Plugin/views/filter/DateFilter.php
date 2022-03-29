<?php

namespace Drupal\jcc_custom\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Database\Database;

/**
 * Date filter.
 *
 * @ViewsFilter("jcc_custom_date_filter")
 */
class DateFilter extends StringFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * Datetime filter.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $datetime = \Drupal::request()->query->get('dt');
    $connection = Database::getConnection();
    $results = $connection->query("SELECT DISTINCT n.field_importer_date_value FROM {node__field_importer_date} n
      WHERE n.field_importer_date_value >= CURDATE()")->fetchAll();
    $dates = [];
    $dates_idx = [];
    $active_date = '';
    foreach ($results as $row) {
      $active = '';
      $formatted_date = $this->dateTimeWithTimezone($row->field_importer_date_value);
      if (!empty($datetime) && $datetime == $row->field_importer_date_value) {
        $active_date = '<br/><div class="current-active-date">Viewing date: <strong>' . $this->dateTimeWithTimezone($row->field_importer_date_value) . '</strong></div>';
        $active = 'active';
      }
      if (!in_array($formatted_date, $dates_idx)) {
        $dates_idx[] = $formatted_date;
        $dates[] = '<li><a href="?dt=' . $row->field_importer_date_value . '" class="' . $active . '">' . $formatted_date . '</a></li>';
      }
    }
    $form['value'] = [
      '#type' => 'item',
      '#markup' => Markup::create('<ul class="usa-list usa-list--unstyled">' . implode(' ', $dates) . $active_date . '</ul>'),
    ];
  }

  /**
   * Get correctly formatted date (correction with the timezone).
   */
  protected function dateTimeWithTimezone($db_date, $format = 'l, M d, Y') {
    $utc = new \DateTimeZone("UTC");
    $newTZ = new \DateTimeZone("America/Los_Angeles");
    $date = new \DateTime($db_date, $utc);
    $date->setTimezone($newTZ);
    return $date->format($format);
  }

}
