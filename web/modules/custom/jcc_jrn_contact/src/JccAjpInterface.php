<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a jcc ajp entity type.
 */
interface JccAjpInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the jcc ajp creation timestamp.
   *
   * @return int
   *   Creation timestamp of the jcc ajp.
   */
  public function getCreatedTime();

  /**
   * Sets the jcc ajp creation timestamp.
   *
   * @param int $timestamp
   *   The jcc ajp creation timestamp.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc ajp entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the jcc ajp status.
   *
   * @return bool
   *   TRUE if the jcc ajp is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the jcc ajp status.
   *
   * @param bool $status
   *   TRUE to enable this jcc ajp, FALSE to disable.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc ajp entity.
   */
  public function setStatus($status);

}
