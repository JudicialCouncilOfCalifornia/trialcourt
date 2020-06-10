<?php

// Develop.
$sites['develop-jcc-tc.pantheonsite.io'] = 'slo';

// Stage.
$sites['stage-jcc-tc.pantheonsite.io'] = 'slo';

// Live.
$sites['live-jcc-tc.pantheonsite.io'] = 'slo';
$sites['www.slo.courts.ca.gov'] = 'slo';

// Local using Other.
// If you're not using Lando, place additional site definitions in
// sites.local.php next to this file. It will be ignored by git.
if (file_exists($app_root . '/sites/sites.local.php')) {
  include $app_root . '/sites/sites.local.php';
}

// Epics.
$sites['epic-multi-jcc-tc.pantheonsite.io'] = 'default';
$sites['epic-slo-jcc-tc.pantheonsite.io'] = 'slo';

// Local using Lando.
// This list is at the end as it may be automatically ammended by installer.
$sites['tc.lndo.site'] = 'default';
$sites['tc-slo.lndo.site'] = 'slo';
