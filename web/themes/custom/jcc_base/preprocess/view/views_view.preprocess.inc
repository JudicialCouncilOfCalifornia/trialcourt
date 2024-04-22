<?php

/**
 * @file
 * Taxonomy term manipulation.
 */

use Drupal\Component\Utility\Html;
use Drupal\taxonomy\Entity\Term;

/**
 * @file
 * Preprocessors for views.
 */

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_views_view(&$variables) {
  $view = $variables['view'];
  switch ($view->id()) {
    case 'location':
      if ($view->current_display == 'block' || $view->current_display == 'block_1') {
        $moduleHandler = Drupal::service('module_handler');
        $user = Drupal::currentUser();

        foreach ($view->result as $row) {
          $placeholder = FALSE;
          if ($moduleHandler->moduleExists('contextual') && $user->hasPermission('access contextual links')) {
            $contextual_links = [
              'node' => [
                'route_parameters' => [
                  'node' => $row->_entity->id(),
                ],
              ],
            ];
            $placeholder = [
              '#type' => 'contextual_links_placeholder',
              '#id' => _contextual_links_to_id($contextual_links),
            ];
          }
          $location = $row->_entity->toArray();
          $location['contextual_links'] = $placeholder;
          $variables['sidebar']['rows'][] = $location;
        }
        $num_rows = $view->total_rows;
        $verb = $num_rows == 1 ? 'is' : 'are';
        $noun = $num_rows == 1 ? 'Location' : 'Locations';
        $replacements = [
          '@verb' => $verb,
          '@num' => $num_rows,
          '@noun' => $noun,
        ];

        $variables['sidebar']['description'] = t('There @verb @num Court @noun', $replacements);

        // Hide checkboxes if there's only one filter.
        $variables['exposed_enabled'] = count($variables['exposed']['field_location_type_target_id']['#options']) > 1
          && $view->args[0] == 'all' ?? TRUE;

        if ($view->current_display == 'block_1') {
          if ($view->args[0]) {
            $term = Term::load($view->args[0]);
            $variables['view_title'] = $term ? $term->getName() . ' locations' : $view->getTitle();
          }
          else {
            $variables['view_title'] = $view->getTitle();
          }
        }
      }
      break;

    case 'faq':
      if ($view->current_display == 'block') {
        $groups = [];
        foreach ($view->result as $row) {
          $entity = $row->_entity;
          $subject_matter = !empty($entity->field_subject_matter) ? $entity->field_subject_matter->referencedEntities() : [];
          foreach ($subject_matter as $term) {
            $groups[$term->getName()]['items'][] = [
              'title' => $entity->getTitle(),
              'body' => $entity->body->view(),
            ];
          }
        }
        ksort($groups);
      }
      break;

    case 'news_newsroom_only_':
      if ($view->current_display == 'page_3') {
        if ($view->pager->current_page != 0) {
          $variables['header'] = [];
        }
      }
      break;
  }
}

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_views_view_unformatted__faq__block(&$variables) {
  foreach ($variables['rows'] as $row) {
    $node = $row['content']['#row']->_entity;
    $view_mode = 'accordion_item';
    $content = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $view_mode);
    $content = render($content);
    $variables['accordion']['expand_first'] = 0;
    $variables['accordion']['items'][] = [
      'title' => $row['content']['#row']->_entity->getTitle(),
      'body' => $content,
    ];
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function jcc_base_preprocess_views_view__search(&$variables) {
  // Personalized no results message.
  $variables['no_results_heading'] = 'We are unable to find a result for your search.';
  if (theme_get_setting('no_search_results_heading')) {
    $variables['no_results_heading'] = theme_get_setting('no_search_results_heading');
  }
  $variables['no_results_message'] = ['#markup' => Html::decodeEntities('Visit our <a href="/">homepage</a> for more options.')];
  $no_results_msg = theme_get_setting('no_search_results_message');
  if ($no_results_msg['value']) {
    $variables['no_results_message'] = ['#markup' => Html::decodeEntities($no_results_msg['value'])];
  }
}
