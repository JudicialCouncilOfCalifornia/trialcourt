<?php

namespace Drupal\jcc_elevated_custom\Plugin\Linkit\Matcher;

use Drupal\linkit\Plugin\Linkit\Matcher\NodeMatcher;
use Drupal\linkit\Suggestion\EntitySuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides specific linkit matchers for the node entity type.
 *
 * @Matcher(
 *   id = "entity:node",
 *   label = @Translation("Content"),
 *   target_entity = "node",
 *   provider = "jcc_elevated_custom"
 * )
 */
class ElevatedNodeMatcher extends NodeMatcher {
  /**
   * {@inheritdoc}
   */
  public function execute($string) {
    $suggestions = new SuggestionCollection();
    $query = $this->buildEntityQuery($string);
    $query->accessCheck(TRUE);
    $query_result = $query->execute();
    $url_results = $this->findEntityIdByUrl($string);
    $result = array_merge($query_result, $url_results);

    // If no results, return an empty suggestion collection.
    if (empty($result)) {
      return $suggestions;
    }

    $entities = $this->entityTypeManager->getStorage($this->targetType)->loadMultiple($result);

    foreach ($entities as $entity) {
      // Check the access against the defined entity access handler.
      /** @var \Drupal\Core\Access\AccessResultInterface $access */
      $access = $entity->access('view', $this->currentUser, TRUE);

      if (!$access->isAllowed()) {
        continue;
      }
      $entity = $this->entityRepository->getTranslationFromContext($entity);

      // Get jcc_section taxonomy term ID loaded.
      $term_name = '';
      if ($entity->hasField('jcc_section')) {
        $term_id = $entity->jcc_section->getValue();
        if (isset($term_id[0])) {
          $term = Term::load($term_id[0]['target_id']);
          if ($term_id[0]['target_id']) {
            $term_name = ' ' . $term->getName();
          }
        }
      }

      $suggestion = new EntitySuggestion();
      $suggestion->setLabel($this->buildLabel($entity))
        ->setGroup($this->buildGroup($entity))
        ->setDescription($this->buildDescription($entity) . $term_name)
        ->setEntityUuid($entity->uuid())
        ->setEntityTypeId($entity->getEntityTypeId())
        ->setSubstitutionId($this->configuration['substitution_type'])
        ->setPath($this->buildPath($entity));
      $suggestions->addSuggestion($suggestion);
    }

    return $suggestions;
  }

}
