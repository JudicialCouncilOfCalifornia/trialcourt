<?php

namespace Drupal\jcc_elevated_rfp_solicitations\Utility;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Helper class to check if a daterange item covers all day.
 *
 * @package Drupal\date_all_day\Utility
 */
class JccElevatedRfpSolicitationsAllDayHelper {
  const TIME_FORMAT = 'H:i:s';
  const DATE_FORMAT = 'm/d/Y';

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
  public static function isAllDay(array|DateRangeItem $item): bool {

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
   *
   * @return bool
   *   A boolean indicating if a daterange item covers all day or not.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public static function isSingleDate(array|DateTimeItem $item): bool {
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
    $formatted_start = $start_date->format(self::DATE_FORMAT, ['timezone' => $timezone]);
    $formatted_end = $end_date ? $end_date->format(self::DATE_FORMAT, ['timezone' => $timezone]) : FALSE;

    // If the formatted start and end dates are the same, regardless of time, we
    // can conclude that this is a single day.
    if ($formatted_start == $formatted_end) {
      return TRUE;
    }

    return FALSE;
  }

}
