<?php

namespace Drupal\jcc_elevated_custom\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * JCC Elevated Custom configuration form.
 */
class JccElevatedCustomNavigationAdminForm extends FormBase {

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
    return 'jcc_elevated_custom_navigation_admin_form';
  }

  /**
   * Get list of available content types.
   */
  private function getContentTypesList(): array {
    $types = [];
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    foreach ($content_types as $type) {
      $types[$type->id()] = $type->label();
    }

    return $types;
  }

  /**
   * Get list of available primary navigation styles.
   */
  private function getPrimaryNavigationStyles(): array {
    return [
      'default' => $this->t('Default style'),
    ];
  }

  /**
   * Get list of available secondary navigation styles.
   */
  private function getUtilityNavigationStyles(): array {
    return [
      'with-divider' => $this->t('"With divider" style'),
      'default' => $this->t('"No divider" style'),
    ];
  }

  /**
   * Get list of available contextual navigation styles.
   */
  private function getContextualNavigationStyles(): array {
    return [
      'default' => $this->t('Default style'),
      'alternate' => $this->t('Alternate style'),
    ];
  }

  /**
   * Get list of available contextual navigation placement options.
   */
  private function getContextualNavigationPlacement(): array {
    return [
      '' => $this->t('Disabled'),
      'sidebar' => $this->t('Sidebar (default)'),
      'header' => $this->t('Header'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return [
      'primary_menu' => $this->state->get('jcc_elevated.primary_menu'),
      'primary_menu_style' => $this->state->get('jcc_elevated.primary_menu_style'),
      'utility_menu' => $this->state->get('jcc_elevated.utility_menu'),
      'utility_menu_style' => $this->state->get('jcc_elevated.utility_menu_style'),
      'sidebar_menu_style' => $this->state->get('jcc_elevated.sidebar_menu_style'),
      'sidebar_menu_placement' => $this->state->get('jcc_elevated.sidebar_menu_placement'),
      'sidebar_menu_types' => $this->state->get('jcc_elevated.sidebar_menu_types'),
      'section_menu_style' => $this->state->get('jcc_elevated.section_menu_style'),
      'section_menu_placement' => $this->state->get('jcc_elevated.section_menu_placement'),
      'section_menu_types' => $this->state->get('jcc_elevated.section_menu_types'),
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

    $form['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this
        ->t('Configure general theme settings specific for this site.'),
    ];

    $form['primary'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Primary navigation'),
      '#open' => FALSE,
    ];

    $form['primary']['primary_menu'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Select primary menu'),
      '#description' => $this
        ->t('The primary menu displays in the header'),
      '#options' => $this
        ->getAllMenus(TRUE),
      '#default_value' => $state['primary_menu'] ?? 'main',
    ];

    $form['primary']['primary_menu_style'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Style'),
      '#description' => $this
        ->t('Select the style (storybook variant) to display the primary navigation.'),
      '#options' => $this
        ->getPrimaryNavigationStyles(),
      '#default_value' => $state['primary_menu_style'],
    ];

    $form['utility'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Utility navigation'),
      '#open' => FALSE,
    ];

    $form['utility']['utility_menu'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Select utility menu'),
      '#description' => $this
        ->t('The utility menu displays in the header, above the primary navigation.'),
      '#options' => $this
        ->getAllMenus(TRUE),
      '#default_value' => $state['utility_menu'] ?? 'featured-links',
    ];

    $form['utility']['utility_menu_style'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Style'),
      '#description' => $this
        ->t('Select the style (storybook variant) to display the utility navigation.'),
      '#options' => $this
        ->getUtilityNavigationStyles(),
      '#default_value' => $state['utility_menu_style'],
    ];

    $desc = [
      $this->t("Select content types that are allowed to render the contextual menu."),
      $this->t("If none are selected, the tertiary nav will try to render for all types."),
      $this->t("A menu will only show if the current node is added to a menu and it's 3rd level or deeper."),
    ];

    $form['sidebar'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Sidebar contextual navigation'),
      '#open' => FALSE,
    ];

    $form['sidebar']['sidebar_menu_placement'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Placement'),
      '#description' => $this
        ->t('Display the contextual menu in the header or sidebar area'),
      '#options' => $this
        ->getContextualNavigationPlacement(),
      '#default_value' => $state['sidebar_menu_placement'],
    ];

    $form['sidebar']['sidebar_menu_style'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Style'),
      '#description' => $this
        ->t('Select the style to display the contextual menu.'),
      '#options' => $this
        ->getContextualNavigationStyles(),
      '#default_value' => $state['sidebar_menu_style'],
    ];

    $form['sidebar']['sidebar_menu_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Content types'),
      '#description' => implode('<br/> ', $desc),
      '#options' => $this
        ->getContentTypesList(),
      '#default_value' => $state['sidebar_menu_types'],
    ];

    $form['section'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Section contextual navigation'),
      '#open' => FALSE,
    ];

    $form['section']['section_menu_placement'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Placement'),
      '#description' => $this
        ->t('Display the contextual menu in the header or sidebar area'),
      '#options' => $this
        ->getContextualNavigationPlacement(),
      '#default_value' => $state['section_menu_placement'],
    ];

    $form['section']['section_menu_style'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Style'),
      '#description' => $this
        ->t('Select the style to display the contextual menu.'),
      '#options' => $this
        ->getContextualNavigationStyles(),
      '#default_value' => $state['section_menu_style'],
    ];

    $form['section']['section_menu_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this
        ->t('Content types'),
      '#description' => implode('<br/> ', $desc),
      '#options' => $this
        ->getContentTypesList(),
      '#default_value' => $state['section_menu_types'],
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
    $this->messenger->addMessage(
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

    return $options;
  }

}
