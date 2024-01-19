<?php

/**
 * @file
 * The jcc_elevated_sections include file for form_alter functionality.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements hook_form_ENTITY_FORM_alter().
 */
function jcc_elevated_sections_form_taxonomy_term_form_alter(&$form, FormStateInterface $form_state) {
  // Set some variables.
  $term = $form_state->getFormObject()->getEntity();
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $vocab_source = $section_service->getSectionSourceId();

  // We are going to alter the admin/add/edit form for all terms in the section
  // taxonomy. This will improve the UI and management of the functionality.
  if ($term->bundle() == $vocab_source) {

    // Prevent going to view the term on save. Reloads the edit page.
    $form_state->disableRedirect();

    // Hide the description.
    $form['description']['#access'] = FALSE;

    // Hide the relations.
    $form['relations']['#access'] = FALSE;

    // Hide the Generate URL options. Set to FALSE always.
    $form['path']['widget'][0]['pathauto']['#default_value'] = FALSE;
    $form['path']['widget'][0]['pathauto']['#access'] = FALSE;

    // Hide the alias field. Set to FALSE always.
    $form['path']['widget'][0]['alias']['#access'] = FALSE;

    // Hide the Save and Go to list link.
    $form['actions']['overview']['#access'] = FALSE;

    // Add custom submission that will redirect to admin page on save.
    $form['actions']['submit']['#submit'][] = '_jcc_elevated_sections_term_redirect';

    // If delete is block from entity_access, print our helper message.
    if (!$form['actions']['delete']['#access'] && $form['tid']['#value']) {
      $message[] = t('Deleting this Section term is prevented.');
      $message[] = t('This section has content, media, and/or users associated with it.');
      $message[] = t('Please delete or edit that content first, to remove this section term.');
      $form['notify'] = [
        '#prefix' => '<p class="form-item__description">',
        '#markup' => implode('<br/>', $message),
        '#weight' => 101,
        '#suffix' => '</p>',
      ];
    }
  }

  if ($term->bundle() != $vocab_source) {
    // Hide the section related fields from none section taxonomy terms.
    $form['jcc_section_machine_name']['#access'] = FALSE;
    $form['jcc_section_url_prefix']['#access'] = FALSE;
    $form['jcc_section_homepage']['#access'] = FALSE;
  }
}

/**
 * Custom redirect on Site Section term save. Goes to custom admin page.
 */
function _jcc_elevated_sections_term_redirect($form, FormStateInterface $form_state) {
  $url = Url::fromRoute('jcc_elevated_sections.settings');
  $form_state->setRedirectUrl($url);
}

/**
 * Implements hook_form_ENTITY_FORM_alter().
 */
function jcc_elevated_sections_form_user_form_alter(&$form, FormStateInterface $form_state) {
  // Set some variables.
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $listed_user = $form_state->getFormObject()->getEntity();
  $current_account = \Drupal::currentUser();
  $current_user = \Drupal::entityTypeManager()->getStorage('user')->load($current_account->id());

  // Listed user that is being viewed/edited.
  $listed_user_exempt_from_any_restrictions = $section_service->userCanAdminSections($listed_user);

  // Current logged-in user information.
  $current_user_has_access_to_set_sections = $section_service->userCanAdminSections($current_user);

  $form['allowed'] = [
    '#type' => 'details',
    '#title' => t('Sections'),
    '#description' => t('Assign sections for this user.'),
    '#open' => TRUE,
    '#weight' => 91,
  ];

  $form['jcc_sections']['#group'] = 'allowed';
  $form['jcc_restrict']['#group'] = 'allowed';

  // If listed user is exempt from any section restrictions (they are admins).
  if ($listed_user_exempt_from_any_restrictions) {
    $form['allowed']['#description'] = t('This user has a role(s) that exempts them from any Section restrictions.');
    $form['jcc_sections']['#access'] = FALSE;
    $form['jcc_restrict']['#access'] = FALSE;
  }

  // If listed user is NOT exempt from any section restrictions.
  if (!$listed_user_exempt_from_any_restrictions) {

    // If current user is allowed to admin the settings for the listed user.
    if ($current_user_has_access_to_set_sections) {
      $form['allowed']['#description'] = t('Please assign a section(s) for this user.');
    }

    // If current user is not allowed to admin the settings for the listed user.
    if (!$current_user_has_access_to_set_sections) {
      $sections = implode(', ', $section_service->getUserAllowedSections($listed_user));
      $form['allowed']['#description'] = t('This user is assigned to @sections. Only an admin can change the assigned sections.', ['@sections' => $sections]);
      $form['jcc_sections']['#access'] = FALSE;
      $form['jcc_restrict']['#access'] = FALSE;

      if ($current_account->id() == $listed_user->id()) {
        $form['allowed']['#description'] = t('Your are assigned to sections @sections. Contact an admin to change the assigned sections.', ['@sections' => $sections]);
      }
    }
  }
}

/**
 * Implements hook_form_ENTITY_FORM_alter().
 */
function jcc_elevated_sections_form_node_form_alter(&$form, FormStateInterface $form_state) {
  // Set some variables.
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $node = $form_state->getFormObject()->getEntity();
  $entity_manager = \Drupal::entityTypeManager();
  $account = \Drupal::currentUser();
  $user = $entity_manager->getStorage('user')->load($account->id());

  // Determine if Node type is sectionable.
  $is_sectionable = $section_service->isNodeSectionable($node->bundle());

  if ($is_sectionable) {

    // Get the allowed sections for current user.
    $user_is_restricted = (bool) $section_service->userIsRestrictedFromGeneralContent($user);

    $form['section'] = [
      '#type' => 'details',
      '#title' => t('Section'),
      '#open' => TRUE,
      '#weight' => -9,
      '#group' => 'advanced',
      '#description' => t('Assign this node to a section.'),
    ];
    $form['jcc_section']['#group'] = 'section';

    // Clarify the General content value.
    if (isset($form['jcc_section']['widget']['#options']['_none'])) {
      $form['jcc_section']['widget']['#options']['_none'] = t('- No section (General content) -');
    }

    // If user is restricted from general content editing, require that they
    // add the content to Section.
    $form['jcc_section']['widget']['#required'] = $user_is_restricted;

    if (!$section_service->userCanAdminSections($user)) {

      // If user doesn't have free access to admin sections for content, then
      // we assume the user is restricted to specific sections. We limit the
      // list of allowable options that the content can be sectioned to.
      $forbidden = $section_service->getUserForbiddenSections($user);
      foreach ($form['jcc_section']['widget']['#options'] as $id => $value) {
        if (isset($forbidden[$id])) {
          unset($form['jcc_section']['widget']['#options'][$id]);
        }
      }

      if ($user_is_restricted) {
        unset($form['jcc_section']['widget']['#options']['_none']);
      }
    }
  }

  if (!$is_sectionable) {
    $form['jcc_section']['#access'] = FALSE;
    $form['section']['#access'] = FALSE;
  }

}

/**
 * Implements hook_form_ENTITY_FORM_alter().
 */
function jcc_elevated_sections_form_media_form_alter(&$form, FormStateInterface $form_state) {
  // Set some variables.
  $section_service = Drupal::service('jcc_elevated_sections.service');
  $media = $form_state->getFormObject()->getEntity();
  $entity_manager = \Drupal::entityTypeManager();
  $account = \Drupal::currentUser();
  $user = $entity_manager->getStorage('user')->load($account->id());
  $is_sectionable = $section_service->isMediaSectionable($media->bundle());

  if (!$is_sectionable) {

    // Get sections of a user.
    $user_is_restricted = (bool) $section_service->userIsRestrictedFromGeneralContent($user);

    $form['section'] = [
      '#type' => 'details',
      '#title' => t('Section'),
      '#open' => TRUE,
      '#weight' => 20,
      '#group' => 'advanced',
      '#description' => t('Assign this node to a section.'),
    ];

    $form['jcc_section']['#group'] = 'section';

    // Clarify the General content value.
    if (isset($form['jcc_section']['widget']['#options']['_none'])) {
      $form['jcc_section']['widget']['#options']['_none'] = t('- No section (General content) -');
    }

    // If user is restricted from general content editing, require that they
    // add the content to Section.
    $form['jcc_section']['widget']['#required'] = $user_is_restricted;

    if (!$section_service->userCanAdminSections($user)) {

      // If user doesn't have free access to admin sections for content, then
      // we assume the user is restricted to specific sections. We limit the
      // list of allowable options that the content can be sectioned to.
      $forbidden = $section_service->getUserForbiddenSections($user);
      foreach ($form['jcc_section']['widget']['#options'] as $id => $value) {
        if (isset($forbidden[$id])) {
          unset($form['jcc_section']['widget']['#options'][$id]);
        }
      }

      if ($user_is_restricted) {
        unset($form['jcc_section']['widget']['#options']['_none']);
      }
    }
  }

  if (!$is_sectionable) {
    $form['jcc_section']['#access'] = FALSE;
    $form['section']['#access'] = FALSE;
  }

}
