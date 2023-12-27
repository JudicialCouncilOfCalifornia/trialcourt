<?php
namespace Drupal\jcc_tc_migration\Controller;
/**
 * @file
 * Contains custom_cleanup module functionality.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Controller for deleting events older than an hour.
 */
class MigrateEventsDeletionController extends ControllerBase {

  /**
   * Deletes events older than an hour.
   */
  public function deleteOldEvents() {
    
    
    $current_time = \Drupal::time()->getRequestTime();

    
    $query = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'events']);
   
    
    
    $nids=$query;
   
    foreach ($nids as $nid) {
    
      $node = Node::load($nid->id());
      if ($node) {
        $node->delete();
      }
    }

    
    return [
      '#markup' => $this->t('Old events deleted.'),
    ];
  }

}
