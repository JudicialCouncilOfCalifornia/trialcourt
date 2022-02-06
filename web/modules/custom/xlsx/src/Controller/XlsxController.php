<?php

namespace Drupal\xlsx\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\xlsx\Plugin\XlsxSourceManager;
use Drupal\xlsx\Plugin\XlsxExportManager;

/**
 * Provides a listing of imports/exports.
 */
class XlsxController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * @var \Drupal\xlsx\Plugin\XlsxSourceManager
   */
  protected $xlsxSourceManager;

  /**
   * @var \Drupal\xlsx\Plugin\XlsxExportManager
   */
  protected $xlsxExportManager;

  /**
   * Constructs a new XlsxListBuilder object.
   *
   * @param \Drupal\xlsx\XlsxSourceManager $xlsx_source
   *   The XLSX source plugin manager.
   * @param \Drupal\xlsx\XlsxExportManager $xlsx_export
   *   The XLSX source plugin manager.
   */
  public function __construct(XlsxSourceManager $xlsx_source, XlsxExportManager $xlsx_export) {
    $this->xlsxSourceManager = $xlsx_source;
    $this->xlsxExportManager = $xlsx_export;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.xlsx_source'),
      $container->get('plugin.manager.xlsx_export')
    );
  }

  /**
   * List of available imports.
   */
  public function imports() {
    return $this->buildPageTemplate('import');
  }

  /**
   * List of available exports.
   */
  public function exports() {
    return $this->buildPageTemplate('export');
  }

  /**
   * Page template.
   */
  protected function buildPageTemplate($link) {
    $items = [];
    $entities = $this->entityTypeManager()->getStorage('xlsx')->loadByProperties([]);
    foreach ($entities as $entity) {
      $xlsx_source = $this->xlsxSourceManager->createInstance($entity->getSourcePlugin());
      if ($link == 'import' && $entity->isExportOnly()) {
        continue;
      }
      $items[] = [
        'title' => $entity->label(),
        'link' => $entity->toUrl($link),
        'source' => $xlsx_source->getName(),
      ];
    }
    return [
      '#type' => 'inline_template',
      '#template' => "{% if items %}<ul class=\"admin-list\">
        {% for item in items %}
          <li class=\"clearfix\"><a href=\"{{ item.link }}\"><span class=\"label\">{{ item.title }}</span><div class=\"description\">{{ item.source }}</div></a></li>
        {% endfor %}
      </ul>{% else %}<p>{{'No data mapping found. Please <a href=\"@link\">create new mapping</a>.'|t({ '@link': path('entity.xlsx.new') })}}</p>{% endif %}",
      '#context' => [
        'items' => $items,
      ],
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('xlsx')->getListCacheTags(),
      ],
    ];
  }

}
