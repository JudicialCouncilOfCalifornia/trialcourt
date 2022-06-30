<?php

namespace Drupal\jcc_feeds_file_proxy\EventSubscriber;

use Drupal\media\entity\Media;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Image download management for bing feed imports.
 */
class JCCFeedsFileProxyEventsSubscriber implements EventSubscriberInterface {

  /**
   * Request used to find hostname.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */

  protected $request;

  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack) {
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Modify events after existing parser.
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParse(ParseEvent $event) {
    /**
     * @var \Drupal\feeds\Result\ParserResultInterface
     * @var \Drupal\feeds\Feeds\Item\ItemInterface
     */
    if ($event->getFeed()->getType()->id() != 'news_bing_feed') {
      return;
    }
    else {
      foreach ($event->getParserResult() as $item) {
        // 'news_image' should be set in feeds mappings
        $news_image = $item->get('news_image');
        if (!empty($news_image)) {
          // Removing arguments in feeds url.
          $news_image = str_replace("&pid=News", "", $news_image);
          $url_components = parse_url($news_image);
          parse_str($url_components['query'], $img_url_args);
          $img_id = str_replace("ON.", "", $img_url_args['id']);

          // Creating a new media entity and altering the parser.
          $newEntityId = $this->createImgEntity($news_image, $img_id);
          if ($newEntityId) {
            $item->set('news_image', $newEntityId);
          }
        }
      }
    }
  }

  /**
   * Create image entity out of downloaded image.
   */
  protected function createImgEntity(string $img_url, string $img_id) {
    $media = \Drupal::entityQuery("media")
      ->condition("name", $img_id)
      ->sort("mid", "DESC")
      ->execute();
    $mid = reset($media);
    if ($mid) {
      return $mid;
    }

    $file_data = file_get_contents($img_url);

    if (!file_exists('public://newslink/')) {
      drupal_mkdir('public://newslink/');
    }

    $filename = "public://newslink/" . $img_id . ".png";
    if (!file_exists($filename)) {
//      $file = file_save_data($file_data, $filename, FILE_EXISTS_REPLACE);

//      $filesaved = file_save_data($file, $destination, FileSystemInterface::EXISTS_REPLACE);
      $fileRepository = \Drupal::service('file.repository');
      $file = $fileRepository->writeData($file_data, $filename, FileSystemInterface::EXISTS_REPLACE);


      $media = Media::create([
        'bundle' => 'image',
        'uid' => '1',
        'field_media_image' => $file,

        // NewsLink in Media Directory.
        'directory' => '188',
      ]);
      $media->setName($img_id)->setPublished(TRUE)->save();
      return $media->id();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    return $events;
  }

}
