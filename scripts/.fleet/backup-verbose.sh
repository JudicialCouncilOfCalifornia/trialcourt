#!/usr/bin/env bash


show_help() {
  name="backup"
  description="Runs a pantheon backup for all sites in the sites directory."
  usage="scripts/fleet backup [env]"
  # Use this exact template in all show_help functions for consistentency.
  echo -e "\n${G}${name}${RE}\t\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

do_command() {
  PIDS=""
  declare -A sitemap

  for site in $sites
    do
      site="jcc-${site}.${env}"
      echo -e "\n${B}Creating backup for ${site}${RE}"
      terminus backup:create ${site} &
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
    do_command
    ;;

esac
