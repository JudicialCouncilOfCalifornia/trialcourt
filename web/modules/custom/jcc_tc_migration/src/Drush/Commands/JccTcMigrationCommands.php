<?php

namespace Drupal\jcc_tc_migration\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\file\FileUsage\FileUsageInterface;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for JCC TC Migration.
 */
class JccTcMigrationCommands extends DrushCommands {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a JccTcMigrationCommands object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FileUsageInterface $file_usage,
    LoggerChannelFactoryInterface $logger_factory,
    FileSystemInterface $file_system
  ) {
    parent::__construct();
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUsage = $file_usage;
    $this->fileSystem = $file_system;
    $this->setLogger($logger_factory->get('jcc_tc_migration'));
  }

  /**
   * Bulk delete media and file entities from a CSV.
   *
   * CSV should have columns: filename, media_id, url.
   *
   * @param string $csv
   *   Path to the CSV file.
   * @param array $options
   *   Command options.
   *
   * @command jcc-tc-migration:bulk-media-delete
   * @aliases bmd
   * @option dry-run Preview deletions without actually deleting.
   * @option batch-size Number of entities to process per batch. Default: 50.
   * @usage drush jcc-tc-migration:bulk-media-delete /path/file.csv --dry-run.
   *   Preview which media and files would be deleted.
   * @usage drush jcc-tc-migration:bulk-media-delete /path/to/file.csv
   *   Delete all media and files listed in the CSV.
   */
  public function bulkMediaDelete($csv,
    $options = ['dry-run' => FALSE, 'batch-size' => 50]) {
    if (!file_exists($csv)) {
      $this->logger()->error(dt('CSV file not found: @path', ['@path' => $csv]));
      return;
    }

    $handle = fopen($csv, 'r');
    if (!$handle) {
      $this->logger()->error(dt('Could not open CSV file: @path', ['@path' => $csv]));
      return;
    }

    $header = fgetcsv($handle);
    if (!$header) {
      $this->logger()->error(dt('CSV file is empty.'));
      fclose($handle);
      return;
    }

    // Normalize header column names.
    $header = array_map('trim', array_map('strtolower', $header));
    $filename_col = array_search('filename', $header);
    $media_id_col = array_search('media_id', $header);
    $url_col = array_search('url', $header);

    if ($filename_col === FALSE) {
      $this->logger()->error(dt('CSV must have a "filename" column. Found: @cols', [
        '@cols' => implode(', ', $header),
      ]));
      fclose($handle);
      return;
    }

    $rows = [];
    while (($row = fgetcsv($handle)) !== FALSE) {
      if (empty(array_filter($row))) {
        continue;
      }
      $rows[] = [
        'filename' => trim($row[$filename_col]),
        'media_id' => ($media_id_col !== FALSE && isset($row[$media_id_col])) ? trim($row[$media_id_col]) : '',
        'url' => ($url_col !== FALSE && isset($row[$url_col])) ? trim($row[$url_col]) : '',
      ];
    }
    fclose($handle);

    $total = count($rows);
    if ($total === 0) {
      $this->logger()->warning(dt('No data rows found in CSV.'));
      return;
    }

    $dry_run = $options['dry-run'];
    $batch_size = (int) $options['batch-size'];

    if ($dry_run) {
      $this->logger()->notice(dt('DRY RUN — no entities will be deleted.'));
    }

    $this->logger()->notice(dt('Setting up batch for @count rows...', ['@count' => $total]));

    // Use a single batch operation that processes rows one at a time
    // with sandbox for progress tracking.
    $batch = [
      'title' => dt('Deleting media and file entities'),
      'operations' => [
        [[static::class, 'processBatch'], [$rows, $dry_run, $batch_size]],
      ],
      'finished' => [static::class, 'batchFinished'],
      'init_message' => dt('Starting bulk media delete...'),
      'error_message' => dt('An error occurred during bulk media delete.'),
    ];

    batch_set($batch);
    drush_backend_batch_process();
  }

  /**
   * Batch operation callback: process a chunk of rows.
   *
   * @param array $rows
   *   Array of row data from the CSV.
   * @param bool $dry_run
   *   Whether this is a dry run.
   * @param int $batch_size
   *   Number of rows to process in this batch operation.
   * @param array $context
   *   Batch context.
   */
  public static function processBatch(array $rows, $dry_run, $batch_size, &$context) {
    // Initialize sandbox on first call.
    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['total'] = count($rows);
      $context['sandbox']['rows'] = $rows;
      $context['results']['deleted'] = 0;
      $context['results']['files_deleted'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['errors'] = 0;
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    $file_usage = \Drupal::service('file.usage');
    $file_system = \Drupal::service('file_system');
    $media_storage = $entity_type_manager->getStorage('media');
    $file_storage = $entity_type_manager->getStorage('file');

    $progress = $context['sandbox']['progress'];
    $chunk = array_slice($context['sandbox']['rows'], $progress, $batch_size);

    foreach ($chunk as $item) {
      $context['sandbox']['progress']++;
      $media_id = $item['media_id'];
      $filename = $item['filename'];
      $url = $item['url'];

      $media = NULL;
      $file = NULL;

      // Extract numeric media ID from URL if needed.
      if (!empty($media_id) && !is_numeric($media_id)) {
        if (preg_match('#/media/(\d+)#', $media_id, $matches)) {
          $media_id = $matches[1];
        }
        else {
          $media_id = '';
        }
      }

      // Try to load the media entity if an ID is provided.
      if (!empty($media_id)) {
        /** @var \Drupal\media\MediaInterface $media */
        $media = $media_storage->load($media_id);
      }

      if ($media) {
        // Get the source file entity from the media.
        $source_field = $media->getSource()->getConfiguration()['source_field'];
        $file_field = $media->get($source_field);
        if (!$file_field->isEmpty()) {
          $file = $file_storage->load($file_field->target_id);
        }

        if ($dry_run) {
          \Drupal::logger('jcc_tc_migration')->notice(dt('[DRY RUN] Would delete media @id (@name)@file', [
            '@id' => $media_id,
            '@name' => $media->label(),
            '@file' => $file ? ' and file ' . $file->getFilename() : '',
          ]));
          $context['results']['deleted']++;
          $context['message'] = dt('[DRY RUN] Media @id (@name)', [
            '@id' => $media_id,
            '@name' => $media->label(),
          ]);
          continue;
        }

        try {
          $media->delete();
          $context['results']['deleted']++;
          $context['message'] = dt('Deleted media @id', ['@id' => $media_id]);
        }
        catch (\Exception $e) {
          \Drupal::logger('jcc_tc_migration')->error(dt('Error deleting media @id: @msg', [
            '@id' => $media_id,
            '@msg' => $e->getMessage(),
          ]));
          $context['results']['errors']++;
          continue;
        }
      }
      else {
        // Media missing or no media_id — derive file URI from URL.
        if (!empty($url)) {
          $uri = static::resolveUriFromUrl($url);
          if ($uri) {
            // Try decoded URI first, then URL-encoded version.
            $files = $file_storage->loadByProperties(['uri' => $uri]);
            if (!$files) {
              $encoded_uri = str_replace(' ', '%20', $uri);
              $files = $file_storage->loadByProperties(['uri' => $encoded_uri]);
            }
            $file = $files ? reset($files) : NULL;
          }
        }

        if (!$file) {
          \Drupal::logger('jcc_tc_migration')->warning(dt('No media or file entity found for row: media_id=@id, filename=@name, url=@url', [
            '@id' => $media_id ?: '(empty)',
            '@name' => $filename,
            '@url' => $url ?: '(empty)',
          ]));
          $context['results']['skipped']++;
          continue;
        }

        if ($dry_run) {
          \Drupal::logger('jcc_tc_migration')->notice(dt('[DRY RUN] No media; would delete file entity @fid (@uri)', [
            '@fid' => $file->id(),
            '@uri' => $file->getFileUri(),
          ]));
          $context['results']['files_deleted']++;
          $context['message'] = dt('[DRY RUN] File @fid', ['@fid' => $file->id()]);
          continue;
        }
      }

      // Delete file entity if it exists and is no longer referenced.
      if ($file) {
        $usage = $file_usage->listUsage($file);
        if (empty($usage)) {
          try {
            $file->delete();
            $context['results']['files_deleted']++;
          }
          catch (\Throwable $e) {
            // A hook (e.g. CDN purge) may throw on URIs with special
            // characters, rolling back the transaction. Fall back to
            // direct database removal.
            \Drupal::logger('jcc_tc_migration')->info(dt('Normal delete failed for file @fid, falling back to direct removal: @msg', [
              '@fid' => $file->id(),
              '@msg' => $e->getMessage(),
            ]));
            try {
              $fid = $file->id();
              $file_uri = $file->getFileUri();
              $db = \Drupal::database();
              $db->delete('file_usage')->condition('fid', $fid)->execute();
              $db->delete('file_managed')->condition('fid', $fid)->execute();
              // Remove the physical file.
              $real_path = $file_system->realpath($file_uri);
              if ($file_uri && $real_path && file_exists($real_path)) {
                $file_system->delete($file_uri);
              }
              $file_storage->resetCache([$fid]);
              $context['results']['files_deleted']++;
              \Drupal::logger('jcc_tc_migration')->notice(dt('File @fid force-deleted via direct DB removal.', ['@fid' => $fid]));
            }
            catch (\Throwable $e2) {
              \Drupal::logger('jcc_tc_migration')->error(dt('Error force-deleting file @fid: @msg', [
                '@fid' => $file->id(),
                '@msg' => $e2->getMessage(),
              ]));
              $context['results']['errors']++;
            }
          }
        }
        else {
          \Drupal::logger('jcc_tc_migration')->info(dt('File @fid (@fname) still in use, kept.', [
            '@fid' => $file->id(),
            '@fname' => $file->getFilename(),
          ]));
        }
      }
    }

    // Reset entity caches after each chunk to free memory.
    $media_storage->resetCache();
    $file_storage->resetCache();

    // Update progress for the progress bar.
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    $context['message'] = dt('Processed @progress of @total rows (@percent%).', [
      '@progress' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
      '@percent' => round($context['finished'] * 100),
    ]);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed without errors.
   * @param array $results
   *   Accumulated results from batch operations.
   * @param array $operations
   *   Remaining operations if batch failed.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      \Drupal::logger('jcc_tc_migration')->notice(dt('Done. Media deleted: @deleted, Files deleted: @files, Skipped: @skipped, Errors: @errors', [
        '@deleted' => $results['deleted'] ?? 0,
        '@files' => $results['files_deleted'] ?? 0,
        '@skipped' => $results['skipped'] ?? 0,
        '@errors' => $results['errors'] ?? 0,
      ]));
    }
    else {
      \Drupal::logger('jcc_tc_migration')->error(dt('Batch finished with errors. @count operations remaining.', [
        '@count' => count($operations),
      ]));
    }
  }

  /**
   * Resolve a public URL to a Drupal stream wrapper URI.
   *
   * @param string $url
   *   The full URL, e.g.
   *   https://example.com/sites/default/files/newsroom/doc.pdf.
   *
   * @return string|null
   *   The stream URI (public:// or private://), or NULL if unresolvable.
   */
  protected static function resolveUriFromUrl($url) {
    // Strip scheme and host to get the path. We avoid parse_url() because
    // filenames may contain '#' which it interprets as a fragment delimiter.
    $path = preg_replace('#^https?://[^/]+#', '', $url);
    $path = urldecode($path);
    if (!$path) {
      return NULL;
    }

    // /system/files/ serves private files.
    if (preg_match('#/system/files/(.+)$#', $path, $matches)) {
      return 'private://' . $matches[1];
    }

    // Public files are served from the configured file_public_path.
    // e.g. sites/default/files/newsroom — strip it to get the stream target.
    $public_base = PublicStream::basePath();
    if ($public_base) {
      $escaped = preg_quote('/' . $public_base, '#');
      if (preg_match('#' . $escaped . '/(.+)$#', $path, $matches)) {
        return 'public://' . $matches[1];
      }
    }

    return NULL;
  }

}
