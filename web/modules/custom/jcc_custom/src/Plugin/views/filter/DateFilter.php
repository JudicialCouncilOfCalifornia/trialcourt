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
    // 
  }

  /**
   * Datetime filter.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $datetime = \Drupal::request()->query->get('dt');
    $connection = Database::getConnection();
    $results = $connection->query("SELECT n.entity_id, n.field_importer_date_value FROM {node__field_importer_date} n
      WHERE YEARWEEK(n.field_importer_date_value) = YEARWEEK(NOW()) AND n.field_importer_date_value >= CURDATE()")->fetchAll();
    $dates = [];
    $active_date = '';
    foreach ($results as $row) {
      $active = '';
      if (!empty($datetime) && $datetime == $row->field_importer_date_value) {
        $active_date = '<div class="current-active-date">' . $this->dateTimeWithTimezone($row->field_importer_date_value) . '</div>';
        $active = 'active';
      }
      $dates[] = '<a href="?dt=' . $row->field_importer_date_value . '" class="' . $active .  '">' . $this->dateTimeWithTimezone($row->field_importer_date_value) . '</a>';
    }
    $form['value'] = [
      '#type' => 'item',
      '#markup' => Markup::create('<div class="datetime-links">' . implode(' ', $dates) . $active_date . '</div>'),
    ];
  }

  /**
   * Get correctly formatted date (correction with the timezone).
   */
  protected function dateTimeWithTimezone($db_date, $format = 'l, M d, Y') {
    $UTC = new \DateTimeZone("UTC");
    $newTZ = new \DateTimeZone("America/Los_Angeles");
    $date = new \DateTime($db_date, $UTC);
    $date->setTimezone( $newTZ );
    return $date->format($format);
  }

}
