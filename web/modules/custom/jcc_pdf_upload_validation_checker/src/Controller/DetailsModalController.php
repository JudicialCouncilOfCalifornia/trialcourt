<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns field content for display inside a modal dialog.
 */
final class DetailsModalController extends ControllerBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $customEntityTypeManager;

  /**
   * Constructs a DetailsModalController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->customEntityTypeManager = $entityTypeManager;
  }

  /**
   * Creates a new controller instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   The controller instance.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the modal content for a field on an entity.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string|int $entity
   *   The entity ID.
   * @param string $field_name
   *   The field machine name.
   *
   * @return array
   *   A render array.
   */
  public function content(string $entity_type, $entity, string $field_name): array {
    $storage = $this->customEntityTypeManager->getStorage($entity_type);
    if (!$storage) {
      throw new NotFoundHttpException();
    }

    $entity = $storage->load($entity);
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    if (!$entity->access('view')) {
      throw new AccessDeniedHttpException();
    }

    if (!$entity->hasField($field_name) || $entity->get($field_name)->isEmpty()) {
      return [
        '#markup' => $this->t('No details available.'),
      ];
    }

    $raw_text = (string) $entity->get($field_name)->value;

    // Convert plain-text URLs to clickable links, then preserve line breaks.
    $processed = preg_replace_callback(
      '~(https?://[^\s]+)~',
      function (array $m): string {
        $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">View full report</a>';
      },
      htmlspecialchars($raw_text, ENT_QUOTES, 'UTF-8')
    );
    $processed = nl2br($processed ?? '');

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['jcc-details-modal-content'],
      ],
      '#attached' => [
        'library' => [
          'jcc_pdf_upload_validation_checker/details_modal',
        ],
      ],
      'content' => [
        '#markup' => $processed,
      ],
    ];
  }

}
