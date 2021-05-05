#!/usr/bin/env bash

set -e

# Get the lando logger
. /helpers/log.sh

# Set generic config
FILE=""
WIPE=true
HOST=localhost
SERVICE=$LANDO_SERVICE_NAME
DATABASE=""

PORT=${LANDO_DB_IMPORT_PORT:-3306}
USER=${LANDO_DB_IMPORT_USER:-root}

show_help () {
  lando_pink "\nThis is a helper script similar to 'lando db-import' but supports multisite with multiple databases in one container/service instead of requiring a service for every multisite."
  echo -e "\nUsage: lando dbim -d [database] [file]"
  echo -e "\t-h|--help\t\t\tShow this help message."
  echo -e "\t-d|--database [database]\tThere is a database for every directory in the web/sites/ directory. i.e. default, inyo, slo, etc."
  echo -e "\t--no-wipe\t\t\tDo not wipe the database before import."
}

# PARSE THE ARGZZ
while (( "$#" )); do
  case "$1" in
    -d|--database|--database=*)
      if [ "${1##--database=}" != "$1" ]; then
        DATABASE=${1##--database=}
        shift
      else
        DATABASE=$2
        shift 2
      fi
      # echo $DATABASE
      # exit
      ;;
    -h|--help)
      show_help
      exit 0
      ;;
    --no-wipe)
        WIPE=false
        shift
      ;;
    --)
      shift
      break
      ;;
    -*|--*=)
      shift
      ;;
    *)
      if [[ "$1" = /* ]]; then
        FILE="${1//\\//}"
      else
        FILE="$(pwd)/${1//\\//}"
      fi
      shift
      ;;
  esac
done

if [ "$DATABASE" == ""] ; then
  show_help
  exit 1
fi

# Set positional arguments in their proper place
eval set -- "$FILE"
PV=""
CMD=""

# Ensure file perms on linux
if [ "$LANDO_HOST_OS" = "linux" ] && [ $(id -u) = 0 ]; then
  chown $LANDO_HOST_UID:$LANDO_HOST_GID "${FILE}"
fi

# Use file or stdin
if [ ! -z "$FILE" ]; then
  # Validate we have a file
  if [ ! -f "$FILE" ]; then
    lando_red "File $FILE not found!"
    exit 1;
  fi

  CMD="$FILE"
else
  # Build DB specific connection string
  CMD="mysql -h $HOST -P $PORT -u $USER ${LANDO_EXTRA_DB_IMPORT_ARGS}"

  # Read stdin into DB
  $CMD #>/dev/null
  exit 0;
fi

# Inform the user of things
echo "Preparing to import $FILE into database '$DATABASE' on service '$SERVICE' as user $USER..."

# Wipe the database if set
if [ "$WIPE" == "true" ]; then
  echo ""
  echo "Emptying $DATABASE... "
  lando_yellow "NOTE: See the --no-wipe flag to avoid this step!"

  # DO db specific wiping
  # Build the SQL prefix
  SQLSTART="mysql -h $HOST -P $PORT -u $USER ${LANDO_EXTRA_DB_IMPORT_ARGS} $DATABASE"

  # Gather and destroy tables
  TABLES=$($SQLSTART -e 'SHOW TABLES' | awk '{ print $1}' | grep -v '^Tables' || true)

  # PURGE IT ALL! Drop views and tables as needed
  for t in $TABLES; do
    echo "Dropping $t from $DATABASE database..."
    $SQLSTART <<-EOF
      SET FOREIGN_KEY_CHECKS=0;
      DROP VIEW IF EXISTS \`$t\`;
      DROP TABLE IF EXISTS \`$t\`;
EOF
  done
fi

# Check to see if we have any unzipping options or GUI needs
if command -v gunzip >/dev/null 2>&1 && gunzip -t $FILE >/dev/null 2>&1; then
  echo "Gzipped file detected!"
  if command -v pv >/dev/null 2>&1; then
    CMD="pv $CMD"
  else
    CMD="cat $CMD"
  fi
  CMD="$CMD | gunzip"
elif command -v unzip >/dev/null 2>&1 && unzip -t $FILE >/dev/null 2>&1; then
  echo "Zipped file detected!"
  CMD="unzip -p $CMD"
  if command -v pv >/dev/null 2>&1; then
    CMD="$CMD | pv"
  fi
else
  if command -v pv >/dev/null 2>&1; then
    CMD="pv $CMD"
  else
    CMD="cat $CMD"
  fi
fi

CMD="$CMD | mysql -h $HOST -P $PORT -u $USER ${LANDO_EXTRA_DB_IMPORT_ARGS} $DATABASE"

# Import
lando_pink "Importing $FILE to $DATABASE"
eval "$CMD" && lando_green "Import complete!" || lando_red "Import failed."
