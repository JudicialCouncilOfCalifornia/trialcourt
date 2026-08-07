<?php

/**
 * @file
 * Post Updates for immutable.
 */

/**
 * Rebuild permissions after updating nodeaccess to v2.
 */
function jcc_tc2_all_immutable_config_post_update_rebuild_permissions(): void {
  // Run the permissions rebuild. This forces the permissions for each node to
  // be reprocessed into the node_access table.
  node_access_rebuild(TRUE);
}
