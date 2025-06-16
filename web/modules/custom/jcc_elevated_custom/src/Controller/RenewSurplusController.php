<?php

namespace Drupal\jcc_elevated_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for renewing surplus materials.
 */
class RenewSurplusController extends ControllerBase {

  /**
    * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RenewSurplusController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Renews the surplus node by updating its renewal date.
   *
   * @param int $node
   *   The node ID of the surplus material.
   *   Redirects to the canonical node page.
   */
  public function renew($node) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->load($node);
    $node = Node::load($node);
    if ($node->bundle() === 'surplus_materials') {
      if ($node->hasField('field_renewal_date')) {
        $current_date = new \DateTime();
        $new_date = $current_date->modify('+60 days');
        $node->set('field_renewal_date', $new_date->format('Y-m-d'));
        $node->save();
        return new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString());
      }
    }
  }

}
