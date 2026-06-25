#!/usr/bin/env bash

## Custom database refresh script
## Called from the main refresh command when HOSTING_PROVIDER=custom

#ddev-generated

green='\033[0;32m'
yellow='\033[1;33m'
red='\033[0;31m'
NC='\033[0m'
divider='===================================================\n'


# Function to refresh database from a custom SSH host
refresh_custom_database() {
    echo -e "\n${yellow} Get database from custom host: ${HOSTING_USER}@${HOSTING_HOST}. ${NC}"
    echo -e "${green}${divider}${NC}"

    # Define the local database dump file path
    DB_DUMP="${HOSTING_DB_DUMP:-/tmp/db.sql}"
    DB_DUMP_GZ="${DB_DUMP}.gz"

    echo -e "\nChecking for local database dump file..."

    # Calculate current time and 12-hour threshold.
    CURRENT_TIME=$(date +%s)
    TWELVE_HOURS_AGO=$((CURRENT_TIME - 43200))  # 12 hours = 43200 seconds

    DOWNLOAD_NEW_BACKUP=false

    # Check if local dump file exists and its age
    if [ -f "$DB_DUMP_GZ" ]; then
        # Get file modification time using a more reliable method
        LOCAL_FILE_TIME=""

        # Try different methods to get the file modification time
        if [ -z "$LOCAL_FILE_TIME" ]; then
            # Method 1: Try stat with format specifier (GNU/Linux style)
            LOCAL_FILE_TIME=$(stat -c %Y "$DB_DUMP_GZ" 2>/dev/null)
        fi

        if [ -z "$LOCAL_FILE_TIME" ]; then
            # Method 2: Try stat with format specifier (BSD/macOS style)
            LOCAL_FILE_TIME=$(stat -f %m "$DB_DUMP_GZ" 2>/dev/null)
        fi

        if [ -z "$LOCAL_FILE_TIME" ]; then
            # Method 3: Use date command with file reference
            LOCAL_FILE_TIME=$(date -r "$DB_DUMP_GZ" +%s 2>/dev/null)
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

    # Download the database backup using terminus if needed
    if [ "$DOWNLOAD_NEW_BACKUP" = true ]; then
        echo -e "\nDownloading database backup from ${SITE_ENV}..."

        # Remove old dump file if it exists before downloading
        if [ -f "$DB_DUMP_GZ" ]; then
            echo -e "${yellow}Removing old database dump file...${NC}"
            rm -f "$DB_DUMP_GZ"
        fi

        if [ -z "$HOSTING_USER" ] || [ -z "$HOSTING_HOST" ]; then
            echo "HOSTING_USER and HOSTING_HOST variables required."
            exit 1
        fi

        # Load SSH key from agent
        SSH_CMD="ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no -p ${HOSTING_PORT:-22}"
        TEMP_KEY="/tmp/temp_key"
        ssh-add -L | grep "${HOSTING_KEY:-id_rsa}" > "$TEMP_KEY"
        if eval "$SSH_CMD -i ${TEMP_KEY} ${HOSTING_USER}@${HOSTING_HOST} 'echo SSH connection successful'"; then
            echo -e "${green}SSH connection successful.${NC}"
        else
            echo -e "${red}Error: Cannot connect to remote host via SSH${NC}"
            echo -e "${red}Please ensure:${NC}"
            echo -e "${red}1. Your SSH key is properly configured on the remote server${NC}"
            echo -e "${red}2. SSH agent is running: ddev auth ssh${NC}"
            echo -e "${red}3. Your key is added to the remote server's ~/.ssh/authorized_keys${NC}"
            echo -e "${red}4. Key name is set in config.local.yaml: HOSTING_KEY=your_key_name${NC}"
            exit 1
        fi

        $SSH_CMD -i "${TEMP_KEY}" "${HOSTING_USER}@${HOSTING_HOST}" "cd ${HOSTING_PATH:-.}; ${HOSTING_DRUSH:-vendor/bin/drush} sql:dump --result-file=${DB_DUMP} --extra-dump=\"--disable-ssl --no-tablespaces\" ${HOSTING_DB_DUMP_OPTIONS:---gzip}"
        rsync -avz -e "${SSH_CMD} -i ${TEMP_KEY}" --remove-source-files "${HOSTING_USER}@${HOSTING_HOST}:${DB_DUMP_GZ}" "${DB_DUMP_GZ}"
    else
        echo -e "\nUsing existing local database dump file."
    fi

    echo -e "\nReset DB"
    drush sql-drop -y

    echo -e "\nImport DB"
    gunzip -c ${DB_DUMP_GZ} | $(drush sql:connect)
}

# Main execution
refresh_custom_database

