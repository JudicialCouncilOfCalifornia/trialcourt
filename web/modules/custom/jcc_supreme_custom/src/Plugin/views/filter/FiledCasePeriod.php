<?php

namespace Drupal\jcc_supreme_custom\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jcc_supreme_custom\FiledCasePeriod as FilingWeek;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Selects recent or archived cases using their filing date, not import time.
 */
#[ViewsFilter('jcc_filed_case_period')]
final class FiledCasePeriod extends FilterPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The filing week calculator.
   */
  protected FilingWeek $filingWeek;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->filingWeek = $container->get('jcc_supreme_custom.filed_case_period');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['period'] = ['default' => 'recent'];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['period'] = [
      '#type' => 'select',
      '#title' => $this->t('Filing period'),
      '#options' => [
        'recent' => $this->t('Most recently completed filing week'),
        'archive' => $this->t('Earlier filing weeks'),
      ],
      '#default_value' => $this->options['period'],
      '#required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->options['period'] === 'archive' ? $this->t('Earlier filing weeks') : $this->t('Most recently completed filing week');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (!$this->query instanceof Sql) {
      throw new \LogicException('Filing periods require a SQL view.');
    }
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";
    $window = $this->filingWeek->getWindow();
    // These are date-only strings: do not convert midnight through UTC.
    if ($this->options['period'] === 'archive') {
      $this->query->addWhere(0, $field, $window['start'], '<');
    }
    else {
      $this->query->addWhere(0, $field, $window['start'], '>=');
      $this->query->addWhere(0, $field, $window['end'], '<');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // Reevaluate the weekly boundary, including for anonymous page requests.
    return 0;
  }

}
