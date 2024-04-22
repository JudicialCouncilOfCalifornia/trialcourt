<?php

namespace Drupal\jcc_roc\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted rule id is not already used by another node.
 *
 * @Constraint(
 *   id = "JccRocRuleUniqueIdConstraint",
 *   label = @Translation("Jcc: Roc unique id", context = "Validation"),
 *   type = "string"
 * )
 */
class JccRocRuleUniqueIdConstraint extends Constraint {

  /**
   * The message that will be shown if the subsection value is not unique.
   *
   * @var string
   */
  public string $nonUniqueRuleSubsectionId = 'The subsection id must be unique. %value is already in use.';

  /**
   * The message that will be shown if the document id value is not unique.
   *
   * @var string
   */

  public string $nonUniqueRuleDocumentId = 'The document id must be unique. %value is already in use.';

}
