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
//$sites['develop.madera.courts.ca.gov'] = 'md';
//$sites['stage.madera.courts.ca.gov'] = 'md';
//$sites['www.madera.courts.ca.gov'] = 'md';
// = 'santacruz';
$sites['develop.santacruz.courts.ca.gov'] = 'sc';
$sites['stage.santacruz.courts.ca.gov'] = 'sc';
$sites['www.santacruz.courts.ca.gov'] = 'sc';
// = 'sierra';
$sites['develop-jcc-sierra.pantheonsite.io'] = 'sierra';
$sites['stage-jcc-sierra.pantheonsite.io'] = 'sierra';
$sites['live-jcc-sierra.pantheonsite.io'] = 'sierra';
$sites['www.sierra.courts.ca.gov'] = 'sierra';
// = 'slo';
$sites['www.slo.courts.ca.gov'] = 'slo2';
// = 'tularesuperiorcourt';
$sites['develop-jcc-tularesuperiorcourt.pantheonsite.io'] = 'tularesuperiorcourt';
$sites['stage-jcc-tularesuperiorcourt.pantheonsite.io'] = 'tularesuperiorcourt';
$sites['live-jcc-tularesuperiorcourt.pantheonsite.io'] = 'tularesuperiorcourt';
$sites['www.tularesuperiorcourt.ca.gov'] = 'tularesuperiorcourt';
// = 'eldorado';
$sites['www.eldoradocourt.org'] = 'eldorado';
$sites['eldoradocourt.org'] = 'eldorado';
// = 'sacramento';
$sites['www.saccourt.ca.gov'] = 'sacramento';
$sites['saccourt.ca.gov'] = 'sacramento';
// = 'glenn';
$sites['www.glenncourt.ca.gov'] = 'glenn';
$sites['glenncourt.ca.gov'] = 'glenn';
// = 'yuba';
$sites['www.yubacourts.org'] = 'yuba';
$sites['yubacourts.org'] = 'yuba';
// = 'sanfrancisco';
$sites['www.sfsuperiorcourt.org'] = 'sanfrancisco';
$sites['sfsuperiorcourt.org'] = 'sanfrancisco';
// = 'sutter';
$sites['www.suttercourts.com'] = 'sutter';
$sites['suttercourts.com'] = 'sutter';
// = 'mariposa';
$sites['www.mariposacourt.org'] = 'mariposa';
$sites['mariposacourt.org'] = 'mariposa';

// Local using Other.
// If you're not using Lando, place additional site definitions in
// sites.local.php next to this file. It will be ignored by git.
if (file_exists('./sites/sites.local.php')) {
  include './sites/sites.local.php';
}
