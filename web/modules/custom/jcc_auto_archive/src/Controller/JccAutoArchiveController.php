<?php

namespace Drupal\jcc_auto_archive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use GuzzleHttp\Exception\RequestException;
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
    $config['system.logging']['error_level'] = 'hide';

    // $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'news');
    // dump($field_definitions['field_news_type']);
    // Current timestamp.
    $now = REQUEST_TIME;
    $four_years_ago = strtotime('-4 years', $now);
    $five_years_ago = strtotime('-5 years', $now);

    $term_id = \Drupal::entityQuery('taxonomy_term')
    // Verified.
      ->condition('vid', 'news_type')
      ->condition('name', 'News Release')
      ->execute();

    if (!empty($term_id)) {
      // dump($term_id);
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'news')
        ->condition('created', $five_years_ago, '>')
        ->condition('created', $four_years_ago, '<')
        ->condition('status', 1)
        ->condition('field_news_type.target_id', reset($term_id));
      $nids = $query->execute();
      // dump($nids);
    }
    else {
      printf("! nids are empty");
    }

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
          //$node->save();
          \Drupal::logger('jcc_auto_archive')->info("awu saved as archived for " . $nid);
        }
        catch (RequestException $e) {
          \Drupal::logger('search_api_pantheon')->error('Solr Request Failed: @message', ['@message' => $e->getMessage()]);
          //drupal_set_message(t('Search is temporarily unavailable. Please try again later.'), 'error');
        }
        catch (\Throwable $e) {
          \Drupal::logger('jcc_auto_archive')->notice("awu something bad happened");
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
