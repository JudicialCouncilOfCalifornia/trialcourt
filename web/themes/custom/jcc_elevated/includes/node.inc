<?php

/**
 * @file
 * Preprocess and functions for node types and components.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Views;

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
    if ($media && $media->field_media_image) {
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
function jcc_elevated_node_advisory_body(array &$variables, NodeInterface $node) {
  // Meetings tab.
  $events = views_embed_view('advisory_body_events', 'block_1', $node->id());
  $variables['content']['meetings_tab']['0']['#markup'] = \Drupal::service('renderer')->renderRoot($events);

  $past_events = Views::getView('advisory_body_events');
  $past_events->setDisplay('page_1');
  $past_events->setArguments([$node->id()]);
  $past_events->execute();
  if (count($past_events->result) > 0) {
    $variables['content']['meetings_tab']['1']['#markup'] = '<div><h3>Past Meetings</h3><a class="button button--text button--normal icon--arrow" href="/past-meetings/' . $node->id() . '">View ' . $node->getTitle() . ' Past meetings</a></div>';
  }

  // About tab.
  if ($variables['elements']['field_date_established']) {
    $variables['content']['about_tab']['0']['#markup'] = '<div><h3>Date established</h3><div>' . $variables['elements']['field_date_established'][0]['#text'] . '</div><div>' . $variables['elements']['field_date_note'][0]['#text'] . '</div></div>';
  }
  $variables['content']['about_tab']['1'] = $variables['elements']['field_about'];
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
  $label = '<strong>' . $node->label() . '</strong>';
  $variables['content']['body'][0]['#text'] = $label . ', ' . $variables['content']['body'][0]['#text'];
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
    if ($media->get('field_document_type')->target_id) {
      $doc_type = Term::load($media->get('field_document_type')->target_id) ? Term::load($media->get('field_document_type')->target_id)->get('name')->value : '';
    }
    if ($media->get('field_media_file_multiple')->target_id) {
      $file = File::load($media->get('field_media_file_multiple')->target_id);
      $file_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->uri->value);
      // Profile doc as download link, else general docs.
      if ($doc_type && $doc_type == 'Profile') {
        $variables['profile_download'] = [
          'url' => $file_url,
          'file_name' => $media_name,
        ];
      }
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
 * Node: Request (RFP) preprocess.
 *
 * Display: Full page/Default.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 *
 * @throws \Drupal\Core\TypedData\Exception\MissingDataException
 */
function jcc_elevated_node_request(array &$variables, NodeInterface $node): void {
  $entity_manager = \Drupal::entityTypeManager();
  $media_manager = $entity_manager->getStorage('media');
  $file_manager = $entity_manager->getStorage('file');
  $formatter = \Drupal::service('date.formatter');
  $renderer = \Drupal::service('renderer');

  //
  // Get the full text of out type.
  $variables['rfp_type'] = FALSE;
  $rfp_type = $node->get('field_rfp_type')->getValue();
  if (isset($rfp_type[0])) {
    $variables['rfp_type'] = $node->get('field_rfp_type')->first()->view('full');
  }

  //
  // Create an array for our important dates.
  $rfp_dates = [];

  // Grab our start date and add to our array.
  $rfp_start_date = $node->get('field_rfp_start_date')->getValue();
  if (isset($rfp_start_date[0])) {
    $rfp_start_date = [
      'brow' => t('Issued date'),
      'heading' => $node->get('field_rfp_start_date')->first()->view('full'),
    ];
    $rfp_dates[] = $rfp_start_date;
  }

  // Grab our additional dates and add to our array.
  $rfp_important_dates = $node->get('field_rfp_important_dates')->referencedEntities();
  if (isset($rfp_important_dates[0])) {
    foreach ($rfp_important_dates as $date) {
      $rfp_dates[] = [
        'brow' => $date->get('field_heading')->first()->view('full'),
        'heading' => $date->get('field_date_range')->first()->view('full'),
        'text' => $date->get('field_text')->first()->view('full'),
      ];
    }
  }

  // Grab our end date and add to our array.
  $rfp_end_date = $node->get('field_rfp_end_date')->getValue();
  if (isset($rfp_end_date[0])) {
    $rfp_end_date = [
      'brow' => t('Deadline'),
      'heading' => $node->get('field_rfp_end_date')->first()->view('full'),
    ];
    $rfp_dates[] = $rfp_end_date;
  }

  $variables['rfp_dates'] = $rfp_dates;

  //
  // Grab our submission info and create our arrays.
  $variables['rfp_first_submission_info'] = [];
  $variables['rfp_submission_info'] = [];
  $rfp_submission_information = $node->get('field_rfp_submission_information')->referencedEntities();
  if (isset($rfp_submission_information[0])) {
    foreach ($rfp_submission_information as $sub_info) {
      $links = [];
      foreach ($sub_info->get('field_link_multiple')->getValue() as $link) {

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
        'brow' => $sub_info->get('field_brow')->first() ? $sub_info->get('field_brow')->first()->view('full') : NULL,
        'heading' => $sub_info->get('field_heading')->first() ? $sub_info->get('field_heading')->first()->view('full') : NULL,
        'text' => $sub_info->get('field_text')->first() ? $sub_info->get('field_text')->first()->view('full') : NULL,
        'links' => !empty($links) ? $links : NULL,
      ];
    }
  }

  // We separate the first submission info for a primary SidebarList callout,
  // with the others rendered as a secondary SidebarList callout.
  if (!empty($rfp_submission_info)) {
    $first = array_shift($rfp_submission_info);
    $variables['rfp_first_submission_info'] = [$first];
    $variables['rfp_submission_info'] = !empty($rfp_submission_info) ? $rfp_submission_info : [];
  }

  //
  // Create our BlurbBox data.
  $rfp_blurb_box = [];

  $rfp_status = $node->get('field_rfp_status')->getValue();
  if (isset($rfp_status[0])) {
    $rfp_status = [
      'brow' => $node->get('field_rfp_status')->getFieldDefinition()->getLabel(),
      'heading' => $node->get('field_rfp_status')->first()->view('full'),
      'text' => FALSE,
    ];
    $rfp_blurb_box[] = $rfp_status;
  }

  $rfp_topic = $node->get('field_rfp_topic')->getValue();
  if (isset($rfp_topic[0])) {
    $rfp_topic = [
      'brow' => $node->get('field_rfp_topic')->getFieldDefinition()->getLabel(),
      'heading' => $node->get('field_rfp_topic')->first()->view('full'),
      'text' => FALSE,
    ];
    $rfp_blurb_box[] = $rfp_topic;
  }

  $rfp_end_date = $node->get('field_rfp_end_date')->getValue();
  if (isset($rfp_end_date[0])) {
    $rfp_end_date = [
      'brow' => t('Deadline'),
      'heading' => $node->get('field_rfp_end_date')->first()->view('full'),
      'text' => FALSE,
    ];
    $rfp_blurb_box[] = $rfp_end_date;
  }

  $rfp_number = $node->get('field_rfp_number')->getValue();
  if (isset($rfp_number[0])) {
    $rfp_number = [
      'brow' => $node->get('field_rfp_number')->getFieldDefinition()->getLabel(),
      'heading' => $node->get('field_rfp_number')->first()->view('full'),
      'text' => FALSE,
    ];
    $rfp_blurb_box[] = $rfp_number;
  }

  $variables['rfp_blurb_box_data'] = $rfp_blurb_box;

  //
  // Build our attachments list from multiple media field.
  $variables['attachments_list'] = [];
  $items = [];
  if ($node->hasField('field_media_file_multiple')) {
    $current = Drupal::time()->getCurrentTime();
    $offset = 2592000;

    $rfp_attachments = $node->get('field_media_file_multiple')->getValue();
    foreach ($rfp_attachments as $delta => $media_target) {
      $media = $media_target['target_id'] ? $media_manager->load($media_target['target_id']) : FALSE;
      if ($media) {
        $fid = $media->getSource()->getSourceFieldValue($media);
        $file = $file_manager->load($fid);
        $file_url = $file->createFileUrl();
        $url = Url::fromUserInput($file_url);
        $file_field_data = $media->get('field_media_file')->first()->getValue();
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
