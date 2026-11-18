<?php

namespace Drupal\jcc_migrate_source_ui\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileExists;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\StateInterface;
use Drupal\file\FileUsage\DatabaseFileUsageBackend;
use Drupal\jcc_migrate_source_ui\MigrateBatchExecutable;
use Drupal\jcc_migrate_source_ui\StubMigrationMessage;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Json;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Xml;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class JCCMigrateSourceUiForm extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * The migration definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * The database connection.
   *
   * @var array
   */
  protected $database;

  /**
   * The state interface.
   *
   * @var array
   */
  protected $state;

  /**
   * The database backend for discovery migration cache.
   *
   * @var array
   */
  protected $cacheDiscoveryMigration;

  /**
   * The file usage backend.
   *
   * @var array
   */
  protected $fileUsage;

  /**
   * The drupal messenger service.
   *
   * @var array
   */
  protected $messenger;

  /**
   * The drupal logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * MigrateSourceUiForm constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   The migration plugin manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_discovery_migration
   *   The cache service for discovery_migration.
   * @param \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage
   *   The file.usage service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannel $logger_channel
   *   The logger channel.
   * @param \Drupal\Core\Entity\EntityTypeManager $type_manager
   *   Entity Type Manager.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValue
   *   The key value factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    MigrationPluginManager $plugin_manager_migration,
    Connection $database,
    StateInterface $state,
    CacheBackendInterface $cache_discovery_migration,
    DatabaseFileUsageBackend $file_usage,
    Messenger $messenger,
    LoggerChannel $logger_channel,
    EntityTypeManager $type_manager,
    protected KeyValueFactoryInterface $keyValue,
    protected TimeInterface $time,
  ) {

    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
    $this->database = $database;
    $this->state = $state;
    $this->cacheDiscoveryMigration = $cache_discovery_migration;
    $this->fileUsage = $file_usage;
    $this->messenger = $messenger;
    $this->logger = $logger_channel;
    $this->entityTypeManager = $type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('database'),
      $container->get('state'),
      $container->get('cache.discovery_migration'),
      $container->get('file.usage'),
      $container->get('messenger'),
      $container->get('logger.factory')->get('jcc_migrate_source_ui'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_migrate_source_ui_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create form options of available migrations.
    $options = [];
    foreach ($this->definitions as $definition) {
      $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
      if ($migrationInstance->getSourcePlugin() instanceof CSV || $migrationInstance->getSourcePlugin() instanceof Json || $migrationInstance->getSourcePlugin() instanceof Xml || $migrationInstance->getSourcePlugin() instanceof Url) {
        $plugin_id = $migrationInstance->getSourcePlugin()->getPluginId();
        $id = $definition['id'];
        $options[$plugin_id][$id] = $this->t('%id (supports %file_type)', [
          '%id' => $definition['label'] ?? $id,
          '%file_type' => $plugin_id,
        ]);
      }
    }
    // Supported type options.
    $supported_types = [
      'csv' => 'csv',
      'json' => 'json',
      'xml' => 'xml',
      'url' => 'url',
    ];
    // Limit type selection to avaialble types.
    $available_types = array_intersect_key($supported_types, $options);

    $form = [
      '#tree' => TRUE,
    ];
    $form['source_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select source type'),
      '#required' => TRUE,
      '#options' => $available_types,
    ];
    // Create selects for each type.
    foreach ($available_types as $type) {
      $opts = $options[$type];
      asort($opts);
      $type_title = strtoupper($type) . ' ' . $this->t('Sources');
      $form['migrations'][$type] = [
        '#type' => 'select',
        '#title' => $type_title,
        '#options' => $opts,
        '#states' => [
          'visible' => [
            ':input[name="source_type"]' => ['value' => $type],
          ],
        ],
      ];
      // Indicators for existing source records, displayed when matching
      // option is selected.
      foreach (array_keys($opts) as $key) {
        $stored_source = $this->getMigrationSource($key);
        $form['migrations'][$key] = [
          '#type' => 'item',
          '#title' => $this->t('Stored Source'),
          '#plain_text' => is_array($stored_source) ? implode(', ', $stored_source) : (string) $stored_source,
          '#value' => $stored_source,
          '#access' => !empty($stored_source),
          '#states' => [
            'visible' => [
              ':input[name="source_type"]' => ['value' => $type],
              ':input[name="migrations[' . $type . ']"]' => ['value' => $key],
            ],
          ],
        ];
      }
    }
    $form['source_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the source file'),
      '#states' => [
        'visible' => [
          [':input[name="source_type"]' => ['value' => 'csv']],
          [':input[name="source_type"]' => ['value' => 'xml']],
          [':input[name="source_type"]' => ['value' => 'json']],
        ],
      ],
    ];
    $form['source_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL for the source.'),
      '#description' => $this->t('Include protocol: https://'),
      '#states' => [
        'visible' => [
          ':input[name="source_type"]' => ['value' => 'url'],
        ],
      ],
      '#prefix' => '<div id="migrate-source-url">',
      '#suffix' => '</div>',
      '#maxlength' => 255,
    ];
    $form['run_migration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run Migration'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="remove_source"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['remove_source'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove Source'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="run_migration"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['update_existing_records'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing records'),
      '#default_value' => 0,
      '#states' => [
        'visible' => [
          ':input[name="run_migration"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $migrations = $form_state->getValue('migrations');

    if ($form_state->getValue('remove_source')) {
      return;
    }

    $source_type = $form_state->getValue('source_type');
    $migration_id = $migrations[$source_type];
    $definition = $this->pluginManagerMigration->getDefinition($migration_id);
    $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
    if ($source_type === 'url') {
      $url = trim((string) $form_state->getValue('source_url'));
      $url = $url !== '' ? $url : $this->getMigrationSource($migration_id);
      $urls = is_array($url) ? $url : [$url];
      $protocols = ['http', 'https'];
      foreach ($urls as $source_url) {
        if (!$source_url || !UrlHelper::isValid($source_url, TRUE) || !in_array(parse_url($source_url, PHP_URL_SCHEME), $protocols, TRUE)) {
          $form_state->setErrorByName('source_url', $this->t('Enter a valid HTTP or HTTPS source URL.'));
          return;
        }
      }
      $form_state->setValue('file_path', $url);
      return;
    }

    $extension = $this->getFileExtensionSupported($migrationInstance);
    $validators = ['FileExtension' => ['extensions' => $extension]];
    $file = file_save_upload('source_file', $validators, 'private://', 0, FileExists::Replace);
    if ($file) {
      $form_state->setValue('file_path', $file->getFileUri());
    }
    elseif ($file === NULL && ($source = $this->getMigrationSource($migration_id))) {
      $form_state->setValue('file_path', $source);
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('Upload a valid source file.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source_type = $form_state->getValue('source_type');
    $migrations = $form_state->getValue('migrations');
    $migration_id = $migrations[$source_type];

    $type = $form_state->getValue('source_type') == 'url' ? 'urls' : 'path';
    if ($form_state->getValue('remove_source')) {
      $this->deleteMigrationSource($migration_id, $type);

      return;
    }
    else {
      if ($source_type !== 'url' || !empty($form_state->getValue('source_url'))) {
        $this->setMigrationSource($migration_id, $form_state->getValue('file_path'), $type);
      }
    }

    // Allows user to run migration from this page if desired.
    if ($form_state->getValue('run_migration')) {
      $this->logger->log('notice', 'Run migration %id, TYPE: %type', [
        '%id' => $migration_id,
        '%type' => $type,
      ]);

      if (!$this->checkDependencies($migration_id)) {
        return;
      }
      /** @var \Drupal\migrate\Plugin\Migration $migration */
      $migration = $this->pluginManagerMigration->createInstance($migration_id);
      // Leave running migrations alone.
      $status = $migration->getStatus();
      if ($status !== MigrationInterface::STATUS_IDLE) {
        $this->messenger()->addError($this->t('Migration @id is busy. Wait for it to finish before importing.', [
          '@id' => $migration_id,
        ]));
        return;
      }
      $source_key = $migration->getSourcePlugin()->getPluginId() === 'url' ? 'urls' : 'path';
      $options = [
        'configuration' => [
          'source' => [$source_key => $form_state->getValue('file_path')],
        ],
      ];
      if (in_array($migration_id, ['courts_node_job', 'appellate_node_job'], TRUE)) {
        $options['configuration']['source']['jcc_job_source_timestamp'] = $this->time->getCurrentTime();
      }
      // Force updates or not.
      if ($form_state->getValue('update_existing_records')) {
        $options['update'] = 1;
      }
      $executable = new MigrateBatchExecutable(
        $migration,
        new StubMigrationMessage(),
        $this->keyValue,
        $this->time,
        $this->getStringTranslation(),
        $this->pluginManagerMigration,
        $options,
      );
      $executable->batchImport();
    }
  }

  /**
   * Check the migration dependencies.
   *
   * @param string $migration_id
   *   The id of the migration to check.
   */
  public function checkDependencies($migration_id) {
    $migrationInstance = $this->pluginManagerMigration->createStubMigration($this->definitions[$migration_id]);
    $dependencies = $migrationInstance->getMigrationDependencies();
    $depends_on = implode(', ', $dependencies['required']);
    foreach ($dependencies['required'] as $id) {
      if (!$this->getMigrationSource($id)) {
        $this->messenger()->addError($this->t('@migration_id depends on: @ids', [
          '@migration_id' => $migration_id,
          '@ids' => $depends_on,
        ]));
        return FALSE;
      }
    }
    if ($depends_on) {
      $this->messenger()->addMessage($this->t('@migration_id depends on: @ids', [
        '@migration_id' => $migration_id,
        '@ids' => $depends_on,
      ]));
    }

    return TRUE;
  }

  /**
   * The allowed file extension for the migration.
   *
   * @param \Drupal\migrate\Plugin\Migration $migrationInstance
   *   The migration instance.
   *
   * @return string
   *   The file extension.
   */
  public function getFileExtensionSupported(Migration $migrationInstance) {
    $extension = 'csv';
    if ($migrationInstance->getSourcePlugin() instanceof CSV) {
      $extension = 'csv';
    }
    elseif ($migrationInstance->getSourcePlugin() instanceof Json) {
      $extension = 'json';
    }
    elseif ($migrationInstance->getSourcePlugin() instanceof Xml) {
      $extension = 'xml';
    }

    return $extension;
  }

  /**
   * Set the migration source via the State API.
   *
   * @param string $migration_id
   *   The migration id as indicated in the migration template.
   * @param string $uri
   *   The uri of the local file or external source.
   * @param string $type
   *   The type of source, url or path, as required by the migrate source
   *   plugin.
   */
  private function setMigrationSource($migration_id, $uri, $type = 'urls') {
    $sources = $this->state->get('jcc_migrate_sources', []);
    // If a source exists, delete the old one so and update it's file usage.
    if (!empty($sources[$migration_id])) {
      $this->deleteMigrationSource($migration_id, $sources[$migration_id]['type']);
    }
    // Set the file usage for local files.
    if ($type == 'path') {
      $this->setFileUsage($migration_id, $uri, 'add');
    }
    $sources[$migration_id] = [
      'type' => $type,
      'uri' => $uri,
    ];
    // Set the update state value array and clear necessary caches for
    // migration discovery.
    $this->state->set('jcc_migrate_sources', $sources);
    $this->configFactory()->reset('migrate_plus.migration.' . $migration_id);
    $this->pluginManagerMigration->clearCachedDefinitions();
    $this->messenger->addStatus($this->t('Migration source set for %s', ['%s' => $migration_id]));
    $this->logger->log(
      'notice',
      'Set migration source %id, TYPE: %type',
      ['%id' => $migration_id, '%type' => $type]
    );
  }

  /**
   * Load a migration source by migration id.
   *
   * @param string $migration_id
   *   The migration id as indicated in the migtation template.
   *
   * @return string|array|bool
   *   The source URI, list of URLs, or FALSE.
   */
  private function getMigrationSource($migration_id) {
    $sources = $this->state->get('jcc_migrate_sources', []);
    return $sources[$migration_id]['uri'] ?? $this->definitions[$migration_id]['source']['urls'] ?? $this->definitions[$migration_id]['source']['path'] ?? FALSE;
  }

  /**
   * Delete a migration source.
   *
   * @param string $migration_id
   *   The migration id as indicated in the migtation template.
   * @param string $type
   *   The type of source, url or path, as required by the migrate source
   *   plugin.
   */
  private function deleteMigrationSource($migration_id, $type = 'urls') {
    $sources = $this->state->get('jcc_migrate_sources', []);
    if (empty($sources[$migration_id])) {
      return;
    }
    if ($sources[$migration_id]['type'] == 'path') {
      $this->setFileUsage(
        $migration_id,
        $sources[$migration_id]['uri'],
        'delete'
      );
    }
    unset($sources[$migration_id]);
    $this->state->set('jcc_migrate_sources', $sources);
    $this->configFactory()->reset('migrate_plus.migration.' . $migration_id);
    $this->pluginManagerMigration->clearCachedDefinitions();
    $this->messenger->addStatus($this->t('Migration source deleted for %s.', ['%s' => $migration_id]));
    $this->logger->log('notice', 'Delete migration source %id, TYPE: %type', [
      '%id' => $migration_id,
      '%type' => $type,
    ]);
  }

  /**
   * Set file usage for source.
   *
   * @param string $migration_id
   *   The migration id as indicated in the migration template.
   * @param string $uri
   *   The uri of the local file or external source.
   * @param string $operation
   *   The file usage operation. add|delete.
   */
  private function setFileUsage($migration_id, $uri, $operation) {
    $files = $this->entityTypeManager
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    foreach ($files as $file) {
      $this->fileUsage->{$operation}(
        $file,
        'jcc_migrate_source_ui',
        'migration',
        $migration_id
      );
    }
  }

}
