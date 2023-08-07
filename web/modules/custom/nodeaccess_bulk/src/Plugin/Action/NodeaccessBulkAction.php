<?php

namespace Drupal\noddeaccess_bulk\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Some description.
 *
 * @Action(
 *   id = "noddeaccess_vbo_action_base",
 *   label = @Translation("Node grant access"),
 *   type = "node",
 *   confirm = TRUE
 * )
 */

class NodeaccessBulkAction extends ViewsBulkOperationsActionBase
{
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
 
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // If certain fields are updated, access should be checked against them as well.
    // @see Drupal\Core\Field\FieldUpdateActionBase::access().
    return $object->access('update', $account, $return_as_object);
  }

}
