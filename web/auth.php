<?php

/**
 * @file
 * The PHP page that serves all page requests on a Drupal installation.
 *
 * All Drupal code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt files in the "core" directory.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

$kernel = new DrupalKernel('prod', $autoloader);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);

// ID of the user.
// REPLACE WITH WHATEVER ID YOU WANT TO LOGIN AS;
$uid = 1; 
$user = Drupal\user\Entity\User::load($uid);

// This is required to call user_login_finalize here.
$kernel->prepareLegacyRequest($request);
user_login_finalize($user);

$response->send();

$kernel->terminate($request, $response);
