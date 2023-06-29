<?php
namespace Drupal\jcc_custom\Plugin\EntityReferenceSelection;

use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;

/**
* Entity reference selection.
*
* @EntityReferenceSelection(
*   id = "jcc_custom:node",
*   label = @Translation("node"),
*   group = "jcc_custom",
* )
*/
class EntityAutocompleteSelection extends NodeSelection {
  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    return parent::getReferenceableEntities($match, $match_operator, 25);
  }
  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $query->LeftJoin('media');
    $query->Condition('status', 1);
    return $query;
  }

}
