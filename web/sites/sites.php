<?php

/**
 * @file
 * Sites routing map.
 */

// Dynamically match sites directory to current host.
$dirs = preg_grep('/^([^.])/', scandir('sites/'));
foreach ($dirs as $dir) {
  // Map any host that contains dir as the part of subdomain to that dir.
  // Either as the start of the string, or preceded by a - or . and always
  // followed by a .
  if (is_dir("sites/$dir")) {
    $matches = preg_grep("/(^|-|\.)$dir\./", [$http_host]);
    if (!empty($matches)) {
      $sites[$http_host] = $dir;
    }
  }
}

// Custom domains for any that do not match above.
// The patterns to match in the domain are as follows.
// slo. - Starts with directory name and ends in .
// -slo. - - followed by directroy name and ends in .
// .slo. - . followed by directory name and ends in .
// If the custom domain does not contain one of these patterns,
// add it manually below.
$sites['www.occourts.org'] = 'oc';

// @todo perhaps move these sites to dir that mathches their domain.
// = 'jcart';
$sites['develop.jcart.courts.ca.gov'] = 'deprep';
$sites['stage.jcart.courts.ca.gov'] = 'deprep';
$sites['jcart.courts.ca.gov'] = 'deprep';
// = 'madera';
$sites['develop.madera.courts.ca.gov'] = 'md';
$sites['stage.madera.courts.ca.gov'] = 'md';
$sites['www.madera.courts.ca.gov'] = 'md';
// = 'santacruz';
$sites['develop.santacruz.courts.ca.gov'] = 'sc';
$sites['stage.santacruz.courts.ca.gov'] = 'sc';
$sites['www.santacruz.courts.ca.gov'] = 'sc';

// Local using Other.
// If you're not using Lando, place additional site definitions in
// sites.local.php next to this file. It will be ignored by git.
if (file_exists('./sites/sites.local.php')) {
  include './sites/sites.local.php';
}
