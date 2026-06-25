#!/usr/bin/env bash

## Acquia Database Refresh Script
## Called by the main refresh command for Acquia platforms

#ddev-generated

green='\033[0;32m'
yellow='\033[1;33m'
red='\033[0;31m'
NC='\033[0m'
divider='===================================================\n'

# Parameters from main refresh command
ENVIRONMENT=${1:-prod}
FORCE_REFRESH=${2:-false}

echo -e "${green}${divider}${NC}"
echo -e "${green}Refreshing database from Acquia ${ENVIRONMENT} environment${NC}"
if [[ "$FORCE_REFRESH" == "true" ]]; then
  echo -e "${yellow}Force refresh enabled - will create new backup${NC}"
fi
echo -e "${green}${divider}${NC}"

# Check environment variables and authenticate with Acquia CLI
echo -e "${yellow}Checking Acquia CLI authentication...${NC}"

# Get environment variables from DDEV container
ACQUIA_API_KEY=$(printenv ACQUIA_API_KEY 2>/dev/null)
ACQUIA_API_SECRET=$(printenv ACQUIA_API_SECRET 2>/dev/null)

# Check for required environment variables
if [ -z "${ACQUIA_API_KEY:-}" ] || [ -z "${ACQUIA_API_SECRET:-}" ]; then
  echo -e "${red}Error: ACQUIA_API_KEY and ACQUIA_API_SECRET must be set${NC}"
  echo -e "${red}Please set these in ~/.ddev/global_config.yaml or your environment${NC}"
  echo -e "${red}Example in ~/.ddev/global_config.yaml:${NC}"
  echo -e "${red}web_environment:${NC}"
  echo -e "${red}  - ACQUIA_API_KEY=your_api_key${NC}"
  echo -e "${red}  - ACQUIA_API_SECRET=your_api_secret${NC}"
  exit 1
fi

# Authenticate with environment variables
echo -e "${yellow}Authenticating with Acquia CLI using environment variables...${NC}"
if acli -n auth:login -n --key="${ACQUIA_API_KEY}" --secret="${ACQUIA_API_SECRET}"; then
  echo -e "${green}Successfully authenticated with Acquia CLI!${NC}"
else
  echo -e "${red}Error: Failed to authenticate with Acquia CLI.${NC}"
  echo -e "${red}Please verify your ACQUIA_API_KEY and ACQUIA_API_SECRET are correct.${NC}"
  echo -e "${red}You can get these from https://cloud.acquia.com/a/profile/tokens${NC}"
  exit 1
fi

# Get application UUID from environment variable
APP_UUID=$(printenv HOSTING_SITE 2>/dev/null)
if [[ -z "$APP_UUID" ]]; then
  echo -e "${red}Error: HOSTING_SITE environment variable not set. Check .ddev/config.yaml web_environment section.${NC}"
  exit 1
fi

echo -e "${green}Using Acquia application: ${APP_UUID}${NC}"
echo -e "${green}Environment: ${ENVIRONMENT}${NC}"

# Get all databases from the environment first for backup age checking
echo -e "${yellow}Getting list of databases from Acquia ${ENVIRONMENT} environment...${NC}"
# Try the database list command with error handling
DATABASES=""
for attempt in 1 2 3; do
  echo -e "${yellow}Attempt ${attempt}: Fetching database list...${NC}"
  set -x # Adding verbosity here seems to stop it hanging when call from ddev init
  DATABASES=$(acli api:environments:database-list "${APP_UUID}.${ENVIRONMENT}" | jq -r ".[].name" 2>/dev/null)
  set +x
  if [[ -n "$DATABASES" ]]; then
    break
  fi
  echo -e "${yellow}Attempt ${attempt} failed, retrying in 5 seconds...${NC}"
  sleep 5
done

if [[ -z "$DATABASES" ]]; then
  echo -e "${red}Error: No databases found for ${ENVIRONMENT} environment${NC}"
  exit 1
fi

# Convert to array
DB_ARRAY=($DATABASES)

# Find the primary database by matching APP_UUID name
PRIMARY_DB=""
for db in "${DB_ARRAY[@]}"; do
  if [[ "$db" == "$APP_UUID" ]]; then
    PRIMARY_DB="$db"
    break
  fi
done

if [[ -z "$PRIMARY_DB" ]]; then
  echo -e "${yellow}Warning: No database matching APP_UUID '${APP_UUID}' found, falling back to first database${NC}"
  PRIMARY_DB=${DB_ARRAY[0]}
fi

echo -e "${green}Found databases: ${DATABASES}${NC}"
echo -e "${green}Primary database: ${PRIMARY_DB}${NC}"

# Check for existing backup age using primary database
BACKUP_AGE=""
if [[ "$FORCE_REFRESH" == "false" ]]; then
  echo -e "${yellow}Checking for recent database backups for ${PRIMARY_DB}...${NC}"

  # Get the most recent backup for the primary database
  RECENT_BACKUP=$(acli api:environments:database-backup-list "${APP_UUID}.${ENVIRONMENT}" "${PRIMARY_DB}" | jq -r ".[0].completed_at // empty" 2>/dev/null)

  if [[ -n "$RECENT_BACKUP" ]]; then
    # Calculate backup age in hours (strip timezone info)
    BACKUP_CLEAN=$(echo "$RECENT_BACKUP" | sed 's/+.*$//')
    BACKUP_TIMESTAMP=$(date -j -f "%Y-%m-%dT%H:%M:%S" "${BACKUP_CLEAN}" +%s 2>/dev/null)
    CURRENT_TIMESTAMP=$(date +%s)
    BACKUP_AGE_SECONDS=$((CURRENT_TIMESTAMP - BACKUP_TIMESTAMP))
    BACKUP_AGE_HOURS=$((BACKUP_AGE_SECONDS / 3600))

    echo -e "${yellow}Most recent backup for ${PRIMARY_DB} is ${BACKUP_AGE_HOURS} hours old${NC}"

    if [[ $BACKUP_AGE_HOURS -lt 12 ]]; then
      echo -e "${green}Recent backup found (less than 12 hours old), using existing backup${NC}"
    else
      echo -e "${yellow}Backup is older than 12 hours, creating new backup${NC}"
      FORCE_REFRESH=true
    fi
  else
    echo -e "${yellow}No recent backup found for ${PRIMARY_DB}, creating new backup${NC}"
    FORCE_REFRESH=true
  fi
fi

# Function to create backup if needed for a specific database
create_backup_if_needed() {
  local DB_NAME=$1

  if [[ "$FORCE_REFRESH" == "true" ]]; then
    echo -e "${yellow}Creating new database backup for ${DB_NAME}...${NC}"

    # Create the backup and get notification ID
    NOTIFICATION_RESPONSE=$(acli api:environments:database-backup-create "${APP_UUID}.${ENVIRONMENT}" "${DB_NAME}" 2>/dev/null)
    NOTIFICATION_ID=$(echo "$NOTIFICATION_RESPONSE" | jq -r '._links.notification.href // empty' 2>/dev/null | sed 's#https://cloud.acquia.com/api/notifications/##')

    if [[ -z "$NOTIFICATION_ID" ]]; then
      echo -e "${red}Error: Failed to create backup for ${DB_NAME}${NC}"
      return 1
    fi

    echo -e "${yellow}Waiting for backup to complete for ${DB_NAME} (notification: ${NOTIFICATION_ID})...${NC}"

    # Poll for completion (with timeout)
    TIMEOUT=1800  # 30 minutes
    ELAPSED=0
    while [[ $ELAPSED -lt $TIMEOUT ]]; do
      STATUS=$(acli api:notifications:find "${NOTIFICATION_ID}" | jq -r ".status // empty" 2>/dev/null)

      if [[ "$STATUS" == "completed" ]]; then
        echo -e "${green}Backup completed successfully for ${DB_NAME}!${NC}"
        # Give the API a moment to update the backup list
        sleep 10
        return 0
      elif [[ "$STATUS" == "failed" ]]; then
        echo -e "${red}Backup failed for ${DB_NAME}${NC}"
        return 1
      fi

      echo -e "${yellow}Backup still in progress for ${DB_NAME}... (${ELAPSED}s elapsed, status: ${STATUS})${NC}"
      sleep 30
      ELAPSED=$((ELAPSED + 30))
    done

    if [[ $ELAPSED -ge $TIMEOUT ]]; then
      echo -e "${red}Backup timed out after 30 minutes for ${DB_NAME}${NC}"
      return 1
    fi
  fi

  return 0
}

# Function to download and import a database
import_database() {
  local DB_NAME=$1
  local TARGET_DB=$2

  echo -e "${yellow}Processing database: ${DB_NAME} -> ${TARGET_DB}${NC}"

  # Create backup if needed
  if ! create_backup_if_needed "$DB_NAME"; then
    echo -e "${red}Failed to create backup for ${DB_NAME}${NC}"
    return 1
  fi

  # Get the most recent backup for this database
  BACKUP_ID=$(acli api:environments:database-backup-list "${APP_UUID}.${ENVIRONMENT}" "${DB_NAME}" | jq -r ".[0].id // empty" 2>/dev/null)

  if [[ -z "$BACKUP_ID" ]]; then
    echo -e "${red}Error: No database backups found for ${DB_NAME} in ${ENVIRONMENT} environment${NC}"
    return 1
  fi

  echo -e "${green}Using backup ID: ${BACKUP_ID} for database: ${DB_NAME}${NC}"

  # Download the database backup
  BACKUP_FILE="/tmp/acquia_backup_${ENVIRONMENT}_${DB_NAME}_$(date +%Y%m%d_%H%M%S).sql.gz"

  echo -e "${yellow}Getting download URL for ${DB_NAME}...${NC}"
  DOWNLOAD_URL=$(acli api:environments:database-backup-download "${APP_UUID}.${ENVIRONMENT}" "${DB_NAME}" "${BACKUP_ID}" | jq -r ".url // empty" 2>/dev/null)

  if [[ -z "$DOWNLOAD_URL" ]]; then
    echo -e "${red}Error: Failed to get download URL for ${DB_NAME}${NC}"
    return 1
  fi

  echo -e "${yellow}Downloading database backup for ${DB_NAME}...${NC}"
  curl -fsSL -o "${BACKUP_FILE}" "${DOWNLOAD_URL}"

  if [[ ! -f "$BACKUP_FILE" ]] || [[ ! -s "$BACKUP_FILE" ]]; then
    echo -e "${red}Error: Failed to download database backup for ${DB_NAME}${NC}"
    return 1
  fi

  echo -e "${green}Database backup downloaded to: ${BACKUP_FILE}${NC}"

  # Import the database
  if [[ "$TARGET_DB" == "db" ]]; then
    # Primary database goes to default 'db' database
    echo -e "${yellow}Importing primary database ${DB_NAME} into DDEV default database...${NC}"

    # First, drop the existing database to ensure clean import
    mysql -e "DROP DATABASE IF EXISTS db; CREATE DATABASE db;"

    # Import the database backup
    if [[ "$BACKUP_FILE" == *.gz ]]; then
      gunzip -c "${BACKUP_FILE}" | mysql db
    else
      mysql db < "${BACKUP_FILE}"
    fi
  else
    # Secondary databases get their own named database
    echo -e "${yellow}Creating and importing secondary database: ${TARGET_DB}${NC}"

    # Create the database
    mysql -e "DROP DATABASE IF EXISTS \`${TARGET_DB}\`; CREATE DATABASE \`${TARGET_DB}\`;"

    # Import the database backup to the named database
    if [[ "$BACKUP_FILE" == *.gz ]]; then
      gunzip -c "${BACKUP_FILE}" | mysql "${TARGET_DB}"
    else
      mysql "${TARGET_DB}" < "${BACKUP_FILE}"
    fi
  fi

  # Clean up the downloaded file
  echo -e "${yellow}Cleaning up temporary backup file: ${BACKUP_FILE}${NC}"
  rm -f "${BACKUP_FILE}"

  echo -e "${green}Database ${DB_NAME} import completed successfully!${NC}"
}

# Import all databases
for DB_NAME in "${DB_ARRAY[@]}"; do
  if [[ "$DB_NAME" == "$PRIMARY_DB" ]]; then
    # Primary database goes to default 'db' database
    import_database "$DB_NAME" "db"
  else
    # Additional databases keep their original names
    import_database "$DB_NAME" "$DB_NAME"
  fi
done

# Function to update Drupal database settings for multiple databases
update_drupal_database_settings() {
  if [[ ${#DB_ARRAY[@]} -le 1 ]]; then
    echo -e "${yellow}Single database detected, no additional database configuration needed${NC}"
    return 0
  fi

  echo -e "${yellow}Multiple databases detected, updating Drupal database configuration...${NC}"

  # Use DDEV_DOCROOT if available, otherwise fall back to docroot
  DOCROOT_DIR="${DDEV_DOCROOT:-docroot}"
  SETTINGS_FILE="${DOCROOT_DIR}/sites/default/settings.php"

  # Create the database configuration block
  DB_CONFIG=""
  for DB_NAME in "${DB_ARRAY[@]}"; do
    if [[ "$DB_NAME" == "$PRIMARY_DB" ]]; then
      # Skip primary database as it's already configured as 'default'
      continue
    fi

    # Add database connection for secondary databases
    DB_CONFIG+="
// Database connection for ${DB_NAME}
\$databases['${DB_NAME}']['default'] = array(
  'database' => '${DB_NAME}',
  'username' => 'db',
  'password' => 'db',
  'host' => 'db',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
);
"
  done

  # Only remove existing configuration if we have new configuration to add
  if [[ -n "$DB_CONFIG" ]] && grep -q "// DDEV Multi-Database Configuration" "$SETTINGS_FILE"; then
    echo -e "${yellow}Removing existing multi-database configuration...${NC}"
    # Remove existing block
    sed -i.bak '/\/\/ DDEV Multi-Database Configuration/,/\/\/ End DDEV Multi-Database Configuration/d' "$SETTINGS_FILE"
  fi

  # Check if DDEV settings block exists, if not restore it
  if ! grep -q "// Automatically generated include for settings managed by ddev." "$SETTINGS_FILE"; then
    echo -e "${yellow}Restoring missing DDEV settings section...${NC}"
    cat >> "$SETTINGS_FILE" << 'EOF'

// Automatically generated include for settings managed by ddev.
$ddev_settings = __DIR__ . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
  $settings['file_private_path'] = 'sites/default/files/private';
}
EOF
  elif ! grep -q "file_private_path" "$SETTINGS_FILE"; then
    echo -e "${yellow}Adding missing file_private_path setting to DDEV section...${NC}"
    # Add the missing line before the closing brace of the DDEV if block
    sed -i.bak '/require \$ddev_settings;/a\
  $settings['\''file_private_path'\''] = '\''sites/default/files/private'\'';
' "$SETTINGS_FILE"
  fi

  # Only add database configuration if there are secondary databases to configure
  if [[ -n "$DB_CONFIG" ]]; then
    # Add database configuration inside the DDEV settings block
    TEMP_CONFIG_FILE="/tmp/ddev_db_temp_$$.php"
    cat > "$TEMP_CONFIG_FILE" << EOF
  // DDEV Multi-Database Configuration${DB_CONFIG}  // End DDEV Multi-Database Configuration
EOF

    # Use awk to insert the configuration inside the DDEV if block, before the closing brace
    awk -v config_file="$TEMP_CONFIG_FILE" '
      /^}$/ && in_ddev_block {
        while ((getline line < config_file) > 0) {
          print line
        }
        close(config_file)
        in_ddev_block = 0
      }
      /getenv\(.*IS_DDEV_PROJECT.*\)/ { in_ddev_block = 1 }
      { print }
    ' "$SETTINGS_FILE" > "${SETTINGS_FILE}.tmp"

    mv "${SETTINGS_FILE}.tmp" "$SETTINGS_FILE"
    rm -f "$TEMP_CONFIG_FILE"
  fi

  echo -e "${green}Database configuration updated successfully!${NC}"

  # Clean up backup file
  rm -f "${SETTINGS_FILE}.bak"
}

# Update database settings if multiple databases exist
update_drupal_database_settings

echo -e "${yellow}Installing/updating composer packages...${NC}"
composer install

echo -e "${yellow}Running database updates...${NC}"
drush updatedb -y

echo -e "${yellow}Importing configuration...${NC}"
drush config:import -y

echo -e "${yellow}Clearing caches...${NC}"
drush cr

echo -e "${yellow}Adding Cypress test users...${NC}"
# Use the DDEV command path since we're in a script
if [ -x "../commands/host/cypress:users" ]; then
  ../commands/host/cypress:users 2>/dev/null || echo "Cypress users command completed"
else
  echo "Cypress users command not found, skipping..."
fi

echo -e "${green}${divider}${NC}"
echo -e "${green}Acquia database refresh complete!${NC}"
echo -e "${green}${divider}${NC}"
