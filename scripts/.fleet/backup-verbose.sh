#!/usr/bin/env bash


show_help() {
  name="backup"
  description="Runs a pantheon backup for all sites in the sites directory."
  usage="scripts/fleet backup [env]"
  # Use this exact template in all show_help functions for consistency.
  echo -e "\n${G}${name}${RE}\t\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
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
      else
        terminus backup:create ${site} &
      fi

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
