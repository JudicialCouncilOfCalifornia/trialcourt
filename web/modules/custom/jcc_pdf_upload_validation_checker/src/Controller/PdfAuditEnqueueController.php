<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Queue\QueueFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for PDF audit enqueue operations.
 *
 * Route: /api/pdf-audit/{fid}/pending.
 */
final class PdfAuditEnqueueController extends ControllerBase {

  /**
   * The queue factory service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a PdfAuditEnqueueController object.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory service.
   */
  public function __construct(QueueFactory $queueFactory) {
    $this->queueFactory = $queueFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('queue'),
    );
  }

  /**
   * Marks a file's PDF audit status as pending and enqueues it for processing.
   *
   * @param int $fid
   *   The file entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response indicating the result of the operation.
   */
  public function markPendingAndQueue(int $fid): JsonResponse {
    /** @var \Drupal\file\FileInterface|null $file */
    $file = $this->entityTypeManager()->getStorage('file')->load($fid);
    if (!$file) {
      throw new NotFoundHttpException("File $fid not found.");
    }

    // Optional: only PDFs.
    if ($file->getMimeType() !== 'application/pdf') {
      return new JsonResponse([
        'ok' => FALSE,
        'fid' => $fid,
        'message' => 'Not a PDF.',
      ], 400);
    }

    if (!$file->hasField('field_pdf_audit_status')) {
      return new JsonResponse([
        'ok' => FALSE,
        'fid' => $fid,
        'message' => 'field_pdf_audit_status is not present on file entity.',
      ], 500);
    }

    // Set pending (and optionally clear prior report).
    $file->set('field_pdf_audit_status', 'pending');
    if ($file->hasField('field_pdf_audit_report')) {
      $file->set('field_pdf_audit_report', NULL);
    }
    $file->save();

    // Queue it (this ID must match your @QueueWorker id).
    $queue_name = 'jcc_pdf_upload_validation_checker_pdf_audit';
    $this->queueFactory->get($queue_name)->createItem(['fid' => $fid]);

    return new JsonResponse([
      'ok' => TRUE,
      'fid' => $fid,
      'queued' => $queue_name,
      'status' => 'pending',
    ]);
  }

}
