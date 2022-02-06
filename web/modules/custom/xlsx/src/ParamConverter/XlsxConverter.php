<?php

namespace Drupal\xlsx\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Http\Exception\CacheableNotFoundHttpException;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting xlsx entity.
 */
class XlsxConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\ParamConverter\ParamNotConvertedException
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);

    // Get the xlsx ID.
    $id = $defaults['xlsx'] ?? FALSE;

    // Load the xlsx entity.
    if (!$id || !($entity = $this->entityTypeManager->getStorage($entity_type_id)->loadByProperties(['id' => $id]))) {
      $cache_metadata = new CacheableMetadata();
      throw new CacheableNotFoundHttpException($cache_metadata->setCacheContexts(['url']), 'Unable to load XLSX entity.');
    }

    return reset($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (parent::applies($definition, $name, $route) && $definition['type'] === 'entity:xlsx');
  }

}
