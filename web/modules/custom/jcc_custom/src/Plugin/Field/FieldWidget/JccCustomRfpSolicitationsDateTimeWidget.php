<?php

namespace Drupal\jcc_custom\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeDefaultWidget;

/**
 * Plugin implementation of the 'daterange_all_day' widget.
 *
 * @FieldWidget(
 *   id = "jcc_custom_datetime",
 *   label = @Translation("Date and time with 'all day' option (JCC)"),
 *   field_types = {
 *     "datetime",
 *   }
 * )
 */
class JccCustomRfpSolicitationsDateTimeWidget extends DateTimeDefaultWidget {

  const TIME_FORMAT = 'H:i:s';

  const DATE_FORMAT = 'm-d-Y';

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $values = $items->get($delta)->getValue();

    // Add All day checkbox with states api.
    $element['all_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All day'),
      '#weight' => 3,
      '#default_value' => !empty($values) ? $this->isAllDay($items->get($delta)) : TRUE,
      '#value' => !empty($values) ? $this->isAllDay($items->get($delta)) : TRUE,
      '#parents' => [$items->getName(), $delta, 'all_day'],
    ];

    $element['#attached'] = [
      'library' => [
        'jcc_custom/date_all_day',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state): array {
    if (!empty($values)) {

      $timezone = timezone_open(date_default_timezone_get());
    }
    foreach ($values as &$item) {

      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {

        $is_all_day = $this->isAllDay($item);

        $start_date = $item['value'];
        // All day fields start at midnight on the starting date, but are
        // stored like datetime fields, so we need to adjust the time.
        // This function is called twice, so to prevent a double conversion
        // we need to explicitly set the timezone.
        $start_date->setTimeZone($timezone);
        if ($is_all_day) {
          $start_date->setTime(0, 0, 0);
        }
        $item['value'] = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, [
          'timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
        ]);
      }
    }
    return $values;
  }

  /**
   * Helper function to check if a datetime item covers all day.
   *
   * @param array|\Drupal\datetime\Plugin\Field\FieldType\DateTimeItem $item
   *   The date range item to check.
   *
   * @return bool
   *   A boolean indicating if a daterange item covers all day or not.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function isAllDay(array|DateTimeItem $item): bool {

    // Get our start and end times (if exists) from our DateRangeItem.
    if ($item instanceof DateTimeItem) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $date */
      $date = $item->get('value')->getDateTime();
    }
    elseif (is_array($item) && isset($item['value'])) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $date = $item['value'];
    }
    else {
      throw new \InvalidArgumentException('Argument $item should be either a Drupal\datetime\Plugin\Field\FieldType\DateTimeItem object, either an array with a \Drupal\Core\Datetime\DrupalDateTime in the "value" key.');
    }

    // If no Start Date, return.
    if (empty($date)) {
      return FALSE;
    }

    $timezone = date_default_timezone_get();
    $time = $date->format(self::TIME_FORMAT, ['timezone' => $timezone]);

    // If the start time is at the beginning of the day, and no end date exists
    // (of if the end date exists and its end time is set to very end of day),
    // we can conclude that this range is an all day event.
    if ($time == "00:00:00" || $time == "23:59:59") {
      return TRUE;
    }

    return FALSE;
  }

}
