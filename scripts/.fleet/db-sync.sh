#!/usr/bin/env bash

show_help() {
  name="db-sync"
  description="Syncs source database to its local counterpart."
  usage="scripts/fleet db-sync [env]"
  # Use this exact template in all show_help functions for consistentency.
  echo -e "\n${G}${name}${RE}\t\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

do_command() {
  for site in $sites
    do
      source="@${site}.${env}"
      lando dbget $source
      lando dbim data/${site}.${env}-$(TZ=UTC date +%Y-%m-%d).sql.gz -d $site
    done
}

case $1 in

  --help|-h|-?)
    show_help
    ;;
  *)
    env=$1
    do_command
    ;;

esac
