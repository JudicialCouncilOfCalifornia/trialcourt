<?php

namespace Drupal\media_boxcast\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A widget for Courtyard Icons.
 *
 * @FieldWidget(
 *   id = "boxcast_content_widget",
 *   module = "media_boxcast",
 *   label = @Translation("Boxcast Content Widget"),
 *   field_types = {
 *     "boxcast_content",
 *   }
 * )
 */
class BoxcastContentWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Boxcast video url'),
      '#placeholder' => 'https://boxcast.tv/channel/[id]',
      '#required' => TRUE,
      '#default_value' => $items[$delta]->url,
    ];

    $element['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Title'),
      '#default_value' => $items[$delta]->show_title ?? 0,
    ];

    $element['show_description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Description'),
      '#default_value' => $items[$delta]->show_description ?? 0,
    ];

    $element['show_highlights'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Hightlights'),
      '#default_value' => $items[$delta]->show_highlights ?? 0,
    ];

    $element['show_related'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Related'),
      '#default_value' => $items[$delta]->show_related ?? 0,
    ];

    $element['default_video'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Video'),
      '#default_value' => $items[$delta]->default_video ?? 'next',
    ];

    $element['market'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Market'),
      '#default_value' => $items[$delta]->market,
    ];

    $element['show_countdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Countdown'),
      '#default_value' => $items[$delta]->show_countdown ?? 0,
    ];

    $element['show_documents'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Documents'),
      '#default_value' => $items[$delta]->show_documents ?? 0,
    ];

    $element['show_index'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Index'),
      '#default_value' => $items[$delta]->show_index ?? 0,
    ];

    $element['show_donations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Donations'),
      '#default_value' => $items[$delta]->show_donations ?? 0,
    ];

    $element['layout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Layout'),
      '#default_value' => $items[$delta]->layout,
    ];

    $element['#element_validate'] = [
      [$this, 'validate'],
    ];

    return $element;
  }

  /**
   * Validates the URL.
   *
   * @param array $element
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function validate(array $element, FormStateInterface $form_state) {
    $url = $element['url']['#value'];
    if ($url) {
      $valid_url = 'https://boxcast.tv/channel/';
      // URL must start with this string.
      if (strpos($url, $valid_url) !== 0) {
        $form_state->setError($element['url'], $this->t('Valid urls must be in the format %valid[id]', ['%valid' => $valid_url]));
      }
    }
  }

}
