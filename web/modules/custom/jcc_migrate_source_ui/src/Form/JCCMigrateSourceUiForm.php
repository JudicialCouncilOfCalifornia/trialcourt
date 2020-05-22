<?php

namespace Drupal\jcc_migrate_source_ui\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * MigrateSourceUiForm constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager_migration
   *   The migration plugin manager.
   */
  public function __construct(MigrationPluginManager $plugin_manager_migration) {
    $this->pluginManagerMigration = $plugin_manager_migration;
    $this->definitions = $this->pluginManagerMigration->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migration')
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
    $options = [];
    $migrationLabels = [];
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
    $type_options = [
      'csv' => 'csv',
      'json' => 'json',
      'xml' => 'xml',
      'url' => 'url'
    ];

    $form['source_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select source type'),
      '#options' => $type_options,
    ];
    foreach ($type_options as $type) {
      $opts = $options[$type];
      asort($opts);
      $form['migrations'][$type] = [
        '#type' => 'select',
        '#title' => $this->t(strtoupper($type) . ' Sources'),
        '#options' => $opts,
        '#states' => [
          'visible' => [
            ':input[name="source_type"]' => ['value' => $type],
          ],
        ],
      ];
    }
    $form['source_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload the source file'),
      '#states' => [
        'invisible' => [
          ':input[name="source_type"]' => ['value' => 'url'],
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
    ];
    $form['update_existing_records'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing records'),
      '#default_value' => 1,
    ];
    $form['import'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $source_type = $form_state->getValue('source_type');
    $migration_id = $form_state->getValue($source_type);
    $definition = $this->pluginManagerMigration->getDefinition($migration_id);
    $migrationInstance = $this->pluginManagerMigration->createStubMigration($definition);
    $extension = $this->getFileExtensionSupported($migrationInstance);

    $validators = ['file_validate_extensions' => [$extension]];
    $file = file_save_upload('source_file', $validators, FALSE, 0, FileSystemInterface::EXISTS_REPLACE);
    $url = $form_state->getValue('source_url');

    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        $form_state->setValue('file_path', $file->getFileUri());
      }
      // File upload failed.
      else {
        $form_state->setErrorByName('source_file', $this->t('The file could not be uploaded.'));
      }
    }
    elseif (!empty($url)) {
      $valid = UrlHelper::isValid($url, TRUE);
      if ($valid) {
        $form_state->setValue('file_path', $url);
      }
      else {
        $form_state->setErrorByName('source_url', $this->t('Please enter a valid URL with https://.'));
      }
    }
    else {
      $form_state->setErrorByName('source_file', $this->t('You have to upload a source file or enter a URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source_type = $form_state->getValue('source_type');
    $migration_id = $form_state->getValue($source_type);
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->pluginManagerMigration->createInstance($migration_id);

    // Reset status.
    $status = $migration->getStatus();
    if ($status !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
      $this->messenger()->addWarning($this->t('Migration @id reset to Idle', ['@id' => $migration_id]));
    }

    $options = [
      'file_path' => $form_state->getValue('file_path'),
    ];
    // Force updates or not.
    if ($form_state->getValue('update_existing_records')) {
      $options['update'] = TRUE;
    }

    $executable = new MigrateBatchExecutable($migration, new StubMigrationMessage(), $options);
    $executable->batchImport();
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

}
