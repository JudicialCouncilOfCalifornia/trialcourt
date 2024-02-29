<?php

namespace Drupal\jcc_tc_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines block Related News (used in events page)
 *
 * @Block(
 *   id = "related_news_custom",
 *   admin_label = @Translation("Related News"),
 *   )
 */
class RelatednewsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();

    if (!empty($config['related_news_custom'])) {
      $block_content = $config['related_news_custom'];
    }
    else {
      $block_content = $this->t('<p><a href="https://supreme.courts.ca.gov/case-information/oral-arguments">Oral Argument Archive</a><br /><a href="https://jcc.legistar.com/Calendar.aspx">Judicial Council Meeting Archive</a></p>');
    }
    return [
      '#markup' => $block_content,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $form['related_news_custom'] = [
      '#type' => 'textarea',
      '#title' => $this->t('content'),
      '#default_value' => isset($config['related_news_custom']) ? $config['related_news_custom'] : '<p><a href="https://supreme.courts.ca.gov/case-information/oral-arguments">Oral Argument Archive</a><br /><a href="https://jcc.legistar.com/Calendar.aspx">Judicial Council Meeting Archive</a></p>\'',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['related_news_custom'] = $values['related_news_custom'];
  }

}
