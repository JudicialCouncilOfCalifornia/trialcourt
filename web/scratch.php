<?php

use Symfony\Component\HttpFoundation\Response;

$bundle_fields = \Drupal::getContainer()->get('entity_field.manager')->getFieldDefinitions('media', 'file');
$field_definition = $bundle_fields['field_media_file'];

if (filefield_paths_batch_update($field_definition)) {
  $response = batch_process();
  if ($response instanceof Response) {
    $response->send();
  }
}
