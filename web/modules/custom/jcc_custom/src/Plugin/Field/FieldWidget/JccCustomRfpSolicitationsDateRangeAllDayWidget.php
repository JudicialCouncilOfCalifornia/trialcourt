<?php

namespace Drupal\jcc_custom\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;

/**
 * Plugin implementation of the 'daterange_all_day' widget.
 *
 * @FieldWidget(
 *   id = "jcc_custom_daterange_all_day",
 *   label = @Translation("Date and time range with 'all day' option (JCC)"),
 *   field_types = {
 *     "daterange",
 *     "daterange_all_day"
 *   }
 * )
 */
class JccCustomRfpSolicitationsDateRangeAllDayWidget extends DateRangeDefaultWidget {

  const TIME_FORMAT = 'H:i:s';

  const DATE_FORMAT = 'm-d-Y';

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $values = $items->get($delta)->getValue();

    $element['settings'] = [
      '#type' => 'container',
    ];

    // Add All day checkbox with states api.
    $all_day_value = !empty($values) ? $this->isAllDay($items->get($delta)) : TRUE;
    $element['settings']['all_day'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('All day'),
      '#weight' => 3,
      '#default_value' => $all_day_value,
      '#value' => $all_day_value,
      '#parents' => [$items->getName(), $delta, 'all_day'],
    ];

    // Add checkbox to enable/disable the end date.
    $enable_end_date_value = !empty($values) ? !$this->enableEndDate($items->get($delta)) : FALSE;
    $element['settings']['enable_end_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable end date'),
      '#weight' => 4,
      '#default_value' => $enable_end_date_value,
      '#value' => $enable_end_date_value,
      '#parents' => [$items->getName(), $delta, 'enable_end_date'],
    ];

    $element['#attached'] = [
      'library' => [
        'jcc_custom/date_all_day',
      ],
    ];

    // Set end date as optional.
    $element['end_value']['#required'] = FALSE;

    $element['settings']['help_text'] = [
      '#type' => 'markup',
      '#prefix' => '<small class="datetime__help-text">',
      '#markup' => $this->t("To display a single time, set start and end times to same value."),
      '#suffix' => '</small>',
      '#weight' => 5,
      '#parents' => [$items->getName(), $delta, 'match_times_action'],
    ];

    // Add action link that sets the end time to match the start time.
    $element['settings']['match_times_action'] = [
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#value' => $this->t("Click to match end to start time")->render(),
      '#attributes' => [
        'class' => 'button--small button datetime__action__match-times',
        'value' => $this->t("Match end to start time")->render(),
      ],
      '#weight' => 6,
      '#parents' => [$items->getName(), $delta, 'match_times_action'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $start_date = $element['value']['#value']['object'];
    $end_date = $element['end_value']['#value']['object'];

    if ($start_date instanceof DrupalDateTime) {
      if ($end_date instanceof DrupalDateTime) {
        if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
          $interval = $start_date->diff($end_date);
          if ($interval->invert === 1) {
            $form_state->setError($element, $this->t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
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

        if (!empty($item['end_value']) && $item['end_value'] instanceof DrupalDateTime) {
          $end_date = $item['end_value'];
          // All day fields end at midnight on the end date, but are
          // stored like datetime fields, so we need to adjust the time.
          // This function is called twice, so to prevent a double conversion
          // we need to explicitly set the timezone.
          $end_date->setTimeZone($timezone);
          if ($is_all_day) {
            $end_date->setTime(23, 59, 59);
          }
          $item['end_value'] = $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, [
            'timezone' => DateTimeItemInterface::STORAGE_TIMEZONE,
          ]);
        }
      }
    }
    return $values;
  }

  /**
   * Helper function to check if a daterange item covers all day.
   *
   * @param array|\Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem $item
   *   The date range item to check.
   *
   * @return bool
   *   A boolean indicating if a daterange item covers all day or not.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function isAllDay(array|DateRangeItem $item): bool {

    // Get our start and end times (if exists) from our DateRangeItem.
    if ($item instanceof DateRangeItem) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item->get('value')->getDateTime();
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $item->get('end_value')->getDateTime();
    }
    elseif (is_array($item) && isset($item['value'])) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item['value'];
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      if (isset($item['end_value']) && is_array($item['end_value']) && empty($item['end_value']['object'])) {
        $end_date = NULL;
      }
      else {
        $end_date = $item['end_value'];
      }
    }
    else {
      throw new \InvalidArgumentException('Argument $item should be either a Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem object, either an array with a \Drupal\Core\Datetime\DrupalDateTime in the "value" key.');
    }

    // If no Start Date, return.
    if (empty($start_date)) {
      return FALSE;
    }

    $timezone = date_default_timezone_get();
    $start_time = $start_date->format(self::TIME_FORMAT, ['timezone' => $timezone]);
    $end_time = $end_date ? $end_date->format(self::TIME_FORMAT, ['timezone' => $timezone]) : FALSE;

    // If the start time is at the beginning of the day, and no end date exists
    // (of if the end date exists and its end time is set to very end of day),
    // we can conclude that this range is an all day event.
    if ($start_time == "00:00:00" && $end_time == "23:59:59") {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Helper function to check if a daterange item covers all day.
   *
   * @param array|\Drupal\datetime\Plugin\Field\FieldType\DateTimeItem $item
   *   The date range item to check.
   * @param bool $check_end_time
   *   Whether to check the date or the time value of the date range item.
   *
   * @return bool
   *   A boolean indicating if a daterange item covers all day or not.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function enableEndDate(array|DateTimeItem $item, $check_end_time = FALSE): bool {

    $format = $check_end_time ? self::TIME_FORMAT : self::DATE_FORMAT;

    // Get our start and end times (if exists) from our DateRangeItem.
    if ($item instanceof DateRangeItem) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item->get('value')->getDateTime();
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      $end_date = $item->get('end_value')->getDateTime();
    }
    elseif (is_array($item) && isset($item['value'])) {
      /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
      $start_date = $item['value'];
      /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
      if (isset($item['end_value']) && is_array($item['end_value']) && empty($item['end_value']['object'])) {
        $end_date = NULL;
      }
      else {
        $end_date = $item['end_value'];
      }
    }
    else {
      throw new \InvalidArgumentException('Argument $item should be either a Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem object, either an array with a \Drupal\Core\Datetime\DrupalDateTime in the "value" key.');
    }

    // If no Start Date, return.
    if (empty($start_date)) {
      return FALSE;
    }

    $timezone = date_default_timezone_get();
    $formatted_start = $start_date->format($format, ['timezone' => $timezone]);
    $formatted_end = $end_date ? $end_date->format($format, ['timezone' => $timezone]) : FALSE;

    // If the formatted start and end dates are the same, regardless of time, we
    // can conclude that this is a single day. If check_end_time is true, then
    // the time values are checked if they are the same.
    if ($formatted_start == $formatted_end) {
      return TRUE;
    }

    return FALSE;
  }

}
