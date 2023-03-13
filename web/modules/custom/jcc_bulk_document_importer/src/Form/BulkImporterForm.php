<?php

namespace Drupal\jcc_bulk_document_importer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Form to import media as document content type.
 *
 * @internal
 */
class BulkImporterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_bulk_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {   
    // Defaulting document type to oral argument.
    //$default_doc_type = Term::load('5');
    $form['#attached']['library'][] = 'jcc_bulk_document_importer/importer_styling';

    $form['document_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Documents'),
      '#upload_location' => 'public://documents',
      '#multiple' => TRUE,
      '#unlimited' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf zip doc docx xls xlsx ppt pptx'],
      ],
    ];

    $form['document_case_bundle'] = [
      '#title' => $this->t('Related Case'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'target_bundles' => ['case'],
      ],
    ];

    $form['document_type'] = [
      '#title' => $this->t('Document type'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      //'#default_value' => $default_doc_type,
      '#selection_settings' => [
        'target_bundles' => ['document_type'],
      ],
    ];

    $form['hearing_date'] = [
      '#type' => 'fieldset',
      '#title' => t('Hearing Date'),
      '#collapsible' => FALSE,
    ];

    $form['hearing_date']['document_daterange_start'] = [
      '#title' => $this->t('Start'),
      '#type' => 'date',
      '#attributes' => [
        'type' => 'date',
        'min' => '-12 months',
        'max' => '+12 months',
      ],
      '#default_value' => date("Y-m-d"),
      '#date_date_format' => 'Y/m/d',
    ];
    $form['hearing_date']['document_daterange_end'] = [
      '#title' => $this->t('End'),
      '#type' => 'date',
      '#attributes' => [
        'type' => 'date',
        'min' => '-12 months',
        'max' => '+12 months',
      ],
      '#default_value' => date("Y-m-d"),
      '#date_date_format' => 'Y/m/d',
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => t('Body'),
      '#format' => 'body',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create documents'),
    ];    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_files = $form_state->getValue('document_upload', 0);

    if ($form_files) {
      foreach ($form_state->getUserInput()['document_upload']['documents'] as $document) {
        if (isset($document) && !empty($document)) {
          $file = File::load($document['doc_id']);
          $file->setPermanent();
          $file->save();

          $media = Media::create([
            'bundle' => 'file',
            'uid' => \Drupal::currentUser()->id(),
            'field_media_file' => [
              'target_id' => $file->id(),
            ],
            'field_document_type' => $form_state->getValue('document_type', 0),
            'field_category' => $document['category'], 
          ]);
          $media->setName($document['custom_title'])->setPublished(TRUE)->save();

          // Hearing date 12:00a default time with GMT adjustment as needed.
          $gmt = date('P');
          $offset = substr(date('P'), 1, 2);
          switch ($gmt) {
            case str_contains($gmt, '-'):
              $adjust = 00 + $offset;
              break;

            case str_contains($gmt, '+'):
              $adjust = 24 - $offset;
              break;

            default:
              $adjust = 0;
          }
          $time = strval($adjust);
          $time = $time . ':00:00';
          if ($adjust < 10) {
            $time = '0' . $time;
          }

          // Save node
          // Create node object with attached file.
          $node = Node::create([
            'type' => 'document',
            'title' => $document['custom_title'],
            'field_verbose_title' => $document['custom_verbose_title'],
            'field_media' => [
              'target_id' => $media->id(),
              'alt' => $document['custom_title'],
              'title' => $document['custom_title'],
            ],
            'field_date_range' => [
              'value' => $form_state->getValue('document_daterange_start', 0) . 'T' . $time,
              'end_value' => $form_state->getValue('document_daterange_end', 0) . 'T' . $time,
            ],
            'field_date' => $document['filing_date'],
            'field_document_type' => $form_state->getValue('document_type', 0),                        
            'field_case' => $form_state->getValue('document_case_bundle', 0),
            'body' => $form_state->getValue('body', 0),
            'status' => 1,
          ]);
          $node->save();
        }
      }
    }
  }

}
