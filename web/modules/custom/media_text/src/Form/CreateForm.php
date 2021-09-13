<?php

namespace Drupal\media_text\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Drupal\media_library\Form\AddFormBase;

/**
 * Create text source.
 */
class CreateForm extends AddFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_text';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $mapping = $this->getMediaType($form_state)->getFieldMap();
    if (!empty($mapping['text'])) {
      $form['container'] = [
        '#type' => 'container',
      ];
      $form['container'][$mapping['text']] = [
        '#type' => 'text_format',
        '#title' => $this->t('Text'),
        '#required' => TRUE,
      ];

      $form['container']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add'),
        '#button_type' => 'primary',
        '#submit' => ['::addButtonSubmit'],
        '#ajax' => [
          'callback' => '::updateFormCallback',
          'wrapper' => 'media-library-wrapper',
          'url' => Url::fromRoute('media_library.ui'),
          'options' => [
            'query' => $this->getMediaLibraryState($form_state)->all() + [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * Submit handler for the add button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function addButtonSubmit(array $form, FormStateInterface $form_state) {
    $mapping = $this->getMediaType($form_state)->getFieldMap();
    if (!empty($mapping['text'])) {
      $this->processInputValues([$form_state->getValue($mapping['text'])], $form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityFormElement(MediaInterface $media, array $form, FormStateInterface $form_state, $delta) {
    $element = parent::buildEntityFormElement($media, $form, $form_state, $delta);
    $source_field = $this->getSourceFieldName($media->bundle->entity);
    if (isset($element['fields'][$source_field])) {
      $element['fields'][$source_field]['widget'][0]['#process'][] = [
        static::class, 'hideExtraSourceFieldComponents',
      ];
    }
    return $element;
  }

}
