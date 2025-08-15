<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a jcc address entity type.
 */
interface JccAddressInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the jcc address creation timestamp.
   *
   * @return int
   *   Creation timestamp of the jcc address.
   */
  public function getCreatedTime();

  /**
   * Sets the jcc address creation timestamp.
   *
   * @param int $timestamp
   *   The jcc address creation timestamp.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc address entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the jcc address status.
   *
   * @return bool
   *   TRUE if the jcc address is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the jcc address status.
   *
   * @param bool $status
   *   TRUE to enable this jcc address, FALSE to disable.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc address entity.
   */
  public function setStatus($status);

}
