<?php

namespace Drupal\jcc_messaging_center\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;

class RegistrationConfirmation extends ControllerBase {

  /**
   * Temp store.
   *
   * @var Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Class constructor.
   */
  public function __construct(SharedTempStoreFactory $tempstore) {
    $this->tempstore = $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.shared')
    );
  }

  /**
   * Returns a simple message page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function registrationconfirmation() {
    return [
      '#markup' => '<div class="jcc-section--container section__content box"><div class="jcc-section jcc-section--background-alt container stack"><div class="jcc-section__inner">' . t("<h1>Thank you!</h1>A welcome message with further instructions has been sent to your email address. If you have not seen it check your spam folder.") . '<br><br><a class="usa-button usa-button--primary" href="/">Home</a></div></div></div>',
    ];
  }
}
