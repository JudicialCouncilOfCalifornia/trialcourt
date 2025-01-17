<?php

/**
 * @file
 * Functions for views processing.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\linkit\SubstitutionManagerInterface;
use Drupal\linkit\Utility\LinkitHelper;
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

/**
 * Helper function for Linkit formatting of media URL.
 *
 * @param string $uri
 *   The Drupal media entity URI. Also supports URL.
 *
 * @return string
 *   Substitute path if provided.
 */
function _jcc_components_media_substitute_url($uri) {
  $path = $uri;
  if (\Drupal::service('module_handler')->moduleExists('linkit')) {
    $entity = LinkitHelper::getEntityFromUri($uri);
    if ($entity && $entity->getEntityTypeId() == 'media') {
      $linkit_profile_storage = \Drupal::service('entity_type.manager')->getStorage('linkit_profile');
      $linkit_profile = $linkit_profile_storage->load('default');
      $matcher = $linkit_profile->getMatcherByEntityType('media');
      if ($matcher) {
        $substitution_type = $matcher ? $matcher->getConfiguration()['settings']['substitution_type'] : SubstitutionManagerInterface::DEFAULT_SUBSTITUTION;
        $substitution = \Drupal::service('plugin.manager.linkit.substitution')->createInstance($substitution_type)->getUrl($entity);
        if ($substitution->getOptions()['path']) {
          $path = $substitution->getOptions()['path'];
        }
      }
    }
  }
  return $path;
}
