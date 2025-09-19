<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the jcc staff entity type.
 */
class JccStaffListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new JccStaffListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $form = \Drupal::formBuilder()->getForm('Drupal\jcc_jrn_contact\Form\JccStaffFilterForm');
    $table = parent::render();
    if (isset($table['table']['#header']['operations'])) {
      unset($table['table']['#header']['operations']);
    }
    if (isset($table['table']['#rows']) && is_array($table['table']['#rows'])) {
      foreach ($table['table']['#rows'] as &$row) {
        if (isset($row['operations'])) {
          unset($row['operations']);
        }
      }
    }
    $total = $this->getStorage()
      ->getQuery()
      ->count()
      ->execute();
    $build['summary']['#markup'] = $this->t('Total staff: @total', ['@total' => $total]);

    $headers = [];
    if (!empty($table['table']['#header'])) {
      foreach ($table['table']['#header'] as $key => $info) {
        if (is_array($info) && isset($info['data'])) {
          $headers[$key] = (string) $info['data'];
        }
        else {
          $headers[$key] = (string) $info;
        }
      }
    }

    foreach ($table['table']['#rows'] as $row) {
      $table_rows[] = [
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'department' => $row['department'] ?? '',
        'phone' => $row['contact'] ?? '',
        'location' => $row['location'] ?? '',
        'temporary' => $row['temporary'] ?? '',
      ];
    }

    if (!empty($table['table']['#rows'])) {
      foreach ($table['table']['#rows'] as &$row) {
        foreach ($row as &$cell) {
          if (empty($cell)) {
            $cell = ['data' => 'none'];
          }
        }
      }
    }

    return [
      '#theme' => 'custom_staff_view',
      '#exposed' => $form,
      '#rows' => $table_rows,
      '#headers' => $headers,
      '#table' => $table,
      '#summary' => 'Directory: Judicial Council Staff',
      '#attached' => [
        'library' => [
          'jcc_jrn_contact/custom_staff_view',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['first_name'] = [
      'data' => $this->t('First Name'),
      'field' => 't.first_name',
    ];
    $header['last_name'] = [
      'data' => $this->t('Last Name'),
      'field' => 't.last_name',
      'sort' => 'asc',
    ];
    $header['department'] = [
      'data' => $this->t('Department'),
      'field' => 't.department',
    ];
    $header['phone'] = [
      'data' => $this->t('Phone and Email'),
      'field' => 't.phone',
      'class' => ['contact'],
    ];
    $header['location'] = [
      'data' => $this->t('Location'),
      'field' => 't.location',
    ];
    $header['temporary'] = [
      'data' => $this->t('Temp Hire'),
      'field' => 't.temporary',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**  @var \Drupal\jcc_jrn_contact\JccStaffInterface $entity */
    $row['first_name'] = $entity->get('first_name')->value;
    $row['last_name'] = $entity->get('last_name')->value;
    $row['department'] = $entity->get('department')->entity?->label();
    $phone_raw = preg_replace('/\D+/', '', $entity->get('phone')->value);
    $phone = '';
    if (strlen($phone_raw) === 10) {
      $phone = sprintf('(%s) %s-%s',
      substr($phone_raw, 0, 3),
      substr($phone_raw, 3, 3),
      substr($phone_raw, 6)
      );
    }
    $email_value = $entity->get('email')->value;
    $email_value = $email_value ? strtolower($email_value) : '';
    $email = $email_value ? '<a href="mailto:' . $email_value . '" class="test">' . $email_value . '</a>' : '';
    $row['contact'] = [
      'data' => [
        '#markup' => $phone . ($phone && $email ? '<br>' : '') . $email,
        'class' => ['contact'],
      ],
    ];
    $row['location'] = $entity->get('location')->value;
    $row['temporary'] = $entity->get('temporary')->value ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = \Drupal::entityQuery($this->entityTypeId);
    $request = \Drupal::request();

    if ($value = $request->get('keyword')) {
      $or_group = $query->orConditionGroup()
        ->condition('first_name', $value, 'CONTAINS')
        ->condition('last_name', $value, 'CONTAINS')
        ->condition('email', $value, 'CONTAINS');
      $query->condition($or_group);
    }

    $temporary = $request->get('temporary');
    if ($temporary !== NULL && $temporary !== '' && $temporary !== 'both') {
      $query->condition('temporary', (int) $temporary);
    }

    if ($department = $request->get('department')) {
      $query->condition('department', $department);
    }

    $order = $request->get('order');
    if ($order) {
      $sort = $request->get('sort');
      foreach ($this->buildHeader() as $name => $field) {
        if (is_array($field) && $field['data'] == $order) {
          $header = [
            $name => $field + [
              'specifier' => $name,
              'sort' => $sort ?? $field['sort'] ?? 'asc',
            ],
          ];
        }
      }
      if (!empty($header)) {
        $query->tableSort($header);
      }
    }
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
