<?php

namespace Drupal\jcc_ckeditor_link;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the JCC CKEditor Link module.
 */
class JccCkeditorLinkServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {

    $filter_protocols = $container->getParameter('filter_protocols');

    // Add "geo" and "maps" URI protocols to the allowed list of filtered
    // protocols that are allowed in links (href). Without this, geo: and maps:
    // would be stripped out of href urls.
    $filter_protocols[] = 'geo';
    $filter_protocols[] = 'maps';
    $filter_protocols[] = 'comgooglemapsurl';

    $container->setParameter('filter_protocols', $filter_protocols);
  }

}
