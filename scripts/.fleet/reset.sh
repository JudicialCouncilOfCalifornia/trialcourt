#!/usr/bin/env bash

show_help() {
  name="reset"
  description="Convenience command: runs updb, cim, cr on the fleet for a given env."
  usage="scripts/fleet reset [env]"
  # Use this exact template in all show_help functions for consistentency.
  echo -e "\n${G}${name}${RE}\t\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

do_command() {
  for site in $sites
  do
    [[ "$env" == "local" ]] && alias="@local.${site}" || alias="@${site}.${env}"

    echo -e "\n${B}Resetting ${alias}${RE}"
    lando drush $alias cr
    lando drush $alias updb $@
    lando drush $alias cim $@
    lando drush $alias cr
  done

  for site in $sites
  do
    [[ "$env" == "local" ]] && alias="@local.${site}" || alias="@${site}.${env}"
    # Now generate login links at the end.
    lando drush $alias uli
  done
}

case $1 in

  --help|-h|-?)
    show_help
    ;;
  *)
    env=$1
    shift 1

    if [[ -z "$env" ]] || [[ -z "$command" ]] ; then
      echo -e "\n${R}You must enter a valid [environment].${RE}"
      show_help
    fi
    do_command $@
    ;;

esac
