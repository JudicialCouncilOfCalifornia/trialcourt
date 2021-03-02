<?php

/**
 * @file
 * Sites routing map.
 */

// Develop.
$sites['develop-jcc-tc-default.pantheonsite.io'] = 'default';
$sites['develop-jcc-tc.pantheonsite.io'] = 'slo';
$sites['develop-jcc-oc.pantheonsite.io'] = 'oc';
$sites['develop-jcc-napa.pantheonsite.io'] = 'napa';
$sites['develop-jcc-newsroom.pantheonsite.io'] = 'newsroom';
$sites['develop-jcc-deprep.pantheonsite.io'] = 'deprep';
$sites['develop-jcc-md.pantheonsite.io'] = 'md';
$sites['develop-jcc-webservices.pantheonsite.io'] = 'webservices';

// Stage.
$sites['stage-jcc-tc-default.pantheonsite.io'] = 'default';
$sites['stage-jcc-tc.pantheonsite.io'] = 'slo';
$sites['stage-jcc-oc.pantheonsite.io'] = 'oc';
$sites['stage-jcc-napa.pantheonsite.io'] = 'napa';
$sites['stage-jcc-newsroom.pantheonsite.io'] = 'newsroom';
$sites['stage-jcc-deprep.pantheonsite.io'] = 'deprep';
$sites['stage-jcc-md.pantheonsite.io'] = 'md';
$sites['stage-jcc-webservices.pantheonsite.io'] = 'webservices';

// Live.
$sites['dev-jcc-tc-default.pantheonsite.io'] = 'default';
$sites['test-jcc-tc-default.pantheonsite.io'] = 'default';
$sites['live-jcc-tc-default.pantheonsite.io'] = 'default';

$sites['dev-jcc-tc.pantheonsite.io'] = 'slo';
$sites['test-jcc-tc.pantheonsite.io'] = 'slo';
$sites['live-jcc-tc.pantheonsite.io'] = 'slo';
$sites['www.slo.courts.ca.gov'] = 'slo';

$sites['dev-jcc-oc.pantheonsite.io'] = 'oc';
$sites['test-jcc-oc.pantheonsite.io'] = 'oc';
$sites['live-jcc-oc.pantheonsite.io'] = 'oc';
$sites['www.occourts.org'] = 'oc';

$sites['dev-jcc-napa.pantheonsite.io'] = 'napa';
$sites['test-jcc-napa.pantheonsite.io'] = 'napa';
$sites['live-jcc-napa.pantheonsite.io'] = 'napa';
$sites['www.napa.courts.ca.gov'] = 'napa';

$sites['dev-jcc-newsroom.pantheonsite.io'] = 'newsroom';
$sites['test-jcc-newsroom.pantheonsite.io'] = 'newsroom';
$sites['live-jcc-newsroom.pantheonsite.io'] = 'newsroom';
$sites['newsroom.courts.ca.gov'] = 'newsroom';
$sites['beta.newsroom.courts.ca.gov'] = 'newsroom';
$sites['stage.newsroom.courts.ca.gov'] = 'newsroom';
$sites['develop.newsroom.courts.ca.gov'] = 'newsroom';

$sites['dev-jcc-deprep.pantheonsite.io'] = 'deprep';
$sites['test-jcc-deprep.pantheonsite.io'] = 'deprep';
$sites['live-jcc-deprep.pantheonsite.io'] = 'deprep';
$sites['develop.jcart.courts.ca.gov'] = 'deprep';
$sites['stage.jcart.courts.ca.gov'] = 'deprep';
$sites['jcart.courts.ca.gov'] = 'deprep';

$sites['dev-jcc-md.pantheonsite.io'] = 'md';
$sites['test-jcc-md.pantheonsite.io'] = 'md';
$sites['live-jcc-md.pantheonsite.io'] = 'md';
$sites['develop.madera.courts.ca.gov'] = 'md';
$sites['stage.madera.courts.ca.gov'] = 'md';
$sites['www.madera.courts.ca.gov'] = 'md';

$sites['dev-jcc-webservices.pantheonsite.io'] = 'webservices';
$sites['test-jcc-webservices.pantheonsite.io'] = 'webservices';
$sites['live-jcc-webservices.pantheonsite.io'] = 'webservices';
$sites['webservices.courts.ca.gov'] = 'webservices';

// Local using Other.
// If you're not using Lando, place additional site definitions in
// sites.local.php next to this file. It will be ignored by git.
if (file_exists('./sites/sites.local.php')) {
  include './sites/sites.local.php';
}

// Local using Lando.
// This list is at the end as it may be automatically ammended by installer.
$sites['default.lndo.site'] = 'default';
$sites['slo.lndo.site'] = 'slo';
$sites['oc.lndo.site'] = 'oc';
$sites['napa.lndo.site'] = 'napa';
$sites['newsroom.lndo.site'] = 'newsroom';
$sites['deprep.lndo.site'] = 'deprep';
$sites['md.lndo.site'] = 'md';
$sites['webservices.lndo.site'] = 'webservices';
$sites['sc.lndo.site'] = 'sc';
