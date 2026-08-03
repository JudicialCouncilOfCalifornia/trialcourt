<?php

/**
 * @file
 * Theme and preprocess functions for forms.
 */

/**
 * Implements hook_form_alter().
 */
function jcc_newsroom_form_alter(&$form, $form_state, $form_id) {
  switch ($form_id) {
    case 'user_register_form':
      $form['account']['mail']['#title'] = t('Enter your email address:');
      break;
  }
}
