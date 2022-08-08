#!/usr/bin/env bash

show_help() {
  name="multidev-create"
  description="Creates a multiedev environment (from live) on all sites."
  usage="scripts/fleet multidev-create [env] [options]"
  # Use this exact template in all show_help functions for consistentency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
}

do_command() {
  PIDS=""
  declare -a sitemap

  for site in $sites
  do
    alias="jcc-${site}.live"

    echo -e "\n${B}Running ${RE}terminus multidev:create ${alias} ${env} ${@}"
    terminus multidev:create $alias $env $@ &

    PIDS+=" $!"
    sitemap["$!"]="${site}"
  done

  for p in $PIDS; do
    if wait $p; then
      echo "${sitemap["$p"]} succeeded"
    else
      echo "${sitemap["$p"]} failed"
    fi
  done
}

case $1 in

  --help|-h|-?)
    show_help
    ;;
  *)
    env=$1
    shift 1

    if [[ -z "$env" ]] ; then
      echo -e "\n${R}You must enter a valid multidev [environment] name.${RE}"
      show_help
      exit 1
    fi
    do_command $@
    ;;

esac
