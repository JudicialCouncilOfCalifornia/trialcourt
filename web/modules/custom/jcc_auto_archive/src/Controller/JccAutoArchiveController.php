<?php

namespace Drupal\custom_module\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the JCC auto archive Module.
 */
class JccAutoArchiveController extends ControllerBase {
/**
   * Returns a simple page.
   */
  public function autoArchive_cron() {
    $five_years_ago = strtotime('-5 years');
    $query = \Drupal::entityQuery('node')
        ->condition('created', $five_years_ago, '<')
        ->condition('status', 1); // Only published content
    $nids = $query->execute();
    if (!empty($nids)) {
        foreach ($nids as $nid) {
            $node = \Drupal\node\Entity\Node::load($nid);
            $node->set('moderation_state', 'archived'); // Set your custom state
            $node->save();
        }
    }
  }


    public function autoArchivePage() {
      return [
        '#markup' => $this->t('Hello, this is a JCC auto archive page.'),
      ];
  }
}
