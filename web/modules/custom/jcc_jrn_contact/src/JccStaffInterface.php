<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a jcc staff entity type.
 */
interface JccStaffInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the jcc staff creation timestamp.
   *
   * @return int
   *   Creation timestamp of the jcc staff.
   */
  public function getCreatedTime();

  /**
   * Sets the jcc staff creation timestamp.
   *
   * @param int $timestamp
   *   The jcc staff creation timestamp.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc staff entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the jcc staff status.
   *
   * @return bool
   *   TRUE if the jcc staff is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the jcc staff status.
   *
   * @param bool $status
   *   TRUE to enable this jcc staff, FALSE to disable.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc staff entity.
   */
  public function setStatus($status);

}
