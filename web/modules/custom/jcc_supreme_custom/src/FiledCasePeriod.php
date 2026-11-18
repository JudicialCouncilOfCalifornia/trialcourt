<?php

namespace Drupal\jcc_supreme_custom;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Calculates the filing week shown after the Tuesday 9 a.m. refresh.
 */
final class FiledCasePeriod {

  public function __construct(
    private TimeInterface $time,
    private ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Returns inclusive start and exclusive end dates in the site's timezone.
   */
  public function getWindow(?int $timestamp = NULL): array {
    $timezone = $this->configFactory->get('system.date')->get('timezone.default') ?: 'UTC';
    $now = (new \DateTimeImmutable('@' . ($timestamp ?? $this->time->getCurrentTime())))
      ->setTimezone(new \DateTimeZone($timezone));
    $refresh = $now->modify('monday this week')->modify('+1 day')->setTime(9, 0);
    if ($now < $refresh) {
      $refresh = $refresh->modify('-1 week');
    }
    return [
      'start' => $refresh->modify('-1 week')->format('Y-m-d'),
      'end' => $refresh->format('Y-m-d'),
    ];
  }

}
