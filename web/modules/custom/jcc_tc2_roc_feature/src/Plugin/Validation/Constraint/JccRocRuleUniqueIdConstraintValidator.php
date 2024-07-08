<?php

namespace Drupal\jcc_tc2_roc_feature\Plugin\Validation\Constraint;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the JccRocRuleUniqueIdConstraint constraint.
 */
class JccRocRuleUniqueIdConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new UniqueContentTitleValidator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    $value = !empty($items->getValue()) ? $items->getValue()[0]['value'] : '';
    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->getType();
    $bundle_field = 'type';
    $id_field = 'nid';

    if ($bundle == 'roc_rule') {
      $unique_field_name = 'field_roc_rule_id';
      if ($this->uniqueValidation($unique_field_name, $value, $entity_type, $bundle_field, $bundle, $id_field)) {
        $this->context->addViolation($constraint->nonUniqueRuleId, [
          '%value' => $value,
        ]);
      }
    }

    if ($bundle == 'rule_document') {
      $unique_field_name = 'field_roc_rule_index_id';
      if ($this->uniqueValidation($unique_field_name, $value, $entity_type, $bundle_field, $bundle, $id_field)) {
        $this->context->addViolation($constraint->nonUniqueRuleIndexId, [
          '%value' => $value,
        ]);
      }
    }
  }

  /**
   * Unique validation.
   *
   * @param string $field_name
   *   The name of the field.
   * @param string $value
   *   Value of the field to check for uniqueness.
   * @param string $entity_type
   *   Id of the Entity Type.
   * @param string $bundle_field
   *   Field of the Entity type.
   * @param string $bundle
   *   Bundle of the entity.
   * @param string $id_field
   *   Id field of the entity.
   *
   * @return bool
   *   Whether the entity is unique or not
   */
  private function uniqueValidation($field_name, $value, $entity_type, $bundle_field, $bundle, $id_field): bool {
    if ($entity_type && $value && $field_name && $bundle_field && $bundle) {
      $query = $this->entityTypeManager->getStorage($entity_type)->getQuery()
        ->condition($field_name, $value)
        ->condition($bundle_field, $bundle)
        ->range(0, 1);
      // Exclude the current entity.
      if (!empty($id = $this->context->getRoot()->getEntity()->id())) {
        $query->condition($id_field, $id, '!=');
      }
      $entities = $query->accessCheck(FALSE)->execute();
      if (!empty($entities)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
