<?php

/**
 * @file
 * Theme and preprocess functions for views.
 */

use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;
use Drupal\views\ViewExecutable;
use Drupal\Core\Render\Markup; 

/**
 * Implements hook_views_pre_render().
 */
function jcc_newsroom_views_pre_render(ViewExecutable &$view) {
    switch ($view->storage->id()) {
        case 'events':
            if ($view->current_display == 'page_1'){
                foreach ($view->result as $key => $value) {
                   $value->_entity->set('body',Html::decodeEntities($value->_entity->body->getValue()[0]['value']));
                }

            }
            // Calendar (page_2) and the list header only when that list is
            // embedded by page_2 (parent_views is set by Views area plugins).
            // Standalone /events-list is page_1 with empty parent_views.
            $is_calendar = $view->current_display === 'page_2';
            $is_embedded_list = $view->current_display === 'page_1' && !empty($view->parent_views);
            if (($is_calendar || $is_embedded_list) && isset($view->header['area_text_custom'])) {
              $content = $view->header['area_text_custom']->options['content'] ?? '';
              $replaced = preg_replace(
                '/<h3(\s+class="jcc-card__title")>(.*?)<\/h3>/s',
                '<h2$1>$2</h2>',
                $content,
                1
              );
              if ($replaced !== NULL && $replaced !== $content) {
                $view->header['area_text_custom']->options['content'] = $replaced;
              }
            }
        }
}

/**
 * Implements hook_preprocess_views_view().
 */
function jcc_newsroom_preprocess_views_view(array &$variables) {
  $view = $variables['view'];
  if ($view->id() === 'events' && $view->current_display === 'page_2') {
    $variables['#attached']['library'][] = 'jcc_newsroom/calendar_headings';
  }
}
