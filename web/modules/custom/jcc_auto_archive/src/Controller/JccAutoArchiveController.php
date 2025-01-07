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


    \Drupal::service('jcc_auto_archive.archiver_controller')->autoArchive_cron();

    \Drupal::logger('jcc_newslinks')->info("awu hitting autoArchive_cron()");

    $five_years_ago = strtotime('-5 years');
    $query = \Drupal::entityQuery('node')
        ->condition('type', 'news')
        ->condition('field_news_type.entity:taxonomy_term.name', 'NewsLink')
        ->condition('created', $five_years_ago, '<')
        ->condition('status', 1); // Only published content

    $nids = $query->execute();
    if (!empty($nids)) {
        foreach ($nids as $nid) {

            \Drupal::logger('jcc_newslinks')->info("awu to change the state of ".$nid );
            $node = \Drupal\node\Entity\Node::load($nid);
            $node->set('moderation_state', 'archived'); // Set your custom state

            $node->save();
            \Drupal::logger('jcc_newslinks')->info("awu saved as archived for ".$nid );
        }
    }

    \Drupal::logger('jcc_newslinks')->info("awu exiting autoArchive_cron()");
  }

    public function autoArchivePage() {
      return [
        '#markup' => $this->t('Hello, this is a JCC auto archive page.'),
      ];
  }
}
