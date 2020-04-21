<?php

namespace Drupal\jcc_block_field_decorator;

use Drupal\block_field\BlockFieldManager;
use Drupal\block_field\BlockFieldManagerInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;

class BlockFieldManagerDecorator extends BlockFieldManager {

  /**
   * The inner service.
   *
   * @var \Drupal\block_field\BlockFieldManagerInterface
   */
  protected $innerBlockManager;


  /**
   * Constructs inner BlockFieldManager service.
   *
   * @param \Drupal\block_field\BlockFieldManagerInterface $inner_block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository.
   */
  public function __construct(BlockFieldManagerInterface $inner_block_manager, BlockManagerInterface $block_manager, ContextRepositoryInterface $context_repository) {
    $this->innerBlockManager = $inner_block_manager;
    parent::__construct($block_manager, $context_repository);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockDefinitions() {
    $available_blocks = $this->innerBlockManager->getBlockDefinitions();

    $config = \Drupal::config('jcc_block_field_decorator.settings');
    $prohibited_blocks = $config->get('prohibited_blocks');

    foreach ($prohibited_blocks as $prohibited_block) {
      if ($prohibited_block) {
        unset($available_blocks[$prohibited_block]);
      }
    }

    return $available_blocks;
  }

  public function getBlockDefinitionsList() {
    $available_blocks = $this->innerBlockManager->getBlockDefinitions();

    $list = [];
    foreach ($available_blocks as $id => $definition) {
      $category = (string) $definition['category'];
      $list[$id] = $category . ': ' . $definition['admin_label'];
    }
    return $list;
  }

}
