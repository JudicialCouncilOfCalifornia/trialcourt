<?php
/**
 * @file
 * Contains \Drupal\jcc_newslinks\Plugin\QueueWorker\NewsLinksArchiveQueueWorker.
 */

namespace Drupal\jcc_newslinks\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

/**
 * Processes tasks for newslinks module.
 *
 * @QueueWorker(
 *   id = "newslinks_archive_queue",
 *   title = @Translation("NewsLinks: Archive items in queue."),
 *   cron = {"time" = 90}
 * )
 */
class NewsLinksArchiveQueueWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($item) {
    // Load the node.
    $node = Node::load($item->nid);
    $moderationState = $node->get('moderation_state')->value;

    switch ($moderationState) {
      case 'draft':
        // Archive newslink drafts.
        //$node->setPublished(FALSE);
        //$node->set('moderation_state', 'archived');
        //$node->save();

        $message =  'Archiving draft: "' . $node->get('title')->value . '"';
        \Drupal::logger('jcc_newslinks')->notice($message);
        break;
      case 'archived':
        // Purge image from archived newslinks.
        $newsImages = $node->field_images;
        foreach($newsImages as $key => $value) {
          if (isset($node->field_images[$key]->entity->mid->value)) {
            $mid = $node->field_images[$key]->entity->mid->value;
            $media = Media::load($mid);
            $fid = $media->field_media_image->target_id;
            $file = File::load($fid);
            $fileName = $file->label();

            // Removing media image from node.
            unset($node->field_images[$key]);
            $node->save();
            $removeMsg = 'Removing ' . $fileName . ' from "' . $node->get('title')->value . '"';
            \Drupal::logger('jcc_newslinks')->notice($removeMsg);

            // If media image is not referenced by any nodes.
            sleep(3);
            $queryMediaUsage = \Drupal::entityQuery('node')
              ->condition('type', 'news')
              ->condition('field_news_type.entity:taxonomy_term.name', 'NewsLink')
              ->condition('field_images', $mid);
            $mediaUsage = $queryMediaUsage->execute();
            if (empty($mediaUsage)) {
              // Delete media image entity/page.
              $media->delete();
              $deleteMsg = 'Deleting media image entity for ' . $fileName;
              \Drupal::logger('jcc_newslinks')->notice($deleteMsg);

              // Mark image file for auto deletion.
              sleep(3);
              $fileUsage = \Drupal::service('file.usage');
              $usage = $fileUsage->listUsage($file);

              if (empty($usage)) {
                $file->setTemporary();
                $file->save();
                $deleteMsg = 'File marked temporary for deletion: ' . $fileName;
                \Drupal::logger('jcc_newslinks')->notice($deleteMsg);
              }
            }
            else {
              $deleteMsg = 'Image file still in use: ' . $fileName;
              \Drupal::logger('jcc_newslinks')->notice($deleteMsg);
            }
          }
          else {
            \Drupal::logger('jcc_newslinks')->warning('No image media found: "' . $node->get('title')->value . '"');
          }
        }
        break;
      default:
        \Drupal::logger('jcc_newslinks')->info('Nothing to archive: "' . $node->get('title')->value . '"');
    }
  }

}
