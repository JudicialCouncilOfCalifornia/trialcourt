<?php

namespace Drupal\jcc_elevated_roc\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Build a printable rule.
 */
class JccElevatedRocRulePrintService {

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
   * JccElevatedRocRulePrintService constructor.
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
