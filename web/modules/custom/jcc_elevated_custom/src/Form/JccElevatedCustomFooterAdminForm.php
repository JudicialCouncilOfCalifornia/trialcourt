<?php

namespace Drupal\jcc_elevated_custom\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JCC Elevated Custom configuration form for Footer settings.
 */
class JccElevatedCustomFooterAdminForm extends FormBase {

  /**
   * Entity manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * The state store.
   *
   * @var Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * The messenger interface.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Creates a MyForm instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StateInterface $state, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_elevated_custom_footer_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'footer_variant' => $this->state->get('jcc_elevated.footer_variant'),
      'upper_footer_menu' => $this->state->get('jcc_elevated.upper_footer_menu'),
      'lower_footer_menu' => $this->state->get('jcc_elevated.lower_footer_menu'),
      'about_header' => $this->state->get('jcc_elevated.about_header'),
      'about_text' => $this->state->get('jcc_elevated.about_text'),
      'about_link_title' => $this->state->get('jcc_elevated.about_link_title'),
      'about_link_url' => $this->state->get('jcc_elevated.about_link_url'),
      'about_link_aria_label' => $this->state->get('jcc_elevated.about_link_aria_label'),
      'social_links_facebook' => $this->state->get('jcc_elevated.social_links_facebook'),
      'social_links_linkedin' => $this->state->get('jcc_elevated.social_links_linkedin'),
      'social_links_rss' => $this->state->get('jcc_elevated.social_links_rss'),
      'social_links_twitter' => $this->state->get('jcc_elevated.social_links_twitter'),
      'social_links_youtube' => $this->state->get('jcc_elevated.social_links_youtube'),
      'social_links_flickr' => $this->state->get('jcc_elevated.social_links_flickr'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $this->state->set('jcc_elevated.' . $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $state = $this->getState();

    $link = Link::createFromRoute('Theme settings', 'system.theme_settings_theme', ['theme' => 'jcc_elevated'])->toString();
    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Configure the specific settings for this site. Some settings are set in the @theme_settings_link page.', ['@theme_settings_link' => $link]),
    ];

    $form['footer'] = [
      '#type' => 'fieldset',
      '#title' => $this
        ->t('Footer: General settings'),
      '#open'  => TRUE,
    ];

    $form['footer']['footer_variant'] = [
      '#type' => 'radios',
      '#title' => $this
        ->t('Style variant'),
      '#options' => [
        'default' => $this->t('Default - gray background / black text'),
        'alternate' => $this->t('Alternate - blue background / white text'),
      ],
      '#default_value' => $state['footer_variant'] ?? 'default',
    ];

    $form['footer']['upper_footer_menu'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Upper navigation'),
      '#description' => $this
        ->t('Select a menu to display in the footer (upper footer), inline with the footer logo. Leave unset to not display a menu.'),
      '#options' => $this
        ->getAllMenus(TRUE),
      '#default_value' => $state['upper_footer_menu'] ?? 'footer',
    ];

    $form['footer']['lower_footer_menu'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Lower navigation'),
      '#description' => $this
        ->t('Select a menu to display in the footer (lower footer), below the footer logo and inline with the about content. <strong>Leave unset to not display a lower footer menu or anything in the lower footer section. This value must be set to trigger any display of the lower footer section.</strong>'),
      '#options' => $this
        ->getAllMenus(TRUE),
      '#default_value' => $state['lower_footer_menu'] ?? '',
    ];

    $form['about'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Footer: About us section'),
      '#description' => $this->t('This section will only display if a lower footer menu has been set.'),
      '#open'  => TRUE,
    ];

    $form['about']['about_header'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Header'),
      '#default_value' => $state['about_header'],
    ];

    $form['about']['about_text'] = [
      '#type' => 'text_format',
      '#title' => $this
        ->t('Text'),
      '#default_value' => $state['about_text']['value'] ?? '',
      '#default_format' => 'full_html',
    ];

    $form['about']['about_link'] = [
      '#type' => 'fieldset',
      '#title' => $this
        ->t('About section link'),
    ];

    $form['about']['about_link']['about_link_title'] = [
      '#title' => $this->t('Link title'),
      '#type' => 'textfield',
      '#default_value' => $state['about_link_title'] ?? '',
    ];

    $form['about']['about_link']['about_link_url'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#title' => $this->t('Link url'),
      '#description' => $this->t('Internal or external urls are allowed.'),
      '#required' => FALSE,
      '#process_default_value' => FALSE,
      '#default_value' => $state['about_link_url'] ?? '',
      '#attributes' => [
        'data-autocomplete-first-character-blacklist' => '/#?',
      ],
      '#element_validate' => [
        [
          'Drupal\link\Plugin\Field\FieldWidget\LinkWidget',
          'validateUriElement',
        ],
      ],
    ];

    $form['about']['about_link']['about_link_aria_label'] = [
      '#title' => $this->t('Link aria label'),
      '#type' => 'textfield',
      '#default_value' => $state['about_link_aria_label'] ?? '',
    ];

    // Process the url for an entity or an external URL.
    if (strpos($form['about']['about_link']['about_link_url']['#default_value'], 'entity:') === 0) {
      $value = explode('/', $form['about']['about_link']['about_link_url']['#default_value']);
      $entity_id = end($value);
      $entity = $this->entityTypeManager->getStorage($form['about']['about_link']['about_link_url']['#target_type'])->load($entity_id);
      $form['about']['about_link']['about_link_url']['#default_value'] = $entity_id ? $entity : '';
      $form['about']['about_link']['about_link_url']['#process_default_value'] = TRUE;
    }

    $form['social'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Footer: Social links'),
      '#description' => $this->t('This section will only display if a lower footer menu has been set.'),
      '#open'  => TRUE,
    ];

    $form['social_links_facebook'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Facebook'),
      '#default_value' => $state['social_links_facebook'],
      '#placeholder' => 'https://www.facebook.com/[name]/',
      '#group' => 'social',
    ];

    $form['social_links_linkedin'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('LinkedIn'),
      '#default_value' => $state['social_links_linkedin'],
      '#placeholder' => 'https://www.linkedin.com/company/[name]/',
      '#group' => 'social',
    ];

    $form['social_links_rss'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('RSS'),
      '#default_value' => $state['social_links_rss'],
      '#placeholder' => 'https://newsroom.courts.ca.gov/rss',
      '#group' => 'social',
    ];

    $form['social_links_twitter'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Twitter'),
      '#default_value' => $state['social_links_twitter'],
      '#placeholder' => 'https://twitter.com/[name]',
      '#group' => 'social',
    ];

    $form['social_links_youtube'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('YouTube'),
      '#default_value' => $state['social_links_youtube'],
      '#placeholder' => 'https://www.youtube.com/user/[name]',
      '#group' => 'social',
    ];

    $form['social_links_flickr'] = [
      '#type' => 'textfield',
      '#title' => $this
        ->t('Flickr'),
      '#default_value' => $state['social_links_flickr'],
      '#placeholder' => 'https://www.flickr.com/',
      '#group' => 'social',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Save settings'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Remove items we don't want to set state for.
    unset($values['submit']);
    unset($values['form_build_id']);
    unset($values['form_token']);
    unset($values['form_id']);
    unset($values['op']);

    foreach ($values as $key => $value) {
      $this->setState($key, $value);
    }

    // Let our guests know that all is updated and well.
    $this->messenger()->addMessage(
      $this->t('Settings updated.')
    );
  }

  /**
   * Gets menu names available for tracking.
   *
   * @param bool $check_access
   *   Only return menu names where the current user has access to "view label".
   *
   * @return array
   *   Menu Names available for tracking.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAllMenus(bool $check_access = TRUE) {
    $options = [];

    $eids = $this->entityTypeManager
      ->getStorage('menu')
      ->getQuery('AND')
      // The access check will check for "view" access. If we want to check
      // access, we want to check for "view label" access. So we'll disable
      // access checking for the query and check "view label" access separately.
      ->accessCheck(FALSE)
      ->execute();
    $menus = $this->entityTypeManager->getStorage('menu')->loadMultiple($eids);
    foreach ($menus as $name => $menu) {
      if ($check_access && !$menu->access('view label')) {
        continue;
      }
      $options[$name] = $menu->label();
    }
    asort($options);

    return ['_none_' => '- Select a menu -'] + $options;
  }

}
