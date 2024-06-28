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
 *   id = "jcc_rule_index_lookup"
 * )
 */
class JccRuleIndexLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $fields = ['destid1', 'destid2', 'sourceid3'];
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

          // Try to get the UUID of the parent paragraph that the section should
          // go into.
          $parent_paragraph_uuids = [];
          if ($row->sourceid3) {
            $parent_fields = ['destid1', 'destid2'];
            $parent_paragraphs = \Drupal::database()->select('migrate_map_' . $this->configuration['migration_id'], 't')
              ->fields('t', $parent_fields)
              ->condition('t.sourceid1', $row->sourceid3)
              ->execute();
            foreach ($parent_paragraphs as $parent_row) {
              $parent_paragraph = Paragraph::load($parent_row->destid1);
              if ($parent_paragraph) {
                $parent_paragraph_uuids[] = $parent_paragraph->uuid();
              }
            }
          }

          $settings = [
            'layout' => 'roc_section_listing',
            'config' => [
              'label' => '',
            ],
            'parent_uuid' => !empty($parent_paragraph_uuids[0]) ? $parent_paragraph_uuids[0] : '',
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
