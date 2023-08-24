<?php

namespace Drupal\jcc_elevated_sections;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jcc_elevated_sections\Constants\JccSectionConstants;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Jcc Site Section Helper Service.
 *
 * @package Drupal\jcc_elevated_sections\Services
 */
class JccSectionService implements JccSectionServiceInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * Drupal\Core\Routing\RedirectDestinationInterface definition.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected RedirectDestinationInterface $redirectDestination;

  /**
   * JccSectionService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              AccountInterface $currentUser,
                              StateInterface $state,
                              RedirectDestinationInterface $redirect_destination) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
    $this->state = $state;
    $this->redirectDestination = $redirect_destination;
  }

  /**
   * {@inheritdoc}
   */
  public function currentPageSection() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanAccessSection($user, $sid): bool {
    if ($this->userCanAdminSections($user)) {
      return TRUE;
    }

    $assigned_sections = $this->getUserAllowedSections($user);

    if (isset($assigned_sections[$sid])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanEditSectionsOnEntity($user, $entity) {
    if ($user->id() == 1) {
      return TRUE;
    }

    if ($user->hasPermission('jcc sections edit any sectioned content')) {
      return TRUE;
    }

    if ($user->hasPermission('jcc sections delete any sectioned content')) {
      return TRUE;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function userCanAdminSections($user) {
    if ($user->id() == 1) {
      return TRUE;
    }

    if ($user->hasPermission('jcc sections admin')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserAllowedSections($user) {
    if ($this->userCanAdminSections($user)) {
      return $this->getSectionFullList();
    }

    // Get the sections the user is assigned to.
    $assigned_sections = [];
    if ($user->hasField('jcc_sections')) {
      $sids = $user->get('jcc_sections')->getValue();

      if (!empty($sids)) {
        foreach ($sids as $sid) {
          if (isset($sid['target_id']) && is_numeric($sid['target_id'])) {
            $section = $this->entityTypeManager->getStorage('taxonomy_term')->load($sid['target_id']);
            $assigned_sections[$sid['target_id']] = $section->label();
          }
        }
      }
    }

    return $assigned_sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionForNode(NodeInterface $node) {
    $value = $node->get('jcc_section')->getValue();
    return $value[0]['target_id'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionForMedia(MediaInterface $media) {
    $value = $media->get('jcc_section')->getValue();
    return $value[0]['target_id'] ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionForEntity(EntityInterface $entity) {
    $type = $entity->bundle();

    $node_types = $this->getSectionableTypes();
    if (!empty($node_types[$type])) {
      $value = $entity->get('jcc_section')->getValue();
      return $value[0]['target_id'] ?? FALSE;
    }

    $media_types = $this->getSectionableMediaTypes();
    if (!empty($media_types[$type])) {
      $value = $entity->get('jcc_section')->getValue();
      return $value[0]['target_id'] ?? FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionAllInfo() {
    $section_ids = $this->getSectionIds();
    $sections = [];

    foreach ($section_ids as $id) {
      $section = $this->entityTypeManager->getStorage('taxonomy_term')->load($id);
      $sections[$section->id()] = [
        'id' => $section->id(),
        'name' => $section->label(),
        'jcc_section_machine_name' => $section->getSectionMachineName(),
        'jcc_section_section_homepage' => $section->getSectionHomepage(),
        'jcc_section_url_prefix' => $section->getSectionUrlPrefix(),
      ];
    }

    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionIds() {
    return $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $this->getSectionSourceId())
      ->sort('weight')
      ->execute() ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSections(): array {
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($this->getSectionIds());
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionFullList() {
    $terms = [];
    foreach ($this->getSections() as $term) {
      $terms[$term->id()] = $term->label();
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionOptionList(bool $as_links = FALSE, $add_any_option = FALSE) {
    $terms = [];
    $options = ['query' => $this->redirectDestination->getAsArray()];

    foreach ($this->getSections() as $term) {
      $terms[$term->id()] = $as_links ?
        $term->toLink($term->label(), 'edit-form', $options)->toString() :
        $term->label();
    }

    return $terms;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionInfo($id) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->load($id) ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionSourceId() {
    return $this->state->get('jcc_elevated.section_vocab_source', JccSectionConstants::JCC_SECTIONS_DEFAULT_SOURCE_ID);
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionSourceHumanReadableName() {
    return $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($this->getSectionSourceId())->label() ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionableTypes() {
    return $this->state->get('jcc_elevated.section_allowed_types', []);
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionableMediaTypes() {
    return $this->state->get('jcc_elevated.section_allowed_media_types', []);
  }

  /**
   * {@inheritdoc}
   */
  public function isNodeSectionable($bundle): bool {
    $bundles = $this->state->get('jcc_elevated.section_allowed_types', []);
    return (bool) $bundles[$bundle] && $bundles[$bundle] == $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function isMediaSectionable($bundle): bool {
    $bundles = $this->state->get('jcc_elevated.section_allowed_media_types', []);
    return (bool) $bundles[$bundle] && $bundles[$bundle] == $bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntitySectionable($entity) {

    if ($entity->getEntityTypeId() == 'node') {
      return $this->isNodeSectionable($entity->bundle());
    }

    if ($entity->getEntityTypeId() == 'media') {
      return $this->isMediaSectionable($entity->bundle());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isViewSectionable($view_name_display): bool {
    $views = $this->state->get('jcc_elevated.section_applied_views', []);

    if (isset($views[$view_name_display])) {
      return empty($views[$view_name_display]) ? FALSE : TRUE;
    }

    return FALSE;
  }

}
