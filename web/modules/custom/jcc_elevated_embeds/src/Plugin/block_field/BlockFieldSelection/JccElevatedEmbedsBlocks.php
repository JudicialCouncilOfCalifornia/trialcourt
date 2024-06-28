<?php

namespace Drupal\jcc_elevated_embeds\Plugin\block_field\BlockFieldSelection;

use Drupal\block_field\BlockFieldSelectionBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a 'categories' BlockFieldSection.
 *
 * @BlockFieldSelection(
 *   id = "blocks",
 *   label = @Translation("JCC Elevated: Blocks"),
 * )
 */
class JccElevatedEmbedsBlocks extends BlockFieldSelectionBase {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin_ids' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $link = Link::createFromRoute('here', 'jcc_elevated_embeds.content_stream_embed_override_settings', ['theme' => 'jcc_elevated'])->toString();
    $form['blocks'] = [
      '#type' => 'details',
      '#title' => $this->t('Blocks'),
      '#description' => $this->t('To avoid having to manage config, these options are managed @here via state settings.', ['@here' => $link]),
      '#open' => TRUE,
    ];

    $items = [];
    foreach ($this->getOverrideBlockList(TRUE) as $name) {
      if (!empty($name)) {
        $items[] = $name;
      }
    }

    $form['blocks']['list'] = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#title' => '',
      '#items' => $items,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableBlockDefinitions() {

    // Get all available blocks.
    $block_field_manager = \Drupal::service('block_field.manager');
    $definitions = $block_field_manager->getBlockDefinitions();

    // Get our custom override allowed blocks.
    $values = $this->getOverrideBlockList(TRUE);

    // Filter out disallowed blocks.
    return array_intersect_key($definitions, $values);
  }

  /**
   * {@inheritdoc}
   */
  private function getOverrideBlockList($flat = FALSE) {
    return jcc_elevated_embeds_jcc_allowed_content_stream_blocks_override($flat);
  }

}
