<?php

namespace Drupal\jcc_custom\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\protected_pages\ProtectedPagesStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for listing protected pages.
 */
class ProtectedPagesController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The protected pages storage service.
   *
   * @var \Drupal\protected_pages\ProtectedPagesStorage
   */
  protected $protectedPagesStorage;

  /**
   * Constructs a ProtectedPagesController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\protected_pages\ProtectedPagesStorage $protectedPagesStorage
   *   The protected pages storage service.
   */
  public function __construct(RendererInterface $renderer, ProtectedPagesStorage $protectedPagesStorage) {
    $this->renderer = $renderer;
    $this->protectedPagesStorage = $protectedPagesStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('protected_pages.storage')
    );
  }

  /**
   * Generate the list of protected pages.
   */
  public function protectedPagesList() {
    $content = [];

    $content['message'] = [
      '#markup' => $this->t('<p>Use the protected pages feature for “Internal Only” data – information that can be shared and used within the company.</p><ul><li>Do not use this page for “Confidential” data – disclosure of which can lead to financial loss and harm to the organization, employees, and partners.</li><li>Do not use this page for “Classified” data – highly sensitive information that may lead to permanent damage when disclosed.</li></ul><p>Since the password for this page is shared in plain text via email, assume that an unintended person will be able to access this information.  If you need to protect data with higher level of sensitivity, consult the Judicial Council for other protection mechanisms.</p>'),
    ];

    $rows = [];
    $headers = [
      $this->t('#'),
      $this->t('Relative Path'),
      $this->t('Operations'),
    ];
    $count = 1;
    $result = $this->protectedPagesStorage->loadAllProtectedPages();
    foreach ($result as $page) {
      $operation_drop_button = [
        [
          '#type' => 'dropbutton',
          '#links' =>
          [
            'edit-protected-page' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromUri('internal:/admin/config/system/protected_pages/' . $page->pid . '/edit'),
            ],
            'delete-protected-page' => [
              'title' => $this->t('Remove Password'),
              'url' => Url::fromUri('internal:/admin/config/system/protected_pages/' . $page->pid . '/delete'),
            ],
            'send-email' => [
              'title' => $this->t('Send E-mail'),
              'url' => Url::fromUri('internal:/admin/config/system/protected_pages/' . $page->pid . '/send_email'),
            ],
          ],
        ],
      ];

      $operations = $this->renderer->render($operation_drop_button);
      $rows[] = [
        'data' =>
        [
          $count,
          Html::escape($page->path),
          $operations,
        ],
      ];
      $count++;
    }
    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No records available.'),
    ];
    $content['pager'] = ['#type' => 'pager'];

    return $content;
  }

}
