<?php

namespace Drupal\jcc_blocks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Core\Routing;

/**
 * Provides the form for adding countries.
 */
class FormFieldsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dn_student_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    

    
    $form['fname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
      '#maxlength' => 20,
      '#default_value' => '',
    ];
    
    $form['crust_size'] = array(
        '#title' => t('Crust Size'),
        '#type' => 'select',
        '#description' => 'Select the desired pizza crust size.',
        '#options' => array(t('--- SELECT ---'), t('10"'), t('12"'), t('16"')),
      );
      $form['life_story'] = array(
        '#title' => t('Your Life Story'),
        '#type' => 'textarea',
      );
      # an html checkbox for our drupal form

# the options to display in our checkboxes
$toppings = array(
    'pepperoni' => t('Pepperoni'),
    'black_olives' => t('Black olives'), 
    'veggies' => t('Veggies')
  );
  
  # the drupal checkboxes form field definition
  $form['pizza'] = array(
    '#title' => t('Pizza Toppings'),
    '#type' => 'checkboxes',
    '#description' => t('Select the pizza toppings you would like.'),
    '#options' => $toppings,
  );
  # an html radio button widget for a drupal form

# the options to display in our form radio buttons
$options = array(
    'punt' => t('Punt'),
    'field_goal' => t('Kick field goal'), 
    'run' => t('Run'),
    'pass' => t('Pass'),
  );
  
  $form['fourth_down'] = array(
    '#type' => 'radios',
    '#title' => t('What to do on fourth down'),
    '#options' => $options,
    '#description' => t('What would you like to do on fourth down?'),
    '#default_value' => $options['punt'],
  );
      
		
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#default_value' => $this->t('Save') ,
    ];
      $form['#theme'] = 'formfields_form';
    return $form;

  }

   /**
   * {@inheritdoc}
   */
  public function validateForm(array & $form, FormStateInterface $form_state) {
        //print_r($form_state->getValues());exit;
		
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array & $form, FormStateInterface $form_state) {

    \Drupal::messenger()->addMessage('Test here');
   
  }

}