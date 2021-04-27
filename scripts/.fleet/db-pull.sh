#!/usr/bin/env bash

show_help() {
  name="db-pull"
  description="Syncs source database to its local counterpart."
  usage="scripts/fleet db-pull [env]"

  echo -e "\n${G}${name}${RE}\t\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

do_command() {
  for site in $sites
    do
      source="@${site}.${1}"
      local="@local.${site}"
      echo -e "\n${B}Syncing database from ${source} to ${local}"
      lando drush sql:sync $source $local -y &
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
