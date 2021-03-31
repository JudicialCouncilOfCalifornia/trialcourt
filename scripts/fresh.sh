#!/bin/bash

# A helper script for resetting install to a fresh state.

set -e

# Reset in case getopts has been used previously in the shell.
OPTIND=1

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

DATABASE_FILE=false
HOST=false

# Make sure data directory exists.
mkdir -p /app/data

show_help () {
  echo
  echo "This is a helper script for resetting install to a fresh state."
  echo
  echo -e " ${Y}-h${RE} \tShow this help message."
  echo -e " ${Y}-f \t[path/to/sql]${RE} - Import a database file."
  echo -e " ${Y}-l${RE} \t${Y}[URI]${RE} for drush commands. Target specific multisites. \n\tSee available appserver proxies in ${G}.lando.yml${RE}\n\tOR urls in ${G}lando info --service=appserver${RE}."
}

while getopts "h?l:f:" opt; do
    case "$opt" in
    h|\?)
      # This script is called multiple times in a row in different services.
      # Only show help once.
      echo "help!" >> /app/data/.help.fresh
      HELP=$(cat /app/data/.help.fresh | wc -l)
      if [[ $HELP > 2 ]] ; then
        show_help
        rm /app/data/.help.fresh
      fi
      exit 0
      ;;
    f)
      DATABASE_FILE=$OPTARG
      ;;
    l)
      HOST=$OPTARG
      ;;
    esac
done

shift $((OPTIND-1))

if [ "$HOST" = false ] ; then
  cat /app/.lando.yml | grep proxy: -A 10
  echo 'more...'
  echo -e "\n${R}No multisite indicated.${RE}\n"
  echo -e "See .lando.yml for proxy for URIs (i.e.: above)"
  echo -e "Please use ${Y}-l [URI]${RE} option."
  exit 1
fi

# Do different things depending on which service this is running in.
if [ "$LANDO_SERVICE_NAME" = "appserver" ] ; then
  if [ "$DATABASE_FILE" != false ] && [ ! -f /app/data/.db-imported.fresh ] ; then
    if [ -f $DATABASE_FILE ] ; then
      echo -e "\nDropping database for $HOST"
      drush sql-drop -l $HOST

      if [[ $DATABASE_FILE =~ \.gz$ ]] ; then
        echo -e "\nImporting $DATABASE_FILE for $HOST"
        gunzip -c $DATABASE_FILE | drush sqlc -l $HOST
      fi
      if [[ $DATABASE_FILE =~ \.sql$ ]] ; then
        echo -e "\nImporting $DATABASE_FILE for $HOST"
        cat $DATABASE_FILE | drush sqlc -l $HOST
      fi
      # Leave an indicator as a condition for dependent commands.
      touch /app/data/.db-imported.fresh
    else
      echo -e "\n${R}File does not exist: $DATABASE_FILE${RE}\n"
      exit 1
    fi
  fi

  if [ ! -f /app/data/.composer-installed.fresh ] ; then
    echo -e "\nRunning composer install...\n"
    cd /app
    composer install
    # Leave an indicator as a condition for dependent commands.
    touch /app/data/.composer-installed.fresh
  fi
fi
if [ "$LANDO_SERVICE_NAME" = "node-cli" ] && [ -f /app/data/.composer-installed.fresh ] ; then
  if [ ! -f /app/data/.themes-built.fresh ] ; then
    echo -e "\nBuilding themes...\n"
    echo -e "\n${Y}Theme Build Suspended. Build manually and include assets in the repo. The sub themes will be removed when old sites migrate to new profile so building every theme is time consuming. New theme will not need build as this is handled in the pattern library.${RE}"
    # /app/scripts/theme.sh -a

    # Leave an indicator as a condition for later appserver commands.
    touch /app/data/.themes-built.fresh
  fi
fi
if [ "$LANDO_SERVICE_NAME" = "appserver" ] && [ -f /app/data/.themes-built.fresh ] ; then
  echo -e "\nResetting drupal for ${HOST}\n"
  echo -e "\nRunning updb..."
  drush updb -y -l $HOST
  echo -e "\nRunning config import..."
  drush cim -y -l $HOST
  echo -e "\nClearing caches..."
  drush cr -l $HOST
  # Clean up the indicator files for the next run.
  rm /app/data/.*.fresh
fi
