<?php

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Include the Composer autoloader.
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Bootstrap Drupal.
$autoloader = require_once __DIR__ . '/../../../autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'dev');
$kernel->boot();

// Add the redirect update code here.
use Drupal\Core\Url;
use Drupal\redirect\Entity\Redirect;

// Load the redirect entities that need to be updated.
$redirects = \Drupal::entityTypeManager()->getStorage('redirect')->loadByProperties([
  'redirect_source__uri' => '%/sites/default/files/santaclara/default/documents/%',
]);

// Initialize an array to store the IDs of updated redirects.
$updatedRedirectIds = [];

// Update each redirect.
foreach ($redirects as $redirect) {
  // Get the current redirect source path.
  $sourcePath = $redirect->getSource()['path'];

  // Extract the file name from the source path.
  $fileName = basename($sourcePath);

  // Remove the file extension from the file name.
  $fileNameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);

  // Convert the file name to lowercase and replace spaces with dashes.
  $newFileName = strtolower(str_replace(' ', '-', $fileNameWithoutExtension)) . '_0.pdf';

  // Build the new redirect URL.
  $newRedirectUrl = 'internal:/system/files/' . $newFileName;

  // Update the redirect.
  // Update with your new path.
  $redirect->setRedirectUrl(Url::fromUri($newRedirectUrl));
  $redirect->save();

  // Add the ID of the updated redirect to the array.
  $updatedRedirectIds[] = $redirect->id();
}

// Optionally, you can output a message indicating that updates were successful.
echo "Redirects updated successfully!\n";

// Output the IDs of the updated redirects.
echo "Updated redirect IDs: " . implode(', ', $updatedRedirectIds) . "\n";
