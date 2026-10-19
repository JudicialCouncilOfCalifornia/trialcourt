<?php

declare(strict_types=1);

namespace Drupal\jcc_ckeditor\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * Allows any class on selected tags.
 *
 * CKEditor 5 does not allow "class" in the Source Editing plugin for tags the
 * Style plugin could style. Content migrated from CKEditor 4 relies on classes
 * outside the Style dropdown, so this keeps them without dropping Style.
 */
class ArbitraryClasses extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['enabled' => FALSE, 'tags' => ''];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $tags = $this->getSupportedTags();
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow any class on the selected tags'),
      '#default_value' => $this->configuration['enabled'],
    ];
    $form['tags'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Tags'),
      '#options' => array_combine($tags, array_map(fn($tag) => "<$tag>", $tags)),
      '#default_value' => $this->getTags(),
      '#states' => [
        'visible' => [':input[name="editor[settings][plugins][jcc_ckeditor_classes][enabled]"]' => ['checked' => TRUE]],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('enabled', (bool) $form_state->getValue('enabled'));
    $form_state->setValue('tags', implode(' ', array_filter($form_state->getValue('tags'))));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled'] = $form_state->getValue('enabled');
    $this->configuration['tags'] = $form_state->getValue('tags');
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    return array_map(fn($tag) => "<$tag class>", array_values(array_intersect($this->getTags(), $this->getSupportedTags())));
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $restrictions = HTMLRestrictions::fromString(implode(' ', $this->getElementsSubset()));
    return [
      'htmlSupport' => [
        'allow' => $restrictions->toGeneralHtmlSupportConfig(),
      ],
    ];
  }

  /**
   * Gets the configured tags.
   *
   * Tags are stored as a space-separated string: the "requiresConfiguration"
   * condition compares configuration values with array_intersect(), which
   * only supports scalar values.
   *
   * @return string[]
   *   The tag names.
   */
  protected function getTags(): array {
    return array_values(array_filter(explode(' ', $this->configuration['tags'] ?? '')));
  }

  /**
   * Gets the tags this plugin can allow any class on.
   *
   * @return string[]
   *   The tag names.
   */
  protected function getSupportedTags(): array {
    return array_keys(HTMLRestrictions::fromString(implode(' ', $this->getPluginDefinition()->getElements()))->getAllowedElements());
  }

}
