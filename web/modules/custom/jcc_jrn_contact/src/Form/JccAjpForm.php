<?php

namespace Drupal\jcc_jrn_contact\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the jcc ajp entity edit forms.
 */
class JccAjpForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New jcc ajp %label has been created.', $message_arguments));
      $this->logger('jcc_jrn_contact')->notice('Created new jcc ajp %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The jcc ajp %label has been updated.', $message_arguments));
      $this->logger('jcc_jrn_contact')->notice('Updated new jcc ajp %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.jcc_ajp.canonical', ['jcc_ajp' => $entity->id()]);
  }

}
