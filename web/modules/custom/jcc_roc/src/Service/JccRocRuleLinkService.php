<?php

namespace Drupal\jcc_roc\Service;

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
   * @var \Drupal\jcc_roc\Service\JccRocRuleService
   */
  protected JccRocRuleService $jccRocRuleService;

  /**
   * JccRocRuleListService constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\jcc_roc\Service\JccRocRuleService $jcc_roc_rule_service
   *   The roc rule service.
   */
  public function __construct(TranslationInterface $string_translation, JccRocRuleService $jcc_roc_rule_service) {
    $this->setStringTranslation($string_translation);
    $this->jccRocRuleService = $jcc_roc_rule_service;
  }

  /**
   * Get the Rule Document associated with a Sub Section.
   */
  public function getRocDocumentFromSubSection($subsection) {
    $document = FALSE;
    if ($subsection->hasField('field_rule_subsection_document')) {
      $document = $subsection->get('field_rule_subsection_document')->referencedEntities();
    }
    return $document[0] ?? FALSE;
  }

  /**
   * Generate a link to a Rules of Court Page.
   */
  public function getRocMainIndexLink(): GeneratedLink {
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
  public function getLinkToRuleDocumentToc($rule_document, $text = FALSE): GeneratedLink|bool {
    $options = [
      'attributes' => [
        'class' => ['roc-document-list__item__link'],
        'title' => $this->t('View the table of contents for "@name"', ['@name' => $rule_document->label()]),
      ],
    ];

    $document_id = $rule_document->get('field_rule_document_id')->first() ? $rule_document->get('field_rule_document_id')->first()->getValue() : FALSE;

    if ($document_id) {
      $url = Url::fromUserInput('/cms/rules/index/' . $document_id['value'], $options);
      $text = $text ?? $rule_document->label();

      return Link::fromTextAndUrl($text, $url)->toString();
    }

    return FALSE;
  }

  /**
   * Generate a link to a specific Rule section.
   */
  public function getLinkToRuleSection($rule_subsection, $rule_document, $text = FALSE): bool|GeneratedLink {

    if (is_string($rule_subsection)) {
      $rule_subsection = $this->jccRocRuleService->getRuleSubSectionFromId($rule_subsection);
    }

    if (!$rule_subsection) {
      return FALSE;
    }

    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__section-link'],
        'title' => $this->t('View the section: "@name"', ['@name' => $rule_subsection->label()]),
      ],
    ];

    $section_id = $rule_subsection->get('field_rule_subsection_id')->first() ? $rule_subsection->get('field_rule_subsection_id')->first()->getValue() : FALSE;
    $document_id = $rule_document->get('field_rule_document_id')->first() ? $rule_document->get('field_rule_document_id')->first()->getValue() : FALSE;

    if ($section_id && $document_id) {
      $url = Url::fromUserInput('/cms/rules/index/' . $document_id['value'] . '/' . $section_id['value'], $options);
      $text = $text ?? $rule_subsection->label();

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
   * Get the SubSections set to a Document, in weight order from paragraphs.
   */
  public function getRocSubSectionIdsFromDocument($document): array {
    $all_subsections_for_document = [];
    $rule_section_groups = $document->get('field_rule_section_attachment')->referencedEntities();

    foreach ($rule_section_groups as $delta => $section_group) {
      $section_group_subsection_items = $section_group->get('field_rule_subsection_content')->referencedEntities();
      foreach ($section_group_subsection_items as $key => $subsection) {
        $all_subsections_for_document[$delta . '__' . $key] = $subsection->id();
      }
    }

    return $all_subsections_for_document;
  }

}
