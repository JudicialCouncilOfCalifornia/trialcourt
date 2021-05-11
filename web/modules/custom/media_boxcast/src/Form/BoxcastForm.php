<?php

namespace Drupal\media_boxcast\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_boxcast\Plugin\media\Source\BoxcastStream;
use Drupal\media_library\Form\AddFormBase;

/**
 * Creates a form to create media entities from oEmbed URLs.
 *
 * @internal
 *   Form classes are internal.
 */
class BoxcastForm extends AddFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_boxcast_stream';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMediaType(FormStateInterface $form_state) {
    if ($this->mediaType) {
      return $this->mediaType;
    }

    $media_type = parent::getMediaType($form_state);

    if (!$media_type->getSource() instanceof BoxcastStream) {
      throw new \InvalidArgumentException('Can only add media types which use a BoxcastStream source plugin.');
    }
    return $media_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    // Add a container to group the input elements for styling purposes.
    $form['container'] = [
      '#type' => 'container',
    ];

    $form['container']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('Add @type via URL', [
        '@type' => $this->getMediaType($form_state)->label(),
      ]),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'https://boxcast.tv/channel/[id]',
      ],
    ];

    $form['container']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
      '#validate' => ['::validateUrl'],
      '#submit' => ['::addButtonSubmit'],
      // @todo Move validation in https://www.drupal.org/node/2988215
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-library-wrapper',
        // Add a fixed URL to post the form since AJAX forms are automatically
        // posted to <current> instead of $form['#action'].
        // @todo Remove when https://www.drupal.org/project/drupal/issues/2504115
        //   is fixed.
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Validates the URL.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validateUrl(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('url');
    if ($url) {
      $valid_url = 'https://boxcast.tv/channel/';
      // URL must start with this string.
      if (strpos($url, $valid_url) !== 0) {
        $form_state->setErrorByName('url', $this->t('Valid urls must be in the format %valid[id]', ['%valid' => $valid_url]));
      }
    }
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
    $this->processInputValues([$form_state->getValue('url')], $form, $form_state);
  }

}
