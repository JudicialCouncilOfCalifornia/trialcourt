<?php

/**
 * @file
 * Views integration for the JCC PDF Upload Validation Checker module.
 */

function jcc_pdf_upload_validation_checker_views_data_alter(array &$data) {
  $data['file_managed']['jcc_pdf_audit_link'] = [
    'title' => t('PDF Audit link'),
    'help' => t('Displays a link to run PDF Audit for the file in this row.'),
    'field' => [
      'id' => 'jcc_pdf_audit_link',
    ],
  ];
}
