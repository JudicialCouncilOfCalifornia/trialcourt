#!/usr/bin/env bash

show_help() {
  name="db-pull"
  description="Pull source database to data/ and import to local counterpart."
  usage="scripts/fleet db-pull [env]"
  # Use this exact template in all show_help functions for consistentency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
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
