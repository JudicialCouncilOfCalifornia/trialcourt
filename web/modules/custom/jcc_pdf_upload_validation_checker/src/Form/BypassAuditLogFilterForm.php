<?php

namespace Drupal\jcc_pdf_upload_validation_checker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Exposed-style GET filters for the PDF bypass audit log report.
 */
final class BypassAuditLogFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jcc_pdf_upload_validation_checker_bypass_audit_log_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $query = $this->getRequest()->query;

    $form['#method'] = 'get';
    $form['#action'] = Url::fromRoute('jcc_pdf_upload_validation_checker.bypass_audit_log')->toString();
    $form['#attributes']['class'][] = 'views-exposed-form';
    $form['#attributes']['class'][] = 'jcc-bypass-audit-filters';
    $form['#attributes']['class'][] = 'form--inline';
    $form['#attributes']['class'][] = 'container-inline';

    $form['media'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Media ID'),
      '#default_value' => (string) $query->get('media', ''),
      '#size' => 24,
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User ID'),
      '#default_value' => (string) $query->get('user', ''),
      '#size' => 24,
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['from'] = [
      '#type' => 'date',
      '#title' => $this->t('From'),
      '#default_value' => (string) $query->get('from', ''),
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['to'] = [
      '#type' => 'date',
      '#title' => $this->t('To'),
      '#default_value' => (string) $query->get('to', ''),
      '#wrapper_attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
    ];

    $form['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('jcc_pdf_upload_validation_checker.bypass_audit_log'),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op. GET forms rely on query parameters, no config is persisted.
  }

}
