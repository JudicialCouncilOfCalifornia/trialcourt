<?php

namespace Drupal\jcc_migrate_source_ui\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Returns responses for jcc_migrate_source_ui routes.
 */
class JccMigrateSourceLogController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a JccMigrateSourceLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap() {
    return [
      RfcLogLevel::INFO => 'jcc-migrate-source-ui-info',
    ];
  }

  /**
   * Displays a listing of database log messages.
   *
   * Messages are truncated at 56 chars.
   * Full-length messages can be viewed on the message details page.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @see Drupal\jcc_migrate_source_ui\Form\JccMigrateSourceLogClearLogConfirmForm
   * @see Drupal\jcc_migrate_source_ui\Controller\JccMigrateSourceLogController::eventDetails()
   */
  public function overview() {

    $filter = $this->buildFilterQuery();
    $rows = [];

    $classes = static::getLogLevelClassMap();

    $this->moduleHandler->loadInclude('jcc_migrate_source_ui', 'admin.inc');

    $build['jcc_migrate_source_ui_filter_form'] = $this->formBuilder->getForm('Drupal\jcc_migrate_source_ui\Form\JccMigrateSourceLogFilterForm');

    $header = [
      // Icon column.
      '',
      [
        'data' => $this->t('Type'),
        'field' => 'l.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'l.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
      [
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Operations'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $query = $this->database->select('jcc_migrate_source_log', 'l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('l', [
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ]);
    $query->leftJoin('users_field_data', 'ufd', 'l.uid = ufd.uid');

    if (!empty($filter['lhere'])) {
      $query->where($filter['lhere'], $filter['args']);
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $jcc_migrate_source_ui) {
      $message = $this->formatMessage($jcc_migrate_source_ui);
      if ($message && isset($jcc_migrate_source_ui->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        // The link generator will escape any unsafe HTML entities in the final
        // text.
        $message = Link::fromTextAndUrl($log_text, new Url('jcc_migrate_source_ui.event', ['event_id' => $jcc_migrate_source_ui->wid], [
          'attributes' => [
            // Provide a title for the link for useful hover hints. The
            // Attribute object will escape any unsafe HTML entities in the
            // final text.
            'title' => $title,
          ],
        ]))->toString();
      }
      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($jcc_migrate_source_ui->uid),
      ];
      $rows[] = [
        'data' => [
          // Cells.
          ['class' => ['icon']],
          $this->t($jcc_migrate_source_ui->type),
          $this->dateFormatter->format($jcc_migrate_source_ui->timestamp, 'short'),
          $message,
          ['data' => $username],
          ['data' => ['#markup' => $jcc_migrate_source_ui->link]],
        ],
        // Attributes for table row.
        'class' => [Html::getClass('jcc_migrate_source_ui-' . $jcc_migrate_source_ui->type), $classes[$jcc_migrate_source_ui->severity]],
      ];
    }

    $build['jcc_migrate_source_ui_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-jcc-migrate-source-ui', 'class' => ['admin-jcc-migrate-source-ui']],
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['jcc_migrate_source_ui/jcc_migrate_source_ui.jcc_migrate_source_log'],
      ],
    ];
    $build['jcc_migrate_source_ui_pager'] = ['#type' => 'pager'];

    return $build;

  }

  /**
   * Displays details about a specific database log message.
   *
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by \Drupal\Core\Render\RendererInterface::render().
   */
  public function eventDetails($event_id) {
    $build = [];
    if ($jcc_migrate_source_ui = $this->database->query('SELECT l.*, u.uid FROM {jcc_migrate_source_log} l LEFT JOIN {users} u ON u.uid = l.uid WHERE l.wid = :id', [':id' => $event_id])->fetchObject()) {
      $severity = RfcLogLevel::getLevels();
      $message = $this->formatMessage($jcc_migrate_source_ui);
      $username = [
        '#theme' => 'username',
        '#account' => $jcc_migrate_source_ui->uid ? $this->userStorage->load($jcc_migrate_source_ui->uid) : User::getAnonymousUser(),
      ];
      $rows = [
        [
          ['data' => $this->t('Type'), 'header' => TRUE],
          $this->t($jcc_migrate_source_ui->type),
        ],
        [
          ['data' => $this->t('Date'), 'header' => TRUE],
          $this->dateFormatter->format($jcc_migrate_source_ui->timestamp, 'long'),
        ],
        [
          ['data' => $this->t('User'), 'header' => TRUE],
          ['data' => $username],
        ],
        [
          ['data' => $this->t('Location'), 'header' => TRUE],
          $this->createLink($jcc_migrate_source_ui->location),
        ],
        [
          ['data' => $this->t('Referrer'), 'header' => TRUE],
          $this->createLink($jcc_migrate_source_ui->referer),
        ],
        [
          ['data' => $this->t('Message'), 'header' => TRUE],
          $message,
        ],
        [
          ['data' => $this->t('Severity'), 'header' => TRUE],
          $severity[$jcc_migrate_source_ui->severity],
        ],
        [
          ['data' => $this->t('Hostname'), 'header' => TRUE],
          $jcc_migrate_source_ui->hostname,
        ],
        [
          ['data' => $this->t('Operations'), 'header' => TRUE],
          ['data' => ['#markup' => $jcc_migrate_source_ui->link]],
        ],
      ];
      $build['jcc_migrate_source_ui_table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => ['class' => ['jcc_migrate_source_ui-event']],
        '#attached' => [
          'library' => ['jcc_migrate_source_ui/drupal.jcc_migrate_source_ui'],
        ],
      ];
    }

    return $build;
  }

  /**
   * Builds a query for database log administration filters based on session.
   *
   * @return array|null
   *   An associative array with keys 'lhere' and 'args' or NULL if there were
   *   no filters set.
   */
  protected function buildFilterQuery() {
    if (empty($_SESSION['jcc_migrate_source_ui_overview_filter'])) {
      return;
    }

    $this->moduleHandler->loadInclude('jcc_migrate_source_ui', 'admin.inc');

    $filters = jcc_migrate_source_ui_filters();

    // Build query.
    $where = $args = [];
    foreach ($_SESSION['jcc_migrate_source_ui_overview_filter'] as $key => $filter) {
      $filter_where = [];
      foreach ($filter as $value) {
        $filter_where[] = $filters[$key]['lhere'];
        $args[] = $value;
      }
      if (!empty($filter_where)) {
        $where[] = '(' . implode(' OR ', $filter_where) . ')';
      }
    }
    $where = !empty($where) ? implode(' AND ', $where) : '';

    return [
      'lhere' => $where,
      'args' => $args,
    ];
  }

  /**
   * Formats a database log message.
   *
   * @param object $row
   *   The record from the jcc_migrate_source_log table. The object properties
   *   are: wid, uid, severity, type, timestamp, message, variables, link,
   *   name.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   The formatted log message or FALSE if the message or variables properties
   *   are not set.
   */
  public function formatMessage($row) {
    // Check for required properties.
    if (isset($row->message, $row->variables)) {
      $variables = @unserialize($row->variables);
      // Messages without variables or user specified text.
      if ($variables === NULL) {
        $message = Xss::filterAdmin($row->message);
      }
      elseif (!is_array($variables)) {
        $message = $this->t('Log data is corrupted and cannot be unserialized: @message', ['@message' => Xss::filterAdmin($row->message)]);
      }
      // Message to translate with injected variables.
      else {
        // Ensure backtrace strings are properly formatted.
        if (isset($variables['@backtrace_string'])) {
          $variables['@backtrace_string'] = new FormattableMarkup(
            '<pre class="backtrace">@backtrace_string</pre>', $variables
          );
        }
        $message = $this->t(Xss::filterAdmin($row->message), $variables);
      }
    }
    else {
      $message = FALSE;
    }
    return $message;
  }

  /**
   * Creates a Link object if the provided URI is valid.
   *
   * @param string|null $uri
   *   The uri string to convert into link if valid.
   *
   * @return \Drupal\Core\Link|string|null
   *   Return a Link object if the uri can be converted as a link. In case of
   *   empty uri or invalid, fallback to the provided $uri.
   */
  protected function createLink($uri) {
    if (UrlHelper::isValid($uri, TRUE)) {
      return new Link($uri, Url::fromUri($uri));
    }
    return $uri;
  }

  /**
   * Shows the most frequent log messages of a given event type.
   *
   * Messages are not truncated on this page because events detailed herein do
   * not have links to a detailed view.
   *
   * @param string $type
   *   Type of database log events to display (e.g., 'search').
   *
   * @return array
   *   A build array in the format expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function topLogMessages($type) {
    $header = [
      ['data' => $this->t('Count'), 'field' => 'count', 'sort' => 'desc'],
      ['data' => $this->t('Message'), 'field' => 'message'],
    ];

    $count_query = $this->database->select('jcc_migrate_source_log');
    $count_query->addExpression('COUNT(DISTINCT(message))');
    $count_query->condition('type', $type);

    $query = $this->database->select('jcc_migrate_source_log', 'l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->addExpression('COUNT(wid)', 'count');
    $query = $query
      ->fields('l', ['message', 'variables'])
      ->condition('l.type', $type)
      ->groupBy('message')
      ->groupBy('variables')
      ->limit(30)
      ->orderByHeader($header);
    $query->setCountQuery($count_query);
    $result = $query->execute();

    $rows = [];
    foreach ($result as $jcc_migrate_source_ui) {
      if ($message = $this->formatMessage($jcc_migrate_source_ui)) {
        $rows[] = [$jcc_migrate_source_ui->count, $message];
      }
    }

    $build['jcc_migrate_source_ui_top_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['jcc_migrate_source_ui/drupal.jcc_migrate_source_ui'],
      ],
    ];
    $build['jcc_migrate_source_ui_top_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
