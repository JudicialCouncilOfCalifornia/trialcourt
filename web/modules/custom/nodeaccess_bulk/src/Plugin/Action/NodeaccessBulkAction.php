<?php

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Some description.
 *
 * @Action(
 *   id = "noddeaccess_vbo_action_base",
 *   label = @Translation("Nodeaccess view bulk action"),
 *   type = "",
 *   confirm = TRUE,
 *   requirements = {
 *     "_permission" = "Access content",
 *     "_custom_access" = TRUE,
 *   },
 * )
 */

class NodeaccessBulkAction extends ViewsBulkOperationsActionBase
{
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // Do some processing..

    // Don't return anything for a default completion message, otherwise return translatable markup.
    return $this->t('Some result');
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
