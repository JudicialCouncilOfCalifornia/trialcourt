<?php

/**
 * @file
 * Contains jcc_custom.views.inc.
 *
 * Make nodeaccess data available to views.
 * @todo Perhaps this should be contributed to the nodeaccess project.
 */

/**
 * Implements hook_views_data().
 */
function jcc_custom_views_data() {
  $data = [];

  // Base data.
  $data['nodeaccess']['table']['group'] = t('Node Access Grants');
  $data['nodeaccess']['table']['provider'] = 'nodeaccess';

  $data['nodeaccess']['table']['base'] = [
    'field' => 'nid',
    'title' => t('Node Access Grant'),
    'help' => t('Node access grant data from the nodeaccess module.'),
  ];

  // Fields.
  $data['nodeaccess']['nid'] = [
    'title' => t('Node Id'),
    'help' => t('The node id for the grant.'),
    'field' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      // Views name of the table to join to for the relationship.
      'base' => 'node_field_data',
      // Database field name in the other table to join on.
      'base field' => 'nid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t('Node'),
    ],
  ];

  $data['nodeaccess']['gid'] = [
    'title' => t('Grant (user/role) Id'),
    'help' => t('The Grant Id is the user or role id, depending on the grant realm.'),
    'field' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      // Views name of the table to join to for the relationship.
      'base' => 'users_field_data',
      // Database field name in the other table to join on.
      'base field' => 'uid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t('User'),
    ],
  ];

  $data['nodeaccess']['realm'] = [
    'title' => t('Realm'),
    'help' => t('The nodeaccess realm.'),
    'field' => [
      'id' => 'standard',
    ],
  ];

  $data['nodeaccess']['grant_view'] = [
    'title' => t('Grant View'),
    'help' => t('User can view.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];

  $data['nodeaccess']['grant_update'] = [
    'title' => t('Grant Update'),
    'help' => t('User can edit.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];

  $data['nodeaccess']['grant_delete'] = [
    'title' => t('Grant Delete'),
    'help' => t('User can delete.'),
    'field' => [
      'id' => 'numeric',
    ],
  ];

  return $data;
}
