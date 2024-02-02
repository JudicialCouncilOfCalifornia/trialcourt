<?php

namespace Drupal\jcc_elevated_sections\Controller;

use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\jcc_elevated_sections\JccElevatedSectionsEntityAutocompleteMatcher;
use Drupal\system\Controller\EntityAutocompleteController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AutoComplete Controller for adding sections to the output.
 */
class JccElevatedSectionsEntityAutocompleteController extends EntityAutocompleteController {

  /**
   * The autocomplete matcher for entity references.
   *
   * @var \Drupal\jcc_elevated_sections\JccElevatedSectionsEntityAutocompleteMatcher
   */
  protected $matcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(JccElevatedSectionsEntityAutocompleteMatcher $matcher, KeyValueStoreInterface $key_value) {
    $this->matcher = $matcher;
    $this->keyValue = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jcc_elevated_sections.autocomplete_matcher'),
      $container->get('keyvalue')->get('entity_autocomplete')
    );
  }

}
