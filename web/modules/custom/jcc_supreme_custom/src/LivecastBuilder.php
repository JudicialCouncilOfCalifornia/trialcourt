<?php

namespace Drupal\jcc_supreme_custom;

/**
 * Builds time-sensitive livecast banner.
 */
class LivecastBuilder {

  /**
   * Builds the banner using provided theme settings.
   *
   * @param string $theme
   *   The active theme name.
   *
   * @return array
   *   An uncacheable render array, including when no event is active.
   */
  public static function build($theme) {
    // Keep results uncacheable as event status changes with time.
    $build = ['#cache' => ['max-age' => 0]];

    // Theme settings required.
    $term_id = theme_get_setting('granicus_event_type', $theme);
    if (!$term_id) {
      return $build;
    }

    // Use event type specified in theme settings.
    $entity_type_manager = \Drupal::entityTypeManager();
    $term = $entity_type_manager->getStorage('taxonomy_term')->load($term_id);
    if (!$term) {
      \Drupal::logger('jcc_supreme_custom')->warning('The configured Granicus event type @id does not exist.', ['@id' => $term_id]);
      return $build;
    }

    // Load and process events for the specified type.
    $events = $entity_type_manager->getStorage('node')->loadByProperties(['field_event_type' => $term_id]);
    $current_timestamp = \Drupal::time()->getCurrentTime();
    foreach ($events as $event) {
      $start_timestamp = strtotime($event->get('field_date_range')->start_date);
      $end_timestamp = strtotime($event->get('field_date_range')->end_date);

      // Display only if the event is currently active.
      if ($current_timestamp <= $start_timestamp - 3600 || $current_timestamp >= $end_timestamp) {
        continue;
      }

      // Display event status.
      $status = !theme_get_setting('granicus_status_override', $theme) && $current_timestamp < $start_timestamp
        ? 'starting soon'
        : 'live';

      // Get livecast URL from event.
      preg_match('/\/\/jcc\.granicus\.com\/(.*)\d+/', $event->get('body')->value ?? '', $matches);
      $event_url = $matches[0] ?? $event->get('field_origin_url_plain')->value;

      // Get captioning links from theme settings.
      $captioning_links = [];
      $captions = [
        'English' => theme_get_setting('granicus_english_captions', $theme),
        'Español' => theme_get_setting('granicus_spanish_captions', $theme),
      ];
      foreach ($captions as $title => $caption) {
        if ($caption) {
          $url = \Drupal::service('file_url_generator')->generate($caption);
          $captioning_links[] = \Drupal::service('link_generator')->generate($title, $url);
        }
      }

      // Build banner component.
      $build['#theme'] = 'jcc_supreme_custom_livecast';
      $build['#livecast'] = [
        'title' => $term->label(),
        'date' => \Drupal::service('date.formatter')->format($start_timestamp, 'custom', 'm/d/y'),
        'time' => \Drupal::service('date.formatter')->format($start_timestamp, 'custom', 'h:i A'),
        'url' => $event_url,
        'captioning' => $captioning_links ? ['#markup' => 'View ' . implode(' or ', $captioning_links) . ' Captions'] : NULL,
        'status' => $status,
      ];
    }

    return $build;
  }

}
