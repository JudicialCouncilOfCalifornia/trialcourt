<?php
/**
 * @file
 * Contains \Drupal\jcc_newslinks\Plugin\QueueWorker\NewsLinksUnpromoteQueueWorker.
 */

namespace Drupal\jcc_newslinks\Plugin\NewsLinksUnpromoteQueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes tasks for newslinks module.
 *
 * @QueueWorker(
 *   id = "newslinks_unpromote_queue",
 *   title = @Translation("NewsLinks: Unpromote items in queue."),
 *   cron = {"time" = 90}
 * )
 */
class NewsLinksUnpromoteQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {

    \Drupal::logger('jcc_newslinks')->notice("Cron processed this item...");

    // Load the node
    $node = \Drupal\node\Entity\Node::load($item->nid);

    // Unpromote the node
    if ($node->isPromoted() == TRUE) {
      $node->setPromoted(FALSE);
      $node->save();
    }
  }

}
