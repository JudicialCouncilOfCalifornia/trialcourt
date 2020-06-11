<?php

// Develop.
$sites['develop-jcc-tc.pantheonsite.io'] = 'slo';
$sites['develop-jcc-oc.pantheonsite.io'] = 'oc';

// Stage.
$sites['stage-jcc-tc.pantheonsite.io'] = 'slo';
$sites['stage-jcc-oc.pantheonsite.io'] = 'oc';

// Live.
$sites['dev-jcc-tc.pantheonsite.io'] = 'slo';
$sites['test-jcc-tc.pantheonsite.io'] = 'slo';
$sites['live-jcc-tc.pantheonsite.io'] = 'slo';
$sites['www.slo.courts.ca.gov'] = 'slo';

$sites['dev-jcc-oc.pantheonsite.io'] = 'oc';
$sites['test-jcc-oc.pantheonsite.io'] = 'oc';
$sites['live-jcc-oc.pantheonsite.io'] = 'oc';
$sites['www.occourts.org'] = 'oc';

// Local using Other.
// If you're not using Lando, place additional site definitions in
// sites.local.php next to this file. It will be ignored by git.
if (file_exists($app_root . '/sites/sites.local.php')) {
  include $app_root . '/sites/sites.local.php';
}

// Epics.
$sites['epic-multi-jcc-tc.pantheonsite.io'] = 'default';
$sites['epic-slo-jcc-tc.pantheonsite.io'] = 'slo';
$sites['epic-oc-jcc-tc.pantheonsite.io'] = 'oc';
$sites['epic-multi-jcc-oc.pantheonsite.io'] = 'oc';

// Local using Lando.
// This list is at the end as it may be automatically ammended by installer.
$sites['tc.lndo.site'] = 'default';
$sites['tc-slo.lndo.site'] = 'slo';
$sites['tc-oc.lndo.site'] = 'oc';
