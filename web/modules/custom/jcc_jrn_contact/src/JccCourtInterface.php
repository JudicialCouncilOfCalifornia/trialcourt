<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a jcc court entity type.
 */
interface JccCourtInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the jcc court creation timestamp.
   *
   * @return int
   *   Creation timestamp of the jcc court.
   */
  public function getCreatedTime();

  /**
   * Sets the jcc court creation timestamp.
   *
   * @param int $timestamp
   *   The jcc court creation timestamp.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc court entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the jcc court status.
   *
   * @return bool
   *   TRUE if the jcc court is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the jcc court status.
   *
   * @param bool $status
   *   TRUE to enable this jcc court, FALSE to disable.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc court entity.
   */
  public function setStatus($status);

}
