<?php

namespace Drupal\linkit_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\linkit\SubstitutionManagerInterface;
use Drupal\linkit_field\Utility\LinkitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Linkit' formatter.
 *
 * @FieldFormatter(
 *   id = "linkit_field_linkit",
 *   label = @Translation("Linkit (JCC)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkitFormatter extends LinkFormatter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->substitutionManager = $container->get('plugin.manager.linkit.substitution');
    $instance->pathValidator = $container->get('path.validator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkit_profile' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $linkit_profiles = $this->entityTypeManager->getStorage('linkit_profile')->loadMultiple();

    $options = [];
    foreach ($linkit_profiles as $linkit_profile) {
      $options[$linkit_profile->id()] = $linkit_profile->label();
    }

    $elements['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#description' => $this->t('Must be the same as the profile selected on the form display for this field.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('linkit_profile'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $linkit_profile_id = $this->getSetting('linkit_profile');
    $linkit_profile = $this->entityTypeManager->getStorage('linkit_profile')->load($linkit_profile_id);

    if ($linkit_profile) {
      $summary[] = $this->t('Linkit profile: @linkit_profile', ['@linkit_profile' => $linkit_profile->label()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Link field formatter double encodes urls which becomes a problem when
    // we allow file urls that have special characters like spaces in the
    // filename. Decode the uri before allowing it to render. It will be re-
    // encoded by the core link field formatter.
    foreach ($items as $delta => $item) {
      $link_value = $item->getValue();
      if (isset($link_value['uri']) && $link_value['uri'] !== NULL) {
        $link_value['uri'] = rawurldecode($link_value['uri']);
      }
      $item->setValue($link_value);
      $items[$delta] = $item;
    }

    $elements = parent::viewElements($items, $langcode);

    // Loop over the elements and substitute the URL.
    foreach ($elements as $delta => &$item) {
      /** @var \Drupal\link\LinkItemInterface $link_item */
      $link_item = $items->get($delta);
      $substituted_url = $this->getSubstitutedUrl($link_item);
      // Convert generated URL into a URL object.
       if ($substituted_url && ($url = $this->pathValidator->getUrlIfValid($substituted_url->toString()))) {
        // Keep query and fragment.
        $parsed_url = parse_url($link_item->uri);
        if (!empty($parsed_url['query'])) {
          $parsed_query = [];
          parse_str($parsed_url['query'], $parsed_query);
          if (!empty($parsed_query)) {
            $url->setOption('query', $parsed_query);
          }
        }
        if (!empty($parsed_url['fragment'])) {
          $url->setOption('fragment', $parsed_url['fragment']);
        }
        // Add cache dependency to the generated substituted URL.
        $cacheable_metadata = BubbleableMetadata::createFromRenderArray($item)
          ->addCacheableDependency($substituted_url);
        // Add cache dependency to the referenced entity, e.g. for media direct
        // file substitution.
        if ($entity = LinkitHelper::getEntityFromUserInput($link_item->uri)) {
          $cacheable_metadata->addCacheableDependency($entity);
        }
        $cacheable_metadata->applyTo($item);
        $item['#url'] = $url;
      }
    }

    return $elements;
  }

  /**
   * Returns a substitution URL for the given linked item.
   *
   * In case the items links to an entity use a substituted/generated URL.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link item.
   *
   * @return \Drupal\Core\GeneratedUrl|null
   *   The substitution URL, or NULL if not able to retrieve it from the item.
   */
  protected function getSubstitutedUrl(LinkItemInterface $item) {
    $value = $item->getValue();

    if (strpos($value['uri'], 'entity:') === 0) {
      list($type, $entity_id) = explode('/', $value['uri']);
      $entity_type = substr($type, strpos($type, ':') + 1);
    }

    if (empty($entity_type) || empty($entity_id)) {
      return NULL;
    }

    switch ($entity_type) {
      case 'file':
      case 'media':
        $substitution_type = $entity_type;
        break;

      default:
        $substitution_type = SubstitutionManagerInterface::DEFAULT_SUBSTITUTION;

    }

    $entity = LinkitHelper::getEntityFromUri($value['uri']);

    if ($entity) {
      /** @var \Drupal\Core\GeneratedUrl $url */
      $url = $this->substitutionManager
        ->createInstance($substitution_type)
        ->getUrl($entity);

      return $url;
    }

    return NULL;
  }

}
