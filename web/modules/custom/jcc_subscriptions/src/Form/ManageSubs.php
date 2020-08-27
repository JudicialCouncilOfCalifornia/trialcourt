<?php

namespace Drupal\jcc_subscriptions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MarkRoland\Emma\Client;

/**
 * Deletes all groups from user.
 */
class ManageSubs extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'manage_subs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $member_email = '') {

    $emma_config = \Drupal::config('webform_myemma.settings');
    $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));
    $user = $emma->get_member_detail_by_email($member_email);

    // Getting groups from myemma / only keeping ones with naming convention.
    $myemma_groups = $emma->list_groups();
    $form_groups = [];
    foreach ($myemma_groups as $group) {
      if (strpos($group->group_name, 'Newsroom mailing') !== FALSE && strpos($group->group_name, 'Statewide Media') == FALSE) {
        $form_groups[$group->member_group_id] = str_replace('Newsroom mailing ', '', $group->group_name);
      }
    }

    // Creating list of groups form myEmma.
    $form['myemma_groups'] = [
      '#type' => 'checkboxes',
      '#options' => $form_groups,
      '#title' => t('Manage subscriptions:'),
    ];

    // Populating default values.
    if (!isset($user->error)) {
      // pre-populate active categories.
      $emma_user_id = $user->member_id;
      $user_groups_object = $emma->list_member_groups($emma_user_id);
      $user_groups = [];
      foreach ($user_groups_object as $group_objects) {
        array_push($user_groups, $group_objects->member_group_id);
      }
      $form['myemma_groups']['#default_value'] = $user_groups;
    }

    $form['email'] = [
      '#type' => 'value',
      '#value' => $member_email,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $member_email = $form['email']['#value'];
    $emma_config = \Drupal::config('webform_myemma.settings');
    $emma = new Client($emma_config->get('account_id'), $emma_config->get('public_key'), $emma_config->get('private_key'));

    $user_req = $emma->get_member_detail_by_email($member_email);
    $user_emma_id = $user_req->member_id;
    // Create / Update user.
    $fields = [
      'first_name' => $member_email,
    ];
    $groups = [];
    $groups_to_remove = [];
    foreach ($form_state->cleanValues()->getValues()['myemma_groups'] as $key => $val) {
      if ($val !== 0) {
        $groups[] = $val;
      }
      else {
        $groups_to_remove[] = $key;
      }
    }
    $emma->import_single_member($member_email, $fields, $groups);
    // Need an extra call to account for groups to remove.
    $emma->remove_member_from_groups($user_emma_id, $groups_to_remove);
  }

}
