<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Plugin\views\field;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a "Run PDF Audit" link for file rows.
 *
 * @ViewsField("jcc_pdf_audit_link")
 */
final class PdfAuditLink extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Constructs a PdfAuditLink field plugin.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // Intentionally empty: this field is computed from the row entity.
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    return $this->t('PDF Audit link');
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity ?? NULL;
    if (!$entity instanceof FileInterface) {
      return ['#markup' => ''];
    }

    if ($entity->getMimeType() !== 'application/pdf') {
      return ['#markup' => ''];
    }

    $absolute_pdf_url = $this->fileUrlGenerator->generateAbsoluteString($entity->getFileUri());
    $pdfaudit_url = 'https://www.pdfaudit.org/?pdf_url=' . rawurlencode($absolute_pdf_url);

    if (!UrlHelper::isValid($pdfaudit_url, TRUE)) {
      return ['#markup' => ''];
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('Details'),
      '#url' => Url::fromUri($pdfaudit_url),
      '#attributes' => [
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
    ];
  }

}
