<?php

namespace Drupal\jcc_feeds_file_proxy\EventSubscriber;

use Drupal\file\FileRepositoryInterface;
use Drupal\media\Entity\Media;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\Core\File\FileSystemInterface;
use Drupal\File\FileRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\feeds\Event\EntityEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Image download management for bing feed imports.
 */
class JCCFeedsFileProxyEventsSubscriber implements EventSubscriberInterface {

  /**
   * Request used to find hostname.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack, FileRepositoryInterface $file_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->fileRepository = $file_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('file.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Modify events after saving imported data from feeds.
   *
   * @param \Drupal\feeds\Event\EntityEvent $event
   *   The Entity Event.
   */
  public function postSaveNews(EntityEvent $event) {
    $node = $event->getEntity();
    $media_id = $node->get('field_media')->target_id;
    $media = $this->entityTypeManager->getStorage('media')->load($media_id);
    if ($media) {
      $image_url = $media->getName();
      $title = pathinfo($image_url, PATHINFO_FILENAME);
      $title = urldecode($title);
      $data = file_get_contents($image_url);
      if ($data) {
        $file = \Drupal::service('file.repository')->writeData($data, 'public://images/' . basename($title), FileSystemInterface::EXISTS_REPLACE);

        if ($file) {
          $file_id = $file->id();
          $media->set('field_media_image', ['target_id' => $file_id]);
          $media->save();
        }
      }
    }
    if ($node instanceof Node && $node->bundle() === 'news') {
      if ($node->get('moderation_state')->value !== 'published') {
        $node->set('moderation_state', 'published');
        $node->save();
      }
    }
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
      ->accessCheck(FALSE)
      ->condition("name", $img_id)
      ->sort("mid", "DESC")
      ->execute();
    $mid = reset($media);
    if ($mid) {
      return $mid;
    }

    $file_data = file_get_contents($img_url);

    if (!file_exists('public://newslink/')) {
      \Drupal::service('file_system')->mkdir('public://newslink/');
    }

    $filename = "public://newslink/" . $img_id . ".png";
    if (!file_exists($filename)) {
      $file = $this->fileRepository->writeData($file_data, $filename, FileSystemInterface::EXISTS_REPLACE);

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
    $events[FeedsEvents::PROCESS_ENTITY_POSTSAVE][] = ['postSaveNews', FeedsEvents::AFTER];
    return $events;
  }

}
