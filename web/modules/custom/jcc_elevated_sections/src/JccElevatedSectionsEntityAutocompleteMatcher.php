<?php

namespace Drupal\jcc_elevated_sections;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcher;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * AutoComplete Controller for adding sections to the output.
 */
class JccElevatedSectionsEntityAutocompleteMatcher extends EntityAutocompleteMatcher {

  /**
   * The entity reference selection handler plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Section service interface.
   *
   * @var \Drupal\jcc_elevated_sections\JccSectionServiceInterface
   */
  protected $jccElevatedSectionsService;

  /**
   * Constructs an EntityAutocompleteMatcher object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The entity reference selection handler plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\jcc_elevated_sections\JccSectionServiceInterface $jcc_elevated_sections_service
   *   The entity repository.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager,
                              EntityTypeManagerInterface $entity_type_manager,
                              EntityRepositoryInterface $entity_repository,
                              JccSectionServiceInterface $jcc_elevated_sections_service) {
    $this->selectionManager = $selection_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->jccElevatedSectionsService = $jcc_elevated_sections_service;
  }

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = ''): array {

    $matches = [];

    $options = [
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];

    $handler = $this->selectionManager->getInstance($options);

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $entity = $this->entityTypeManager->getStorage($target_type)->load($entity_id);
          $entity = $this->entityRepository->getTranslationFromContext($entity);

          // $type = !empty($entity->type->entity) ?
          // $entity->type->entity->label() : $entity->bundle();
          $section = FALSE;
          $section_label = FALSE;

          if ($entity->getEntityType()->id() == 'node') {
            $section_id = $this->jccElevatedSectionsService->getSectionForNode($entity);
            $section = $this->jccElevatedSectionsService->getSectionInfo($section_id);
            $section_label = $section->label();
            $section_label = $section ? " <span style='font-size: 13px;color: var(--gin-color-primary);font-style: italic'>{$section_label}</span>" : "";
          }

          $key = $label . ' (' . $entity_id . ')';

          // Strip undesirable things like starting/trailing white spaces,
          // line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));

          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);

          $label = "{$label} ({$entity_id}) {$section_label}";

          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;
  }

}
