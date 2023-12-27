<?php

namespace Drupal\jcc_tc_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Provides a 'node_handling' process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "node_handling"
 * )
 */
class NodeHandling extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    \Drupal::logger('my_module')->notice('NodeHandling process called.');
    // Get the source values.
    $location = $value[0];
    $startdate = $value[1];
    $enddate = $value[2];
    $subject=$value[3];
    $url=$value[4];
    $accesscode=$value[5];

    // $row->setSourceProperty('StartDate',($startdate != null)?$startdate:NULL);
    // $row->setSourceProperty('EndDate',($enddate != null)?$enddate:NULL);    
    // dd($value);
  
    \Drupal::logger('my_module')->notice('Location: ' . $location);
    \Drupal::logger('my_module')->notice('Start Date: ' . $startdate);
    \Drupal::logger('my_module')->notice('End Date: ' . $enddate);
  
    // Check if a node with the same location already exists.
    $existing_nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'events', 'title' => $location]);
    
    if (!empty($existing_nodes)) {
      // If a node exists, update it by appending the startdate and enddate.
      foreach ($existing_nodes as $node) {
        $pm=str_contains($subject,"PM");
        if($pm){
         
        dump($subject);
        $node->field_pmstart[] = $startdate;
        $node->field_pmend[] = $enddate;
        $node->field_pmurl[]=$url;
        $node->field_pmurl->title = 'Open MS Teams Hearing';
        $node->field_pmacces[]=$accesscode;
        $node->save();
        }
      }

      // Skip the current row.
      throw new MigrateSkipRowException();
    }
  }

  /**
   * Deletes duplicate nodes based on location.
   */
  public function delete($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Load nodes with the same location.
    $location = $value[0];
    $existing_nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => 'events', 'title' => $location]);
    
    // Delete duplicate nodes.
    foreach ($existing_nodes as $node) {
      $node->delete();
    }
  }

}
