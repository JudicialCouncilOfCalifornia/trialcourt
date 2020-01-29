<?php

// Database Updates.
echo "Running database updates.\n";
passthru('drush updb -y');
echo "Database updates have run.\n";
// Import all config changes.
echo "Importing configuration from yml files...\n";
passthru('drush config-import -y');
echo "Import of configuration complete.\n";
// Clear all cache.
echo "Rebuilding cache.\n";
passthru('drush cr');
echo "Rebuilding cache complete.\n";
