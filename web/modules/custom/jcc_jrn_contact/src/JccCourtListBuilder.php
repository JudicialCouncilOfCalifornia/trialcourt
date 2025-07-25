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
 * Provides a list controller for the jcc court entity type.
 */
class JccCourtListBuilder extends EntityListBuilder {

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
   * Constructs a new JccCourtListBuilder object.
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
    $build['form'] = \Drupal::formBuilder()->getForm('Drupal\jcc_jrn_contact\Form\JccCourtFilterForm');
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total courts: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['court_type'] = [
      'data' => $this->t('Type'),
      'field' => 't.court_type',
    ];
    $header['name_1'] = [
      'data' => $this->t('Name'),
      'field' => 't.name_1',
      'sort' => 'asc',
    ];
    $header['name_2'] = [
      'data' => $this->t('Name 2'),
      'field' => 't.name_2',
    ];
    $header['name_3'] = [
      'data' => $this->t('Name 3'),
      'field' => 't.name_3',
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /**  @var \Drupal\jcc_jrn_contact\JccStaffInterface $entity */
    $row['court_type'] = $entity->get('court_type')->entity?->label();
    $row['name_1'] = $entity->get('name_1')->value;
    $row['name_2'] = $entity->get('name_2')->value;
    $row['name_3'] = $entity->get('name_3')->value;
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
        ->condition('name_1', $value, 'CONTAINS')
        ->condition('name_2', $value, 'CONTAINS')
        ->condition('name_3', $value, 'CONTAINS');
      $query->condition($or_group);
    }

    if ($department = $request->get('court_type')) {
      $query->condition('court_type', $department);
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
      $query->sort('name_1', 'asc');
    }

    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
