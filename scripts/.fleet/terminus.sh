#!/usr/bin/env bash

show_help() {
  name="terminus"
  description="Runs a terminus command on all sites, in the given environment."
  usage="scripts/fleet terminus [env] [command] [options]"

  echo -e "\n${G}${name}${RE}\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

do_command() {
  for site in $sites
  do
    alias="jcc-${site}.${env}"

    echo -e "\n${B}Running ${RE}terminus ${alias} ${command} ${@}"
    terminus $command $alias $@
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
