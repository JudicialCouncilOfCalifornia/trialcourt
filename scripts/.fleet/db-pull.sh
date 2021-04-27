#!/usr/bin/env bash

case $1 in

  --help|-h|-?)
    echo -e "\n${B}Syncs source database to its local counterpart.${RE}\n(Assumes pantheon site names jcc-[site], i.e. jcc-colusa)\nUsage:\n\tscripts/fleet db-pull [env]"
    exit
    ;;
  *)
    env=$1
    ;;

esac

# Use drush to sync source database to local for all sites.
for site in $sites
do
  source="@${site}.${1}"
  local="@local.${site}"
  echo -e "\n${B}Syncing database from ${source} to ${local}"
  lando drush sql:sync $source $local -y &
done
