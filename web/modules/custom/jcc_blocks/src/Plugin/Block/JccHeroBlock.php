<?php

namespace Drupal\jcc_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'JccHeroBlock' block.
 *
 * @Block(
 *  id = "jcc_hero_block",
 *  admin_label = @Translation("JCC Hero Block"),
 * )
 */
class JccHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * Account interface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Block plugin construct.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Block plugin create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
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
    $form['background_image'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Background image'),
      '#default_value' => $this->configuration['background_image'],
      '#description' => $this->t('Upload or select the background image.'),
      '#weight' => '0',
    ];
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

    $build['#hero_icon_nav']['hero_banner'] = [
      'brow' => $this->configuration['brow'],
      'title' => $this->configuration['title'],
      'subtitle' => $this->configuration['subtitle'],
    ];
    $build['#hero_icon_nav']['media'] = [
      'src' => $this->getMediaUrl($this->configuration['background_image']),
      'alt' => $this->getMediaAlt($this->configuration['background_image']),
      'renderer' => 'patternlab',
    ];

    $placeholder = FALSE;
    if ($this->moduleHandler->moduleExists('contextual') && $this->account->hasPermission('access contextual links')) {
      $contextual_links = [
        'menu' => [
          'route_parameters' => [
            'menu' => 'featured-links',
          ],
        ],
      ];
      $placeholder = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($contextual_links),
      ];
    }

    $build['#hero_icon_nav']['contextual_links'] = $placeholder;
    if ($this->configuration['featured_links']) {
      $build['#hero_icon_nav']['card_section'] = [
        'theme' => 'jcc-cards--tiles',
        'cards' => self::getFeaturedLinks(),
      ];
    }

    return $build;
  }

  /**
   * Get the menu items from Featured Links.
   */
  public static function getFeaturedLinks() {
    $menu_name = 'featured-links';
    $menu_tree = \Drupal::menuTree();
    $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMinDepth(0);
    $parameters->onlyEnabledLinks();

    $tree = $menu_tree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $links = [];

    $index = 0;
    foreach ($tree as $item) {
      if ($index > 4) {
        break;
      }

      $menu_item_extra = \Drupal::entityTypeManager()
        ->getStorage('menu_link_content')
        ->loadByProperties(
          ['uuid' => $item->link->getDerivativeId()]
        );
      $entity = array_pop($menu_item_extra);
      $icon = $entity->field_featured_type->value;
      $link_title = $item->link->getTitle();

      $links[] = [
        'title' => $link_title,
        'action' => [
          'style' => 'card',
          'url' => $item->link->getUrlObject()->toString(),
        ],
        'width' => 'grid-col-6',
        'media_style' => "icon",
        'media' => [
          'src' => '/libraries/courtyard-artifact/1.x/public/images/icons/light/' . $icon . '.svg',
          'alt' => $link_title . ' decorative image',
          'renderer' => 'patternlab',
        ],
      ];

      $index++;
    }

    return $links;
  }

  /**
   * Get the url of the file attached to media.
   */
  public static function getMediaUrl($media) {
    $background_image_url = '';
    if ($media) {
      $background_image_media_entity = is_numeric($media) ? Media::load($media) : $media;
      $media_file_id = $background_image_media_entity->getSource()->getSourceFieldValue($background_image_media_entity);
      $file_entity = File::load($media_file_id);
      $background_image_url = $file_entity->createFileUrl(FALSE);
    }

    return $background_image_url;
  }

  /**
   * Get the alt for the media.
   */
  public static function getMediaAlt($media) {
    $background_image_alt = '';
    if ($media) {
      $background_image_alt = Media::load($media)->field_media_image->alt;
    }

    return $background_image_alt;
  }

}
