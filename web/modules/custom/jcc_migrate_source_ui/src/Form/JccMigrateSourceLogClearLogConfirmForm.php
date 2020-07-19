<?php

namespace Drupal\jcc_migrate_source_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form before clearing out the logs.
 *
 * @internal
 */
class JccMigrateSourceLogClearLogConfirmForm extends ConfirmFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new JccMigrateSourceLogClearLogConfirmForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_migrate_source_ui_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the recent logs?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('jcc_migrate_source_ui.form');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['jcc_migrate_source_ui_overview_filter'] = [];
    $this->connection->truncate('jcc_migrate_source_log')->execute();
    $this->messenger()->addStatus($this->t('Database log cleared.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
