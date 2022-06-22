<?php

namespace Drupal\jcc_autocomplete_duplicates;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;

/**
 * Autocomplete spotlight search customizations.
 */
class JccEntityAutocompleteMatcher extends EntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];

    $options = [
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 50);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
          $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity);

          $key = "{$label} ({$entity_id})";

          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\\s\\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);

          // If node is tagged newslink, prefix title with type.
          if ($entity->getEntityType()->id() == 'node' && isset($entity->field_news_type)) {
            $news_type = $entity->get('field_news_type')->target_id;

            if ($news_type == 134) {
              $label = '(NEWSLINK) ' . $label;
            }
          }

          $matches[] = [
            'value' => $key,
            'label' => $label,
          ];
        }
      }
    }

    return $matches;
  }

}
