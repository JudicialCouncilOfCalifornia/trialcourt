<?php

namespace Drupal\jcc_elevated_roc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleDisplayService;
use Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleListService;
use Drupal\jcc_elevated_roc\Service\JccElevatedRocRulePrintService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Master display for rendering the TOC and individual rule.
 */
class JccElevatedRocRuleDisplayController extends ControllerBase {

  /**
   * The Roc display service.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleDisplayService
   */
  protected JccElevatedRocRuleDisplayService $jccElevatedRocRuleDisplayService;

  /**
   * The Roc print service.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRulePrintService
   */
  protected JccElevatedRocRulePrintService $jccElevatedRocRulePrintService;

  /**
   * The Roc list service.
   *
   * @var \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleListService
   */
  protected jccElevatedRocRuleListService $jccElevatedRocRuleListService;

  /**
   * The Roc display.
   *
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleDisplayService $jcc_elevated_roc_rule_display_service
   *   JCC Roc Rule display service.
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRulePrintService $jcc_elevated_roc_rule_print_service
   *   JCC Roc Rule print service.
   * @param \Drupal\jcc_elevated_roc\Service\JccElevatedRocRuleListService $jcc_elevated_roc_rule_list_service
   *   JCC Roc Rule listing service.
   */
  public function __construct(JccElevatedRocRuleDisplayService $jcc_elevated_roc_rule_display_service,
                              JccElevatedRocRulePrintService $jcc_elevated_roc_rule_print_service,
                              JccElevatedRocRuleListService $jcc_elevated_roc_rule_list_service) {
    $this->jccElevatedRocRuleDisplayService = $jcc_elevated_roc_rule_display_service;
    $this->jccElevatedRocRulePrintService = $jcc_elevated_roc_rule_print_service;
    $this->jccElevatedRocRuleListService = $jcc_elevated_roc_rule_list_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jcc_elevated_roc.rule_display.service'),
      $container->get('jcc_elevated_roc.rule_print.service'),
      $container->get('jcc_elevated_roc.rule_list.service'),
    );
  }

  /**
   * Returns a individual rule section.
   */
  public function displayRuleSection($doc_id, $doc_section_id) {
    return $this->jccElevatedRocRuleDisplayService->getRuleSectionDisplay($doc_id, $doc_section_id);
  }

  /**
   * Returns a printable version of an individual rule section.
   */
  public function displayRuleSectionPrint($doc_id, $doc_section_id): array {
    return $this->jccElevatedRocRulePrintService->getRulePrintableSection($doc_id, $doc_section_id);
  }

  /**
   * Returns a printable version of the complete rule.
   */
  public function displayRuleFullPrint($doc_id): array {
    return $this->jccElevatedRocRulePrintService->getRulePrintable($doc_id);
  }

  /**
   * Returns a listing of all the Rule Files.
   */
  public function displayRuleList(): array {
    return $this->jccElevatedRocRuleListService->getList();
  }

  /**
   * Returns a listing of all the Rule Files.
   */
  public function displayRuleToc($doc_id = NULL): array {
    return $this->jccElevatedRocRuleListService->getToc($doc_id);
  }

}
