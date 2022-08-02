#!/usr/bin/env bash

show_help() {
  name="pantheon-workflow"
  description="Deploy code from Pantheon dev (master) to test, or test to live. This is only required to trigger changes to pantheon.yml file on live env. Our parallel workflow is not supported by Pantheon, so this is a workaround."
  usage="scripts/fleet pantheon-workflow [target-env] [options]"
  # Use this exact template in all show_help functions for consistentency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
}

do_command() {
  PIDS=""
  declare -a sitemap

  for site in $sites
    do
      site="jcc-${site}.${target}"
      echo -e "\n${B}Deploying code to ${site}${RE} $@"
      terminus env:deploy ${site} $@ &

      PIDS+=" $!"
      sitemap["$!"]="${site}"

      sleep 3
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
    terminus env:pantheon-workflow --help | grep "^\s*-"
    ;;
  --help|-h|-?)
    show_help
    ;;
  *)
    target=$1
    shift 1
    echo $@
    do_command $@
    ;;

esac
