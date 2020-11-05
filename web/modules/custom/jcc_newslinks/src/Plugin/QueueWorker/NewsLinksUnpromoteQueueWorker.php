<?php

namespace Drupal\jcc_newslinks\Plugin\QueueWorker;

use Drupal\node\Entity\Node;
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
    // Load the node.
    $node = Node::load($item->nid);

    // Unpromote the node.
    if ($node->isPromoted() == TRUE) {
      $node->setPromoted(FALSE);
      $node->save();
      $message = $node->get('title')->value . ' is unpromoted';
      \Drupal::logger('jcc_newslinks')->notice($message);
    }
  }

}
