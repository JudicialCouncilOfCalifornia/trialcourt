<?php

namespace Drupal\jcc_elevated_roc\Service;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Build a rule display.
 */
class JccElevatedRocRuleDisplayService {

  use StringTranslationTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleService
   */
  protected JccElevatedRocRuleService $jccElevatedRocRuleService;

  /**
   * The module handler.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleLinkService
   */
  protected JccElevatedRocRuleLinkService $jccElevatedRocRuleLinkService;

  /**
   * JccElevatedRocRuleDisplayService constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleService $jcc_elevated_roc_rule_service
   *   The roc rule service.
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleLinkService $jcc_elevated_roc_rule_link_service
   *   The roc rule link service.
   */
  public function __construct(TranslationInterface $string_translation,
                              JccElevatedRocRuleService $jcc_elevated_roc_rule_service,
                              JccElevatedRocRuleLinkService $jcc_elevated_roc_rule_link_service) {
    $this->setStringTranslation($string_translation);
    $this->jccElevatedRocRuleService = $jcc_elevated_roc_rule_service;
    $this->jccElevatedRocRuleLinkService = $jcc_elevated_roc_rule_link_service;
  }

  /**
   * Return renderable array of an individual Rule of Court section.
   *
   * @return array
   *   Return a renderable item array of content.
   */
  public function getRuleSectionDisplay(string $doc_id = '', string $doc_section_id = ''): array {
    $rule_service = $this->jccElevatedRocRuleService;
    $rule_link_service = $this->jccElevatedRocRuleLinkService;

    $rule_document_entity = $rule_service->getRuleDocument($doc_id);

    $items = [];
    $content = $rule_service->getRuleDocumentSectionContent($doc_id, $doc_section_id);
    if ($content) {
      foreach ($content as $item) {
        $items[] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $item['text'],
          '#attributes' => [
            // 'data-doc-style' => $item['style'] ?? 'Normal',
            'class' => $item['style'] ? $rule_service->getStyleClassFromStyleName($item['style']) : '',
          ],
        ];
      }
    }

    $text_override = $this->t('Back to Table of contents')->render();
    $links = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => [
        'previous' => $rule_link_service->getLinkToRulePreviousSection($rule_document_entity, $doc_section_id),
        'back' => $rule_link_service->getLinkToRuleDocumentToc($rule_document_entity, $text_override),
        'next' => $rule_link_service->getLinkToRuleNextSection($rule_document_entity, $doc_section_id),
        'print' => [
          '#markup' => $rule_link_service->getPrintCurrentPageLink(),
          '#wrapper_attributes' => ['class' => 'item__align-right'],
        ],
      ],
      '#attributes' => [
        'class' => ['container'],
      ],
    ];

    $back_to_top_url = Url::fromRoute('<current>')->setOption('fragment', 'document_top');
    $links['#items']['back_to_top'] = Link::fromTextAndUrl('Back to top', $back_to_top_url);

    $upper_links = $links;
    $upper_links['#items']['search_in_page'] = $rule_link_service->getInPageSearchForm();

    $build['anchor'] = ['#markup' => '<span id="document_top"></span>'];

    $build['display'] = [
      '#theme' => 'jcc_roc_rule',
      '#hero_title' => [
        'background' => 'solid-base-light-xx',
        'heading' => $this->t('@year California Rules of Court', ['@year' => date('Y')]),
      ],
      '#rule_text' => $items,
      '#upper_links' => $upper_links,
      '#lower_links' => $links,
    ];

    return $build;
  }

  /**
   * Return renderable array of an individual Rule of Court section.
   *
   * @return array
   *   Return a renderable item array of content.
   */
  public function getRulePrintableSection(string $doc_id = '', string $doc_section_id = ''): array {
    return ['#markup' => $this->t('This is the printable display of an individual section of a given rule')];
  }

  /**
   * Return renderable array of an individual Rule of Court section.
   *
   * @return array
   *   Return a renderable item array of content.
   */
  public function getRulePrintable(string $doc_id = ''): array {
    return ['#markup' => $this->t('This is the printable display of a given rule')];
  }

}
