<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Helper function for $entity edit contextual link.
 *
 * @param Drupal\Core\Entity\EntityInterface $entity
 *   The entity to check for access.
 * @param string $cta
 *   The text string for the edit link.
 *
 * @return mixed
 *   NULL or a render array.
 */
function _jcc_components_can_edit_entity(EntityInterface $entity, string $cta) {
  $user = User::load(\Drupal::currentUser()->id());
  $can_edit = empty($entity) ? FALSE : $entity->access('update', $user);
  $edit = NULL;

  if ($can_edit) {
    $url = Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.edit_form', [$entity->getEntityTypeId() => $entity->id()]);
    $edit = Link::fromTextAndUrl($cta, $url);
    $edit = $edit->toRenderable();
    $title = 'Page: ';
    if (isset($entity->title->value)) {
      $title = $entity->getType() . ': ' . $entity->title->value;
    }
    elseif ($entity->getName() !== NULL) {
      $title = 'file: ' . $entity->getName();
    }
    $edit['#attributes'] = ['aria-label' => ['Edit ' . $title]];
  }

  return \Drupal::service('renderer')->render($edit);
}

/**
 * Helper function to remove protocol from image paths.
 *
 * @param string $image
 *   The Drupal image url.
 *
 * @return string
 *   Converted url with no http or https.
 */
function _jcc_components_remove_image_protocol($image) {
  return preg_replace('(^https?:||^http?:)', '', $image);
}
