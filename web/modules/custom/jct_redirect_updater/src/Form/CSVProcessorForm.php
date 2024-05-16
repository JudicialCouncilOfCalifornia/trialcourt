<?php

namespace Drupal\jct_redirect_updater\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

class CSVProcessorForm extends FormBase {

    public function getFormId() {
        return 'csv_processor_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['csv_file'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Upload CSV File'),
            '#upload_location' => 'public://csv_files/',
            '#required' => TRUE,
            '#upload_validators' => [
                'file_validate_extensions' => ['csv']
            ],
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Process CSV'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $file_id = reset($form_state->getValue('csv_file'));
        $file = File::load($file_id);
        \Drupal::messenger()->addMessage(t($file->getFileUri()));
        $file->setPermanent();
        $file->save();
        $batch = [
            'title' => $this->t('Processing CSV...'),
            'operations' => [
                ['\Drupal\jct_redirect_updater\CSVProcessorBatch::processItems', [$file->getFileUri()]],
            ],
            'finished' => '\Drupal\jct_redirect_updater\CSVProcessorBatch::finished',
        ];
        batch_set($batch);
    }
}
