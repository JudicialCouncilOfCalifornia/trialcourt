<?php

namespace Drupal\bulk_ban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\ban\BanIpManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a textarea to dd IP addresses in bulk to the ban list.
 *
 * @internal
 */
class BulkBan extends FormBase {

  /**
   * @var \Drupal\ban\BanIpManagerInterface
   */
  protected $ipManager;

  /**
   * Constructs a new BulkBan object.
   *
   * @param \Drupal\ban\BanIpManagerInterface $ip_manager
   */
  public function __construct(BanIpManagerInterface $ip_manager) {
    $this->ipManager = $ip_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('bulk_ban.ip_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_ban_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['ips'] = [
      '#title' => $this->t('IP addresses'),
      '#type' => 'textarea',
      '#size' => 48,
      '#description' => $this->t('Enter one valid IP address per line.'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ips = trim($form_state->getValue('ips'));
    $ips = preg_split('/\r\n|[\r\n]/', $ips);
    $ips = array_filter($ips, 'trim');
    $errors = [];

    foreach ($ips as $ip) {
      if ($this->ipManager->isBanned($ip)) {
        $errors[] = $this->t('This IP address is already banned: :ip', [':ip' => $ip]);
      }
      elseif ($ip == $this->getRequest()->getClientIP()) {
        $errors[] = $this->t('You may not ban your own IP address: :ip', [':ip' => $ip]);
      }
      elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) == FALSE) {
        $errors[] = $this->t('IP address is not valid: :ip', [':ip' => $ip]);
      }
    }

    if ($errors) {
      $error_message = implode('<br>', $errors);
      $form_state->setErrorByName('ips', Markup::create($error_message));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ips = trim($form_state->getValue('ips'));
    $ips = preg_split('/\r\n|[\r\n]/', $ips);
    $ips = array_filter($ips, 'trim');

    foreach ($ips as $ip) {
      $this->ipManager->banIp($ip);
    }

    $this->messenger()->addStatus($this->t('The following IP addresses have been banned:'));
    $ip_list = [
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#items' => $ips,
    ];
    $this->messenger()->addStatus($ip_list);
    $form_state->setRedirect('ban.admin_page');
  }

}
