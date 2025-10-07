<?php

namespace Drupal\jcc_elevated_embeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Directory Block'.
 *
 * @Block(
 *   id = "jrn_directory",
 *   admin_label = @Translation("JRN Directory block")
 * )
 */
class JRNDirectoryBlock extends BlockBase {

  /**
   * Constructs a new MyClass object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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
   * {@inheritdoc}
   */
  public function build() {
    $courts = $this->entityTypeManager->getStorage('jcc_court')->loadByProperties(['status' => 1]);
    $court_options = [];
    foreach ($courts as $court) {
      $name1 = $court->get('name_1')->value ?? '';
      $name2 = $court->get('name_2')->value ?? '';
      $name3 = $court->get('name_3')->value ?? '';
      $combined_name = trim($name1 . ' ' . $name2 . ' ' . $name3);
      $court_options[$court->id()] = $combined_name;
    }
    return [
      '#theme' => 'block__jrn_directory',
      '#title' => $this->t('JCC Directory'),
      'jcc_court_options' => $court_options,
    ];
  }

}
