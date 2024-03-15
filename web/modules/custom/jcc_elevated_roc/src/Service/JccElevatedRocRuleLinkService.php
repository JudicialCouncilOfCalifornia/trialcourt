<?php

namespace Drupal\jcc_elevated_roc\Service;

use Drupal\Core\GeneratedLink;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Class for creating links for Rules.
 */
class JccElevatedRocRuleLinkService {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleService
   */
  protected JccElevatedRocRuleService $jccElevatedRocRuleService;

  /**
   * JccElevatedRocRuleListService constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleService $jcc_elevated_roc_rule_service
   *   The roc rule service.
   */
  public function __construct(TranslationInterface $string_translation,
                              JccElevatedRocRuleService $jcc_elevated_roc_rule_service) {
    $this->setStringTranslation($string_translation);
    $this->jccElevatedRocRuleService = $jcc_elevated_roc_rule_service;
  }

  /**
   * Get a link to the full document pdf.
   */
  public function getLinkToRuleDocumentPdf($rule_media_item): bool|GeneratedLink {
    $rule_service = $this->jccElevatedRocRuleService;

    if ($rule_media_item->get('attach_pdf')->value) {
      $options = [
        'attributes' => [
          'class' => ['roc-document-list__item__pdf-link'],
          'title' => $this->t('Download PDF of "@name"', ['@name' => $rule_media_item->label()]),
        ],
      ];

      // If we have a Word doc, then it has processed content and a page.
      if ($rule_service->isRuleDocumentFileWordDoc($rule_media_item)) {
        $url = Url::fromUserInput('/cms/rules/index/' . $rule_media_item->get('document_id')->value . '/print', $options);
      }
      else {
        // Otherwise generate link to the Media's direct file (pdf, txt).
        $options['query']['inline'] = TRUE;
        $url = Url::fromUserInput('/media/' . $rule_media_item->id() . '/download', $options);
      }

      return Link::fromTextAndUrl('PDF', $url)->toString();
    }

    return FALSE;
  }

  /**
   * Generate a link to a specific Rule documents TOC output.
   */
  public function getLinkToRuleDocumentToc($rule_node_item, $text_override = NULL) {
    if (!$rule_node_item) {
      return FALSE;
    }

    $rule_service = $this->jccElevatedRocRuleService;

    $options = [
      'attributes' => [
        'class' => ['roc-document-list__item__link'],
        'title' => $this->t('View the table of contents for "@name"', ['@name' => $rule_node_item->label()]),
      ],
    ];

    // If we have a Word doc, then it has processed content and a page.
    if ($rule_service->isRuleDocumentFileWordDoc($rule_node_item)) {
      $url = Url::fromUserInput('/cms/rules/index/' . $rule_node_item->get('field_document_id')->value, $options);
    }
    // Otherwise generate link to the Media's direct file (pdf, txt).
    else {
      $options['query']['inline'] = TRUE;
      $options['attributes']['title'] = $this->t('Download PDF of "@name"', ['@name' => $rule_node_item->label()]);
      $url = Url::fromUserInput('/media/' . $rule_node_item->id() . '/download', $options);
    }

    $label = is_string($text_override) ? $text_override : $rule_node_item->label();
    return Link::fromTextAndUrl($label, $url)->toString();
  }

  /**
   * Generate a link to a specific Rule section.
   */
  public function getLinkToRuleSection($rule_media_item, $section_id, $text): GeneratedLink {
    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__section-link'],
      ],
    ];
    $document_id = $rule_media_item->get('document_id')->value;
    $url = Url::fromUserInput('/cms/rules/index/' . $document_id . '/linkid/' . $section_id, $options);
    return Link::fromTextAndUrl($text, $url)->toString();
  }

  /**
   * Generate a link to a specific Rule section.
   */
  public function getBackLinkToRuleIndexList(): GeneratedLink {
    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__index-list-link'],
      ],
    ];
    $url = Url::fromUserInput('/cms/rules/index/', $options);
    return Link::fromTextAndUrl(t('Back to all Rules of Court'), $url)->toString();
  }

  /**
   * Generate a print link.
   */
  public function getPrintCurrentPageLink() {
    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__index-list-link'],
        'onclick' => 'javascript:window.print();',
      ],
    ];
    $url = Url::fromRoute('<current>', [], $options);
    return Link::fromTextAndUrl(t('Print'), $url)->toString();
  }

  /**
   * Generate a link to a specific Rule section.
   */
  public function getLinkToRuleNextSection($rule_media_item, $section_id): GeneratedLink {
    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__section-link'],
      ],
    ];
    $document_id = $rule_media_item->get('document_id')->value;
    $url = Url::fromUserInput('/cms/rules/index/' . $document_id . '/linkid/' . $section_id, $options);
    return Link::fromTextAndUrl(t('Next section'), $url)->toString();
  }

  /**
   * Generate a link to a specific Rule section.
   */
  public function getLinkToRulePreviousSection($rule_media_item, $section_id,): GeneratedLink {
    $options = [
      'attributes' => [
        'class' => ['roc-document-rule__section-link'],
      ],
    ];
    $document_id = $rule_media_item->get('document_id')->value;
    $url = Url::fromUserInput('/cms/rules/index/' . $document_id . '/linkid/' . $section_id, $options);
    return Link::fromTextAndUrl(t('Previous section'), $url)->toString();
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

}
