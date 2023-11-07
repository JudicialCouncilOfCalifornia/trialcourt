<?php
namespace Drupal\jcc_migrate_pdf\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\jcc_migrate_pdf\Services\JccMigratePdfService;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JccMigratePdfController extends ControllerBase
{
    public $jccMigratePdfService;
    /**
     * Class constructor.
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jcc_migrate_pdf.jcc_migrate_pdf_service')
        );
    }

    public function __construct(JccMigratePdfService $jccMigratePdfService)
    {
        $this->jccMigratePdfService = $jccMigratePdfService;
    }

    public function migratePdf()
    {

        $query = $this->jccMigratePdfService->getContentTypeQuery();

        $nodes = Node::loadMultiple($query);

        foreach ($nodes as $key => $node_values) {
            $taxonomy_id = $node_values->field_mediator->target_id;
            $legacy_id = $node_values->field_id->value;

            $terms = \Drupal::entityTypeManager()
                ->getStorage('taxonomy_term')
                ->loadByProperties(['tid' => $taxonomy_id]);
            $termsname = '';
            foreach ($terms as $key => $terms_values) {
                $termsname .= $terms_values->name->value;
            }
            switch ($termsname) {

                case 'Civil Judicial Arbitrator':
                    $file_path = 'https://www.scscourt.org/court_divisions/civil/adr/searchjudarb/jaPDF/128.pdf';
                    $filename = 'Civil_Judicial_Arbitrator';
                    $this->fileAddedToField($file_path, $node_values, $legacy_id, $filename);
                    $this->messenger()->addStatus("Civil Judicial Arbitrator Added");
                    break;
                case 'Family ADR Providers':
                    $file_path = 'https://www.scscourt.org/court_divisions/family/adr/background/13.pdf';
                    $filename = 'Family_ADR_Providers';
                    $this->fileAddedToField($file_path, $node_values, $legacy_id, $filename);
                    $this->messenger()->addStatus("Family ADR Providers Added");
                    break;
                case 'Civil ADR Provider':
                    $file_path = 'https://www.scscourt.org/court_divisions/civil/adr/searchadr/background/16.pdf';
                    $filename = 'Civil_ADR_Provider';
                    $this->fileAddedToField($file_path, $node_values, $legacy_id, $filename);
                    $this->messenger()->addStatus("Civil ADR Providers Added");
                    break;
                case 'Probate Early Settlement Program Neutrals':
                    $this->messenger()->addStatus("Migrate Family Mediator Sheet");
                    break;
                case 'Civil Early Settlement Conference (CESC) Neutral':
                    $this->messenger()->addStatus("Migrate Family Mediator Sheet");
                    break;
            }
        }
        return ["#markup" => "Added Successfully"];
    }

    public function fileAddedToField($file_path, $node_values, $legacy_id, $file_name)
    {
        $urllegacyId = null;
        if (file_exists($file_path)) {
            $file_contents = file_get_contents($file_path);
            if (preg_match('/(\d+)\.pdf$/', $file_path, $matches)) {
                $destination = $this->createFolder() . '/' . $file_name . $urllegacyId . '.pdf';
                $urllegacyId = $matches[1];
                if ($legacy_id == $urllegacyId) {
                    $uid = \Drupal::currentUser()->id();
                    $file = File::create([
                        'uri' => $destination,
                        'filemime' => 'application/pdf',
                        'uid' => $uid,
                    ]);

                    $file->setPermanent();
                    file_save_data($file_contents, $destination, FileSystemInterface::EXISTS_REPLACE);

                    $file->save();

                    $fileID = $file->id();
                    $node_values->field_arbitrary_pdf->setValue([
                        'target_id' => $fileID,
                        'display' => 1,
                        'uri' => $destination,
                    ]);
                    $node_values->save();
                }

            }
        }
    }
    public function createFolder()
    {
        $directory = 'public://arbitrator_pdf';
        $file_system = \Drupal::service('file_system');
        if (!$file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY)) {
            $this->messenger->addError(t('Failed to create the folder.'));
        }
        return $directory;
    }
    public static function triggerPdfPageLink()
    {
        $current_user = \Drupal::currentUser();
        $username = $current_user->getAccountName();
        $value = 'Welcome User ' . $username;

        // Create the first link with attributes.
        $link_text = t('Migrate Pdf Custom');
        $link_url = '/admin/migrate/pdf/'; // Adjust the URL path as needed
        $link = Link::fromTextAndUrl($link_text, Url::fromUserInput($link_url));
        $link = $link->toRenderable();
        $link['#attributes']['class'] = ['btn', 'btn-success'];
        $link = Markup::create(\Drupal::service('renderer')->render($link));

        // Combine the welcome message and links using line breaks.
        $html = '<h5> Click Below For Migrate PDF Url Custom  !!</h5>';
        $markup = '<h2>' . $value . '</h2>' . '<br>' . $html . '<br>' . '<br>' . $link . '<br>' . '<br';

        return [
            '#markup' => $markup,
        ];
    }

}
