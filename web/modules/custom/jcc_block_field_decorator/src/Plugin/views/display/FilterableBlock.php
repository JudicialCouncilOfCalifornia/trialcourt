<?php

namespace Drupal\jcc_block_field_decorator\Plugin\views\display;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;

/**
 * A block plugin that allows exposed filters to be configured.
 *
 * @ingroup views_display_plugins
 *
 * @ViewsDisplay(
 *   id = "filterable_block",
 *   title = @Translation("Filterable Block"),
 *   help = @Translation("Display the view as a block with more configuration options."),
 *   theme = "views_view",
 *   register_theme = FALSE,
 *   uses_hook_block = TRUE,
 *   contextual_links_locations = {"block"},
 *   admin = @Translation("Filterable Block")
 * )
 *
 * @see \Drupal\views\Plugin\block\block\ViewsBlock
 * @see \Drupal\views\Plugin\Derivative\ViewsBlock
 */
class FilterableBlock extends Block {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['allow']['contains']['exposed_filter'] = ['default' => 'exposed_filter'];
    $options['allow']['contains']['exposed_sort'] = ['default' => 'exposed_sort'];
    $options['allow']['contains']['contextual_filter'] = ['default' => 'contextual_filter'];
    return $options;
  }

  /**
   * Returns plugin-specific settings for the block.
   *
   * @param array $settings
   *   The settings of the block.
   *
   * @return array
   *   An array of block-specific settings to override the defaults provided in
   *   \Drupal\views\Plugin\Block\ViewsBlock::defaultConfiguration().
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::defaultConfiguration()
   */
  public function blockSettings(array $settings) {
    $settings = parent::blockSettings($settings);
    $settings['exposed_filter'] = [];

    // These items must be exposed to be overriden.
    foreach (['filter', 'sort'] as $type) {
      $items = $this->view->display_handler->getHandlers($type);
      foreach ($items as $id => $item) {
        if (!$item->options['exposed']) {
          continue;
        }
        $settings['exposed_' . $type][$id]['enabled'] = FALSE;
        $settings['exposed_' . $type][$id]['value'] = '';
      }
    }

    // All contextual filters can be overridden.
    $contextual_filters = $this->view->display_handler->getHandlers('argument');
    foreach ($contextual_filters as $id => $contextual_filter) {
      $settings['contextual_filter'][$id]['enabled'] = FALSE;
      $settings['contextual_filter'][$id]['value'] = '';
    }
    return $settings;
  }

  /**
   * Provide the summary for page options in the views UI.
   *
   * This output is returned as an array.
   */
  public function optionsSummary(&$categories, &$options) {
    parent::optionsSummary($categories, $options);

    // @todo: make this more general and not reliant on the fact that
    // items_per_page is currently the only allowed block config setting.
    $filtered_allow = array_filter($this->getOption('allow'));
    $allowed = [];
    if (isset($filtered_allow['items_per_page'])) {
      $allowed[] = $this->t('Items per page');
    }
    if (isset($filtered_allow['exposed_filter'])) {
      $allowed[] = $this->t('Exposed filters');
    }
    if (isset($filtered_allow['exposed_sort'])) {
      $allowed[] = $this->t('Exposed sorts');
    }
    if (isset($filtered_allow['contextual_filter'])) {
      $allowed[] = $this->t('Contextual filters');
    }
    $options['allow'] = array(
      'category' => 'block',
      'title' => $this->t('Allow settings'),
      'value' => empty($allowed) ? $this->t('None') : implode(', ', $allowed),
    );
  }

  /**
   * Adds the configuration form elements specific to this views block plugin.
   *
   * This method allows block instances to override the views exposed filters.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array $form
   *   The renderable form array representing the entire configuration form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockForm()
   */
  public function blockForm(ViewsBlock $block, array &$form, FormStateInterface $form_state) {

    parent::blockForm($block, $form, $form_state);
    $allow_settings = array_filter($this->getOption('allow'));
    $block_configuration = $block->getConfiguration();
    // Field type is always text unless we load terms as select options.
    $field_type = 'text';

    foreach ($allow_settings as $type => $enabled) {
      $options = [];
      if (empty($enabled)) {
        continue;
      }
      switch ($type) {
        case 'exposed_filter':
          $items = $this->view->display_handler->getHandlers('filter');
          $item_label = $this->t('Exposed filter');
          break;

        case 'exposed_sort':
          $items = $this->view->display_handler->getHandlers('sort');
          $item_label = $this->t('Exposed sort');
          break;

        case 'contextual_filter':
          $items = $this->view->display_handler->getHandlers('argument');
          $item_label = $this->t('Contextual filter');
          break;

        default:
          continue;

      }
      // Create block form elements for exposed items.
      foreach ($items as $id => $item) {
        if ($type != 'contextual_filter' && !$item->options['exposed']) {
          continue;
        }
        // Set values for vocabulary fields.
        if (!empty($item->options['vid'])) {
          $field_type = 'select';
          $vid = $item->options['vid'];
          $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
          $parent = "";
          $options = $this->treeToOptions($tree);
        }
        // Set field title values.
        if (!empty($item->options['expose']['label'])) {
          $title = $this->t('@type: @id (%label)', [
            '@type' => $item_label,
            '@id' => $id,
            '%label' => $item->options['expose']['label']
          ]);
        }
        else {
          $title = $this->t('@type: @id', ['@id' => $id, '@type' => $item_label]);
        }
        // Create form fields.
        $form['override'][$type][$id]['enabled'] = [
          '#type' => 'checkbox',
          '#title' => $title,
          '#default_value' => $block_configuration[$type][$id]['enabled'],
        ];
        $form['override'][$type][$id]['value'] = [
          '#title' => $this->t('Value for %label', ['%label' => $id]),
          '#type' => $field_type,
          '#default_value' => $block_configuration[$type][$id]['value'],
          '#states' => [
            'visible' => [
              [
                ':input[name="settings[override][' . $type . '][' . $id . '][enabled]"]' => array('checked' => TRUE),
              ],
            ],
          ],
        ];
        if ($field_type == 'select') {
          $form['override'][$type][$id]['value']['#options'] = $options;
        }
      }
    }

    return $form;
  }

  /**
   * Handles form submission for the views block configuration form.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The ViewsBlock plugin.
   * @param array $form
   *   The form definition array for the full block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\views\Plugin\Block\ViewsBlock::blockSubmit()
   */
  public function blockSubmit(ViewsBlock $block, $form, FormStateInterface $form_state) {
    parent::blockSubmit($block, $form, $form_state);

    $overides = $form_state->getValue(['override']);
    $config = $block->getConfiguration();

    foreach ($overides as $type => $values) {
      foreach ($values as $id => $settings) {
        if ($settings['enabled']) {
          $config[$type][$id] = [
            'enabled' => TRUE,
            'value' => $settings['value'],
          ];
        }
        else {
          if (isset($config[$type][$id])) {
            unset($config[$type][$id]);
          }
        }
        $form_state->unsetValue(['override', $type, $id]);
      }
    }

    $block->setConfiguration($config);
  }

  /**
   * Allows to change the display settings right before executing the block.
   *
   * @param \Drupal\views\Plugin\Block\ViewsBlock $block
   *   The block plugin for views displays.
   */
  public function preBlockBuild(ViewsBlock $block) {
    parent::preBlockBuild($block);

    $config = $block->getConfiguration();
    $exposedInput = [];
    foreach (['exposed_filter', 'exposed_sort'] as $type) {
      if (!empty($config[$type])) {
        foreach ($config[$type] as $id => $values) {
          if ($values['enabled']) {
            $exposedInput[$id] = $values['value'];
          }
        }
      }
    }
    $this->view->setExposedInput($exposedInput);

    if (!empty($config['contextual_filter'])) {
      foreach ($config['contextual_filter'] as $id => $values) {
        if ($values['enabled']) {
          $args[] = $values['value'];
        }
      }
      $this->view->setArguments($args);
    }
  }

  /**
   * Block views use exposed widgets only if AJAX is set.
   */
  public function usesExposed() {
    if ($this->ajaxEnabled()) {
      return parent::usesExposed();
    }
    return FALSE;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    if ($form_state->get('section') == 'allow') {
      $form['allow']['#options']['exposed_filter'] = $this->t('Exposed filters');
    }
  }

  /**
   * Converts a Taxonomy Term Tree to form options.
   *
   * @param  array $tree
   *   The term tree from a given vocabulary.
   *
   * @return array
   *   An array suitable for use as form options.
   */
  public function treeToOptions($tree) {
    $options = ['All' => '- Any -'];
    // dpm($tree);
    foreach ($tree as $term) {
      if ($term->depth) {
        $has_depth = TRUE;
      }
    }
    foreach ($tree as $term) {
      if ($has_depth && $term->depth == 0) {
        $parent = $term->name;
      }
      else {
        if ($term->depth > 1) {
          $options[$parent][$term->tid] = str_pad($pad, $term->depth, "-", STR_PAD_LEFT) . $term->name;
        }
        else {
          if ($has_depth) {
            $options[$parent][$term->tid] = $term->name;
          }
          else {
            $options[$term->tid] = $term->name;
          }
        }
      }
    }
    return $options;
  }
}
