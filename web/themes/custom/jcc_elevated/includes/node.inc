<?php

/**
 * @file
 * Preprocess and functions for node types and components.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_preprocess_node(&$variables) {
  $variables['sidebar_navigation'] = FALSE;

  $node = $variables['node'];
  $function = "jcc_elevated_node_" . $node->bundle();
  if (function_exists($function)) {
    $function($variables, $node);
  }

  $allowed_types = jcc_elevated_get_custom_setting('jcc_elevated.sidebar_menu_types');
  $placement = jcc_elevated_get_custom_setting('jcc_elevated.sidebar_menu_placement');
  if (!empty($placement) && in_array($node->bundle(), $allowed_types)) {
    // Build out the relevant menu tree. This depends on the current page
    // being in a menu and only on subpages.
    $menu_name = 'main';
    $level = 3;

    // Find the menu item corresponding to the entity (node).
    $menu_link_service = \Drupal::getContainer()
      ->get('plugin.manager.menu.link');
    $route_params = ['node' => $node->id()];
    $menu_links = $menu_link_service->loadLinksByRoute('entity.node.canonical', $route_params, $menu_name);
    if (!empty($menu_links)) {
      // Get our menu manager to later find the parent menu.
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

      // Build the active trail and menu parameters.
      $active_trail = Drupal::service('menu.active_trail')
        ->getActiveTrailIds($menu_name);
      $parameters = new MenuTreeParameters();
      $parameters->onlyEnabledLinks();
      $parameters->setMinDepth(1);
      $parameters->setMaxDepth(6);
      $parameters->setActiveTrail($active_trail);

      // Set the starting active menu level.
      $menu_trail_ids = array_reverse(array_values($parameters->activeTrail));
      if (isset($menu_trail_ids[$level - 1])) {
        $parameters->setRoot($menu_trail_ids[$level - 1]);
      }

      // Get the tree.
      $menu_tree_service = \Drupal::service('menu.link_tree');
      $tree = $menu_tree_service->load($menu_name, $parameters);

      // Apply some manipulators (checking the access, sorting).
      $tree = $menu_tree_service->transform($tree, [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ]);

      // Actually build the tree.
      $menu_build_tree = $menu_tree_service->build($tree);

      // Get the base active trail menu parent title.
      $parent_link = NULL;
      if (isset($menu_build_tree['#items']) && !empty($menu_build_tree['#items'])) {
        foreach ($menu_build_tree['#items'] as $id => $item) {
          $pid = $menu_link_manager->getParentIds($id);
          $pid = array_key_first(array_slice($pid, -2, 1));
          $parent_menu_item = $menu_link_manager->getInstance(['id' => $pid]);
          $url = $parent_menu_item->getUrlObject();
          $parent_link = [
            'title' => $parent_menu_item->getTitle(),
            'url' => $url,
          ];
          break;
        }
      }

      // Create our storybook component friendly sidebar navigation.
      if (isset($menu_build_tree['#items']) && !empty($menu_build_tree['#items'])) {
        // Set caches to trigger on changes.
        $variables['#cache']['contexts'][] = 'user.roles';
        $variables['#cache']['tags'][] = 'node:' . $node->id();

        foreach ($allowed_types as $type) {
          if (!empty($type)) {
            $variables['#cache']['tags'][] = 'node_list:' . $type;
          }
        }

        $variables['sidebar_navigation'] = [
          'menu_heading' => $parent_link,
          'links' => jcc_custom_build_menu_tree($menu_build_tree['#items']),
        ];
      }
    }
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_elevated_node_surplus_materials(array &$variables, NodeInterface $node): void {
  $node = $variables['node'];
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $category = $node->hasField('field_surplus_category') ? $node->get('field_surplus_category')->target_id : FALSE;
  $condition = $node->hasField('field_condition') ? $node->get('field_condition')->target_id : FALSE;
  $media_manager = $entity_type_manager->getStorage('media');
  $media_url = [];
  foreach ($node->field_media_file_multiple as $media_file) {
    if (!empty($media_file->target_id)) {
      $media = $media_manager->load($media_file->target_id);
      if ($media && $media->field_media_image->entity) {
        $uri = $media->field_media_image->entity->getFileUri();
        $url = file_create_url($uri);
        $media_url[] = ['url' => $url];
      }
    }
  }
  $variables['media_url'] = $media_url;
  $posted_raw = $node->get('field_posted')->value;
  $renewal_raw = $node->get('field_renewal_date')->value;
  $posted_formatted = '';
  $renewal_formatted = '';
  if (!empty($posted_raw)) {
    $posted_date = new DrupalDateTime($posted_raw);
    $posted_formatted = $posted_date->format('M d, Y');
  }
  if (!empty($renewal_raw)) {
    $renewal_date = new DrupalDateTime($renewal_raw);
    $renewal_formatted = $renewal_date->format('M d, Y');
  }
  $variables['title'] = $node->getTitle();
  $variables['surplus'] = [
    'Item Description' => $node->get('field_description')->value ?? '',
    'Posted On' => $posted_formatted ?? '',
    'Brand/Manufacturer' => $node->get('field_brand')->value ?? '',
    'Model Number' => $node->get('field_model_number')->value ?? '',
    'Quantity' => $node->get('field_quantity')->value ?? '',
    'Donation or Sell?' => $node->get('field_donate_or_sell')->value ?? '',
    'Unit Measure' => $node->get('field_unit_measure')->value ?? '',
    'Condition' => $condition && $taxonomy_manager->load($condition) ? $taxonomy_manager->load($condition)->get('name')->value : '',
    'Required Forms' => $node->get('field_required_forms')->value ?? '',
    'Comments' => $node->get('field_comments')->value ?? '',
    'Court' => $node->get('field_entity')->value ?? '',
    'Ref #' => $node->get('field_reference_number')->value,
    'Category' => $category && $taxonomy_manager->load($category) ? $taxonomy_manager->load($category)->get('name')->value : '',
    'Renewal Date' => $renewal_formatted ?? '',
    'Contact' => $node->get('field_contact_person')->value ?? '',
    'Phone' => $node->get('field_contact_number')->value ?? '',
    'Email' => $node->get('field_email_address')->value ?? '',
    'Disposition' => $node->get('field_disposition')->value ?? '',
  ];
}

/**
 * Node: News preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function jcc_elevated_node_news(array &$variables, NodeInterface $node): void {
  $render = \Drupal::service('renderer');
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();

  $body_field = $node->body->value;
  $variables['body_length'] = $body_field !== NULL ? strlen($body_field) : 0;
  // Render and truncate field_components.
  if ($node->body->isEmpty()) {
    $render_array = $node->field_components->view('default');
    $output = \Drupal::service('renderer')->render($render_array);
  }
  else {
    $content = $node->body->view('default');
    if (!empty($content['#items'])) {
      $body_content = $content['#items']->getValue()[0]['value'];
      $output = $body_content;
    }
  }
  if ($output !== NULL) {
    $variables['components_teaser'] =
      mb_strimwidth(trim(strip_tags($output)), 0, 250, '...');
  }
  $basic_page = Node::load($node->id());
  if ($basic_page->hasField('field_components')) {
    $textshare = $basic_page->get('field_components')->referencedEntities();
    foreach ($textshare as $text) {
      $variables['textshare'] = $text->get('field_text')->view('default');
      if ((empty($node->body->value)) && (!empty($text))) {
        $field_view = $text->get('field_text')->view('default');
        $textshare = $render->render($field_view);
        $news_content = trim(str_replace(["\n", "\r"], '', strip_tags($textshare)));
        $node->body->value = $news_content;
        $node->save();
      }
    }
  }
  if ($basic_page->hasField('field_media')) {
    $card_media = $basic_page->get('field_media');
    $media = $card_media->target_id ? Media::load($card_media->target_id) : FALSE;
    if ($media && $media->field_media_image && $media->field_media_image->entity) {
      $uri = $media->field_media_image->entity->getFileUri();
      $image_url = file_create_url($uri);
      $variables['imageurl'] = $image_url;
    }
    if ($media && $media->hasField('field_media_caption') && $media->hasField('field_media_citation')) {
      $caption_value = $media->get('field_media_caption')->value;
      $citation_value = $media->get('field_media_citation')->value;
      $variables['caption'] = $caption_value !== NULL ? strip_tags($caption_value) : '';
      $variables['citation'] = $citation_value !== NULL ? strip_tags($citation_value) : '';
    }
  }
  if ($basic_page->hasField('field_node_reference')) {
    $relatednewsdata = [];
    $relatednews = $basic_page->get('field_node_reference');
    foreach ($relatednews->referencedEntities() as $news) {
      $link = [
        '#markup' => '<a href="' . $news->toUrl()->toString() . '">' . $news->getTitle() . '</a>',
      ];
      $relatednewsdata[] = [
        'text' => $link,
      ];
    }
    $variables['relatednewsdata'] = $relatednewsdata;
  }
  $sidebarimages = [];
  foreach ($node->field_media_file_multiple as $media_file) {
    $media = Media::load($media_file->target_id);
    if ($media && $media->field_media_image->entity) {
      $uri = $media->field_media_image->entity->getFileUri();
      $media_url = file_create_url($uri);
    }
    if ($media && $media->hasField('field_media_caption') && $media->hasField('field_media_citation')) {
      $caption_value = $media->get('field_media_caption')->value;
      $citation_value = $media->get('field_media_citation')->value;
      $caption = $caption_value !== NULL ? strip_tags($caption_value) : '';
      $citation = $citation_value !== NULL ? strip_tags($citation_value) : '';
      $sidebarimages[] = [
        'caption' => $caption,
        'citation' => $citation,
        'url' => $media_url,
      ];
    }
  }
  $variables['sidebarimages'] = $sidebarimages;
}

/**
 * Node: Advisory body preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_advisory_body(array &$variables, NodeInterface $node): void {
  $renderer = \Drupal::service('renderer');
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $node_manager = $entity_type_manager->getStorage('node');

  // Determine if we hide or show the meeting tab by checkbox field check.
  $variables['hide_meetings_tab'] = 0;
  if ($node->hasField('field_hide_meetings_tab') && !empty($node->get('field_hide_meetings_tab')->first())) {
    $variables['hide_meetings_tab'] = $node->get('field_hide_meetings_tab')->first()->getValue()['value'];
  }

  // Build the meetings tab if we are to show it.
  if (!$variables['hide_meetings_tab']) {

    $variables['content']['meetings_tab'] = [
      '#type' => "container",
      '#attributes' => [
        'class' => 'stack',
        'style' => ' --gap: 0',
      ],
    ];

    // Wrap the content of this tab section in a container.
    $variables['content']['meetings_tab']['events'] = [
      '#type' => "container",
      '#attributes' => [
        'class' => 'field field-active-meetings',
        'style' => ' --gap: 0',
      ],
    ];

    // Add the rendered advisory body events view block into the meetings tab.
    $events_list = views_embed_view('advisory_body_events', 'block_1', $node->id());
    $rendered_events_list = $renderer->renderRoot($events_list);
    $variables['content']['meetings_tab']['events']['active_list']['#markup'] = $rendered_events_list;

    // Query to see if there are any upcoming events for this advisory node.
    // We only have to check for 1 item, so we limit range and just return the
    // count. This is much faster than using a view and avoids all the php
    // warnings.
    $now = new DrupalDateTime('now');

    $query_past = $node_manager->getQuery();
    $past_events = $query_past
      ->accessCheck(TRUE)
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_content_reference', $node->id(), '=')
      ->condition('field_event_date', $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT), '<=')
      ->range(0, 1)
      ->count()
      ->execute();

    // Add a link to the advisory_body_events past meetings page in the tab.
    if ($past_events) {

      // Wrap the content of this tab section in a container.
      $variables['content']['meetings_tab']['past_meetings'] = [
        '#type' => "container",
        '#attributes' => [
          'class' => 'field field-past-meetings',
          'style' => ' --gap: var(--s6)',
        ],
      ];

      // Generate the heading.
      $variables['content']['meetings_tab']['past_meetings']['heading'] = [
        '#type' => "html_tag",
        '#tag' => 'h3',
        '#value' => t('Past Meetings'),
      ];

      // Generate the link.
      $options = ['attributes' => ['class' => 'button button--text button--normal icon--arrow']];
      $variables['content']['meetings_tab']['past_meetings']['link_to_past_meetings'] = [
        '#type' => "link",
        '#title' => t('@title Past Meetings', ['@title' => $node->getTitle()])->render(),
        '#url' => Url::fromUserInput('/past-meetings/' . $node->id(), $options),
      ];
    }
  }

  // About tab.
  if ($variables['elements']['field_date_established']) {

    // Wrap the content of this tab in a container.
    $variables['content']['about_tab']['date_established'] = [
      '#type' => "container",
      '#attributes' => ['class' => 'field field-date-established'],
    ];

    // Generate the heading.
    if (isset($variables['content']['field_date_established']['#title'])) {
      $variables['content']['about_tab']['date_established']['heading'] = [
        '#type' => "html_tag",
        '#tag' => 'h3',
        '#value' => $variables['content']['field_date_established']['#title'],
      ];
    }

    // Move the fields into the tab.
    $variables['content']['about_tab']['date_established']['date'] = $variables['content']['field_date_established'];
    $variables['content']['about_tab']['date_established']['note'] = $variables['content']['field_date_note'];
  }

  // Move the About field into the about tab, after established date content.
  $variables['content']['about_tab']['about'] = $variables['elements']['field_about'];
}

/**
 * Node: ITC node preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_invitations_to_comment(array &$variables, NodeInterface $node): void {
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $nodeData = [];
  if ($node->hasField('field_category') && !$node->get('field_category')->isEmpty()) {
    $category_term_id = $node->get('field_category')->target_id;
    $term = $taxonomy_manager->load($category_term_id);
    $nodeData['category'] = $term ? $term->getName() : '';
  }

  if ($node->hasField('field_itc_number') && !$node->get('field_itc_number')->isEmpty()) {
    $nodeData['itc_number'] = $node->get('field_itc_number')->value;
  }

  if ($node->hasField('body') && !$node->get('body')->isEmpty()) {
    $nodeData['body'] = $node->body->value;
  }

  if ($node->hasField('field_deadline') && !$node->get('field_deadline')->isEmpty()) {
    $deadline = $node->field_deadline->value;
    $utc_deadline = new DateTime($deadline, new DateTimeZone('UTC'));
    $utc_deadline->setTimezone(new DateTimeZone('America/Los_Angeles'));
    $nodeData['deadline'] = $utc_deadline->format('F j, Y');
  }

  if ($node->hasField('field_file') && !$node->get('field_file')->isEmpty()) {
    $referenced_media = $node->get('field_file')->referencedEntities();
    if (!empty($referenced_media)) {
      $media = $referenced_media[0];
      if ($media->hasField('field_media_file') && !$media->get('field_media_file')->isEmpty()) {
        $file = $media->get('field_media_file')->entity;
        if ($file instanceof File) {
          $file_url = file_create_url($file->getFileUri());
          $variables['file_link'] = [
            '#type' => 'link',
            '#title' => $file->label(),
            '#url' => Url::fromUri($file_url),
            '#attributes' => [
              'target' => '_blank',
            ],
          ];
        }
      }
    }
    $variables['node'] = $nodeData;
  }
}

/**
 * Node: Facilities preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_facilities(array &$variables, NodeInterface $node): void {
  $renderer = \Drupal::service('renderer');
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $node_manager = $entity_type_manager->getStorage('node');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $media_manager = $entity_type_manager->getStorage('media');

  $basic_page = $node_manager->load($node->id());
  $variables['node'] = $node;
  $schedule_content = '';
  $field_schedule_render_array = $node->field_schedule->view('default');
  if (isset($field_schedule_render_array['#items']) && !$field_schedule_render_array['#items']->isEmpty()) {
    $to_render = ['#markup' => $field_schedule_render_array[0]['#text']];
    $schedule_content = $renderer->render($to_render);
  }
  $completion_date = $node->get('field_completion_date')->value ?? '';
  $occupancy_date = $node->get('field_occupancy_date')->date ? $node->get('field_occupancy_date')->date->format('F j, Y') : '';
  $ceqa = $node->field_ceqa->view('default');
  $ceqa_content = '';
  if (isset($ceqa['#items']) && !$ceqa['#items']->isEmpty()) {
    $to_render = ['#markup' => $ceqa[0]['#text']];
    $ceqa_content = $renderer->render($to_render);
  }

  if (isset($variables['node']) && $node->hasField('field_design_build_entity')) {
    $design_build_entity = $node->get('field_design_build_entity')->value;
  }

  if (isset($variables['node']) && $node->hasField('field_initial_funding_year')) {
    $initial_funding_year = $node->get('field_initial_funding_year')->value;
  }

  $agency_manager = $node->hasField('field_agency_manager') ? $node->get('field_agency_manager')->target_id : FALSE;
  $agency = $node->hasField('field_agency') ? $node->get('field_agency')->target_id : FALSE;
  $contractor = $node->hasField('field_general_contractor') ? $node->get('field_general_contractor')->target_id : FALSE;
  $criteria_architect = $node->hasField('field_criteria_architect') ? $node->get('field_criteria_architect')->target_id : FALSE;
  $managed_by = $node->hasField('field_managed_by') ? $node->get('field_managed_by')->target_id : FALSE;
  $county = $node->hasField('field_facility_county') ? $node->get('field_facility_county')->target_id : FALSE;
  $architectural_firm = $node->hasField('field_architectural_engineering_') ? $node->get('field_architectural_engineering_')->target_id : FALSE;
  $field_award_rendered = '';

  if ($node->hasField('field_award')) {
    $field_award_rendered = $renderer->renderRoot($variables['content']['field_award']);
  }

  $details = [
    'project_information' => [
      'Gross Square Footage' => $node->field_gross_square_footage->value,
      'Total Courtrooms' => $node->field_total_courtrooms->value,
      'Current Authorized Project Budget' => $node->field_budget->value,
      'Architectural/Engineering Firm'  => $architectural_firm && $taxonomy_manager->load($architectural_firm) ? $taxonomy_manager->load($architectural_firm)->get('name')->value : '',
      'Fund' => $node->field_fund->value,
      'Completion Date' => $completion_date,
      'Design-Build Entity' => strip_tags($design_build_entity ?? ''),
      'Initial Funding Year' => $initial_funding_year,
      'Occupancy Date' => $occupancy_date,
      'Construction Manager At Risk' => $agency_manager && $taxonomy_manager->load($agency_manager) ? $taxonomy_manager->load($agency_manager)->get('name')->value : '',
      'Construction Management Agency' => $agency && $taxonomy_manager->load($agency) ? $taxonomy_manager->load($agency)->get('name')->value : '',
      'General Contractor' => $contractor && $taxonomy_manager->load($contractor) ? $taxonomy_manager->load($contractor)->get('name')->value : '',
      'Criteria Architect' => $criteria_architect && $taxonomy_manager->load($criteria_architect) ? $taxonomy_manager->load($criteria_architect)->get('name')->value : '',
      'Managed By' => $managed_by && $taxonomy_manager->load($managed_by) ? $taxonomy_manager->load($managed_by)->get('name')->value : '',
      'Project Company' => strip_tags($node->field_project_company->value ?? ''),
      'Project Delivery method' => strip_tags($node->field_project_delivery_method->value ?? ''),
      'Consortium Partners' => strip_tags($node->field_consortium_partners->value ?? ''),
      'Project Financing' => strip_tags($node->field_project_financing->value ?? ''),
      'Total Capital Project Cost' => strip_tags($node->field_total_capital_project_cost->value ?? ''),
    ],
    'schedule' => $schedule_content,
    'ceqa' => $ceqa_content,
    'planning_study' => strip_tags($node->field_planning_study->value ?? ''),
    'project_advisory_group' => $node->field_project_advisory_group->value,
    'status' => $node->field_project_status->value == 'inprogress' ? 'In-Progress' : 'Completed',
    'hero_title' => strip_tags($node->field_hero_title->value),
    'county' => $county && $taxonomy_manager->load($county) ? $taxonomy_manager->load($county)->get('name')->value : '',
  ];
  $projectimages = [];
  $caption = '';
  $citation = '';
  $media_url = '';
  foreach ($node->field_media_file_multiple as $media_file) {
    if (!empty($media_file->target_id)) {
      $media = $media_manager->load($media_file->target_id);
      if ($media && $media->field_media_image->entity) {
        $uri = $media->field_media_image->entity->getFileUri();
        $media_url = file_create_url($uri);
      }
      if ($media && $media->hasField('field_media_caption') && $media->hasField('field_media_citation')) {
        $caption_value = $media->get('field_media_caption')->value;
        $citation_value = $media->get('field_media_citation')->value;
        $caption = $caption_value !== NULL ? strip_tags($caption_value) : '';
        $citation = $citation_value !== NULL ? strip_tags($citation_value) : '';
      }
      $projectimages[] = [
        'url' => $media_url,
        'caption' => $caption,
        'citation' => $citation,
      ];
    }
  }
  $variables['projectimages'] = $projectimages;

  if ($basic_page->hasField('field_media')) {
    $hero_image = $basic_page->get('field_media');
    $media = $media_manager->load($hero_image->target_id);
    if ($media && $media->field_media_image) {
      $uri = $media->field_media_image->entity->getFileUri();
      $image_url = file_create_url($uri);
      $variables['imageurl'] = $image_url;
      if ($media && $media->hasField('field_media_caption') && $media->hasField('field_media_citation')) {
        $caption_value = $media->get('field_media_caption')->value;
        $citation_value = $media->get('field_media_citation')->value;
        $caption = $caption_value !== NULL ? strip_tags($caption_value) : '';
        $citation = $citation_value !== NULL ? strip_tags($citation_value) : '';
        $variables['hero_caption'] = $caption;
        $variables['hero_citation'] = $citation;
      }
    }
  }

  if (!empty($node->body->summary)) {
    $elements[] = "<h2>Project Info</h2>";
    $elements[] = $node->body->summary;
    $elements[] = "<hr />";
  }

  if (!empty($node->body->value)) {
    $elements[] = "<h2>Background</h2>";
    $elements[] = $node->body->value;
    $elements[] = "<hr />";
  }

  if (!empty($details['ceqa'])) {
    $elements[] = "<h2>CEQA</h2>";
    $elements[] = $details['ceqa'];
    $elements[] = "<hr />";
  }

  if (!empty($details['schedule'])) {
    $elements[] = "<h2>Schedule</h2>";
    $elements[] = $details['schedule'];
    $elements[] = "<hr />";
  }

  if (!empty($details['planning_study'])) {
    $elements[] = "<h2>Planning Study</h2>";
    $elements[] = $details['planning_study'];
    $elements[] = "<hr />";
  }

  if (!empty($details['project_advisory_group'])) {
    $elements[] = "<h2>Project Advisory Group</h2>";
    $elements[] = $details['project_advisory_group'];
    $elements[] = "<hr />";
  }

  if (!empty($field_award_rendered)) {
    $elements[] = "<h2>Awards & Recognition</h2>";
    $elements[] = $field_award_rendered;
    $elements[] = "<hr />";
  }

  if (!empty($projectimages)) {
    $elements[] = "<h2>Project Images</h2>";
  }

  foreach ($elements as $element) {
    $body[]['#text'] = $element;
  }
  $variables['content']['body'] = $body;
  $variables['details'] = $details;
}

/**
 * Node: Alerts preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_alert(array &$variables, NodeInterface $node): void {

  // Prepend alert label directly to the body content so that it renders inline.
  $variables['content']['body'][0]['#format'] = 'restricted_html';

  // Set the type/icons.
  $variables['type'] = NULL;

  // Get the alert type from the variant field.
  if ($node->hasField('field_alert_type')) {
    $items = $node->get('field_alert_type')->first()->getValue();
    if (isset($items['value'])) {
      $variables['type'] = $items['value'] == 'success' ? 'status' : $items['value'];
    }
  }

  // Default is to hide the alert content.
  $is_front = \Drupal::service('path.matcher')->isFrontPage();
  $current_page = \Drupal::routeMatch()->getParameter('node');
  $variables['display_alert'] = jcc_elevated_should_alert_display($node, $current_page, $is_front);
}

/**
 * Node: Subpage preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_subpage(array &$variables, NodeInterface $node): void {
  // If the first paragraph on the subpage is a hero, move it out to the header
  // variable, and hide it from the default content array. We want to inject
  // the sidebar navigation between the hero and the rest of the content.
  $variables['header'] = FALSE;
  $component_items = $node->get('field_components')->referencedEntities();
  $bundle = $component_items ? $component_items[0]->bundle() : NULL;
  if ($bundle == 'hero' || $bundle == 'hero_banner'|| $bundle == 'hero_hangover') {
    $variables['header'] = $node->get('field_components')->first()->view('default');
    $variables['content']['field_components'][0]['#access'] = FALSE;
  }
}

/**
 * Node: Justices & Judges preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_judge(array &$variables, NodeInterface $node): void {
  // Attachments.
  foreach ($node->field_media_file_multiple as $media_file) {
    $media = Media::load($media_file->target_id);
    $media_name = $media->name->value;
    if ($media->get('field_media_file_multiple')->target_id) {
      $file = File::load($media->get('field_media_file_multiple')->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->get('uri')->value);
      $link = [
        '#markup' => '<a href="' . $file_url . '">' . $media_name . '</a>',
      ];
      $documents[] = [
        'text' => $link,
      ];
      $variables['documents'] = $documents;
    }
  }

  // Tags.
  foreach ($node->field_tags as $tag) {
    $tid = $tag->target_id;
    $term = Term::load($tid);
    $tags[] = [
      'variant' => 'default',
      'label' => $term->name->value,
      'url' => $term->toUrl(),
    ];
    $variables['tags'] = $tags;
  }
}

/**
 * Node: Job preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_job(array &$variables, NodeInterface $node): void {
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');

  // Get locations.
  $locations = __jcc_get_job_locations($node);

  // Get salary.
  // Declare currency formatter in USD without cents.
  $usd_format = new NumberFormatter('en_EN', NumberFormatter::CURRENCY);
  $usd_format->setAttribute(NumberFormatter::FRACTION_DIGITS, 0);
  // Get and format values.
  $salary_min = $node->get('field_job_salary')->getValue()[0]['from'] ?? '';
  if ($salary_min) {
    $salary_min = $usd_format->formatCurrency($salary_min, 'USD');
  }
  $salary_max = $node->get('field_job_salary')->getValue()[0]['to'] ?? '';
  if ($salary_max) {
    $salary_max = $usd_format->formatCurrency($salary_max, 'USD');
  }
  $salary = '';
  // If salary range or not.
  if ($salary_min && $salary_max) {
    $salary = $salary_min . ' to ' . $salary_max;
  }
  else {
    if ($salary_min) {
      $salary = $salary_min;
    }
    elseif ($salary_max) {
      $salary = $salary_max;
    }
  }

  // Publishing schedule deadline.
  $deadline = $node->get('unpublish_on')->value ? date('F j, Y', $node->get('unpublish_on')->value) : '';
  // If no deadline or 12/12/9999.
  if (!$deadline || $deadline === 'December 12, 9999') {
    $deadline = t('Open Until Filled');
  }

  // Custom apply button if used.
  $custom_apply = [];
  if ($node->get('field_custom_link')) {
    $custom_apply = [
      'label' => $node->get('field_custom_link')->title ?? '',
      'url' => $node->get('field_custom_link')->uri ?? '',
    ];
  }

  // Check for a job type id, office id, and department id first. This is just
  // here because with dev work It is possible to have a job node without the
  // field or type declared.
  $job_type_id = $node->hasField('field_job_type') ? $node->get('field_job_type')->target_id : FALSE;
  $job_office_id = $node->hasField('field_office') ? $node->get('field_office')->target_id : FALSE;
  $job_department_id = $node->hasField('field_department') ? $node->get('field_department')->target_id : FALSE;

  // Salary range context.
  $salary_range_context = t('per year');
  $field_salary_range = $node->get('field_job_salary_context')->value;
  if ($field_salary_range == 'monthly') {
    $salary_range_context = t('per month');
  }

  // Boilerplate message.
  $boilerplate_msg = t('The Judicial Council of California Is an Equal Opportunity Employer.');
  $boilerplate = ['#markup' => Html::decodeEntities('<p><em>' . $boilerplate_msg . '</em></p>')];

  // Refined job details object.
  $job = [
    'details' => [
      'job_id' => [
        'label' => t('Job ID'),
        'value' => $node->get('field_job_id')->value ?? '',
      ],
      'job_type' => [
        'label' => t('Full/Part-Time'),
        'value' => $job_type_id && $taxonomy_manager->load($job_type_id) ? $taxonomy_manager->load($job_type_id)->get('name')->value : t('Full-Time'),
      ],
      'location' => [
        'label' => t('Location'),
        'value' => $locations,
      ],
      'office' => [
        'label' => t('Office'),
        'value' => $job_office_id && $taxonomy_manager->load($job_office_id) ? $taxonomy_manager->load($job_office_id)->get('name')->value : '',
      ],
      'Department' => [
        'label' => t('Department'),
        'value' => $job_department_id && $taxonomy_manager->load($job_department_id) ? $taxonomy_manager->load($job_department_id)->get('name')->value : '',
      ],
      'salary' => [
        'label' => t('Salary'),
        'value' => $salary ? $salary . ' ' . $salary_range_context : '',
      ],
      'is_temporary' => [
        'label' => t('Regular/Temporary'),
        'value' => $node->get('field_is_temporary')->value ? t('Temporary') : t('Regular'),
      ],
      'deadline' => [
        'label' => t('Deadline'),
        'value' => $deadline,
      ],
      'posted' => [
        'label' => t('Posted'),
        'value' => $node->get('field_date')->date ? $node->get('field_date')->date->format('F j, Y') : '',
      ],
    ],
    'custom_apply' => $custom_apply,
    'boilerplate' => $boilerplate,
  ];

  $variables['job'] = $job;
}

/**
 * Helper function to decide if alert should be displayed or not.
 */
function jcc_elevated_should_alert_display($alert_node, $current_page, $is_front): bool {

  // If we are trying to pass a non-alert node, block the display.
  if ($alert_node->bundle() != 'alert') {
    return FALSE;
  }

  $only_frontpage = $alert_node->get('field_switch')->first()->getValue()['value'];
  $references = $alert_node->get('field_node_reference')->referencedEntities();

  // If we are on the frontpage and the "frontpage only" switch is turned on.
  if ($only_frontpage && $is_front) {
    return TRUE;
  }

  // If not "frontpage only", and no specific references set, show everywhere.
  if (!$only_frontpage && empty($references)) {
    return TRUE;
  }

  // Check if we're on an allowed page. First confirm we are on a node page.
  if (!$only_frontpage && !empty($references) && ($current_page instanceof NodeInterface)) {

    // If the current node is the actual alert node.
    if ($current_page->id() == $alert_node->id()) {
      return TRUE;
    }
    else {
      // Otherwise check specific pages to show alert on.
      foreach ($references as $ref) {
        if ($current_page->id() == $ref->id()) {
          return TRUE;
        }
      }
    }
  }

  // Default is to hide the alert.
  return FALSE;
}

/**
 * Node: Solicitation Request (RFP) preprocess.
 *
 * Display: Full page/Default.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function jcc_elevated_node_request(array &$variables, NodeInterface $node): void {
  $entity_manager = \Drupal::entityTypeManager();
  $media_manager = $entity_manager->getStorage('media');
  $file_manager = $entity_manager->getStorage('file');
  $formatter = \Drupal::service('date.formatter');
  $renderer = \Drupal::service('renderer');

  // Get the full text of out type.
  $variables['rfp_type'] = $node->get('field_rfp_type')->first() ? $node->get('field_rfp_type')->first()->view('full') : FALSE;

  // Determine if request is archived.
  $archived = $node->get('field_rfp_status')->first() && $node->get('field_rfp_status')->first()->getValue()['value'] == 'archived';

  // Get the archived year, if archived.
  $archived_year = FALSE;
  $rfp_end_date = $node->get('field_rfp_end_date')->first() ? $node->get('field_rfp_end_date')->first()->getValue() : FALSE;
  if ($archived && isset($rfp_end_date)) {
    $date_time = new DrupalDateTime($rfp_end_date['value'], new \DateTimeZone('UTC'));
    $archived_year = \Drupal::service('date.formatter')->format($date_time->getTimestamp(), 'custom', 'Y', NULL, NULL);
  }

  // Create an array for our important dates.
  $rfp_dates = [];

  // Grab our start date and add to our array.
  if ($node->get('field_rfp_start_date')->first()) {
    $rfp_dates[] = [
      'brow' => t('Issued date'),
      'heading' => __jcc_elevated_determine_datetime_output($node->get('field_rfp_start_date')->first()->view('full')),
    ];
  }

  // Grab our additional dates and add to our array.
  if ($node->get('field_rfp_important_dates')->first()) {
    foreach ($node->get('field_rfp_important_dates')->referencedEntities() as $date) {
      $heading = __jcc_elevated_determine_date_range_output($date->get('field_date_range')->first() ? $date->get('field_date_range')->first()->view('full') : NULL);
      $rfp_dates[] = [
        'brow' => $date->get('field_heading')->first() ? $date->get('field_heading')->first()->view('full') : NULL,
        'heading' => $heading,
        'text' => $date->get('field_text')->first() ? $date->get('field_text')->first()->view('full') : NULL,
      ];
    }
  }

  // Grab our end date and add to our array.
  if ($node->get('field_rfp_end_date')->first()) {
    $rfp_dates[] = [
      'brow' => $archived ? t('Archived') : t('Deadline'),
      'heading' => __jcc_elevated_determine_datetime_output($node->get('field_rfp_end_date')->first()->view('full')),
    ];
  }

  $variables['rfp_dates'] = $rfp_dates;

  //
  // Grab our submission info and create our arrays.
  $variables['rfp_first_submission_info'] = [];
  $variables['rfp_submission_info'] = [];
  $rfp_submission_info = [];
  $rfp_submission_information = $node->get('field_rfp_submission_information')->referencedEntities();
  if (isset($rfp_submission_information[0])) {
    foreach ($rfp_submission_information as $sub_info) {

      // If callout is an information type, set to our paragraph variable.
      $paragraph = $sub_info->bundle() == 'information' ? $sub_info : FALSE;

      // If the callout is a preset, set the preset reference to our paragraph.
      if ($sub_info->bundle() == 'from_library') {
        $preset_reference = $sub_info->get('field_reusable_paragraph')->referencedEntities();
        $preset_paragraph = $preset_reference[0] ? $preset_reference[0]->get('paragraphs')->referencedEntities() : FALSE;
        if ($preset_paragraph[0] && $preset_paragraph[0]->bundle() == 'information') {
          $paragraph = $preset_paragraph[0];
        }
      }

      if ($paragraph) {
        $links = [];
        foreach ($paragraph->get('field_link_multiple')->getValue() as $link) {
          if (!empty($link['options']['attributes']['mailto_subject'])) {
            $link['options']['query']['subject'] = $link['options']['attributes']['mailto_subject'];
            unset($link['options']['attributes']['mailto_subject']);
          }
          if (!empty($link['options']['attributes']['mailto_body'])) {
            $link['options']['query']['body'] = $link['options']['attributes']['mailto_body'];
            unset($link['options']['attributes']['mailto_body']);
          }
          $links[] = [
            'url' => Url::fromUri($link['uri'], $link['options']),
            'title' => !empty($link['title']) ? $link['title'] : $link['uri'],
          ];
        }
        $rfp_submission_info[] = [
          'brow' => $paragraph->get('field_brow')->first() ? $paragraph->get('field_brow')->first()->view('full') : NULL,
          'heading' => $paragraph->get('field_heading')->first() ? $paragraph->get('field_heading')->first()->view('full') : NULL,
          'text' => $paragraph->get('field_text')->first() ? $paragraph->get('field_text')->first()->view('full') : NULL,
          'links' => !empty($links) ? $links : NULL,
        ];
      }
    }
  }

  // We separate the first submission info for a primary SidebarList callout,
  // with the others rendered as a secondary SidebarList callout.
  if (!empty($rfp_submission_info)) {
    $first = array_shift($rfp_submission_info);
    $variables['rfp_first_submission_info'] = [$first];
    $variables['rfp_submission_info'] = !empty($rfp_submission_info) ? $rfp_submission_info : [];
  }

  // Create our BlurbBox data.
  $rfp_blurb_box = [];

  // Build our status item.
  if ($node->get('field_rfp_status')->first()) {
    $rfp_blurb_box[] = [
      'brow' => t('Status'),
      'heading' => $node->get('field_rfp_status')->first()->view('full'),
      'text' => FALSE,
    ];
  }

  // Build our topic item.
  if ($node->get('field_rfp_topic')->first()) {
    $rfp_blurb_box[] = [
      'brow' => t('Topic'),
      'heading' => $node->get('field_rfp_topic')->view('full'),
      'text' => FALSE,
    ];
  }

  // Build our end date item.
  if ($node->get('field_rfp_end_date')->first()) {
    $rfp_blurb_box[] = [
      'brow' => $archived ? t('Archived') : t('Deadline'),
      'heading' => $archived ? $archived_year : __jcc_elevated_determine_datetime_output($node->get('field_rfp_end_date')->first()->view('full')),
      'text' => FALSE,
    ];
  }

  // Build our number item.
  if ($node->get('field_rfp_number')->first()) {
    $rfp_blurb_box[] = [
      'brow' => t('ID#'),
      'heading' => $node->get('field_rfp_number')->first()->view('full'),
      'text' => FALSE,
    ];
  }

  $variables['rfp_blurb_box_data'] = $rfp_blurb_box;

  // Build our attachments list from multiple media field.
  $variables['attachments_list'] = [];
  $items = [];
  if ($node->hasField('field_rfp_attached_media')) {
    $current = Drupal::time()->getCurrentTime();

    // 15 day offset for New/Updated label to display.
    $offset = 86400 * 15;
    $rfp_attachments = $node->get('field_rfp_attached_media')->getValue();
    foreach ($rfp_attachments as $delta => $media_target) {
      $media = $media_target['target_id'] ? $media_manager->load($media_target['target_id']) : FALSE;
      if ($media) {

        $fid = $media->getSource()->getSourceFieldValue($media);
        if ($fid) {
          $file = $file_manager->load($fid);
          $file_url = $file->createFileUrl();
          $mimetype = $file->getMimeType();

          $options = [];

          // Only apply download to Excel files. Pdfs and docs will display or
          // download based on users browser settings.
          if ($mimetype == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            $options = [
              'attributes' => [
                'download' => $file->label(),
              ],
            ];
          }

          $url = Url::fromUserInput($file_url, $options);
          $file_field_data = $media->get('field_media_file')
            ->first()
            ->getValue();
          $label = !empty($file_field_data['description']) ? $file_field_data['description'] : $media->label();

          // If last updated date of media item is within 30 days, and not a new
          // item, set update TRUE.
          $update = $current - $media->getChangedTime() <= $offset;
          $new = $current - $media->getCreatedTime() <= $offset;
          $status = FALSE;
          if ($new) {
            $status = [
              '#prefix' => '<em class="attachment__new-label">',
              '#markup' => t('New'),
              '#suffix' => '</em>',
            ];
          }
          if ($update and !$new) {
            $status = [
              '#prefix' => '<em class="attachment__recent-updated-date">',
              '#markup' => t('Revised:') . ' ' . $formatter->format($media->getChangedTime(), 'm_d_y'),
              '#suffix' => '</em>',
            ];
          }

          $items[$delta] = [
            '#prefix' => $new ? '<div class="attachment__label attachment__new">' : '<div class="attachment__label">',
            '#markup' => Link::fromTextAndUrl($label, $url)->toString() . $renderer->render($status),
            '#suffix' => '</div>',
            '#wrapper_attributes' => ['class' => 'list__item'],
          ];
        }
      }
    }

    if ($items) {
      $variables['attachments_list'] = [
        '#theme' => 'item_list',
        '#type' => 'ul',
        '#title' => '',
        '#items' => $items,
        '#attributes' => [
          'class' => 'list__container',
        ],
      ];
    }

  }
}

/**
 * Node: Opinion preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_opinion(array &$variables, NodeInterface $node): void {
  $entity_type_manager = \Drupal::service('entity_type.manager');
  $taxonomy_manager = $entity_type_manager->getStorage('taxonomy_term');
  $media_manager = $entity_type_manager->getStorage('media');
  $file_manager = $entity_type_manager->getStorage('file');

  $type = $node->get('field_opinion_type')->target_id ? $taxonomy_manager->load($node->get('field_opinion_type')->target_id)->get('name')->value : '';
  $case_number = $node->get('field_case_number_plain')->value ? $node->get('field_case_number_plain')->value : '';
  $opinion_source = $node->get('field_opinion_source')->target_id ? $taxonomy_manager->load($node->get('field_opinion_source')->target_id)->get('name')->value : '';
  $review_granted = $node->get('field_review_granted')->value;

  // Remote file links - $doc must be valid to display anything.
  $remote_files = [];
  $pdf = __jcc_opinion_file_links_builder($type, $review_granted, 'PDF', $case_number);
  $doc = __jcc_opinion_file_links_builder($type, $review_granted, 'DOCX', $case_number);
  if ($pdf && $doc) {
    $remote_files[] = $pdf;
    if ($doc) {
      $remote_files[] = $doc;
    }
  }

  // Related cases.
  $related_cases_urls = [];
  $related_cases = $node->get('field_related_cases_plain')->value;
  if ($related_cases) {
    // If space delimited by accident or from migration.
    if (strpos($related_cases, ' ')) {
      $related_cases = preg_split('/\s+/', $related_cases);
    }
    else {
      // Remove any spaces from comma delimited list.
      $related_cases = preg_replace('/\s+/', '', $related_cases);
      // Convert to array.
      $related_cases = explode(',', $related_cases);
    }
    foreach ($related_cases as $related_case) {
      $accms_url = __jcc_accms_link_builder($related_case);
      if ($accms_url) {
        $related_cases_urls[] = '<a href="' . $accms_url . '" arial_label="View related case ' . $related_case . '">' . $related_case . '</a>';
      }
    }
  }

  // Citings.
  $citings = [];
  if ($node->get('field_media_file_multiple')->getValue()) {
    foreach ($node->get('field_media_file_multiple')->getValue() as $citing) {
      $media = $media_manager->load($citing['target_id']);
      $file = $file_manager->load($media->get('field_media_file')->target_id);
      if ($file) {
        $citing_title = $media->get('field_media_text')->value;
        if (is_null($citing_title)) {
          $citing_title = $media->get('name')->value;
        }
        $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->get('uri')->value);
        $citings[] = [
          'text' => ['#markup' => '<a href="' . $file_url . '" aria_label="Download or view citing ' . $citing_title . '">' . $citing_title . '</a>'],
        ];
      }
    }
  }

  $opinion = [
    'details' => $case_number && $opinion_source != 'Appellate Division' ? __jcc_accms_link_builder($case_number) : NULL,
    'remote_files' => $remote_files,
    'related_cases' => $related_cases_urls ? implode(' ', $related_cases_urls) : '',
    'citings' => $citings,
  ];

  $variables['opinion'] = $opinion;
}

/**
 * Node: Course preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_course(array &$variables, NodeInterface $node): void {
  $renderer = \Drupal::service('renderer');
  $cache = \Drupal::service('cache.data');
  $cache_action = 'course:action:' . $node->id();
  $cache_schedule = 'course:schedule:' . $node->id();
  $timezone = new DateTimeZone('America/Los_Angeles');
  $today = new DateTime('now', $timezone);
  $type_term = $node->get('field_course_type')[0] ? $node->get('field_course_type')[0]->entity : NULL;
  $type = $type_term ? $type_term->label() : NULL;
  $subtype_term = $node->get('field_course_subtype')[0] ? $node->get('field_course_subtype')[0]->entity : NULL;
  $subtype = $subtype_term ? $subtype_term->label() : NULL;
  $embed = NULL;
  $action = NULL;
  $sidebar_details = NULL;
  $sidebar = NULL;
  $button_styles = 'button button--secondary button--normal full-width label-align--center';
  $button_styles_disabled = $button_styles . ' disabled';

  // Media embed or action.
  if ($node->get('field_media')->entity) {
    $media = $node->get('field_media')->entity;
    $media_bundle_id = $media->bundle->target_id;
    if ($media_bundle_id != 'file' && $media_bundle_id != 'publication') {
      $media_type = 'video';
      if ($media_bundle_id == 'akamai_audio') {
        $media_type = 'audio';
      }
      $embed = [
        'media' => \Drupal::entityTypeManager()->getViewBuilder('media')->view($media),
        'type' => $media_type,
      ];
    }
    else {
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($media->get('field_media_file')->target_id);
      if ($file) {
        $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
        $extension_exp = '/[^.]*$/';
        preg_match($extension_exp, $file_url, $extension);
        $download_label = t('Download');
        if ($extension) {
          $download_label = $download_label . ' ' . strtoupper($extension[0]);
        }
        $action = [
          '#markup' => '<a class="' . $button_styles . '" href="' . $file_url . '">' . $download_label . '</a>',
        ];
      }
    }
  }
  else {
    // Schedule & Registration.
    if ((!$action && !$embed) && count($node->get('field_components')) > 0) {
      // Events.
      $schedule = [];
      foreach ($node->get('field_components')->referencedEntities() as $event) {
        if ($event->get('field_date_range')->start_date) {
          $start_date = $event->get('field_date_range')->start_date->setTimezone($timezone);
          $end_date = $event->get('field_date_range')->end_date->setTimezone($timezone);

          // Event date.
          $event_date = NULL;
          if ($event->get('field_date_range')->start_date) {
            $event_date = $start_date->format('F j, Y');
            if ($start_date->format('F j, Y') != $end_date->format('F j, Y')) {
              $start = $start_date->format('F j');
              if ($start_date->format('Y') != $end_date->format('Y')) {
                $start = $start_date->format('F j, Y');
              }
              $end = $end_date->format('j, Y');
              if ($start_date->format('F') != $end_date->format('F')) {
                $end = $end_date->format('F j, Y');
              }
              $event_date = $start . ' to ' . $end;
            }
          }

          // Is the event today?
          $event_is_today = FALSE;
          if ($start_date->format('Ymd') <= $today->format('Ymd') && $end_date->format('Ymd') >= $today->format('Ymd')) {
            $event_is_today = TRUE;
          }

          // Event time.
          $event_time = $start_date->format('g:ia') . ' to ' . $end_date->format('g:ia');
          if ($start_date->format('g:ia') == '12:00am' && $end_date->format('g:ia') == '11:59pm') {
            $event_time = t('All Day');
          }

          // Event location.
          $location = $event->get('field_event_location')->entity ? $event->get('field_event_location')->entity->label() : NULL;

          // Event notation.
          $notation = $event->get('field_text')->value ? strip_tags($event->get('field_text')->value, 'strong, em') : NULL;
          if ($notation) {
            $notation = preg_replace('/\r|\n/', '', $notation);
          }

          // DEBUG.
          $test = 'RUNNING';
          if (time() > 1753413300) {
            $test = 'STOPPED';
          }
          $notation = $notation . '**' . $test . '**';

          // Schedule.
          $schedule[] = [
            'date' => $event_date,
            'event_is_today' => $event_is_today,
            'time' => $event_time,
            'location' => $location,
            'notation' => $notation,
          ];
        }
      }
      // Cache computed schedule to custom tag.
      \Drupal::service('cache_tags.invalidator')->invalidateTags([$cache_schedule]);
      $cache->set('cache_course_schedule', $schedule, Cache::PERMANENT, [$cache_schedule]);
      $variables['course_schedule'] = $cache->get('cache_course_schedule') ? $cache->get('cache_course_schedule')->data : NULL;

      if ($schedule) {
        $registration = $node->get('field_custom_link');
        if (count($registration) > 0) {
          $register_uri = $registration->uri;

          if (count($node->get('field_registration_period')) > 0) {
            $register_start = $node->get('field_registration_period')->start_date->setTimezone($timezone);
            $register_start = $register_start->format('Ymd');
            $register_end = $node->get('field_registration_period')->end_date->setTimezone($timezone);
            $register_end = $register_end->format('Ymd');
            if ($register_start > $today->format('Ymd')) {
              $action = [
                '#status' => 'pending',
                '#markup' => '<div class="' . $button_styles_disabled . '">' . t('Registration Pending') . '</div>',
              ];
            }
            elseif ($register_end < $today->format('Ymd')) {
              $action = [
                '#status' => 'closed',
                '#markup' => '<div class="' . $button_styles_disabled . '">' . t('Registration Closed') . '</div>',
              ];
            }
            else {
              $label = $registration->title ? $registration->title : t('Register Now');
              $action = [
                '#status' => 'open',
                '#markup' => '<a class="' . $button_styles . '" href="' . $register_uri . '">' . $label . '</a>',
              ];
            }
          }
        }
        else {
          $action = [
            '#status' => 'none',
            '#markup' => '<p class="' . $button_styles_disabled . '">' . t('No Registration Required') . '</p>',
          ];
        }
      }
    }
    elseif ($node->get('field_origin_url')->uri) {
      $launch_title = t('Launch');
      $launch_uri = $node->get('field_origin_url')->uri;
      $action = [
        '#markup' => '<a class="' . $button_styles . '" href="' . $launch_uri . '">' . $launch_title . '</a>',
      ];
    }
  }

  // Cache computed action button to custom tag.
  \Drupal::service('cache_tags.invalidator')->invalidateTags([$cache_action]);
  $cache->set('cache_course_action', $action, Cache::PERMANENT, [$cache_action]);
  $variables['course_action'] = $cache->get('cache_course_action') ? $renderer->render($cache->get('cache_course_action')->data) : NULL;

  // Promotional Image.
  if ($node->get('field_image')->entity) {
    $promo_image_src = $node->get('field_image')->entity->field_media_image->entity->getFileUri();
    $image_url = \Drupal::entityTypeManager()->getStorage('image_style')->load('max_650x650')->buildUrl($promo_image_src);
    if ($image_url) {
      $promo_image = [
        '#markup' => '<img src="' . $image_url . '">',
      ];
      $sidebar['promo_image'] = $promo_image;
    }
  }

  // Details.
  foreach ($node->getFields() as $field) {
    switch ($field->getName()) {
      case 'field_date':
        if ($field->value) {
          $sidebar_details[] = [
            'label' => $field->getFieldDefinition()->getLabel(),
            'text' => $field->date->format('F j, Y'),
            'weight' => 1,
          ];
        }
        break;

      case 'field_course_number':
        if ($field->value) {
          $sidebar_details[] = [
            'label' => $field->getFieldDefinition()->getLabel(),
            'text' => $field->value,
            'weight' => 3,
          ];
        }
        break;

      case 'field_achievable_credit':
        if ($field->value) {
          $credit = $field->value;
          // Remove decimal if zero.
          $credit_arr = explode('.', $credit);
          if ($credit_arr[1] == 0) {
            $credit = $credit_arr[0];
          }
          $achievable_credits = $credit . ' hr';
          // Pluralize if more than 1 hour.
          if ($field->value > 1) {
            $achievable_credits = $achievable_credits . 's';
          }
          $sidebar_details[] = [
            'label' => $field->getFieldDefinition()->getLabel(),
            'text' => $achievable_credits,
            'weight' => 4,
          ];
        }
        break;

      case 'field_audience':
      case 'field_assignment_area':
        if (count($field) > 0) {
          $label = $field->getFieldDefinition()->getLabel();
          $terms = [];
          foreach ($field as $term) {
            if ($term->entity) {
              $terms[] = $term->entity->label();
            }
          }
          if ($terms) {
            switch ($label) {
              case 'Audience':
                $weight = 5;
                break;

              case 'Assignment Area':
                $weight = 6;
                break;

              default:
                $weight = 7;
            }
            $sidebar_details[] = [
              'label' => $label,
              'text' => implode(', ', $terms),
              'weight' => $weight,
            ];
          }
        }
        break;

      case 'field_topics':
      case 'field_subtopics':
        if (count($field) > 0) {
          foreach ($field as $topic) {
            if ($topic->entity) {
              $term = $topic->entity;
              // Node with catalog view must be specified for actionable pill.
              $url = theme_get_setting('landing_course');
              $catalog_url = NULL;
              if ($url && str_starts_with($url, '/node')) {
                $domain = 'https://' . \Drupal::request()->getHost();
                // Use appropriate parameter to prefilter landing.
                $param = '?field_topics_target_id=' . $term->id();
                if ($field->getName() == 'field_subtopics') {
                  $param = '?field_subtopics_target_id=' . $term->id();
                }

                $catalog_url = $domain . $url . $param;
              }
              $topics[] = [
                'label' => $term->label(),
                'url' => $catalog_url,
              ];
            }
          }
          // Sort topics & subtopics alphabetically.
          usort($topics, fn($a, $b) => $a['label'] <=> $b['label']);
          $sidebar['topics'] = $topics;
        }
        break;
    }
  }

  // Sort by weight.
  if ($sidebar_details) {
    usort($sidebar_details, fn($a, $b) => $a['weight'] <=> $b['weight']);
    $sidebar['details'] = $sidebar_details;
  }

  // Processed course information.
  $variables['course'] = [
    'type' => $type,
    'subtype' => $subtype,
    'embed' => $embed,
    'sidebar' => $sidebar,
  ];
}
