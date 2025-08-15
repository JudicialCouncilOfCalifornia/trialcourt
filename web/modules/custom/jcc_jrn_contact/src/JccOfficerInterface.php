<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a jcc officer entity type.
 */
interface JccOfficerInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the jcc officer creation timestamp.
   *
   * @return int
   *   Creation timestamp of the jcc officer.
   */
  public function getCreatedTime();

  /**
   * Sets the jcc officer creation timestamp.
   *
   * @param int $timestamp
   *   The jcc officer creation timestamp.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc officer entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the jcc officer status.
   *
   * @return bool
   *   TRUE if the jcc officer is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the jcc officer status.
   *
   * @param bool $status
   *   TRUE to enable this jcc officer, FALSE to disable.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc officer entity.
   */
  public function setStatus($status);

}
