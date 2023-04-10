#!/usr/bin/env bash
echo $BASEDIR
show_help() {
  name="backup-verbose"
  description="Runs a pantheon backup for all sites in the sites directory."
  usage="scripts/fleet backup [env]"
  # Use this exact template in all show_help functions for consistency.
  . ${BASEDIR}/scripts/.fleet/templates/show_help.sh
}

do_command() {
  PIDS=""
  declare -a sitemap

  echo -e "\n${B}Creating backup for SRL${RE}"
  terminus backup:create "jcc-srl.${env}" &

  for site in $sites
    do
      site="jcc-${site}.${env}"
      echo -e "\n${B}Creating backup for ${site}${RE}"
      if [ ${site} == "jcc-newsroom.${env}" ] || [ ${site} == "jcc-supremecourt.${env}" ]
      then
        echo -e "\n${G}*** Database only for ${site}${RE}"
        terminus backup:create ${site} --element=db &
      elif [ ${site} == "jcc-stanislaus.${env}" ]
      then
        echo -e "\n${G}*** Skipping backup for ${site}${RE}"
      else
        terminus backup:create ${site} &
      fi

      PIDS+=" $!"
      sitemap["$!"]="${site}"

      # Don't overwhelm pantheon.
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

  --help|-h|-?)
    show_help
    ;;
  *)
    env=$1
    do_command
    ;;

esac
