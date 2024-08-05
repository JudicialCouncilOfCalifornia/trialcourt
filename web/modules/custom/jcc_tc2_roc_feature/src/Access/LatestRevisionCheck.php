<?php

namespace Drupal\jcc_tc2_roc_feature\Access;

use Drupal\access_unpublished\Access\LatestRevisionCheck as ContentModerationLatestRevisionCheck;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for the entity moderation tab which supports access_unpublished.
 */
class LatestRevisionCheck extends ContentModerationLatestRevisionCheck {

  /**
   * The decorated access check.
   *
   * @var \Drupal\Core\Routing\Access\AccessInterface
   */
  protected $accessCheck;

  /**
   * LatestRevisionCheck constructor.
   *
   * @param \Drupal\Core\Routing\Access\AccessInterface $access_check
   *   Latest revision access check to decorate.
   */
  public function __construct(AccessInterface $access_check) {
    $this->accessCheck = $access_check;
  }

  /**
   * Checks that there is a pending revision available.
   *
   * This checker assumes the presence of an '_entity_access' requirement key
   * in the same form as used by EntityAccessCheck.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @see \Drupal\Core\Entity\EntityAccessCheck
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\Core\Access\AccessResultInterface $access */
    $access = $this->accessCheck->access($route, $route_match, $account);
    $entity = $this->loadEntity($route, $route_match);
    return $access->orIf(access_unpublished_entity_access($entity, 'view', $account))
      ->orIf(jcc_tc2_roc_feature_entity_access($entity, 'view', $account));
  }

}
