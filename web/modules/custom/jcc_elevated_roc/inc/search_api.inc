<?php

/**
 * @file
 */

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;

/**
 * Implements hook_search_api_index_items_alter().
 */
function jcc_elevated_roc_search_api_index_items_alter(IndexInterface $index, array &$items): void {
//  kint($index);
//  kint($items);
}

/**
 * Implements hook_search_api_query_alter().
 */
function jcc_elevated_roc_search_api_query_alter(QueryInterface $query) {
  // Do not run for queries with a certain tag.
  //  if ($query->hasTag('example_tag')) {
  //    return;
  //  }
  // Otherwise, exclude the node with ID 10 from the search results.
  //  $fields = $query->getIndex()->getFields();
  //  foreach ($query->getIndex()->getDatasources() as $datasource_id => $datasource) {
  //    if ($datasource->getEntityTypeId() == 'processed_content') {
  //      kint($fields);
  //    }
  //  }
}

/**
 * Alter a search query's result set.
 *
 * The hook is invoked after all enabled processors have postprocessed the
 * results.
 *
 * @param \Drupal\search_api\Query\ResultSetInterface $results
 *   The search results to alter.
 *
 * @deprecated in search_api:8.x-1.14 and is removed from search_api:2.0.0.
 *   Please use the "search_api.processing_results" event instead.
 *
 * @see https://www.drupal.org/node/3059866
 */
function jcc_elevated_roc_search_api_results_alter(ResultSetInterface &$results): void {
  // $results->setExtraData('example_hook_invoked', microtime(TRUE));
  $items = $results->getResultItems();

  foreach ($items as $key => $item) {
    $entity = $item->getOriginalObject()->getEntity();
    if ($entity->getEntityTypeId() == 'processed_content') {

      // Remove items that are not a section (aka level 3 items).
      $level = $entity->get('field_section_level')->first()->getValue();
      if ($level['value'] !== '3') {
        unset($items[$key]);
      }

      // Check if the processed content entity is actually set to the node that
      // it claims is its parent.
      $parent_node = $entity->get('field_parent_node')->referencedEntities();
      if (!$parent_node) {
        unset($items[$key]);
      }
      else {
        $pcis = [];

        // Get the processed content entities that are actually on the parent
        // node.
        $processed_content_items = $parent_node[0]->get('field_processed_document_content')->getValue();
        foreach ($processed_content_items as $pci) {
          $pcis[] = $pci['target_id'];
        }

        // If the processed content is not actually in the list of live entities
        // attached to the parent node, it means that it is an orphaned item and
        // should not be allowed in the output.
        if (!in_array($entity->id(), $pcis)) {
          unset($items[$key]);
        }
      }

      // Fix excerpt.
      // kint($item->getExcerpt());
    }

  }

  $results->setResultCount(count($items));
  $results->setResultItems($items);
}
