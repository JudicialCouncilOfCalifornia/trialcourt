#!/bin/bash

# Shared configuration loader for Kanopi Drupal DDEV commands
# Uses environment variables set during add-on installation
# Usage: source this file, then call load_kanopi_config

#ddev-generated

# Configuration variables can be added here by project-configure command

export NGINX_FILE_PROXY="https://-.pantheonsite.io"
load_kanopi_config() {
    # Configuration is now handled entirely via environment variables
    # Set defaults for any missing values

    # Theme Configuration
    export THEME=${THEME:-'themes/custom/mytheme'}
    export THEMENAME=${THEMENAME:-'mytheme'}

    # Hosting Configuration
    export HOSTING_PROVIDER=${HOSTING_PROVIDER:-'pantheon'}
    export HOSTING_SITE=${HOSTING_SITE:-''}
    export HOSTING_ENV=${HOSTING_ENV:-'dev'}

    # Migration Configuration
    export MIGRATE_DB_SOURCE=${MIGRATE_DB_SOURCE:-''}
    export MIGRATE_DB_ENV=${MIGRATE_DB_ENV:-''}

    # Debug output if requested
    if [ "${KANOPI_CONFIG_DEBUG:-false}" = "true" ]; then
        echo "Configuration loaded from environment variables:"
        echo "  Theme: $THEMENAME at $THEME"
        echo "  Hosting: $HOSTING_PROVIDER ($HOSTING_SITE.$HOSTING_ENV)"
        if [ -n "$MIGRATE_DB_SOURCE" ]; then
            echo "  Migration Source: $MIGRATE_DB_SOURCE ($MIGRATE_DB_ENV)"
        fi
    fi
}
