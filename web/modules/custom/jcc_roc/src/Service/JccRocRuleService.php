<?php

namespace Drupal\jcc_roc\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Build a rule object.
 */
class JccRocRuleService {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The file and stream wrapper helper.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * JccElevatedRocRuleService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file and stream wrapper helper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              StateInterface $state,
                              TranslationInterface $string_translation,
                              ModuleHandlerInterface $module_handler,
                              FileSystemInterface $file_system,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->setStringTranslation($string_translation);
    $this->moduleHandler = $module_handler;
    $this->fileSystem = $file_system;
  }

  /**
   * Returns the Document category id defining which items are a Rule.
   */
  public function getRocDefiningDocumentCategoryId(): int {
    return $this->state->get('jcc_elevated_roc.roc_defining_category', 51);
  }

  /**
   * Returns the Document category id defining which items are a Rule.
   */
  public function getRocDefiningDocumentBundleType(): string {
    return 'file';
  }

  /**
   * Returns the Document category id defining which items are a Rule.
   */
  public function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager;
  }

  /**
   * Get file path of file set to media entity.
   */
  public function getMediaDocumentFile($media_entity) {
    $file_manager = $this->entityTypeManager->getStorage('file');
    $fid = $media_entity->getSource()->getSourceFieldValue($media_entity);
    return $fid ? $file_manager->load($fid) : FALSE;
  }

  /**
   * Get the real file path of file set to the media entity.
   */
  public function getMediaDocumentFilePath($media_entity): bool|string {
    if ($file = $this->getMediaDocumentFile($media_entity)) {
      return $this->fileSystem->realpath($file->getFileUri());
    };

    return FALSE;
  }

  /**
   * Get the real file path of file set to the media entity.
   */
  public function getRuleDocumentFileMimeType($media_entity) {
    if ($file = $this->getMediaDocumentFile($media_entity)) {
      return $file->getMimeType();
    };

    return FALSE;
  }

  /**
   * Get the real file path of file set to the media entity.
   */
  public function isRuleDocumentFileWordDoc($media_entity): bool {
    return $this->getRuleDocumentFileMimeType($media_entity) == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
  }

  /**
   * Get Rule Subsection node from ID.
   */
  public function getRuleSubSectionFromId($subsection_id): bool|EntityInterface {
    $node_manager = $this->entityTypeManager->getStorage('node');
    $subsection = $node_manager->load($subsection_id);
    return $subsection && $subsection->bundle() == 'rule_subsection' ? $subsection : FALSE;
  }

  /**
   * Get Rule documents that are Published and Promoted to ROC index.
   */
  public function getRocPublishedPromotedRuleDocuments(): array {
    $node_manager = $this->entityTypeManager->getStorage('node');
    $query = $node_manager->getQuery();
    $nids = $query->condition('type', 'rule_document')
      ->condition('status', TRUE)
      ->condition('promote', TRUE)
      ->execute();
    return $node_manager->loadMultiple($nids);
  }

  /**
   * Get All Rule documents.
   */
  public function getRocAllRuleDocuments(): array {
    $node_manager = $this->entityTypeManager->getStorage('node');
    $query = $node_manager->getQuery();
    $nids = $query->condition('type', 'rule_document')
      ->execute();
    return $node_manager->loadMultiple($nids);
  }

  /**
   * Get the direct path to the (first) media item attached to a rule document.
   */
  public function getRocDocumentAttachedMediaFile($subsection) {
    if ($attached_media = $subsection->get('field_rule_media')->referencedEntities()) {
      $file = $this->getMediaDocumentFile($attached_media[0]);
    }
    return $file ?? FALSE;
  }

  /**
   * Get the direct path to the (first) media item attached to a rule document.
   */
  public function getRocDocumentAttachedMediaFilePath($subsection) {
    if ($attached_media = $subsection->get('field_rule_media')->referencedEntities()) {
      $path = $this->getMediaDocumentFilePath($attached_media[0]);
    }
    return $path ?? FALSE;
  }

}
