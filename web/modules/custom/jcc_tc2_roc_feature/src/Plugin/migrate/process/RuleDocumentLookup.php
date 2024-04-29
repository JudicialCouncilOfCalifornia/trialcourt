<?php

namespace Drupal\jcc_tc2_roc_feature\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Create media entity.
 *
 * @MigrateProcessPlugin(
 *   id = "jcc_rule_document_lookup"
 * )
 */
class RuleDocumentLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $fields = ['destid1', 'destid2'];
    $paragraphs = \Drupal::database()->select('migrate_map_' . $this->configuration['migration_id'], 't')
      ->fields('t', $fields)
      ->condition('t.sourceid2', $source)
      ->execute();

    $items = [];
    foreach ($paragraphs as $row) {
      $items[] = [
        'target_id' => $row->destid1,
        'target_revision_id' => $row->destid2,
      ];

      if ($row->destid1) {
        $paragraph = Paragraph::load($row->destid1);
        if ($paragraph) {
          $settings = [
            'layout' => 'roc_section_listing',
            'config' => [
              'label' => '',
            ],
            'parent_uuid' => '',
            'region' => 'content',
          ];
          $paragraph->setBehaviorSettings('layout_paragraphs', $settings);
          $paragraph->save();
        }
      }
    }

    return $items;
  }

}
