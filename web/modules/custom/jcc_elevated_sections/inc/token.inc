<?php

/**
 * @file
 * The jcc_elevated_sections include file for token functionality.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function jcc_elevated_sections_token_info() {
  $type = [
    'name' => t('JCC Elevated Sections'),
    'description' => t('JCC Elevated Section related Tokens.'),
  ];

  $jcc['node-section-id'] = [
    'name' => t("JCC Section ID"),
    'description' => t('ID for the JCC Section applied to the node'),
  ];

  $jcc['node-section-name'] = [
    'name' => t("JCC Section name"),
    'description' => t('Name for the JCC Section applied to the node'),
  ];

  return [
    'types' => ['jcc_sections' => $type],
    'tokens' => ['jcc_sections' => $jcc],
  ];
}

/**
 * Implements hook_tokens().
 */
function jcc_elevated_sections_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  $section_service = Drupal::service('jcc_elevated_sections.service');
  $type_manager = \Drupal::entityTypeManager();
  $node_manager = $type_manager->getStorage('node');

  if ($type == 'jcc_sections' && isset($data['node']) && !empty($data['node'])) {

    $replacements['[jcc_sections:node-section-id]'] = '';
    $replacements['[jcc_sections:node-section-name]'] = '';

    $node = $data['node'];
    $sid = $section_service->getSectionForEntity($node);
    $section = $section_service->getSectionInfo($sid);

    if ($section) {
      foreach ($tokens as $name => $token) {
        switch ($name) {
          case 'node-section-id':
            $replacements[$token] = ' | ' . $section->id();
            break;

          case 'node-section-name':
            $replacements[$token] = ' | ' . $section->label();
            break;
        }
      }
    }
  }

  return $replacements;
}
