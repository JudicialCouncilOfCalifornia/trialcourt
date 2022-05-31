<?php

namespace Drupal\jcc_case\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Form to import opinion media as document content type.
 *
 * @internal
 */
class OpinionsImporterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_opinions_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'jcc_case/case_styling';

    $form['document_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Opinions'),
      '#upload_location' => 'public://documents',
      '#multiple' => TRUE,
      '#unlimited' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf zip doc docx xls xlsx ppt pptx'],
      ],
      '#description' => $this->t('[Helpful instructions TBD to explain how to use this page].'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Opinions'),
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
            'field_document_type' => $document['document_type'],
          ]);
          $media->setName('Opinion: ' . $document['case_number'] . ' - ' . $document['case_name'])->setPublished(TRUE)->save();

          // Create new case term.
          $new_case = Term::create([
            'vid' => 'case',
            'name' => $document['case_number'],
          ]);
          $new_case->enforceIsNew();
          $new_case->save();

          // Related cases.
          $tags = explode(', ', $document['related_cases']);
          if (count($tags) > 0) {
            $related_cases = [];
            foreach ($tags as $tag) {
              $tag = substr($tag, 0, 8);
              $term = __jcc_get_taxonomy_by_name($tag, 'case');
              array_push($related_cases, $term);
            }
          }

          // Save node
          // Create node object with attached file.
          $node = Node::create([
            'type' => 'document',
            'title' => $document['case_name'],
            'field_media' => [
              'target_id' => $media->id(),
              'alt' => $document['case_number'] . ': ' . $document['case_name'],
              'title' => $document['case_number'] . ': ' . $document['case_name'],
            ],
            'field_document_type' => $document['document_type'],
            'field_district' => $document['district'],
            'field_case' => __jcc_get_taxonomy_by_name($document['case_number'], 'case'),
            'field_date' => $document['post_date'],
            'field_toggle' => $document['review_granted'],
            'field_related_cases' => $related_cases,
            'status' => 1,
          ]);
          $node->save();
        }
      }
    }
  }

}
