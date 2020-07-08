<?php

/**
 * @file
 * Sites routing map.
 */

// Test epic-feat.
$sites['epic-feat-jcc-tc.pantheonsite.io'] = 'slo';
$sites['epic-feat-jcc-oc.pantheonsite.io'] = 'oc';
$sites['epic-feat-jcc-napa.pantheonsite.io'] = 'napa';
$sites['epic-feat-jcc-newsroom.pantheonsite.io'] = 'newsroom';

// Develop.
$sites['develop-jcc-tc.pantheonsite.io'] = 'slo';
$sites['develop-jcc-oc.pantheonsite.io'] = 'oc';
$sites['develop-jcc-napa.pantheonsite.io'] = 'napa';
$sites['develop-jcc-newsroom.pantheonsite.io'] = 'newsroom';

// Stage.
$sites['stage-jcc-tc.pantheonsite.io'] = 'slo';
$sites['stage-jcc-oc.pantheonsite.io'] = 'oc';
$sites['stage-jcc-napa.pantheonsite.io'] = 'napa';
$sites['stage-jcc-newsroom.pantheonsite.io'] = 'newsroom';

// Live.
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
$sites['live_url_goes_here'] = 'newsroom';

// Local using Other.
// If you're not using Lando, place additional site definitions in
// sites.local.php next to this file. It will be ignored by git.
if (file_exists('./sites/sites.local.php')) {
  include './sites/sites.local.php';
}

// Local using Lando.
// This list is at the end as it may be automatically ammended by installer.
$sites['jcc.lndo.site'] = 'default';
$sites['slo.lndo.site'] = 'slo';
$sites['oc.lndo.site'] = 'oc';
$sites['napa.lndo.site'] = 'napa';
$sites['newsroom.lndo.site'] = 'newsroom';
