<?php
namespace Drupal\jcc_migrate_pdf\Services;

class JccMigratePdfService
{
    const CONTENT_TYPE = "common";
    public function getContentTypeQuery()
    {
        $content_type_query = \Drupal::entityQuery('node')
            ->condition('type', self::CONTENT_TYPE)
            ->execute();
        return $content_type_query;
    }
}
