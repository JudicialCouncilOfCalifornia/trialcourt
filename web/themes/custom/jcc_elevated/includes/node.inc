<?php

/**
 * @file
 * Preprocess and functions for alert content type and component.
 */

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
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
        // Set caches to trigger on landing and subpage changes.
        $menu_build_tree['#cache']['contexts'][] = 'user';
        $menu_build_tree['#cache']['tags'][] = 'node_list:landing_page';
        $menu_build_tree['#cache']['tags'][] = 'node_list:subpage';
        $menu_build_tree['#cache']['tags'][] = 'node:' . $node->id();

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
 */
function jcc_elevated_node_news(array &$variables, NodeInterface $node) {
  $variables['breadcrumb'] = \Drupal::service('breadcrumb')
    ->build(\Drupal::routeMatch())
    ->toRenderable();

  // Render and truncate field_components.
  $render_array = $node->field_components->view('default');
  $output = \Drupal::service('renderer')->render($render_array);
  $variables['components_teaser'] =
    mb_strimwidth(trim(strip_tags($output)), 0, 250, '...');
}

/**
 * Node: News preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_alert(array &$variables, NodeInterface $node) {
  // Prepend alert label directly to the body content so that it renders inline.
  $label = '<strong>' . $node->label() . '</strong>';
  $variables['content']['body'][0]['#text'] = $label . ', ' . $variables['content']['body'][0]['#text'];
}

/**
 * Node: Subpage preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_subpage(array &$variables, NodeInterface $node) {
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
 * Node: Subpage preprocess.
 *
 * @param array $variables
 *   The preprocess variables.
 * @param Drupal\node\NodeInterface $node
 *   The node.
 */
function jcc_elevated_node_judge(array &$variables, NodeInterface $node) {
  // Attachments.
  foreach ($node->field_media_file_multiple as $media_file) {
    $media = Media::load($media_file->target_id);
    $media_name = $media->name->value;
    if ($media->get('field_document_type')->target_id) {
      $doc_type = Term::load($media->get('field_document_type')->target_id)->get('name')->value;
    }
    if ($media->get('field_media_file_multiple')->target_id) {
      $file = File::load($media->get('field_media_file_multiple')->target_id);
      $file_url = \Drupal::service('file_url_generator')
        ->generateAbsoluteString($file->uri->value);
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
