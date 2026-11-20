<?php

namespace Drupal\jcc_bulk_document_importer\Form;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to import media as document content type.
 *
 * @internal
 */
class BulkImporterForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The optional sections service.
   *
   * @var \Drupal\jcc_elevated_sections\JccSectionService|null
   */
  protected $sectionService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static();
    $form->database = $container->get('database');
    $form->fileSystem = $container->get('file_system');
    $form->entityTypeManager = $container->get('entity_type.manager');
    $form->moduleHandler = $container->get('module_handler');
    if ($container->has('jcc_elevated_sections.service')) {
      $form->sectionService = $container->get('jcc_elevated_sections.service');
    }
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    $form->setLoggerFactory($container->get('logger.factory'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_bulk_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Defaulting document type to oral argument.
    // $default_doc_type = Term::load('5');.
    $form['#attached']['library'][] = 'jcc_bulk_document_importer/importer_styling';

    $form['document_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Documents'),
      '#upload_location' => 'public://documents',
      '#multiple' => TRUE,
      '#extended' => TRUE,
      '#unlimited' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf zip doc docx xls xlsx ppt pptx csv'],
      ],
      '#description' => $this->t('Allowed formats: pdf, zip, doc, docx, xls, xlsx, ppt, pptx, csv'),
    ];

    $form['document_case_bundle'] = [
      '#title' => $this->t('Related Case'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => ['case'],
      ],
    ];

    $form['document_type'] = [
      '#title' => $this->t('Document type'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      // '#default_value' => $default_doc_type,
      '#selection_settings' => [
        'target_bundles' => ['document_type'],
      ],
    ];

    $form['hearing_date'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Hearing Date'),
      '#collapsible' => FALSE,
    ];

    $form['hearing_date']['document_daterange_start'] = [
      '#title' => $this->t('Start'),
      '#type' => 'date',
      '#attributes' => [
        'type' => 'date',
        'min' => '-12 months',
        'max' => '+12 months',
      ],
      '#default_value' => date("Y-m-d"),
      '#date_date_format' => 'Y/m/d',
    ];
    $form['hearing_date']['document_daterange_end'] = [
      '#title' => $this->t('End'),
      '#type' => 'date',
      '#attributes' => [
        'type' => 'date',
        'min' => '-12 months',
        'max' => '+12 months',
      ],
      '#default_value' => date("Y-m-d"),
      '#date_date_format' => 'Y/m/d',
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body'),
      '#format' => 'body',
    ];

    if ($this->moduleHandler->moduleExists('jcc_elevated_sections')) {
      $section_service = $this->sectionService;
      if ($section_service->isMediaSectionable('file') || $section_service->isNodeSectionable('document')) {
        $form['jcc_section'] = [
          '#type' => 'select',
          '#title' => $this->t('Assign Section'),
          '#options' => $section_service->getSectionOptionList(FALSE, TRUE),
        ];
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create documents'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $upload = $form_state->getValue('document_upload', []);
    $fids = array_unique($upload['fids'] ?? []);
    if (!$fids) {
      return;
    }
    sort($fids, SORT_NUMERIC);

    $database = $this->database;
    $transaction = $database->startTransaction();
    $file_system = $this->fileSystem;
    $backups = [];
    $restore_errors = [];
    $created = 0;
    $skipped = 0;
    try {
      // Serialize overlapping imports until commit, without an expiring lock.
      // Always lock in file-ID order to avoid deadlocks between bulk imports.
      $locked_fids = $database->select('file_managed', 'f')
        ->fields('f', ['fid'])
        ->condition('fid', $fids, 'IN')
        ->orderBy('fid')
        ->forUpdate()
        ->execute()
        ->fetchCol();
      if (count($locked_fids) !== count($fids)) {
        throw new \RuntimeException('An uploaded file no longer exists.');
      }

      $sections_enabled = $this->moduleHandler->moduleExists('jcc_elevated_sections');
      foreach ($fids as $fid) {
        // A saved media reference is the durable record of a completed import.
        // Check only after locking, so concurrent retries see the first result.
        $existing = $this->entityTypeManager->getStorage('media')->getQuery()
          ->accessCheck(FALSE)
          ->condition('bundle', 'file')
          ->condition('field_media_file.target_id', $fid)
          ->range(0, 1)
          ->execute();
        if ($existing) {
          $skipped++;
          continue;
        }

        $document = $upload['documents'][$fid] ?? NULL;
        if (!$document) {
          throw new \RuntimeException('Metadata is missing for an uploaded file.');
        }
        $file = $this->entityTypeManager->getStorage('file')->load($fid);
        // File (Field) Paths can move uploads from entity-save hooks. Keep a
        // copy so a database rollback also leaves the upload usable for retry.
        $backup = $file_system->tempnam('temporary://', 'bulk-import-');
        if (!$backup) {
          throw new \RuntimeException('Unable to back up an uploaded file.');
        }
        $backups[$fid] = [
          'original' => $file->getFileUri(),
          'backup' => $backup,
          'ready' => FALSE,
        ];
        $file_system->copy($file->getFileUri(), $backup, FileSystemInterface::EXISTS_REPLACE);
        $backups[$fid]['ready'] = TRUE;
        $this->createDocument($file, $document, $form_state, $sections_enabled);
        $created++;
      }
      // Commit the complete batch before reporting success or releasing locks.
      unset($transaction);
    }
    catch (\Throwable $exception) {
      // Restore moved uploads while their database rows are still locked.
      foreach ($backups as $fid => $paths) {
        if (!$paths['ready']) {
          continue;
        }
        try {
          if (!file_exists($paths['original'])) {
            $directory = $file_system->dirname($paths['original']);
            $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
            $file_system->copy($paths['backup'], $paths['original'], FileSystemInterface::EXISTS_ERROR);
          }
          $current_uri = $database->select('file_managed', 'f')
            ->fields('f', ['uri'])->condition('fid', $fid)->execute()->fetchField();
          if ($current_uri && $current_uri !== $paths['original'] && file_exists($current_uri)) {
            $file_system->delete($current_uri);
          }
        }
        catch (\Throwable $restore_exception) {
          $restore_errors[$fid] = $restore_exception;
        }
      }
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      // Entity caches may still contain entities from the rolled-back saves.
      foreach (['file', 'media', 'node'] as $entity_type) {
        $this->entityTypeManager->getStorage($entity_type)->resetCache();
      }
      watchdog_exception('jcc_bulk_document_importer', $exception);
      foreach ($restore_errors as $fid => $restore_exception) {
        watchdog_exception('jcc_bulk_document_importer', $restore_exception);
        $this->getLogger('jcc_bulk_document_importer')->error('Upload recovery copy retained at @path for file @fid.', [
          '@path' => $backups[$fid]['backup'],
          '@fid' => $fid,
        ]);
      }
      $this->messenger()->addError(!$restore_errors
        ? $this->t('The import failed. No new documents were saved. Please try again.')
        : $this->t('The import failed. No new documents were saved. An uploaded file could not be restored; please upload the files again.'));
      $form_state->setRebuild();
      return;
    }
    finally {
      foreach ($backups as $fid => $paths) {
        if (isset($restore_errors[$fid])) {
          continue;
        }
        try {
          $file_system->delete($paths['backup']);
        }
        catch (\Throwable $cleanup_exception) {
          watchdog_exception('jcc_bulk_document_importer', $cleanup_exception);
        }
      }
    }

    $this->messenger()->addStatus($this->t('Imported @created file(s). Skipped @skipped file(s) already imported.', [
      '@created' => $created,
      '@skipped' => $skipped,
    ]));
    $form_state->setRedirect('jcc_bulk_document_importer.content');
  }

  /**
   * Creates media and optional document content within the import transaction.
   */
  protected function createDocument(FileInterface $file, array $document, FormStateInterface $form_state, $sections_enabled) {
    $file->setPermanent();
    $file->save();

    $flag = !empty($document['media']['only-media']);
    $media = $this->entityTypeManager->getStorage('media')->create([
      'bundle' => 'file',
      'uid' => $this->currentUser()->id(),
      'field_media_file' => [
        'target_id' => $file->id(),
      ],
      'field_document_type' => $form_state->getValue('document_type', 0),
      'field_category' => $document['category'],
    ]);

    if ($sections_enabled) {
      $section_service = $this->sectionService;
      if ($section_service->isMediaSectionable('file')) {
        $value = $form_state->getValue('jcc_section', '');
        if ($value != '_none' || !empty($value)) {
          $media->set('jcc_section', $value);
        }
      }
    }

    $media->setName($document['custom_title'])->setPublished(TRUE)->save();

    // Hearing date 12:00a default time with GMT adjustment as needed.
    $gmt = date('P');
    $offset = substr(date('P'), 1, 2);
    switch ($gmt) {
      case str_contains($gmt, '-'):
        $adjust = 00 + $offset;
        break;

      case str_contains($gmt, '+'):
        $adjust = 24 - $offset;
        break;

      default:
        $adjust = 0;
    }
    $time = strval($adjust);
    $time = $time . ':00:00';
    if ($adjust < 10) {
      $time = '0' . $time;
    }

    // Save node
    // Create node object with attached file.
    if (!$flag) {
      $node = $this->entityTypeManager->getStorage('node')->create([
        'type' => 'document',
        'title' => $document['custom_title'],
        'field_verbose_title' => $document['custom_verbose_title'],
        'field_media' => [
          'target_id' => $media->id(),
          'alt' => $document['custom_title'],
          'title' => $document['custom_title'],
        ],
        'field_date_range' => [
          'value' => $form_state->getValue('document_daterange_start', 0) . 'T' . $time,
          'end_value' => $form_state->getValue('document_daterange_end', 0) . 'T' . $time,
        ],
        'field_date' => $document['filing_date'],
        'field_document_type' => $form_state->getValue('document_type', 0),
        'field_case' => $form_state->getValue('document_case_bundle', 0),
        'body' => $form_state->getValue('body', 0),
        'status' => 1,
      ]);

      if ($sections_enabled) {
        $section_service = $this->sectionService;
        if ($section_service->isMediaSectionable('file')) {
          $value = $form_state->getValue('jcc_section', '');
          if ($value != '_none' || !empty($value)) {
            $node->set('jcc_section', $value);
          }
        }
      }

      $node->save();
    }
  }

}
