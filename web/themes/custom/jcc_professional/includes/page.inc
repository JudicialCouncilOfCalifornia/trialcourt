<?php

/**
 * @file
 * Functions for page processing.
 */

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_professional_preprocess_page(&$variables) {
  $variables['logo'] = theme_get_setting('logo', 'jcc_professional');
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_professional');

  $variables['site_name'] = [
    'first' => theme_get_setting('site_name_first', 'jcc_professional'),
    'second' => theme_get_setting('site_name_second', 'jcc_professional'),
  ];

  $variables['main_links'] = jcc_components_get_menu_array('main');
  $variables['footer_links'] = jcc_components_get_menu_array('footer');
  $variables['social_links'] = jcc_professional_social_links();

  $variables['hat'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_professional'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_professional'),
    ],
    'links' => jcc_components_get_menu_array('hat'),
  ];

  $variables['shoe'] = [
    'title' => [
      'text' => theme_get_setting('hat_shoe_text', 'jcc_professional'),
      'url' => theme_get_setting('hat_shoe_url', 'jcc_professional'),
    ],
    'links' => jcc_components_get_menu_array('shoe'),
  ];

  // Inherit landing page masthead color setting if sub-page.
  $current_path = \Drupal::service('path.current')->getPath();
  $current_path_alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  preg_match('/^\/[^\/]*/', $current_path_alias, $parent_path);
  $parent_node_path = \Drupal::service('path_alias.manager')->getPathByAlias($parent_path[0]);
  if (preg_match('/node\/(\d+)/', $parent_node_path, $node_path) && ($parent_node_path != $current_path)) {
    $parent_masthead_color = Node::load($node_path[1])->field_masthead_color->getString();
    // Never allow mega masthead for subpages.
    $variables['parent_masthead_color'] = str_replace('mega ', '', $parent_masthead_color);
  }

  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node instanceof NodeInterface) {
    if ($node->hasField('field_pre_selected_notification_')) {
      if (!$node->get('field_pre_selected_notification_')->isEmpty()) {
        if ($node->getType() == 'subpage' ||
          $node->getType() == 'landing_page') {
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('jcc_messaging_center')) {
            $groups = $node->field_pre_selected_notification_->referencedEntities();
            $variables['group_target_id'] = '';
            $group_labels = '';
            foreach ($groups as $key => $group) {
              if ($key == 0) {
                $group_labels = $group->label();
                $variables['group_target_id'] = $group->id();
              }
              else {
                $group_labels = $group_labels . ', ' . $group->label();
                $variables['group_target_id'] = $variables['group_target_id'] . '-' . $group->id();
              }
            }
          }
        }
      }
    }
  }

  // Granicus integration.
  // Setup provided by Drupal theme settings.
  // - granicus_event_type ... find imported events by taxonomy name.
  // - granicus_events_api ... accessing real-time Granicus information.
  // - granicus_status_override ... opt always display as live during timeframe.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    // Find events tagged as granicus_event_type value.
    $term_id = theme_get_setting('granicus_event_type');
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $imported_events = $term_id ? $node_storage->loadByProperties(['field_event_type' => $term_id]) : '';

    // If matching event types found.
    if ($imported_events) {
      $today = date('m/d/y');
      $term_storage = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');

      $livecast = [];
      foreach ($imported_events as $event) {
        $type = $term_storage->load($term_id)->name->value;
        $event_date = $event->get('field_date_range')->start_date ? $event->get('field_date_range')->start_date->format('m/d/y') : '';
        $scheduled_type = $term_storage->load($event->get('field_event_type')->target_id)->name->value;

        // If matching event is scheduled for today.
        if ($event_date == $today && $scheduled_type == $type) {
          $taxonomy_manager = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term');
          $type = $taxonomy_manager->load($term_id)->getName() ? $taxonomy_manager->load($term_id)->getName() : '';
          $start_timestamp = strtotime($event->get('field_date_range')->start_date);
          // Get Webcast URL from event description.
          $body = $event->get('body')->value;
          $pattern = '/\/\/jcc\.granicus\.com\/player\/event\/\d+/';
          preg_match($pattern, $body, $event_url);
          $event_url = $event_url ? $event_url[0] : '';
          // Live captions.
          $captioning = NULL;

          // Banner visibility by status.
          $status = NULL;
          $current_time = date('h:i A');
          $current_timestamp = strtotime($current_time);
          $current_to_start = $current_timestamp - $start_timestamp;
          $end_timestamp = strtotime($event->get('field_date_range')->end_date);

          if (theme_get_setting('granicus_status_override') == 1) {
            // If always live broadcast override.
            if ($current_timestamp < $start_timestamp || $current_timestamp < $end_timestamp) {
              $status = 'live';
            }
          }
          elseif ($current_to_start > -3600  && $current_to_start < 3600) {
            // Display pending 1 hour before & after scheduled time.
            $status = 'starting soon';
          }
          elseif ($current_timestamp >= $start_timestamp && $current_timestamp < $end_timestamp) {
            // Default display until scheduled time ends.
            $status = 'no broadcast';
          }

          // Granicus real-time updates.
          $granicus_events = [];
          $feed = theme_get_setting('granicus_events_feed');
          if ($feed) {
            $request = file_get_contents($feed);
            if ($request) {
              $granicus_events = simplexml_load_string($request, 'SimpleXMLElement', LIBXML_NOCDATA);
              $granicus_events = json_encode($granicus_events);
              $granicus_events = json_decode($granicus_events, TRUE);
            }
          }

          if ($granicus_events) {
            foreach ($granicus_events['event'] as $granicus_event) {
              if ($granicus_event['date'] == $today && $granicus_event['type'] == $type) {
                // Live broadcast check.
                if ($status != 'live') {
                  switch ($granicus_event['status']) {
                    case 'Running':
                      $status = 'live';
                      break;
                  }
                }

                // If Webcast URL not found in Drupal event use URL from feed.
                if (!$event_url) {
                  $event_url = $granicus_event['media_url'];
                }

                // Captioning links which can change at any time.
                // Saved with Newsroom events TBD.
                if ($granicus_event['captioning']) {
                  $captioning_links = [];
                  $captions = explode(';', $granicus_event['captioning']);
                  foreach ($captions as $caption) {
                    if ($caption) {
                      $caption = explode('|', $caption);
                      $title = $caption[0];
                      $url = \Drupal::service('file_url_generator')->generate($caption[1]);
                      $captioning_links[] = \Drupal::service('link_generator')->generate($title, $url);
                    }
                  }
                  switch (count($captioning_links)) {
                    case 2:
                      $captioning_links = implode(' or ', $captioning_links);
                      break;

                    default:
                      $captioning_links = $captioning_links[0];
                  }
                  $captioning = ['#markup' => 'View ' . $captioning_links . ' Captions'];
                }
              }
            }
          }

          // Display banner.
          if ($status) {
            $livecast = [
              'title' => $type,
              'date' => $event_date,
              'time' => \Drupal::service('date.formatter')->format($start_timestamp, 'custom', 'h:i A'),
              'url' => $event_url,
              'captioning' => $captioning,
              'status' => $status,
            ];
          }
        }
      }
      $variables['livecast'] = $livecast;

      // Disable homepage page cache to not block real time Granicus info.
      \Drupal::service('page_cache_kill_switch')->trigger();
    }
  }
}

/**
 * Generate link array for social links.
 *
 * @return array
 *   An array of links formatted for a component.
 */
function jcc_professional_social_links() {
  $social = [
    [
      'name' => 'email',
      'title' => t('Email'),
    ],
    [
      'name' => 'facebook',
      'title' => t('Facebook'),
    ],
    [
      'name' => 'instagram',
      'title' => t('Instagram'),
    ],
    [
      'name' => 'flickr',
      'title' => t('Flickr'),
    ],
    [
      'name' => 'linkedin',
      'title' => t('LinkedIn'),
    ],
    [
      'name' => 'rss',
      'title' => t('RSS'),
    ],
    [
      'name' => 'twitter',
      'title' => t('Twitter'),
    ],
    [
      'name' => 'youtube',
      'title' => t('YouTube'),
    ],
  ];

  foreach ($social as $k => $item) {
    $url = theme_get_setting($item['name'], 'jcc_professional');
    if (!empty($url)) {
      $social[$k]['url'] = $url;
    }
    else {
      unset($social[$k]);
    }
  }

  return $social;
}
