<?php

namespace Drupal\jcc_tc2_roc_feature;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\jcc_tc2_roc_feature\Access\LatestRevisionCheck;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Decorates the access_unpublished.latest_revision service.
 */
class JccTc2RocFeatureServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    // We basically are piling on top of the override/alter that access_
    // unpublished module does. We have to alter the access method that checks
    // if a tokenized version is ok to be looked at. We have our own check for
    // if the user is anonymous, and it's a rule/rule_index type of node.
    // This is from Drupal\access_unpublished\AccessUnpublishedServiceProvider
    // with some service names adjusted. We use our LatestRevisionCheck class
    // which extends the same access_unpublished Class.
    if ($container->hasDefinition('access_unpublished.access_check.latest_revision')) {
      $container->register('jcc_tc2_roc_feature.access_check.latest_revision', LatestRevisionCheck::class)
        ->setDecoratedService('access_unpublished.access_check.latest_revision')
        ->addArgument(new Reference('jcc_tc2_roc_feature.access_check.latest_revision.inner'))
        ->addTag('access_check', ['applies_to' => '_content_moderation_latest_version']);
    }
  }

}
