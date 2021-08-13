<?php

namespace Drupal\jcc_ical\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\pathauto\AliasCleaner;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for jcc_ical routes.
 */
class JccIcalController extends ControllerBase {

  /**
   * Generated iCal file content.
   */
  private $ical = "";

  /**
   * Name value from the node for file name.
   */
  private $name = 'event';

  /**
   * Pathauto Alias Cleaner Service.
   * @var AliasCleaner
   */
  private $aliasCleaner;

  /**
   * Inject services.
   */
  public static function create(ContainerInterface $container) {
    $alias_cleaner = $container->get('pathauto.alias_cleaner');
    return new static($alias_cleaner);
  }

  /**
   * Set injected dependencies.
   */
  public function __construct(AliasCleaner $alias_cleaner) {
    $this->aliasCleaner = $alias_cleaner;
  }

  /**
   * Builds the response.
   */
  public function build(NodeInterface $node = NULL) {
    // Return 404 if node empty or not a configured content type.
    $config = \Drupal::config('jcc_ical.settings');
    $types = $config->get('types');

    if (empty($node) || empty($types[$node->getType()])) {
      throw new NotFoundHttpException();
    }
    // Create the iCal file content.
    $this->createIcal($node);
    // Create the response.
    $response = new Response();
    $response->headers->set('Content-Type', 'text/calendar');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->name . '.ics"');
    $response->setContent($this->ical);

    return $response;
  }

  /**
   * Create ical file from node data.
   *
   * @param Drupal\node\NodeInterface $node
   *   The node to create ical file for.
   */
  private function createIcal(NodeInterface $node) {

    // @todo: Detect date fields instead of hardcoding field_date_range.
    // Or have the module add the fields to configured content types.
    $address = isset($node->field_location) && !empty($node->field_location->first()) ? $node->field_location->first()->getValue() : [];
    // Concatenate address elements.
    $location = !empty($address)
      ? $address['address_line1'] . ' '
      . $address['address_line2'] . ' '
      . $address['locality'] . ', '
      . $address['administrative_area'] . ', '
      . $address['country_code'] . ' '
      : '';

    $daterange = $node->field_date_range->first()->getValue();
    $dtstart = \DateTime::createFromFormat ( 'Y-m-d\TH:i:s', $daterange['value']);
    $dtend = \DateTime::createFromFormat ( 'Y-m-d\TH:i:s', $daterange['end_value']);
    $title = $node->getTitle();
    // Set the file name.
    $this->name = $this->aliasCleaner->cleanString($title);
    // Set the file content.
    $this->ical = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//JudicialCouncil//NONSGML v1.0//EN
BEGIN:VEVENT
UID:event" . $node->id() . "@trial.court
DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z
DTSTART:" . $dtstart->format('Ymd\THis\Z') . "
DTEND:" . $dtend->format('Ymd\THis\Z') . "
SUMMARY:" . $title . "
LOCATION:" . $location . "
END:VEVENT
END:VCALENDAR";
  }

}
