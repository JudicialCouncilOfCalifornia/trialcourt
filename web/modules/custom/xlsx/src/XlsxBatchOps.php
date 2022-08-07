<?php

namespace Drupal\xlsx;

use Drupal\webform\Entity\WebformSubmission;

/**
 * Xlsx Batch API.
 *
 * @ingroup xlsx
 */
class XlsxBatchOps {

  /**
   * Purge previously imported data.
   */
  public static function purge($ids, &$context) {
    $storage = \Drupal::entityTypeManager()->getStorage('xlsx_data');
    $entities = $storage->loadMultiple($ids);
    if ($entities) {
      $first_entity = current($entities);
      $imported_storage = \Drupal::entityTypeManager()->getStorage($first_entity->get('entity_type_id')->value, $first_entity->get('bundle')->value);
      $imported_ids = [];
      foreach ($entities as $entity) {
        $context['results'][] = $entity->id();
        $imported_ids[] = $entity->get('entity_id')->value;
      }
      if (!empty($imported_ids)) {
        $context['message'] = t('Deleting previously imported records. About 100 records at a time.');
        $imported_entities = $imported_storage->loadMultiple($imported_ids);
        $imported_storage->delete($imported_entities);
      }
      $storage->delete($entities);
    }
  }

  /**
   * Import mapped data.
   */
  public static function import($xlsx, $destination, $worksheet_index, $sheet_name, $data, &$context) {
    $mapping = $xlsx->getMapping();
    $entity_fields = [];
    $data_array = array_values($data);
    $mapped_fields = [];
    $webform_submission = [];
    $entity_mapping = $mapping['entity_mapping'];

    if ($destination['type'] == 'webform_submission') {
      $webform_submission['webform_id'] = $destination['bundle'];
      foreach ($mapping['field_mapping'][$worksheet_index] as $webform_field => $info) {
        $value = !empty($data_array[0][$info['column']]) ? $data_array[0][$info['column']] : '';
        if (!empty($value)) {
          $mapped_fields[$webform_field] = trim($value);
          $webform_submission['data'][$webform_field] = $value;
        }
      }
      $entity = WebformSubmission::create($webform_submission);

      // We need this loop to apply XLSX Cell plugin tranformation.
      // Second loop will have access to the complete entity with all the mapping fields populated.
      foreach ($mapping['field_mapping'][$worksheet_index] as $webform_field => $info) {
        $value = !empty($mapped_fields[$webform_field]) ? $mapped_fields[$webform_field] : '';
        // Load cell transformer plugin.
        $xlsx_cell = !empty($info['cell_plugin']) ? $info['cell_plugin'] : 'as_is';
        if ($plugin = \Drupal::service('plugin.manager.xlsx_cell')->createInstance($xlsx_cell)) {
          if ($plugin_value = $plugin->import($entity, $webform_field, $value, $mapped_fields)) {
            $webform_submission['data'][$webform_field] = $plugin_value;
          }
        }
      }
    }
    else {
      $entity = \Drupal::entityTypeManager()->getStorage($destination['type'])->create(['type' => $destination['bundle']]);
      foreach ($mapping['field_mapping'][$worksheet_index] as $drupal_field => $info) {
        $value = !empty($data_array[0][$info['column']]) ? $data_array[0][$info['column']] : '';
        if (!empty($value)) {
          $mapped_fields[$drupal_field] = trim($value);
          $entity->set($drupal_field, $value);
        }
      }

      // We need this loop to apply XLSX Cell plugin tranformation.
      // Second loop will have access to the complete entity with all the mapping fields populated.
      foreach ($mapping['field_mapping'][$worksheet_index] as $drupal_field => $info) {
        $value = !empty($mapped_fields[$drupal_field]) ? $mapped_fields[$drupal_field] : '';
        // Load cell transformer plugin.
        $xlsx_cell = !empty($info['cell_plugin']) ? $info['cell_plugin'] : 'as_is';
        if ($plugin = \Drupal::service('plugin.manager.xlsx_cell')->createInstance($xlsx_cell)) {
          if ($plugin_value = $plugin->import($entity, $drupal_field, $value, $mapped_fields)) {
            $entity->set($drupal_field, $plugin_value);
          }
        }
      }
    }

    if (!empty($mapped_fields)) {
      $entity->save();
      $xlsx_data = \Drupal::entityTypeManager()->getStorage('xlsx_data')->create(['type' => 'xlsx_data']);
      $xlsx_data->set('mapping_id', $xlsx->id());
      $xlsx_data->set('worksheet_index', $worksheet_index);
      $xlsx_data->set('entity_id', $entity->id());
      $xlsx_data->set('entity_type_id', $destination['type']);
      $xlsx_data->set('bundle', $destination['bundle']);
      $xlsx_data->set('hash', md5(serialize($mapped_fields)));
      $xlsx_data->save();
      $context['message'] = t('Importing %name ...', ['%name' => $entity->label()]);
      $context['results'][] = $entity->id();
    }
    else {
      $context['message'] = t('Skipping empty row ...');
    }
  }

  /**
   * BatchAPI complete callback for data purge.
   */
  public static function completePurgeCallback($success, $results, $operations) {
    self::complete('1 record deleted.', '@count records deleted.', $success, $results);
  }

  /**
   * BatchAPI complete callback for data import.
   */
  public static function completeImportCallback($success, $results, $operations) {
    self::complete('1 row processed.', '@count rows processed.', $success, $results);
  }

  /**
   * Helper method for complete callbacks.
   */
  protected static function complete($singular, $plural, $success, $results) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), $singular, $plural);
      \Drupal::logger('xlsx')->notice($message);
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message, 'status', TRUE);
  }

}
