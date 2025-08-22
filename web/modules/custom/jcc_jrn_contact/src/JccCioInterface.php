<?php

namespace Drupal\jcc_jrn_contact;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a jcc cio entity type.
 */
interface JccCioInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Gets the jcc cio creation timestamp.
   *
   * @return int
   *   Creation timestamp of the jcc cio.
   */
  public function getCreatedTime();

  /**
   * Sets the jcc cio creation timestamp.
   *
   * @param int $timestamp
   *   The jcc cio creation timestamp.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc cio entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the jcc cio status.
   *
   * @return bool
   *   TRUE if the jcc cio is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the jcc cio status.
   *
   * @param bool $status
   *   TRUE to enable this jcc cio, FALSE to disable.
   *
   * @return \Drupal\jcc_jrn_contact\JccStaffInterface
   *   The called jcc cio entity.
   */
  public function setStatus($status);

}
