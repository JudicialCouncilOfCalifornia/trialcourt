#!/usr/bin/env bash

show_help() {
  name="enable-solr"
  description="Enables solr on all sites in specified environment."
  usage="scripts/fleet enable-solr [env]"
  # Use this exact template in all show_help functions for consistentency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
}

do_command() {
  PIDS=""
  declare -a sitemap

  for site in $sites
  do
    alias="jcc-${site}.${env}"

    echo -e "\n${B}Enabling ${RE}solr for ${alias}"
    terminus solr:enable $alias &

    PIDS+=" $!"
    sitemap["$!"]="${site}"
    # Wait to not overwhelm Pantheon with too many commands at once.
    sleep 5
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
      echo -e "\n${R}You must enter a valid [environment].${RE}"
      show_help
      exit 1
    fi
    do_command $@
    ;;

esac
