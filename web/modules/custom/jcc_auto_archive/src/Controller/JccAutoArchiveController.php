<?php

namespace Drupal\jcc_auto_archive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller.
 */
class JccAutoArchiveController extends ControllerBase {

  /**
   * The member var.
   */
  // Protected $jccAutoArchiveService;
  //
  //  /**
  //   * The contruct function.
  //   */
  //  public function __construct($autoArchiveService) {
  //    $this->jccAutoArchiveService = $autoArchiveService;
  //  }
  //
  //  /**
  //   * The create function.
  //   */
  //  public static function create(ContainerInterface $container) {
  //    return new static($container->get('jcc_auto_archive.service'));
  //  }.

  /**
   * The trigger function.
   */
  public function performAutoArchiving() {
    \Drupal::logger('jcc_auto_archive')->notice("awu performAutoArchiving() got called ");
    $this->autoArchive_cron();
    return new Response('JCC news release links have been archived');
  }

  /**
   * The cron function.
   */
  public function autoArchive_cron() {
    \Drupal::logger('jcc_auto_archive')->notice("awu auto_archive_cron() got called ");
    $five_years_ago = strtotime('-5 years');
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'news')

//      ->condition('field_news_type.entity:taxonomy_term.name', 'News')

      ->condition('created', $five_years_ago, '<')

      // Only published content.
      ->condition('status', 1);

    $nids = $query->execute();
    if (!empty($nids)) {
      $counter = 0;
      foreach ($nids as $nid) {
        if ($counter >= 2) {
          break;
        }
        $counter += 1;

        \Drupal::logger('jcc_auto_archive')->info("awu to change the state of " . $nid);
        $node = Node::load($nid);
        $node->set('moderation_state', 'archived');
        try {
          $node->save();
        }
        catch (\Throwable $e) {
          \Drupal::logger('jcc_auto_archive')->notice("awu something bad happened");
        }
        \Drupal::logger('jcc_auto_archive')->info("awu saved as archived for " . $nid);
      }
    }
    else {
      \Drupal::logger('jcc_auto_archive')->info("awu no news release link found!");
    }
    \Drupal::logger('jcc_auto_archive')->info("awu exiting autoArchive_cron()");
  }

}
