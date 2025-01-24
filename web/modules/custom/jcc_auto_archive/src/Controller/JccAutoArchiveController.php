<?php

namespace Drupal\jcc_auto_archive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
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
    // $config['system.logging']['error_level'] = 'hide';
    // $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'news');
    // dump($field_definitions['field_news_type']);
    $five_years_ago = strtotime('-5 years', REQUEST_TIME);
    $term_id = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'news_type')
      ->condition('name', 'News Release')
      ->execute();

    if (!empty($term_id)) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'news')
      // Created before 5 years ago.
        ->condition('created', $five_years_ago, '<')
        ->condition('status', 1)
        ->condition('field_news_type.target_id', reset($term_id));
      $nids = $query->execute();
    }
    else {
      printf("error: the node ids are empty");
    }

    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $node = Node::load($nid);
        $node->set('moderation_state', 'archived');
        try {
          $node->save();
          \Drupal::logger('jcc_auto_archive')->info("awu saved as archived for " . $nid);
        }
        catch (EntityStorageException $e) {
          \Drupal::logger('jcc_auto_archive')->error('Failed to archive Node @nid: @message ese', [
            '@nid' => $nid,
            '@message' => $e->getMessage(),
          ]);
        }
        catch (\Exception $e) {
          \Drupal::logger('jcc_auto_archive')->error('Unexpected error for Node @nid: @message', [
            '@nid' => $nid,
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
    else {
      \Drupal::logger('jcc_auto_archive')->info("awu no news release link found!");
    }
    \Drupal::logger('jcc_auto_archive')->info("awu exiting autoArchive_cron()");

    // $config['system.logging']['error_level'] = 'show';
  }

}
