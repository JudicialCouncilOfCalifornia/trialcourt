<?php

namespace Drupal\jcc_book_navigation\Plugin\Block;

use Drupal\book\Plugin\Block\BookNavigationBlock;
use Drupal\node\NodeInterface;

/**
 * Provides a 'JccBookNavigationBlock' block.
 *
 * @Block(
 *  id = "jcc_book_navigation_block",
 *  admin_label = @Translation("JCC Book Navigation Block"),
 *   category = @Translation("Menus")
 * )
 */
class JccBookNavigationBlock extends BookNavigationBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    parent::defaultConfiguration();

    return [
      'icon_library' => "pattern_library",
      'nav_icon_expand' => "expand_more",
      'nav_icon_collapse' => "expand_less",
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['book_block_icons'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Book navigation icons'),
    ];

    $icon_options = [
      'pattern_library' => $this->t('Use default pattern library icons'),
      'other' => $this->t('Use other icon library such as FontAwesome'),
    ];
    $form['book_block_icons']['book_block_icon_library'] = [
      '#type' => 'radios',
      '#title' => $this->t('Icon library'),
      '#options' => $icon_options,
      '#default_value' => $this->configuration['icon_library'],
      '#description' => $this->t("Select the icon library that you want to use within the navigation"),
    ];

    $form['book_block_icons']['book_block_child_expand'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expand child pages indicator'),
      '#default_value' => $this->configuration['nav_icon_expand'],
      '#description' => $this->t("To change the default chevron theme, enter the pattern library icon name or classes for the icon theme that you want to use. (Pattern library default is <em>expand_more</em>)"),
    ];

    $form['book_block_icons']['book_block_child_collapse'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collapse child pages indicator'),
      '#default_value' => $this->configuration['nav_icon_collapse'],
      '#description' => $this->t("To change the default chevron theme, enter the pattern library icon name or classes for the icon theme that you want to use. (Pattern library default is <em>expand_less</em>)"),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    foreach ($this->defaultConfiguration() as $key => $default_value) {
      $this->configuration[$key] = $values[$key];
    }

    $this->configuration['icon_library'] =
      $form_state->getValue(['book_block_icons', 'book_block_icon_library']);
    $this->configuration['nav_icon_expand'] =
      $form_state->getValue(['book_block_icons', 'book_block_child_expand']);
    $this->configuration['nav_icon_collapse'] =
      $form_state->getValue(['book_block_icons', 'book_block_child_collapse']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && !empty($node->book['bid'])) {
      $current_bid = $node->book['bid'];
    }
    if ($this->configuration['block_mode'] == 'all pages') {
      return parent::build();
    }
    elseif ($current_bid) {
      // Only display this block when the user is browsing a book and do
      // not show unpublished books.
      $nid = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('nid', $node->book['bid'], '=')
        ->condition('status', NodeInterface::PUBLISHED)
        ->execute();
      // Only show the block if the user has view access for the top-level node.
      if ($nid) {
        $tree = $this->bookManager->bookTreeAllData($node->book['bid'], $node->book);

        // Search for the book root.
        $book_parent_key = '';
        foreach ($tree as $key => $book) {
          if ($book['link']['nid'] == $current_bid) {
            $book_parent_key = $key;
            break;
          }
        }
        // Put it at the start of the tree.
        if ($book_parent_key) {
          $book_parent = $tree[$book_parent_key];
          unset($tree[$book_parent_key]);
          array_unshift($tree, $book_parent);
        }

        $build = $this->bookManager->bookTreeOutput($tree);
        $build['#theme'] = 'jcc_book_navigation_block';

        // Add active trail to theme.
        $active_trail = $this->bookManager->getActiveTrailIds($node->book['bid'], $node->book);
        $build['#active_trail'] = $active_trail;

        // Add icon library preferences.
        $build['#icon_library'] = $this->configuration['icon_library'];
        $build['#nav_icon_expand'] = $this->configuration['nav_icon_expand'];
        $build['#nav_icon_collapse'] = $this->configuration['nav_icon_collapse'];

        return $build;
      }
    }
    return [];
  }

}
