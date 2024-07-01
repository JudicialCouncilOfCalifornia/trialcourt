<?php

namespace Drupal\jcc_tc2_roc_feature\Service;

use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Class for creating links for Rules.
 */
class JccRocRuleLinkService {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\jcc_tc2_roc_feature\Service\JccRocRuleService
   */
  protected JccRocRuleService $jccRocRuleService;

  /**
   * JccRocRuleListService constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\jcc_tc2_roc_feature\Service\JccRocRuleService $jcc_roc_rule_service
   *   The roc rule service.
   */
  public function __construct(TranslationInterface $string_translation, JccRocRuleService $jcc_roc_rule_service) {
    $this->setStringTranslation($string_translation);
    $this->jccRocRuleService = $jcc_roc_rule_service;
  }

  /**
   * Get the Rule Document associated with a Sub Section.
   */
  public function getRocIndexFromRule($rule) {
    $index = FALSE;
    if ($rule->hasField('field_roc_rule_parent_index')) {
      $index = $rule->get('field_roc_rule_parent_index')->referencedEntities();
    }
    return $index[0] ?? FALSE;
  }

  /**
   * Generate a link to a Rules of Court Page.
   */
  public function getRocMainListingPageLink(): GeneratedLink {
    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__index-list-link'],
        'title' => $this->t('View @year California Rules of Court', ['@year' => date('Y')]),
      ],
    ];

    $url = Url::fromUserInput('/cms/rules/index', $options);
    return Link::fromTextAndUrl($this->t('Back to all Rules of Court'), $url)->toString();
  }

  /**
   * Generate a link to a specific Rule documents TOC output.
   */
  public function getLinkToRuleIndexToc($rule_index, $text = FALSE): GeneratedLink|bool {
    $options = [
      'attributes' => [
        'class' => ['roc-rule-index-list__item__link'],
        'title' => $this->t('View the table of contents for "@name"', ['@name' => $rule_index->label()]),
      ],
    ];

    $index_id = $rule_index->get('field_roc_rule_index_id')->first() ? $rule_index->get('field_roc_rule_index_id')->first()->getValue() : FALSE;

    if ($index_id) {
      $url = Url::fromUserInput('/cms/rules/index/' . $index_id['value'], $options);
      $text = $text ?? $rule_index->label();

      return Link::fromTextAndUrl($text, $url)->toString();
    }

    return FALSE;
  }

  /**
   * Generate a link to a specific Rule section.
   */
  public function getLinkToRuleSection($rule, $rule_index, $text = FALSE): bool|GeneratedLink {

    if (is_string($rule)) {
      $rule = $this->jccRocRuleService->getRuleFromId($rule);
    }

    if (!$rule) {
      return FALSE;
    }

    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__section-link'],
        'title' => $this->t('View the section: "@name"', ['@name' => $rule->label()]),
      ],
    ];

    $rule_id = $rule->get('field_roc_rule_id')->first() ? $rule->get('field_roc_rule_id')->first()->getValue() : FALSE;
    $index_id = $rule_index->get('field_roc_rule_index_id')->first() ? $rule_index->get('field_roc_rule_index_id')->first()->getValue() : FALSE;

    if ($rule_id && $index_id) {
      $url = Url::fromUserInput('/cms/rules/index/' . $index_id['value'] . '/' . $rule_id['value'], $options);
      $text = $text ?? $rule->label();

      return Link::fromTextAndUrl($text, $url)->toString();
    }

    return FALSE;
  }

  /**
   * Build a back to top of page link.
   */
  public function getBackToTopLink($id = 'document_top'): GeneratedLink {
    $back_to_top_url = Url::fromRoute('<current>')->setOption('fragment', $id);

    return Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString();
  }

  /**
   * Build an in-page search form.
   */
  public function getInPageSearchForm() {

    $form['in_page_search'] = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'href' => '#',
        'title' => $this->t('Search this page'),
        'class' => [''],
      ],
    ];

    return $form;
  }

  /**
   * Get the Rules set to an Index, in weight order from paragraphs.
   */
  public function getRocRuleIdsFromIndex($index): array {
    $all_rules_for_index = [];
    $rule_section_groups = $index->get('field_roc_rule_index_sections')->referencedEntities();

    foreach ($rule_section_groups as $delta => $section_group) {
      $section_group_rule_items = $section_group->get('field_rule_index_section_content')->referencedEntities();
      foreach ($section_group_rule_items as $key => $rule) {
        $all_rules_for_index[$delta . '__' . $key] = $rule->id();
      }
    }

    return $all_rules_for_index;
  }

}
