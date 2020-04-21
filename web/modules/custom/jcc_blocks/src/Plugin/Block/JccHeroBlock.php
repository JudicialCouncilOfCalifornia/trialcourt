<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;


/**
 * Provides a 'JccHeroBlock' block.
 *
 * @Block(
 *  id = "jcc_hero_block",
 *  admin_label = @Translation("JCC Hero Block"),
 * )
 */
class JccHeroBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['brow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Intro'),
      '#default_value' => $this->configuration['brow'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->configuration['title'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['subtitle'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $this->configuration['subtitle'],
      '#weight' => '0',
    ];
    $form['background_image'] = array(
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => t('Background image'),
      '#default_value' => $this->configuration['background_image'],
      '#description' => t('Upload or select the background image.'),
      '#weight' => '0',
    );
    $form['featured_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Featured Links'),
      '#default_value' => $this->configuration['featured_links'],
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['brow'] = $form_state->getValue('brow');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['subtitle'] = $form_state->getValue('subtitle');
    $this->configuration['background_image'] = $form_state->getValue('background_image');
    $this->configuration['featured_links'] = $form_state->getValue('featured_links');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'jcc_hero_block';
    $build['#hero_icon_nav'] = [
      'background_img' => $this->getMediaUrl($this->configuration['background_image']),
      'brow' => $this->configuration['brow'],
      'title' => $this->configuration['title'],
      'subtitle' => $this->configuration['subtitle'],
    ];

    if ($this->configuration['featured_links']) {
      $build['#hero_icon_nav']['links'] = $this->getFeaturedLinks();
    }

    return $build;
  }

  public function getFeaturedLinks() {
    $menu_name = 'featured_links';
    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMinDepth(0);
    $parameters->onlyEnabledLinks();

    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);
    $links = [];

    $index = 0;
    foreach ($tree as $item) {
      if ($index > 6) {
        break;
      }

      $entity = array_pop(\Drupal::entityTypeManager()
        ->getStorage('menu_link_content')
        ->loadByProperties(
            ['uuid' => $item->link->getDerivativeId()]
        ));
       $icon = $this->getMediaUrl($entity->get('field_icon')->entity);

       $links[$index < 4 ? 'icons' : 'buttons'][] = [
         'title' => $item->link->getTitle(),
         'url' => $item->link->getUrlObject()->toString(),
         'icon' => $index < 4 ? $icon : '',
       ];

       $index++;
    }

    return $links;
  }

  public function getMediaUrl($media) {
    $background_image_url = '';
    if ($media) {
      $background_image_media_entity = is_numeric($media) ? Media::load($media) : $media;
      $media_file_id = $background_image_media_entity->getSource()->getSourceFieldValue($background_image_media_entity);
      $file_entity = File::load($media_file_id);
      $background_image_url = $file_entity->url();
    }

    return $background_image_url;
  }

}
