#!/usr/bin/env bash

## Pantheon database refresh script
## Called from the main refresh command when HOSTING_PROVIDER=pantheon

green='\033[0;32m'
yellow='\033[1;33m'
red='\033[0;31m'
NC='\033[0m'
divider='===================================================\n'

# Function to authenticate with Terminus
authenticate_terminus() {
    echo -e "\n${yellow} Authenticating with Terminus... ${NC}"
    # Get TERMINUS_MACHINE_TOKEN from environment
    TERMINUS_MACHINE_TOKEN=$(printenv TERMINUS_MACHINE_TOKEN 2>/dev/null)
    if [ -z "${TERMINUS_MACHINE_TOKEN:-}" ]; then
        echo -e "${red}TERMINUS_MACHINE_TOKEN environment variable is not set in the web container. Please configure it in your global ddev config.${NC}"
        exit 1
    fi

    # Authenticate with terminus using the machine token
    if ! terminus auth:login --machine-token="${TERMINUS_MACHINE_TOKEN}"; then
        echo -e "${red}Failed to authenticate with Terminus. Please check your TERMINUS_MACHINE_TOKEN.${NC}"
        exit 1
    fi
    echo -e "${green}Successfully authenticated with Terminus.${NC}"
}

# Function to refresh database from Pantheon
refresh_pantheon_database() {
    # The first argument is the site to refresh (e.g. "alameda"). This maps to
    # the local multisite directory and database name. The Pantheon site we pull
    # from is the same name prefixed with "jcc-" (e.g. "jcc-alameda").
    local SITE_INPUT="$1"
    local FORCE_BACKUP="$2"

    # Fall back to HOSTING_SITE, then the DDEV project name, when no site is
    # passed on the command line.
    if [ -z "$SITE_INPUT" ]; then
        SITE_INPUT="${HOSTING_SITE:-$DDEV_PROJECT}"
    fi

    # Normalise: strip any leading "jcc-" so a single value gives us both the
    # local DB name and the Pantheon site name.
    local SITE="${SITE_INPUT#jcc-}"
    local PANTHEON_SITE="jcc-${SITE}"
    local LOCAL_DB="${SITE}"

    # Pantheon environment to pull from (dev/test/live/multidev). Defaults to
    # HOSTING_ENV, then "live".
    local ENVIRONMENT="${HOSTING_ENV:-live}"

    # Terminus site.env identifier used for all backup operations.
    SITE_ENV="${PANTHEON_SITE}.${ENVIRONMENT}"

    echo -e "\n${yellow} Get database from Pantheon site: ${PANTHEON_SITE} (env: ${ENVIRONMENT}) -> local DB: ${LOCAL_DB}. ${NC}"
    echo -e "${green}${divider}${NC}"

    # Define the local database dump file path
    DB_DUMP="/tmp/pantheon_backup.${SITE_ENV}.sql.gz"

    echo -e "\nChecking for local database dump file..."

    # Calculate current time and 12-hour threshold.
    CURRENT_TIME=$(date +%s)
    TWELVE_HOURS_AGO=$((CURRENT_TIME - 43200))  # 12 hours = 43200 seconds

    DOWNLOAD_NEW_BACKUP=false

    # Check if force flag is set
    if [ "$FORCE_BACKUP" = true ]; then
        echo -e "${yellow}Force flag detected. Will download fresh backup regardless of local file age.${NC}"
        DOWNLOAD_NEW_BACKUP=true
    else
        # Check if local dump file exists and its age
        if [ -f "$DB_DUMP" ]; then
            # Get file modification time using a more reliable method
            LOCAL_FILE_TIME=""

            # Try different methods to get the file modification time
            if [ -z "$LOCAL_FILE_TIME" ]; then
                # Method 1: Try stat with format specifier (GNU/Linux style)
                LOCAL_FILE_TIME=$(stat -c %Y "$DB_DUMP" 2>/dev/null)
            fi

            if [ -z "$LOCAL_FILE_TIME" ]; then
                # Method 2: Try stat with format specifier (BSD/macOS style)
                LOCAL_FILE_TIME=$(stat -f %m "$DB_DUMP" 2>/dev/null)
            fi

            if [ -z "$LOCAL_FILE_TIME" ]; then
                # Method 3: Use date command with file reference
                LOCAL_FILE_TIME=$(date -r "$DB_DUMP" +%s 2>/dev/null)
            fi

            # Ensure we got a valid timestamp (numeric value)
            if [ -n "$LOCAL_FILE_TIME" ] && echo "$LOCAL_FILE_TIME" | grep -q '^[0-9][0-9]*$'; then
                if [ "$LOCAL_FILE_TIME" -lt "$TWELVE_HOURS_AGO" ]; then
                    LOCAL_AGE_HOURS=$(( (CURRENT_TIME - LOCAL_FILE_TIME) / 3600 ))
                    echo -e "${yellow}Local dump file is ${LOCAL_AGE_HOURS} hours old (older than 12 hours).${NC}"
                    DOWNLOAD_NEW_BACKUP=true
                else
                    LOCAL_AGE_HOURS=$(( (CURRENT_TIME - LOCAL_FILE_TIME) / 3600 ))
                    echo -e "${green}Recent local dump file found (${LOCAL_AGE_HOURS} hours old). Using existing file.${NC}"
                fi
            else
                echo -e "${yellow}Could not determine local file age (got: '$LOCAL_FILE_TIME'). Will download fresh backup.${NC}"
                DOWNLOAD_NEW_BACKUP=true
            fi
        else
            echo -e "${yellow}No local dump file found.${NC}"
            DOWNLOAD_NEW_BACKUP=true
        fi
    fi

    CREATE_NEW_BACKUP=false

    if [ "$DOWNLOAD_NEW_BACKUP" = true ]; then
        echo -e "\nChecking for database backup on ${SITE_ENV}..."

        # Check if there's a database backup and get its timestamp.
        LATEST_BACKUP_TIMESTAMP=$(terminus backup:list ${SITE_ENV} --element=database --format=list --field=date 2>/dev/null | head -1)

        # Check if force flag is set
        if [ "$FORCE_BACKUP" = true ]; then
            echo -e "${yellow}Force flag detected. Creating new backup regardless of age.${NC}"
            CREATE_NEW_BACKUP=true
        elif [ -z "$LATEST_BACKUP_TIMESTAMP" ]; then
            echo -e "${yellow}No database backup found.${NC}"
            CREATE_NEW_BACKUP=true
        else
            # Extract integer part of timestamp for comparison
            BACKUP_TIME=${LATEST_BACKUP_TIMESTAMP%.*}

            if [ "$BACKUP_TIME" -lt "$TWELVE_HOURS_AGO" ]; then
                BACKUP_AGE_HOURS=$(( (CURRENT_TIME - BACKUP_TIME) / 3600 ))
                echo -e "${yellow}Latest backup is ${BACKUP_AGE_HOURS} hours old (older than 12 hours).${NC}"
                CREATE_NEW_BACKUP=true
            else
                BACKUP_AGE_HOURS=$(( (CURRENT_TIME - BACKUP_TIME) / 3600 ))
                echo -e "${green}Recent backup found (${BACKUP_AGE_HOURS} hours old): ${LATEST_BACKUP_TIMESTAMP}${NC}"
            fi
        fi
    fi

    if [ "$CREATE_NEW_BACKUP" = true ]; then
        echo -e "${yellow}Creating new backup for ${SITE_ENV}...${NC}"
        if terminus backup:create ${SITE_ENV} --element=database -y; then
            echo -e "${green}Backup created successfully.${NC}"
            # Wait a moment for the backup to be processed
            echo "Waiting for backup to complete..."
            sleep 10
        else
            echo -e "${red}Failed to create backup for ${SITE_ENV}. Exiting.${NC}"
            exit 1
        fi
    fi

    # Download the database backup using terminus if needed
    if [ "$DOWNLOAD_NEW_BACKUP" = true ]; then
      echo -e "\nDownloading database backup from ${SITE_ENV}..."

      # Remove old dump file if it exists before downloading
      if [ -f "$DB_DUMP" ]; then
          echo -e "${yellow}Removing old database dump file...${NC}"
          rm -f "$DB_DUMP"
      fi

      terminus backup:get ${SITE_ENV} --element=database --to=${DB_DUMP}
    else
      echo -e "\nUsing existing local database dump file."
    fi

    # Ensure a clean local database named after the site exists, then import
    # the Pantheon dump into it. Using mysql directly (rather than drush) lets
    # us target the per-site database by name in this multisite setup.
    #
    # The default DDEV 'db' user only has privileges on the built-in 'db'
    # database, so we must explicitly grant it access to each new per-site
    # database (this is the user configured in each site's settings.local.php).
    # The default mysql client connects as root, which is able to grant.
    echo -e "\nReset local database '${LOCAL_DB}' and grant access to the DDEV 'db' user"
    mysql -e "DROP DATABASE IF EXISTS \`${LOCAL_DB}\`; CREATE DATABASE \`${LOCAL_DB}\`; GRANT ALL PRIVILEGES ON \`${LOCAL_DB}\`.* TO 'db'@'%'; FLUSH PRIVILEGES;"

    echo -e "\nImport DB into '${LOCAL_DB}'"
    gunzip -c "${DB_DUMP}" | mysql "${LOCAL_DB}"

    # Ensure Stage File Proxy is enabled so the local site serves files from the
    # remote origin (configured in the site's settings.local.php). The imported
    # production database may not have the module enabled, so enable it against
    # this site's local URI after the import.
    local SITE_URI="${SITE}.trialcourt.ddev.site"
    echo -e "\nEnabling Stage File Proxy for ${SITE_URI}"
    drush -l "${SITE_URI}" pm:enable stage_file_proxy -y
}

# Main execution
authenticate_terminus
refresh_pantheon_database "$@"
