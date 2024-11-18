<?php

/**
 * @file
 * Functions for views processing.
 */


use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\ViewExecutable;
use Drupal\Core\Render\Renderer;

use Drupal\views\Views;
/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_views_exposed_form_alter(array &$suggestions, array $variables) {
  if (strpos($variables['form']['#id'], 'views-exposed-form-') >= 0) {
    $form = str_replace('views-exposed-form-', '', $variables['form']['#id']);
    $form = str_replace('-', '_', $form);

    $suggestions[] = $variables['theme_hook_original'] . '__' . $form;
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_components_theme_suggestions_views_view_alter(array &$suggestions, array $variables) {
  // Add a suggestion based on the view name and the current display.
  $view = $variables['view'];
  $name = $view->id();
  $display = $view->current_display;
  $suggestions[] = 'views_view__' . $name;
  $suggestions[] = 'views_view__' . $name . '__' . $display;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view(&$variables) {
  $variables['header_footer_variant'] = theme_get_setting('header_footer_variant', 'jcc_components');

  if ($variables['id'] == 'search') {
    $variables['keywords'] = $variables['exposed']['search']['#value'];
    $variables['num_results'] = $variables['view']->total_rows;
  }
  $view = $variables['view'];
  // Initialize a counter for the self-help locations.
  $self_help_count = 0;
  // Initialize an array to store self-help flags for each row.
  $self_help_flags = [];
 
 
  // Ensure we are at the right view and display.
  // double check with location_listing
  if ($view->id() == 'location' && ($view->current_display == 'page_1' || $view->current_display == 'block_1')) {
    $location_has_self_help = false;
    
    foreach ($view->result as $key => $row) {
      $view_entity = $row->_entity;
      $has_self_help = false;
      if ($view_entity && $view_entity->hasField('field_add_services') && !$view_entity->get('field_add_services')->isEmpty()) {
        // Loop through the referenced paragraph entities in 'field_add_services'.
        foreach ($view_entity->get('field_add_services')->referencedEntities() as $paragraph) {
          // Check if the paragraph has the 'field_service_category' field and it's not empty.
          if ($paragraph->hasField('field_service_category') && !$paragraph->get('field_service_category')->isEmpty()) {
            // Loop through the referenced taxonomy terms in 'field_service_category'.
            foreach ($paragraph->get('field_service_category')->referencedEntities() as $term) {
              // Check if the term is the "Self-Help" term.
              if ($term->getName() == 'Self-Help') {
                $has_self_help = true;  // Set flag to true if the condition is met.
                break;  // No need to check further, we found "Self-Help".
              }
            }
          }
      
          // Break the loop if "Self-Help" is already found.
          if ($has_self_help) {
            break;
          }
        }
      }

      // Store the flag for the current row.
      $self_help_flags[$key] = $has_self_help;
      $variables['self_help_flag'] = $has_self_help;

      // If the current row has "Self-Help", increment the counter.
      if ($has_self_help) {
        $self_help_count++;
      }

      $view_entity = $row->_entity;
      if ($view_entity->hasField('field_address') && !$view_entity->get('field_address')->isEmpty()) {
          // Render the field using the 'default' view mode or another configured view mode.
          $field_render_array = $view_entity->get('field_address')->view('default');
          
          // Render the field to a string.
          $variables['my_address'] = \Drupal::service('renderer')->renderPlain($field_render_array);
      }
    }

    // Store the count of self-help locations in the $variables array to pass to Twig.
    $variables['self_help_count'] = $self_help_count;
    $variables['self_help_flags'] = $self_help_flags;  // Pass the self-help flags array to Twig.
  }

}

function jcc_components_preprocess_views_view_unformatted(&$variables) {
  $view = $variables['view'];
    if($view->id() == "location"){
      $location_page = \Drupal::configFactory()->getEditable('jcc_custom.settings')
      ->get('new_location_listing_page');

      if($location_page == 1 ){
        $variables['listing_page'] = "new";
      }
    }
}

/**
 * Implements hook_preprocess_HOOK() for news + sticky listing.
 */
function jcc_components_preprocess_views_view__news__sticky_list(&$variables) {
  if ($variables['view']->args && $variables['view']->args[0] != 'all') {
    $taxonomy_id = $variables['view']->args[0];
    $variables['more_custom_title'] = 'See All ' . Term::load($taxonomy_id)->get('name')->value . 's';
    $variables['more_custom_url'] = '/views/news?search=&type[' . $taxonomy_id . ']=' . $taxonomy_id . '&date_start=&date_end=';
  }
}

/**
 * Implements hook_preprocess_HOOK() for news listing.
 */
function jcc_components_preprocess_views_view__news__news_list(&$variables) {
  // Translation via TMGMT breaks dates where translators entered invlid date
  // values in date fields. If date is invalid clear it before template renders.
  $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  if (isset($variables['rows'][0]['#rows']) && is_array($variables['rows'][0]['#rows'])) {
    foreach ($variables['rows'][0]['#rows'] as &$row) {
      $node = $row['#node'];
      $nodeid = $node->id();
      $translated_path = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nodeid, $langcode);
      $row['#lang'] = $langcode;
      if ($langcode === "es") {
        $prefixed_translated_path = '/' . $langcode . $translated_path;
        $row['#sticky'] = $prefixed_translated_path;
      }
      if (!jcc_components_validate_date($node->field_date->value)) {
        $node->field_date->value = '';
      }
    }
  }
}

/**
 * Helpfer function to validate a date string.
 *
 * @param string $date
 *   The string to validate.
 * @param string $format
 *   The optional date format to test.
 *
 * @return bool
 *   TRUE if the string is a valid date format, else FALSE.
 */
function jcc_components_validate_date($date, $format = 'Y-m-d') {
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) === $date;
}

/**
 * Implements hook_preprocess_HOOK() for events full listing.
 */
function jcc_components_preprocess_views_view__events__events_list(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $events = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $nid = $row->id();
    $startDate = $row->field_date_range->start_date;
    $endDate = $row->field_date_range->end_date;

    $event = [
      'title' => $row->title->value,
      'url' => Url::fromRoute('entity.node.canonical', ['node' => $nid], ['absolute' => FALSE])->toString(),
      'type' => $row->field_event_type->target_id ? Term::load($row->field_event_type->target_id)->get('name')->value : '',
      'start_date' => $startDate,
      'end_date' => $endDate,
      'summary' => $row->body->view('teaser'),
    ];
    array_push($events, $event);
  }

  $variables['rows'] = $events;
}

/**
 * Implements hook_preprocess_HOOK() for oral arguments case documents listing.
 */
function jcc_components_preprocess_views_view__oral_arguments__block_1(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $docs = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $title = $row->title->value;
    if ($row->field_verbose_title->value) {
      $title = $row->field_verbose_title->value;
    }

    $media = Media::load($row->field_media->target_id);
    if (isset($media) && $media->field_media_file->target_id) {
      $file = File::load($media->field_media_file->target_id);
      $filename = $file->get('filename')->value;
      // File order weight through file naming convention.
      $file_weight = explode('-', $filename);
    }
    else {
      $file = NULL;
      $filename = NULL;
      $file_weight = "0";
    }
    $case = Term::load($row->field_case->target_id);

    $doc = [
      'case' => [
        'name' => $case && $case->get('name')->value ? $case->get('name')->value : 'NO CASE ASSIGNED',
        'description' => $case && $case->get('description')->value ? $case->get('description')->value : NULL,
        'weight' => $case && $case->get('field_weight')->value ? $case->get('field_weight')->value : 0,
      ],
      'title' => $title,
      'url' => $file ? $file->createFileUrl() : NULL,
      'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      'file_weight' => $file_weight ? $file_weight[0] : 0,
      // Contextual edit document option.
      'edit_option' => _jcc_components_can_edit_entity($row, t('Edit')),
    ];
    array_push($docs, $doc);
  }

  // Sort documents by weight.
  usort($docs, function ($a, $b) {
    return strnatcmp($a['file_weight'], $b['file_weight']);
  });

  // Group documents by case.
  $cases = [];
  foreach ($docs as $item) {
    if ($item['case']['name']) {
      $case = $item['case']['name'];
      $cases[$case]['weight'] = $item['case']['weight'];
      $cases[$case]['docs'][] = $item;
    }
  }

  // Sort cases by weight.
  uasort($cases, function ($a, $b) {
    return ($a['weight'] <=> $b['weight']);
  });

  $variables['items'] = $cases;
}

/**
 * Implements hook_preprocess_HOOK() for oral arguments briefs listing.
 */
function jcc_components_preprocess_views_view__oral_arguments__block_2(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $page = [
      'year' => $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined',
      'link' => $row->toLink(NULL, 'canonical', ['absolute' => TRUE]),
    ];
    array_push($pages, $page);
  }
  $briefs = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $briefs[$year][] = $item;
    }
  }
  $variables['items'] = $briefs;
}

/**
 * Implements hook_preprocess_HOOK() for minutes.
 */
function jcc_components_preprocess_views_view__case_information__block_1(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $current_year = date('Y');
  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $doc_year = $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined';
    $years_past = $current_year - $doc_year;
    if ($years_past < 3) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      $page = [
        'year' => $doc_year,
        'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      ];
      array_push($pages, $page);
    }
  }

  $minutes = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $minutes[$year][] = $item;
    }
  }
  $variables['items'] = $minutes;
}

/**
 * Implements hook_preprocess_HOOK() for weekly case summaries.
 */
function jcc_components_preprocess_views_view__case_information__block_2(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $current_year = date('Y');
  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $doc_year = $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined';
    $years_past = $current_year - $doc_year;
    if ($years_past < 3) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      $page = [
        'year' => $doc_year,
        'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      ];
      array_push($pages, $page);
    }
  }

  $summaries = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $summaries[$year][] = $item;
    }
  }
  $variables['items'] = $summaries;
}

/**
 * Implements hook_preprocess_HOOK() for weekly conference results.
 */
function jcc_components_preprocess_views_view__case_information__block_3(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $current_year = date('Y');
  $pages = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $doc_year = $row->field_date->date ? $row->field_date->date->format('Y') : 'Undefined';
    $years_past = $current_year - $doc_year;
    if ($years_past < 3) {
      $media = Media::load($row->field_media->target_id);
      $file = File::load($media->field_media_file->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      $page = [
        'year' => $doc_year,
        'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
        'filing_date' => $row->field_date->date ? $row->field_date->date->format('F j, Y') : 'Undefined',
      ];
      array_push($pages, $page);
    }
  }

  $results = [];
  foreach ($pages as $item) {
    if ($item['year']) {
      $year = $item['year'];
      $results[$year][] = $item;
    }
  }
  $variables['items'] = $results;
}

/**
 * Implements hook_preprocess_HOOK() for calendar results.
 */
function jcc_components_preprocess_views_view__case_information__block_4(&$variables) {
  if (empty($variables['rows'][0]['#rows'])) {
    return;
  }

  $results = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $row = $row['#node'];
    $media = Media::load($row->field_media->target_id);
    $file = File::load($media->field_media_file->target_id);
    $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
    // BEGIN - Create amended identifier from filename.
    $file_name = preg_replace('/\\.[^.\\s]{3,4}$/', '', $file->filename->value);
    $file_amended = NULL;
    if (preg_match("/^[a-z]$/i", substr($file_name, -1))) {
      $file_amended = strtolower(substr($file_name, -1));
    }
    // END.
    $notes = strip_tags($row->body->processed, '<strong><em><a>');
    $result = [
      'title' => $row->title->value,
      'amended_code' => $file_amended,
      'url' => \Drupal::service('file_url_generator')->transformRelative($file_url),
      'hearing_date' => $row->field_date_range->start_date,
      'publish_date' => $row->field_date->date ? $row->field_date->date->format('m/d/Y') : 'Undefined',
      'notes' => $notes,
      'edit_option' => _jcc_components_can_edit_entity($row, t('Edit')),
    ];
    array_push($results, $result);
  }

  $variables['items'] = $results;
}

/**
 * Implements hook_preprocess_HOOK() for short document listing.
 */
function jcc_components_preprocess_views_view__documents__default(&$variables) {
  // Get latest Oral Arguments page for docs.
  $latest_doc = $variables['rows'][0]['#rows'][0]['#node'];
  $hearing_date = $latest_doc->field_date_range->start_date;
  $term_id = $latest_doc->field_document_type ? $latest_doc->field_document_type->target_id : NULL;

  if (Term::load($term_id)->get('name')->value == 'Oral Argument') {
    $nids = \Drupal::entityQuery('node')->condition('type', 'subpage')->execute();
    $nodes = Node::loadMultiple($nids);

    $oral_args = [];
    foreach ($nodes as $node) {
      if ($node->field_category->target_id == $term_id) {
        array_push($oral_args, $node);
      }
    }

    // Sort pages by publication date field.
    uasort($oral_args, function ($a, $b) {
      return strnatcmp($b->field_date->date->format('Ymd'), $a->field_date->date->format('Ymd'));
    });

    // Return published oral arguments if available.
    $latest_oa = array_slice($oral_args, 0, 1);
    if ($latest_oa && str_contains($latest_oa[0]->label(), $hearing_date->format('F j'))) {
      $variables['args_url'] = $latest_oa[0]->toUrl()->toString();
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for short document listing.
 */
function jcc_components_preprocess_views_view__search(&$variables) {
  // Personalized no results message.
  $variables['no_results_heading'] = 'We are unable to find a result for your search.';
  if (theme_get_setting('no_search_results_heading')) {
    $variables['no_results_heading'] = theme_get_setting('no_search_results_heading');
  }
  $variables['no_results_message'] = ['#markup' => Html::decodeEntities('Visit our <a href="/">homepage</a> for more options.')];
  $no_results_msg = theme_get_setting('no_search_results_message');
  if ($no_results_msg && $no_results_msg['value']) {
    $variables['no_results_message'] = ['#markup' => Html::decodeEntities($no_results_msg['value'])];
  }
}

/**
 * Implements hook_views_pre_render().
 */
function jcc_components_views_pre_render(ViewExecutable $view) {

  switch ($view->storage->id()) {
    case 'tentative_rulings':
      if ($view->current_display == 'block_1') {
        $view->result = [];
      }
      if (($view->current_display == 'tentative_rulings') && ($view->total_rows != 0)) {
        unset($view->attachment_before[1]);
      }
      if ($view->current_display == 'attachment_1' || $view->current_display == 'attachment_2') {
        $view->element['#custom_items'] = [];
        foreach ($view->result as $key => $row) {
          $view->element['#custom_items'][$key]['style'] = "borderless";
          $view_entity = $row->_entity;
          if ($view_entity->hasField('field_judge')) {
            $view->element['#custom_items'][$key]['title'] = $view->field['field_judge']->advancedRender($row)->__toString();
          }
          elseif ($view->field['title']) {
            $view->element['#custom_items'][$key]['title'] = $view->field['title']->advancedRender($row)->__toString();
          }
          $body_content = "";
          if ($view->field['field_department']->advancedRender($row) != "") {
            $body_content = $view->field['field_department']->advancedRender($row)->__toString() . '<br>';
          }
          $renderedContent = $view->field['field_address']->advancedRender($row);
 
          if (is_object($renderedContent) && method_exists($renderedContent, '__toString')) {
            $body_content .= $renderedContent->__toString();
          }
          else {
            $body_content .= $renderedContent;
          }
          $view->element['#custom_items'][$key]['body'] = Markup::create($body_content);
          if ($view->field['field_custom_link']->advancedRender($row) != "") {
            $link_uri = $view->field['field_custom_link']->advancedRender($row)->__toString();
            $view->element['#custom_items'][$key]['link']['url'] = Url::fromUri($link_uri);
            $view->element['#custom_items'][$key]['link']['text'] = 'Read more';
          }
        }
      }
      break;
    case "location":
      if($view->current_display == 'page_1'){
        foreach ($view->result as $key => $row) {

          $view_entity = $row->_entity;

          if ($view_entity->hasField('field_address')) {
            $variables['my_address'] = $view->field['field_address']->advancedRender($row);
          }
        }
      }
      if($view->current_display == 'block_1'){
        // An associative array to track unique Node IDs.
        $unique_results = [];
        
        // Loop through the results.
        foreach ($view->result as $index => $row) {
          $nid = $row->nid; // Ensure this is the correct property for Node ID.  
          // If this Node ID hasn't been added to the unique results, keep it.
          if (!isset($unique_results[$nid])) {
            $unique_results[$nid] = $row;
          }
          // Otherwise, unset the duplicate row.
          else {
            unset($view->result[$index]);
          }
        }     
       // Reindex the result array after removing duplicates.
        $view->result = array_values($view->result);

      }
      break;
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view__feeds_directory__page_1(&$variables) {
  if (empty($variables['rows'])) {
    return;
  }
  $feeds_types = [];
  $feeds_topics = [];
  foreach ($variables['rows'][0]['#rows'] as $row) {
    $term = $row['#row']->_entity;
    $term_name = $term->name->value;
    $tid = $term->tid->value;
    $vid = Term::load($tid)->bundle();
    $feed_details = [
      'icon' => [
        'name' => 'rss2',
        'title' => 'rss2',
      ],
      'name' => $term_name,
      'url' => '/cats/' . $tid,
    ];

    switch ($vid) {
      case 'news_type':
        array_push($feeds_topics, $feed_details);
        break;

      default:
        array_push($feeds_types, $feed_details);
    }
  }

  $feeds_types = $feeds_topics;
  foreach ($feeds_types as $key => $type) {
    if ($type['name'] != 'News Release') {
      unset($feeds_types[$key]);
    }
  }
  $feeds_types = array_values($feeds_types);
  $feeds_types[0]['name'] = 'News & Events';
  $feeds_types[0]['url'] = '/news-events';
  $feeds = [
    [
      'feed_type' => 'News Feed',
      'feed_items' => $feeds_types,
    ],
    [
      'feed_type' => 'Categories',
      'feed_items' => $feeds_topics,
    ],
  ];

  $variables['feeds'] = $feeds;
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_components_preprocess_views_view_row_rss(&$variables) {
  // Extending RSS content.
  $item = $variables['row'];
  $node = Node::load($item->nid);

  if (isset($node)) {
    $content_type = $node->type->entity->label();
    $extend_rss = FALSE;
    $tid = '';
    switch ($content_type) {
      case 'News':
        $extend_rss = TRUE;
        $tid = $node->get('field_news_type')->target_id;
        break;

      case 'Event':
        $extend_rss = TRUE;
        $tid = $node->get('field_event_type')->target_id;
        break;

      default:
        break;
    }
    if (isset($extend_rss)) {
      // Content type.
      $variables['content_type'] = $content_type;
      // "Sub-type" tag.
      if (isset($tid)) {
        $term = Term::load($tid);
        if (isset($term)) {
          $variables['type'] = $term->getName();
        }
      }
      // Author, not page creator, when applicable such as news contact.
      if (isset($node->field_contact) && !empty($node->get('field_contact')->target_id)) {
        $author_id = $node->get('field_contact')->target_id;
        $variables['author'] = Node::load($author_id);
      }
      // Tagline.
      if ($node->hasField('field_tagline')) {
        $variables['tagline'] = $node->get('field_tagline')->value;
      }
      // Publishing date for news article.
      $variables['pubDate'] = date('F j, Y', $node->created->value);
      // Date range when applicable such an event.
      if (isset($node->field_date_range) && !empty($node->get('field_date_range'))) {
        $variables['startDate'] = $node->get('field_date_range')->value;
        $variables['endDate'] = $node->get('field_date_range')->end_value;
      }
      // Body and teaser.
      $variables['body'] = $node->body->view('full');
      $variables['teaser'] = $node->body->view('teaser');
      // 1.x Extended event content to concat with main body.
      if ($content_type == 'Event' && !empty($node->get('field_event_moreinfo'))) {
        $variables['extended_details'] = $node->field_event_moreinfo->view('full');
      }
      // Attach images not inline with body.
      if (isset($node->field_images) && count($node->field_images) != 0) {
        $first_image = _jcc_get_first_image($node);
        $variables['image'] = $first_image['url'];
      }
      // Publish status.
      $variables['pubStatus'] = $node->get('status')->getString();
    }
  }
}

