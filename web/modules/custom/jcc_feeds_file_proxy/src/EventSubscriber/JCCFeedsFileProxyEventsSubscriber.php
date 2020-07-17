<?php

namespace Drupal\jcc_feeds_file_proxy\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
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
        $news_image = $item->get('news_image');
        if (!empty($news_image)) {
          $news_image = str_replace("&pid=News", "", $news_image);
          $url_components = parse_url($news_image);
          parse_str($url_components['query'], $img_url_args);
          $img_id = str_replace("ON.", "", $img_url_args['id']);

          // Full url needed for reference
          // (See https://www.drupal.org/project/feeds/issues/2969401)
          $siteHost = $this->request->getSchemeAndHttpHost();
          $proxy_image = $siteHost . '/sites/default/files/slo/newslink/' . $img_id . '.png';
          $this->createImg($news_image, $img_id);
          $item->set('news_image', $proxy_image);
        }
      }
    }
  }

  /**
   * Download Image in the public folder.
   */
  protected function createImg(string $img_url, string $img_id) {
    system_retrieve_file($img_url, "public://newslink/" . $img_id . ".png", TRUE, FILE_EXISTS_REPLACE);
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
