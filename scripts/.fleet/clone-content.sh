#!/usr/bin/env bash

show_help() {
  name="clone-content"
  description="Clone database and files from one environemnt to another for each site. You may include any valid [options] for the env:clone-content command.\n\t\t--options | -o\tShow detailed [options] for the terminus command."
  usage="scripts/fleet clone-content [source-env] [target-env] [options]"
  # Use this exact template in all show_help functions for consistentency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
}

do_command() {
  PIDS=""
  declare -a sitemap

  for site in $sites
    do
      site="jcc-${site}.${source}"
      echo -e "\n${B}Cloning content from ${site} to ${target}${RE} $@"
      terminus env:clone-content ${site} ${target} $@ &

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

  --options|-o)
    show_help
    echo -e "${Y}Options:${RE}"
    terminus env:clone-content --help | grep "^\s*-"
    ;;
  --help|-h|-?)
    show_help
    ;;
  *)
    source=$1
    target=$2
    shift 2
    echo $@
    do_command $@
    ;;

esac
