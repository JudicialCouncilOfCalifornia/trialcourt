<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\jcc_pdf_upload_validation_checker\Form\BypassAuditLogFilterForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin report for PDF bypass audit events.
 */
final class BypassAuditLogController extends ControllerBase {

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * Date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs a BypassAuditLogController object.
   */
  public function __construct(Connection $database, DateFormatterInterface $dateFormatter) {
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * Access callback for the bypass audit dashboard route.
   */
  public function access(AccountInterface $account): AccessResult {
    $config = \Drupal::config('jcc_pdf_upload_validation_checker.settings');
    $bypass_enabled = (bool) $config->get('pdf_validation_bypass');
    $can_manage_bypass = _jcc_pdf_upload_validation_checker_can_manage_bypass_flag($account);

    return AccessResult::allowedIf($bypass_enabled && $can_manage_bypass)
      // Evaluate dynamically so menu visibility tracks config toggles instantly.
      ->setCacheMaxAge(0);
  }

  /**
   * Builds an admin table of bypass changes.
   */
  public function content(): array {
    if (!$this->database->schema()->tableExists('jcc_pdf_bypass_audit_log')) {
      return [
        '#markup' => $this->t('The bypass audit log table is not available yet. Run database updates first.'),
      ];
    }

    $request = \Drupal::request();
    $media_filter = trim((string) $request->query->get('media', ''));
    $user_filter = trim((string) $request->query->get('user', ''));
    $from_filter = trim((string) $request->query->get('from', ''));
    $to_filter = trim((string) $request->query->get('to', ''));

    $query = $this->database->select('jcc_pdf_bypass_audit_log', 'l')
      ->fields('l', [
        'id',
        'fid',
        'uid',
        'username',
        'file_name',
        'action',
        'reason_label',
        'created',
      ])
      ->orderBy('created', 'DESC')
      ->orderBy('id', 'DESC')
      ->extend('Drupal\\Core\\Database\\Query\\PagerSelectExtender')
      ->limit(50);

    if ($user_filter !== '') {
      if (ctype_digit($user_filter)) {
        $or = $query->orConditionGroup()
          ->condition('uid', (int) $user_filter)
          ->condition('username', '%' . $this->database->escapeLike($user_filter) . '%', 'LIKE');
        $query->condition($or);
      }
      else {
        $query->condition('username', '%' . $this->database->escapeLike($user_filter) . '%', 'LIKE');
      }
    }

    if ($from_filter !== '') {
      $from_timestamp = strtotime($from_filter . ' 00:00:00');
      if ($from_timestamp !== FALSE) {
        $query->condition('created', (int) $from_timestamp, '>=');
      }
    }

    if ($to_filter !== '') {
      $to_timestamp = strtotime($to_filter . ' 23:59:59');
      if ($to_timestamp !== FALSE) {
        $query->condition('created', (int) $to_timestamp, '<=');
      }
    }

    if ($media_filter !== '') {
      $media_lookup = $this->database->select('file_usage', 'fu')
        ->fields('fu', ['fid'])
        ->distinct()
        ->condition('fu.type', 'media')
        ->condition('fu.count', 0, '>');

      $media_lookup->innerJoin('media_field_data', 'm', 'm.mid = fu.id');

      if (ctype_digit($media_filter)) {
        $media_or = $media_lookup->orConditionGroup()
          ->condition('m.mid', (int) $media_filter)
          ->condition('m.name', '%' . $this->database->escapeLike($media_filter) . '%', 'LIKE');
        $media_lookup->condition($media_or);
      }
      else {
        $media_lookup->condition('m.name', '%' . $this->database->escapeLike($media_filter) . '%', 'LIKE');
      }

      $query->condition('fid', $media_lookup, 'IN');
    }

    $rows = [];
    foreach ($query->execute() as $record) {
      $rows[] = [
        $this->dateFormatter->format((int) $record->created, 'short'),
        $this->buildUserLabel((int) $record->uid, (string) $record->username),
        Html::escape((string) $record->file_name) . ' (fid ' . (int) $record->fid . ')',
        ['data' => $this->buildMediaEditLink((int) $record->fid)],
        $this->formatAction((string) $record->action),
        Html::escape((string) $record->reason_label),
      ];
    }

    return [
      'filters' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['views-exposed-form', 'jcc-bypass-audit-filters-wrapper'],
        ],
        'form' => $this->formBuilder()->getForm(BypassAuditLogFilterForm::class),
      ],
      'description' => [
        '#markup' => '<p>' . $this->t('Tracks who changed PDF bypass settings, when the change occurred, and the selected bypass reason.') . '</p>',
      ],
      'table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Date'),
          $this->t('User'),
          $this->t('File'),
          $this->t('Media'),
          $this->t('Action'),
          $this->t('Reason'),
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No bypass events have been logged yet.'),
      ],
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

  /**
   * Returns a readable action label.
   */
  private function formatAction(string $action): string {
    switch ($action) {
      case 'enabled':
        return (string) $this->t('Bypass enabled');

      case 'disabled':
        return (string) $this->t('Bypass disabled');

      case 'reason_updated':
        return (string) $this->t('Reason updated');

      default:
        return (string) $this->t('Bypass updated');
    }
  }

  /**
   * Builds user label in the format USER_EMAIL (ID).
   */
  private function buildUserLabel(int $uid, string $fallbackUsername): string {
    $uid_label = (string) $uid;
    if ($uid <= 0) {
      return Html::escape($fallbackUsername !== '' ? $fallbackUsername : 'anonymous') . ' (' . $uid_label . ')';
    }

    $account = $this->entityTypeManager()->getStorage('user')->load($uid);
    if ($account && method_exists($account, 'getEmail')) {
      $email = trim((string) $account->getEmail());
      if ($email !== '') {
        return Html::escape($email) . ' (' . $uid_label . ')';
      }
    }

    return Html::escape($fallbackUsername !== '' ? $fallbackUsername : 'user') . ' (' . $uid_label . ')';
  }

  /**
   * Builds an edit link to the first media entity referencing a file.
   */
  private function buildMediaEditLink(int $fid): array {
    if ($fid <= 0) {
      return ['#markup' => '-'];
    }

    $media_query = $this->database->select('file_usage', 'fu')
      ->fields('fu', ['id'])
      ->condition('fu.fid', $fid)
      ->condition('fu.type', 'media')
      ->condition('fu.count', 0, '>')
      ->orderBy('fu.id', 'DESC')
      ->range(0, 1);
    $media_query->innerJoin('media_field_data', 'm', 'm.mid = fu.id');
    $media_query->fields('m', ['name']);

    $media = $media_query->execute()->fetchObject();

    if (!$media || empty($media->id)) {
      return ['#markup' => '-'];
    }

    $mid = (int) $media->id;
    $title = trim((string) ($media->name ?? ''));
    if ($title === '') {
      $title = (string) $this->t('Media');
    }

    $label = $title . ' (ID = ' . $mid . ')';

    $url = Url::fromRoute('entity.media.edit_form', ['media' => $mid]);

    return Link::fromTextAndUrl($label, $url)->toRenderable();
  }

}
