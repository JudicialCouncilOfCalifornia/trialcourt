<?php

/**
 * @file
 * Provide views data for jcc_migrate_source_ui.module.
 */

/**
 * Implements hook_views_data().
 */
function jcc_migrate_source_ui_views_data() {
  $data = [];

  $data['jcc_migrate_source_log']['table']['group'] = t('JCC Migrate Source Log');
  $data['jcc_migrate_source_log']['table']['wizard_id'] = 'jcc_migrate_source_log';

  $data['jcc_migrate_source_log']['table']['base'] = [
    'field' => 'wid',
    'title' => t('Log entries'),
    'help' => t('Contains a list of log entries.'),
  ];

  $data['jcc_migrate_source_log']['wid'] = [
    'title' => t('WID'),
    'help' => t('Unique jcc_migrate_source_log event ID.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['uid'] = [
    'title' => t('UID'),
    'help' => t('The user ID of the user on which the log entry was written.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('User'),
      'help' => t('The user on which the log entry as written.'),
      'base' => 'users_field_data',
      'base field' => 'uid',
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['type'] = [
    'title' => t('Type'),
    'help' => t('The type of the log entry, for example "user" or "page not found".'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'jcc_migrate_source_ui_types',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['message'] = [
    'title' => t('Message'),
    'help' => t('The actual message of the log entry.'),
    'field' => [
      'id' => 'jcc_migrate_source_ui_message',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['variables'] = [
    'title' => t('Variables'),
    'help' => t('The variables of the log entry in a serialized format.'),
    'field' => [
      'id' => 'serialized',
      'click sortable' => FALSE,
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['severity'] = [
    'title' => t('Severity level'),
    'help' => t('The severity level of the event; ranges from 0 (Emergency) to 7 (Debug).'),
    'field' => [
      'id' => 'machine_name',
      'options callback' => 'Drupal\jcc_migrate_source_ui\Controller\JccMigrateSourceLogController::getLogLevelClassMap',
    ],
    'filter' => [
      'id' => 'in_operator',
      'options callback' => 'Drupal\Core\Logger\RfcLogLevel::getLevels',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['link'] = [
    'title' => t('Operations'),
    'help' => t('Operation links for the event.'),
    'field' => [
      'id' => 'jcc_migrate_source_ui_operations',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['location'] = [
    'title' => t('Location'),
    'help' => t('URL of the origin of the event.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['referer'] = [
    'title' => t('Referer'),
    'help' => t('URL of the previous page.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['hostname'] = [
    'title' => t('Hostname'),
    'help' => t('Hostname of the user who triggered the event.'),
    'field' => [
      'id' => 'standard',
    ],
    'argument' => [
      'id' => 'string',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  $data['jcc_migrate_source_log']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('Date when the event occurred.'),
    'field' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  return $data;
}
