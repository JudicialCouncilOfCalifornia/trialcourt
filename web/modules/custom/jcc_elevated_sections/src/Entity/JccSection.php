<?php

namespace Drupal\jcc_elevated_sections\Entity;

use Drupal\taxonomy\Entity\Term;

/**
 * A bundle class for Site section taxonomy terms.
 */
class JccSection extends Term {

  /**
   * Get the machine_name of the Section.
   */
  public function getSectionMachineName() {
    return $this->hasField('jcc_section_machine_name') ? $this->get('jcc_section_machine_name')->value : FALSE;
  }

  /**
   * Set the machine_name of the Section.
   */
  public function setSectionMachineName($name) {
    $this->set('jcc_section_machine_name', $name);
    return $this;
  }

  /**
   * Get the NID of the Section Homepage.
   */
  public function getSectionHomepage() {
    return $this->hasField('jcc_section_homepage') ? $this->get('jcc_section_homepage')->target_id : FALSE;
  }

  /**
   * Set the NID of the Section Homepage.
   */
  public function setSectionHomepage($nid) {
    $this->set('jcc_section_homepage', $nid);
    return $this;
  }

  /**
   * Get the URL Prefix for the Section.
   */
  public function getSectionUrlPrefix() {
    return $this->hasField('jcc_section_url_prefix') ? $this->get('jcc_section_url_prefix')->value : FALSE;
  }

  /**
   * Set the URL Prefix for the Section.
   */
  public function setSectionUrlPrefix($alias) {
    $this->set('jcc_section_url_prefix', $alias);
    return $this;
  }

}
