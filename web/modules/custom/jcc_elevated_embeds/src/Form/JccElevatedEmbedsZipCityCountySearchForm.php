<?php

namespace Drupal\jcc_elevated_embeds\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class JccElevatedEmbedsZipCityCountySearchForm.
 */
class JccElevatedEmbedsZipCityCountySearchForm extends FormBase {

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
    $this->entity_type_manager = $entity_type_manager;
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
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jcc_elevated_embeds_zip_city_county_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['search'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search by Zip or City name'),
      '#placeholder' => $this->t('Search'),
      '#autocomplete_route_name' => 'jcc_elevated_embeds.autocomplete.zip_city_county_info',
      '#autocomplete_route_parameters' => array('search' => 'search'),
      '#attached' => array(
        'library' => array('module/autocomplete'),
      ),
    );

//    $form['submit'] = [
//      '#type' => 'submit',
//      '#value' => $this->t('Submit'),
//    ];

    $form['captcha']['#access'] = FALSE;
    //kint($form);
    //kint($this->getFullDistrictInformation());

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //$values = $form_state->getValues();
    //$article_id = EntityAutocomplete::extractEntityIdFromAutocompleteInput($form_state->getValue('search'));

    //$entity_id = $form_state->getValue('search');
    // Perform your logic to get the route name based on the entity id.
    //$route_name = 'my_module.entity_view';  // Replace this with your logic.
    //$form_state->setRedirect($route_name);

    // Let our guests know that all is updated and well.
//    $this->messenger()->addMessage(
//      $this->t('zip/city/county search submitted')
//    );
  }

}
