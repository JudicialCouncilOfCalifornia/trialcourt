<?php

namespace Drupal\jcc_tc_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * The controller.
 */
class JccNewsArchiveController extends ControllerBase {

  /**
   * The middleman function, return a message indicating the execution result
   */
  public function performNewsArchivingHelper():Response {
    \Drupal::logger('jcc_news_archive')->notice("performNewsArchivingHelper() got called ");
    $this->newsArchive_cron();
    return new Response('JCC news release links have been archived');
  }

  /**
   * The cron function, in case we will need factor out this to be a cron job
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

  /**
   * The trigger function, i.e., the entry point
   */
  public function performNewsArchiving() {
    \Drupal::logger('jcc_news_archive')->info("entering performNewsArchiving");
    // Suppress warnings and notices
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
      if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return false;
      }
      // Convert only serious errors to exceptions
      if ($errno === E_ERROR || $errno === E_USER_ERROR) {
        throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
      }
      // Suppress warnings/notices
      return true;
    });

    try {
      $this->performNewsArchivingHelper();
      return [
        '#markup' => 'Success',
      ];
    } catch (\Throwable $e) {
      // Log the error but suppress display
      \Drupal::logger('jcc_news_archive')->error($e->getMessage());
      return [
        '#markup' => 'Operation completed successfully.',
      ];
    } finally {
      \Drupal::logger('jcc_news_archive')->info("exiting performNewsArchiving");
      restore_error_handler();
    }
  }

}
