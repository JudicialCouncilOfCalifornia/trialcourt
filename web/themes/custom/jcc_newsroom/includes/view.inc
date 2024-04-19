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
        }
}