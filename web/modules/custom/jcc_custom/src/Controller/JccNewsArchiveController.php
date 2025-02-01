<?php

namespace Drupal\jcc_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller.
 */
class JccNewsArchiveController extends ControllerBase {

  /**
   * The trigger function.
   */
  public function performNewsArchiving():Response {
    \Drupal::logger('jcc_news_archive')->notice("performNewsArchiving() got called ");
    $this->newsArchive_cron();
    return new Response('JCC news release links have been archived');
  }

  /**
   * The cron function.
   */
  public function newsArchive_cron():void {
    \Drupal::logger('jcc_news_archive')->notice("newsArchive_cron() got called ");

    $term_id = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'news_type')
      ->condition('name', 'News Release')
      ->execute();

    $five_years_ago = strtotime('-5 years', REQUEST_TIME);
    if (!empty($term_id)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'news')
        ->condition('created', $five_years_ago, '<')
        ->condition('status', 1)
        ->condition('field_news_type.target_id', reset($term_id));
      $nids = $query->execute();
    }

    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $node = Node::load($nid);
        $node->set('moderation_state', 'archived');
        try {
          $node->save();
          \Drupal::logger('jcc_news_archive')->info("saved as archived for " . $nid);
        }
        catch (EntityStorageException $e) {
          \Drupal::logger('jcc_news_archive')->error('failed to archive Node @nid: @message ese', [
            '@nid' => $nid,
            '@message' => $e->getMessage(),
          ]);
        }
        catch (\Exception $e) {
          \Drupal::logger('jcc_news_archive')->error('unexpected error for Node @nid: @message', [
            '@nid' => $nid,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
    else {
      \Drupal::logger('jcc_news_archive')->info("no news release link found!");
    }
    \Drupal::logger('jcc_news_archive')->info("exiting newsArchive_cron()");
  }

}
