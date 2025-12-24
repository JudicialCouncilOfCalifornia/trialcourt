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
 * Provides a list controller for the jcc ajp entity type.
 */
class JccAjpListBuilder extends EntityListBuilder {

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
   * Constructs a new JccAjpListBuilder object.
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
  public function render() {
    $form = \Drupal::formBuilder()->getForm('Drupal\jcc_jrn_contact\Form\JccAjpFilterForm');
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
    $result_count = isset($table['table']['#rows']) ? count($table['table']['#rows']) : 0;
    return [
      '#theme' => 'custom_ajp_view',
      '#form' => $form,
      '#table' => $table,
      '#summary' => $result_count,
      '#attached' => [
        'library' => [
          'jcc_jrn_contact/custom_ajp_view',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = [
      'data' => $this->t('Name'),
      'field' => 't.name',
      'sort' => 'asc',
    ];
    $header['fy_service_days'] = [
      'data' => $this->t('FY Service Days'),
      'field' => 't.fy_service_days',
    ];
    $header['life_service_days'] = [
      'data' => $this->t('Life Service Days'),
      'field' => 't.life_service_days',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**  @var \Drupal\jcc_jrn_contact\JccStaffInterface $entity */
    $row['name'] = $entity->get('name')->value;
    $row['fy_service_days'] = $entity->get('fy_service_days')->value;
    $row['life_service_days'] = $entity->get('life_service_days')->value;
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
        ->condition('name', $value, 'CONTAINS')
        ->condition('email', $value, 'CONTAINS');
      $query->condition($or_group);
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
      if ($header) {
        $query->tableSort($header);
      }
    }
    else {
      $query->sort('name', 'asc');
    }

    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
