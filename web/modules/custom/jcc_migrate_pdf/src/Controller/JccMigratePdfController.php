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
use GuzzleHttp\ClientInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JccMigratePdfController extends ControllerBase
{

    protected $jccMigratePdfService;
    protected $httpClient;

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jcc_migrate_pdf.jcc_migrate_pdf_service'),
            $container->get('http_client')
        );
    }

    public function __construct(JccMigratePdfService $jccMigratePdfService, ClientInterface $http_client)
    {
        $this->jccMigratePdfService = $jccMigratePdfService;
        $this->httpClient = $http_client;
    }
    public function migratePdf()
    {

        $query = $this->jccMigratePdfService->getContentTypeQuery();

        $nodes = $query;

        $success = false;
        foreach ($nodes as $key => $nid) {

            $node_values = Node::load($nid);
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
                    $file_url = 'https://www.scscourt.org/court_divisions/family/adr/background/'.$legacy_id.'.pdf';
                    $filename = 'Civil_Judicial_Arbitrator';
                    $this->fileAddedToField($file_url, $node_values, $legacy_id, $filename);

                    $this->messenger()->addStatus("Civil Judicial Arbitrator Added");
                    break;
                case 'Family ADR Providers':
                    $file_url = 'https://www.scscourt.org/court_divisions/civil/adr/searchjudarb/jaPDF/'.$legacy_id.'.pdf';
                    $filename = 'Family_ADR_Providers';
                    $this->fileAddedToField($file_url, $node_values, $legacy_id, $filename);
                    $this->messenger()->addStatus("Family ADR Providers Added");
                    break;
                case 'Civil ADR Provider':

                    $file_url = 'https://www.scscourt.org/court_divisions/civil/adr/searchadr/background/'.$legacy_id.'.pdf';
                    $filename = 'Civil_ADR_Provider';

                    $this->fileAddedToField($file_url, $node_values, $legacy_id, $filename);
                    $this->messenger()->addStatus("Civil ADR Providers Added");
                    break;
                case 'Probate Early Settlement Program Neutrals':
                    $this->messenger()->addStatus("Migrate Family Mediator Sheet");
                    break;
                case 'Civil Early Settlement Conference (CESC) Neutral':
                    $this->messenger()->addStatus("Migrate Family Mediator Sheet");
                    break;
            }
            $fileAddedSuccessfully = true;
            if ($fileAddedSuccessfully) {
                $success = true;
            }

        }

        if ($success) {
            $this->messenger()->addStatus("Data added successfully");
        } else {
            $this->messenger()->addError("Data not added. Please contact the admin.");
        }
        return ["#markup" => "Migrate process completed."];

    }

    public function fileAddedToField($file_url, $node_values, $legacy_id, $file_name)
    {
        if ($file_url) {
        
            if (preg_match('/(\d+)\.pdf$/', $file_url, $matches)) {
                $urllegacyId = $matches[1];
                $destination = $this->createFolder() . '/' . $file_name . $urllegacyId . '.pdf';

                try {
                    $client = \Drupal::httpClient(); 
                    $response = $client->request('GET', $file_url, [ 'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'], 
'verify'=>false, ]);
                    $response->getStatusCode();
                    
                    $file_contents = $response->getBody()->getContents();
                    // Check if the file contents are not empty.
                    if (!empty($file_contents)) {
                        // Save the file to the destination.
                        file_save_data($file_contents, $destination, FileSystemInterface::EXISTS_REPLACE);

                        // Create or load the file entity.
                        $file = File::create([
                            'uri' => $destination,
                            'filemime' => 'application/pdf',
                            'uid' => \Drupal::currentUser()->id(),
                            'origname' => $file_url,
                        ]);

                        $file->setPermanent();
                        $file->save();

                        $fileID = $file->id();
                        $media = Media::create([
                            'bundle' => 'file', // Replace with your media bundle.
                            'uid' => 1, // Replace with the user ID associated with the media.
                            'field_media_file' => [
                            'target_id' => $file->id(),
                             ],
                            ]);
                            $media->save();
                            // Get the media ID.
                            $media_id = $media->id();
                        //dd($fileID);
                        //dd($media_id);
                        //dd($file->getFileUri());

                        // Set the file ID in the field_arbitrary_pdf field.
                        $node_values->field_arbitrary_pdf->setValue([
                            'target_id' => $media_id,
                            'display' => 1,
                            'uri' => $file->getFileUri(),
                        ]);

                        // Save the node.
                        $node_values->save();
                       //dd($node_values);
                       // dd($node_values->field_arbitrary_pdf);
                        

                    } else {
                        $file_contents = file_get_contents($file_url);
                        if (preg_match('/(\d+)\.pdf$/', $file_url, $matches)) {
                            $destination = $this->createFolder() . '/' . $file_name . $urllegacyId . '.pdf';
                            $urllegacyId = $matches[1];
                            if ($legacy_id == $urllegacyId) {
                                $file = File::create([
                                    'uri' => $destination,
                                    'filemime' => 'application/pdf',
                                    'uid' => \Drupal::currentUser()->id(),

                                    'origname' => $file_url,
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
                        \Drupal::logger('jcc_migrate_pdf')->error('Error: Empty file contents received from URL: @url', ['@url' => $file_url]);
                    }
                } catch (\Exception $e) {
                    \Drupal::logger('jcc_migrate_pdf')->error('Error downloading or saving file: @error', ['@error' => $e->getMessage()]);

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
