<?php

namespace Drupal\jcc_elevated_roc\Service;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Build a list of the Rules.
 */
class JccElevatedRocRuleListService {

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
   * JccElevatedRocRuleListService constructor.
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
   * Return renderable List of All ROC assigned File media items.
   *
   * @return array
   *   Return a renderable item array of Rules.
   */
  public function getList(): array {
    $items = [];
    $rule_service = $this->jccElevatedRocRuleService;
    $rule_link_service = $this->jccElevatedRocRuleLinkService;
    $cache_tags = [];

    foreach ($this->getAllRuleDocumentItems() as $nid => $rule_node_item) {
      if (is_int($nid)) {
        $toc_link = [];
        $toc_link[] = $rule_link_service->getLinkToRuleDocumentToc($rule_node_item);
//      if ($note = $rule_service->getRuleDocumentNote($rule_node_item, ['roc-document-list__item__note'])) {
//        $toc_link[] = $note;
//      }
//      if ($attach_pdf = $rule_link_service->getLinkToRuleDocumentPdf($rule_node_item)) {
//        $toc_link[] = $attach_pdf;
//      }
        $items[$nid] = [
          '#markup' => implode('', $toc_link),
          '#wrapper_attributes' => ['class' => 'roc-document-list__item'],
        ];
        $cache_tags[] = 'node:' . $rule_node_item->id();
      }
    }

    return [
      '#theme' => 'jcc_roc_rules_list',
      '#document_list' => [
        '#theme' => 'item_list',
        '#type' => 'ul',
        '#items' => $items,
        '#attributes' => ['class' => 'roc-document-list'],
      ],
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
  }

  /**
   * Return renderable Table of Contents of a ROC assigned File media item.
   *
   * @return array
   *   Return a renderable item array of content.
   */
  public function getToc(string $doc_id): array {
    $rule_link_service = $this->jccElevatedRocRuleLinkService;
    $rule_media_entity = $this->getRuleDocumentById($doc_id);

    $back_to_top_url = Url::fromRoute('<current>')->setOption('fragment', 'document_top');

    $links = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => [
        'back_to_rules_index' => $rule_link_service->getBackLinkToRuleIndexList(),
        'back_to_top' => [
          '#markup' => Link::fromTextAndUrl('Back to top', $back_to_top_url)->toString(),
          '#wrapper_attributes' => ['class' => 'item__align-right'],
        ],
      ],
      '#attributes' => [
        'class' => ['container'],
      ],
    ];

    $upper_links = $links;
    $upper_links['#items']['search_in_page'] = $rule_link_service->getInPageSearchForm();

    $rule_toc = [
      '#theme' => 'item_list',
      '#title' => '',
      '#type' => 'ol',
      '#items' => $this->getDocumentTocItems($rule_media_entity),
      '#attributes' => ['class' => 'roc-document-toc'],
    ];

    $build['anchor'] = ['#markup' => '<span id="document_top"></span>'];

    $build['display'] = [
      '#theme' => 'jcc_roc_rule_toc',
      '#hero_title' => [
        'background' => 'solid-base-light-xx',
        'heading' => $this->t('Rules of Court'),
      ],
      '#rule_toc' => $rule_toc,
      '#upper_links' => $upper_links,
      '#lower_links' => $links,
    ];

    return $build;
  }

  /**
   * Get rule table of contents.
   */
  public function getDocumentTocItems($rule_media_entity): array {
    $rule_link_service = $this->jccElevatedRocRuleLinkService;
    $items = [];
    if ($rule_media_entity) {
      $toc_data = $rule_media_entity->get('processed_toc')->value;
      $toc_data = json_decode($toc_data);
      foreach ($toc_data as $section) {
        $items[$section->linkid] = [
          '#markup' => $section->level == "toc"
            ? $rule_link_service->getLinkToRuleSection($rule_media_entity, $section->linkid, $section->text)
            : $section->text,
          '#wrapper_attributes' => ['class' => $section->level == "toc" ? 'TOClevel3' : 'TOClevel' . $section->level],
        ];
      }
    }

    return $items;
  }

  /**
   * Get word docs.
   */
  public function getAllRuleDocumentItems($document_id = NULL): array {
    return $this->jccElevatedRocRuleService->getRuleDocuments($document_id);
  }

  /**
   * Get word docs.
   */
  private function getRuleDocumentById($document_id) {
    $stuff = $this->getAllRuleDocumentItems($document_id);
    return reset($stuff);
  }

}
