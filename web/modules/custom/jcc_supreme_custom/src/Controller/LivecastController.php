<?php

namespace Drupal\jcc_supreme_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\jcc_supreme_custom\LivecastBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Delivers the livecast independently of cached front-page responses.
 */
class LivecastController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs the livecast controller.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(RendererInterface $renderer, ThemeManagerInterface $theme_manager) {
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('theme.manager')
    );
  }

  /**
   * Do not cache the JSON response for the livecast banner.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The rendered banner, or an empty string when no event is active.
   */
  public function build() {
    $build = LivecastBuilder::build($this->themeManager->getActiveTheme()->getName());
    $html = (string) $this->renderer->renderRoot($build);

    // Fragment max-age alone cannot prevent caching of a complete response.
    return new JsonResponse(['html' => $html], 200, [
      'Cache-Control' => 'private, no-store, no-cache, max-age=0, must-revalidate',
    ]);
  }

}
