<?php

namespace Drupal\jcc_messaging_center\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for Messaging center.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_messaging_center_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'jcc_messaging_center.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('jcc_messaging_center.settings');

    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();

    $default_value = [];
    if($config->get('messaging_content_types') != NULL){
      $default_value = $config->get('messaging_content_types');
    }
    $default_value['custom_email'] = 'custom_email';

    $footer_form_value = FALSE;
    if($config->get('messaging_display_footer_form') != NULL){
      $footer_form_value = $config->get('messaging_display_footer_form');
    }

    $types_options = [];
    foreach ($types as $node_type) {
      $types_options[$node_type->id()] = $node_type->label();
    }

    $form_state->setCached(FALSE);

    $form['text_header'] = array
    (
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t('The messaging feature lets you send email notifications to specific mailing groups when an entity on the site is edited/created.<br>'),
    );

    $form['messaging'] = array(
      '#type' => 'details',
      '#title' => 'Messaging settings',
      '#group' => 'advanced',
    );

    $form['messaging']['text_header'] = array
    (
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t('<br>The ability to send out email notifications to groups is enabled per content type.<br>
        You can also create a custom email, where you can use the wysiwyg to integrate custom links and content. This custom email content type is only used to send email and will not appear on the site.<br>'),
    );

    $form['messaging']['messaging_content_types'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Content types available for email notification'),
      '#options' => $types_options,
      '#default_value' => $default_value,
    );

    $form['messaging']['text_form_header'] = array
    (
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t('<div class="claro-details__description">Pick which content type should have the option available. 
        <br>If selected, the editing page of each node from this content type will have a "Messaging options" tab appear, as show on the screenshot below:</div><br>
        <img src="/modules/custom/jcc_messaging_center/images/info-messaging.png" alt="help messaging" width="200px"/>'),
    );

    $form['subscription'] = array(
      '#type' => 'details',
      '#title' => 'Subscription settings',
      '#group' => 'advanced',
    );

    $form['subscription']['messaging_way1'] = array
    (
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t('
        <h2>Subscription</h2>Visitors can access the subscription page through a link in the footer.<br>
        Newly created users will recieve an email with a link to manage their subscriptions or cancel them.<br><br>
      '),
    );

    $form['subscription']['messaging_display_footer_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display user subscription form in footer'),
      "#default_value" => $footer_form_value,
    );


    $form['subscription']['messaging_way2'] = array
    (
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => t('<hr>It is possible to make a page relevant to a specific subscription category.<br>
        The footer form will then be redirecting the visitor to a pre-selected subscription page. You can decide on which content type this feature is enabled.<br><br>
        <img src="/modules/custom/jcc_messaging_center/images/info-subscription.png" alt="help footer" width="300px"/>
        <br><div class="claro-details__description">(This feature is only enabled for subpages by default - to make it available for other content types please ask your developer to set them up correctly.)</div><br>
      '),
    );

    $form['users'] = array(
      '#type' => 'details',
      '#title' => 'Users settings',
      '#group' => 'advanced',
    );

    $form['users']['messaging_helper'] = array
    (
      '#markup' => t('<br>Every user that signs in for email notification is technically a drupal user with no other right but updating his/her subscription options.<br>
        You can manage and view all users / groups with links below : <br><br>
        <li><a href="/admin/structure/taxonomy/manage/user_groups/overview">Manage mailing groups (Taxonomy)</a></li>
        <li><a href="/admin/messenger/group-overview">Users and groups dashboard</a></li>
        </ul><br>')
        );



    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('jcc_messaging_center.settings');

    $config->set('messaging_content_types', $form_state->getValue('messaging_content_types'))->save();
    $config->set('messaging_display_footer_form', $form_state->getValue('messaging_display_footer_form'))->save();

    parent::submitForm($form, $form_state);
  }
}
