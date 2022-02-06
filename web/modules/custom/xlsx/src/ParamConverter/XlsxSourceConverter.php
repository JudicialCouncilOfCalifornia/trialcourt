<?php

namespace Drupal\xlsx\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Drupal\xlsx\Plugin\XlsxSourceManager;

/**
 * Resolves "xlsx_source" type parameters in routes.
 */
final class XlsxSourceConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\xlsx\XlsxSourceManager
   */
  private $xlsxSourceManager;

  /**
   * XlsxSourceConverter constructor.
   *
   * @param \Drupal\xlsx\XlsxSourceManager $xlsx_source
   *   The XLSX source plugin manager.
   */
  public function __construct(XlsxSourceManager $xlsx_source) {
    $this->xlsxSourceManager = $xlsx_source;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $not_found = new NotFoundHttpException();
    try {
      $xlsx_source = $this->xlsxSourceManager->createInstance(str_replace('-', '_', $value));
      if ($xlsx_source->classExists()) {
        return $xlsx_source;
      }
      else {
        throw $not_found;
      }
    }
    catch (PluginNotFoundException $e) {
      throw $not_found;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'xlsx_source');
  }

}
