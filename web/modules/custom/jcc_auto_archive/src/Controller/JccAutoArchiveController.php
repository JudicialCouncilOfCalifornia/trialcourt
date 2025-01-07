<?php

namespace Drupal\jcc_auto_archive\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the JCC auto archive Module.
 */
class JccAutoArchiveController extends ControllerBase {

  /**
   *  The customized cron function.
   * /
   */
  public function autoArchive_cron() {
    $five_years_ago = strtotime('-5 years');
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'news')
      ->condition('field_news_type.entity:taxonomy_term.name', 'News Release')
      ->condition('created', $five_years_ago, '<')
      ->condition('status', 1); // Only published content

    $nids = $query->execute();
    if (!empty($nids)) {
      foreach ($nids as $nid) {
        \Drupal::logger('jcc_auto_archive')->info("awu to change the state of ".$nid );
        $node = \Drupal\node\Entity\Node::load($nid);
        $node->set('moderation_state', 'archived');
        $node->save();
        \Drupal::logger('jcc_auto_archive')->info("awu saved as archived for ".$nid );
      }
    }
    \Drupal::logger('jcc_auto_archive')->info("awu exiting autoArchive_cron()");
  }
}
