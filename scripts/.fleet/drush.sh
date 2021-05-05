#!/usr/bin/env bash

show_help() {
  name="drush"
  description="Runs a drush command on all sites, in the given environment."
  usage="scripts/fleet drush [env] [command] [options]"
  # Use this exact template in all show_help functions for consistentency.
  echo -e "\n${G}${name}${RE}\t\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

do_command() {
  for site in $sites
  do
    [[ "$env" == "local" ]] && alias="@local.${site}" || alias="@${site}.${env}"

    echo -e "\n${B}Running ${RE}drush ${alias} ${command} ${@}"
    lando drush $alias $command $@
  done
}

case $1 in

  --help|-h|-?)
    show_help
    ;;
  *)
    env=$1
    command=$2
    shift 2

    if [[ -z "$env" ]] || [[ -z "$command" ]] ; then
      echo -e "\n${R}You must enter a valid [environment] and [command].${RE}"
      show_help
    fi
    do_command $@
    ;;

esac
