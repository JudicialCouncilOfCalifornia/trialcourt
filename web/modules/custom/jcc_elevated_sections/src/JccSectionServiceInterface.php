<?php

namespace Drupal\jcc_elevated_sections;

/**
 * @file
 * Providing the service that helps with Site Section functionality.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for the "jcc_elevated_sections.service" service.
 */
interface JccSectionServiceInterface {

  /**
   * Check if user has general content edit restriction.
   *
   * @param object $user
   *   Loaded user entity.
   *
   * @return mixed
   *   Returns true if user is restricted from editing general content.
   */
  public function userIsRestrictedFromGeneralContent($user);

  /**
   * Check if user can edit the sections on a given entity.
   *
   * @param object $user
   *   Loaded user entity.
   * @param object $entity
   *   Section loaded entity (preferably node or media entity).
   *
   * @return mixed
   *   Returns true if user can edit the sections on a given entity.
   */
  public function userCanEditSectionsOnEntity($user, $entity);

  /**
   * Check if user can administer sections.
   *
   * @param object $user
   *   Loaded user entity.
   *
   * @return mixed
   *   Returns true if user can administer sections.
   */
  public function userCanAdminSections($user);

  /**
   * Check if user can access a given section.
   *
   * @param object $user
   *   Loaded user entity.
   * @param string $sid
   *   Section id (Term ID).
   *
   * @return bool
   *   Check if user can access a given section.
   */
  public function userCanAccessSection($user, $sid): bool;

  /**
   * Get the sections that a given user is allowed to edit.
   *
   * @param object $user
   *   Loaded user entity.
   *
   * @return mixed
   *   Returns the sections that a given user is allowed to edit.
   */
  public function getUserAllowedSections($user);

  /**
   * Get the sections that a given user is NOT allowed to edit.
   *
   * @param object $user
   *   Loaded user entity.
   *
   * @return mixed
   *   Returns the sections that a given user is NOT allowed to edit.
   */
  public function getUserForbiddenSections($user);

  /**
   * Returns the section that is applied to a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Loaded node entity.
   *
   * @return mixed
   *   Returns the section that is applied to a given node.
   */
  public function getSectionForNode(NodeInterface $node);

  /**
   * Returns the section that is applied to a given media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Loaded media entity.
   *
   * @return mixed
   *   Returns the section that is applied to a given media item.
   */
  public function getSectionForMedia(MediaInterface $media);

  /**
   * Returns the section that is applied to a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Loaded entity.
   *
   * @return mixed
   *   Returns the section that is applied to a given entity.
   */
  public function getSectionForEntity(EntityInterface $entity);

  /**
   * Returns an array of sections and relevant information.
   *
   * @return mixed
   *   Returns an array of sections and relevant information.
   */
  public function getSectionAllInfo();

  /**
   * Returns an array of section ids.
   *
   * @return mixed
   *   Returns an array of section ids.
   */
  public function getSectionIds();

  /**
   * Returns an array of loaded section term entities.
   *
   * @return mixed
   *   Returns an array of loaded section term entities.
   */
  public function getSectionFullList();

  /**
   * Returns an array of sections, usable in select lists.
   *
   * @param bool $as_links
   *   Option to return the human-readable list as direct links to the sections.
   * @param bool $add_any_option
   *   Option to return the array with a generic "All" option as the first item.
   *
   * @return mixed
   *   Returns an array of sections, usable in select lists.
   */
  public function getSectionOptionList(bool $as_links = FALSE, $add_any_option = FALSE);

  /**
   * Returns a loaded section given a section ID.
   *
   * @param string $id
   *   Section ID (Term ID).
   *
   * @return mixed
   *   Returns a loaded section given a section ID.
   */
  public function getSectionInfo($id);

  /**
   * Returns a vocab ID of the taxonomy used as the section source.
   *
   * @return mixed
   *   Returns a vocab ID of the taxonomy used as the section source.
   */
  public function getSectionSourceId();

  /**
   * Returns a Name of the taxonomy used as the section source.
   *
   * @return mixed
   *   Returns a Name of the taxonomy used as the section source.
   */
  public function getSectionSourceHumanReadableName();

  /**
   * Returns an array of node types that can be sectioned.
   *
   * @return mixed
   *   Returns an array of node types that can be sectioned.
   */
  public function getSectionableTypes();

  /**
   * Returns an array of node types that should have the Prefix applied.
   *
   * @return mixed
   *   Returns an array of node types hat should have the Prefix applied.
   */
  public function getSectionableUrlPrefixTypes();

  /**
   * Returns an array of media types that can be sectioned.
   *
   * @return mixed
   *   Returns an array of media types that can be sectioned.
   */
  public function getSectionableMediaTypes();

  /**
   * Says whether a bundle is sectionable or not.
   *
   * @param string $bundle
   *   The bundle type of node.
   *
   * @return bool
   *   Says whether a bundle is sectionable or not.
   */
  public function isNodeSectionable($bundle): bool;

  /**
   * Says whether a media bundle is sectionable or not.
   *
   * @param string $bundle
   *   The bundle type of media.
   *
   * @return bool
   *   Says whether a media bundle is sectionable or not.
   */
  public function isMediaSectionable($bundle): bool;

  /**
   * Says whether an entity is sectionable or not.
   *
   * @param object $entity
   *   A loaded entity.
   *
   * @return mixed
   *   Says whether an entity is sectionable or not.
   */
  public function isEntitySectionable($entity);

  /**
   * Returns if a view should have a section filter applied to it.
   *
   * @param string $view_name_display
   *   The view name and display combo string in "name:display" format.
   *
   * @return bool
   *   Returns if a view should have a section filter applied to it.
   */
  public function isViewSectionable($view_name_display): bool;

  /**
   * Returns if a view should have non-sectioned/general content excluded..
   *
   * @param string $view_name_display
   *   The view name and display combo string in "name:display" format.
   *
   * @return bool
   *   Returns if a view should have a section filter applied to it.
   */
  public function isViewGeneralContentExcluded($view_name_display): bool;

}
