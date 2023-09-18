<?php

namespace Drupal\jcc_elevated_sections\ThemeNegotiator;

/**
 * @file
 * Contains JccElevatedSectionsThemeNegotiator class.
 */

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\jcc_elevated_sections\Entity\JccSection;
use Symfony\Component\Routing\Route;

/**
 * Sets the admin theme when viewing JCC Section taxonomy terms.
 */
class JccElevatedSectionsThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a JccElevatedSectionsThemeNegotiator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Check if we are on a Section term for determineActiveTheme method.
   */
  public function applies(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();
    if (!$route instanceof Route) {
      return FALSE;
    }

    // Set the Section taxonomy terms page view to trigger the response for the
    // theme applied in determineActiveTheme(), which will be the admin theme.
    if ($route_match->getRouteName() == 'entity.taxonomy_term.canonical') {
      if ($tid = $route_match->getRawParameter('taxonomy_term')) {
        $section_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
        return $section_term instanceof JccSection;
      }
    }

    return FALSE;
  }

  /**
   * Determine the active theme if applies method returns TRUE.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('system.theme')->get('admin') ?: NULL;
  }

}
