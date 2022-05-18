<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Helper function for $entity edit contextual link.
 */
function _jcc_components_can_edit_entity($entity, $cta) {
  $user = User::load(\Drupal::currentUser()->id());
  $can_edit = $entity->access('update', $user);
  $edit = NULL;
  if ($can_edit) {
    $url = Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.edit_form', [$entity->getEntityTypeId() => $entity->id()]);
    $edit = Link::fromTextAndUrl($cta, $url);
    $edit = $edit->toRenderable();
    $edit['#attributes'] = ['aria-label' => ['Edit file for ' . $entity->getName()]];
  }

  return render($edit);
}
