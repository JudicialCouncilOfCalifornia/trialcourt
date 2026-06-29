#!/usr/bin/env bash

## Upsun database refresh script
## Called from the main refresh command when HOSTING_PROVIDER=upsun

#ddev-generated

green='\033[0;32m'
yellow='\033[1;33m'
red='\033[0;31m'
NC='\033[0m'
divider='===================================================\n'

# Function to authenticate with Terminus
authenticate_upsun() {
    echo -e "\n${yellow} Authenticating with Upsun API Token... ${NC}"
    # Get PLATFORMSH_CLI_TOKEN from environment
    PLATFORMSH_CLI_TOKEN=$(printenv PLATFORMSH_CLI_TOKEN 2>/dev/null)
    PLATFORMSH_USER_ID=$(platform auth:info id 2>/dev/null)
    if [ -z "${PLATFORMSH_USER_ID:-}" ] && [ -z "${PLATFORMSH_CLI_TOKEN:-}" ]; then
        echo -e "${red}PLATFORMSH_CLI_TOKEN environment variable is not set in the web container. Please configure it in your global ddev config.${NC}"
        exit 1
    fi

    # Authenticate with platform using the machine token
    if [ -z "${PLATFORMSH_USER_ID:-}" ] && ! platform auth:api-token-login; then
        echo -e "${red}Failed to authenticate with Platform.sh CLI. Please check your PLATFORMSH_CLI_TOKEN.${NC}"
        exit 1
    fi
    echo -e "${green}Successfully authenticated with Platform.sh CLI.${NC}"
}

# Function to refresh database from Upsun
refresh_upsun_database() {
    local ENVIRONMENT="$1"
    local FORCE_BACKUP="$2"
    
    echo -e "\n${yellow} Get database from Upsun environment: ${ENVIRONMENT}. ${NC}"
    echo -e "${green}${divider}${NC}"
    
    # Define the local database dump file path
    DB_DUMP="/tmp/upsun_backup.${ENVIRONMENT}.sql.gz"

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

    # Download the database backup using terminus if needed
    if [ "$DOWNLOAD_NEW_BACKUP" = true ]; then
      echo -e "\nDownloading database backup from ${SITE_ENV}..."

      # Remove old dump file if it exists before downloading
      if [ -f "$DB_DUMP" ]; then
          echo -e "${yellow}Removing old database dump file...${NC}"
          rm -f "$DB_DUMP"
      fi

      platform db:dump -e ${ENVIRONMENT} --gzip -f ${DB_DUMP}
    else
      echo -e "\nUsing existing local database dump file."
    fi

    echo -e "\nReset DB"
    drush sql-drop -y

    echo -e "\nImport DB"
    gunzip -c ${DB_DUMP} | $(drush sql:connect)
}

# Main execution
authenticate_upsun
refresh_upsun_database "$@"

