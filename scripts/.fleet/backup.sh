#!/usr/bin/env bash

show_help() {
  name="backup"
  description="Runs a pantheon backup for all sites in the sites directory."
  usage="scripts/fleet backup [env]"
  # Use this exact template in all show_help functions for consistentency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
}

do_command() {
  for site in $sites
    do
      site="jcc-${site}.${env}"
      echo -e "\n${B}Creating backup for ${site}${RE}"
      terminus backup:create ${site} &
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
