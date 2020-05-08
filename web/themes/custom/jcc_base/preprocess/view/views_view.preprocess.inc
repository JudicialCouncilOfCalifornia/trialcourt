<?php

/**
 * Implements hook_preprocess_HOOK().
 */
function jcc_base_preprocess_views_view(&$variables) {
  $view = $variables['view'];
  switch($view->id()) {
    case 'location':
      if ($view->current_display == 'block') {
        foreach($view->result as $row) {
          $variables['sidebar']['rows'][] = $row->_entity;
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
      }
      break;

    case 'faq':
      if ($view->current_display == 'block') {
        foreach($view->result as $row) {
          $entity = $row->_entity;
          $subject_matter = !empty($entity->field_subject_matter) ? $entity->field_subject_matter->referencedEntities() : [];
          foreach($subject_matter as $term) {
            $groups[$term->getName()]['items'][] = [
              'title' => $entity->getTitle(),
              'body' => $entity->body->view(),
            ];
          }
        }
        ksort($groups);
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
      'body' => $content
    ];
  }
}
