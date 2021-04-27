#!/usr/bin/env bash

BASEDIR=$(dirname $(dirname $(realpath $0)))

case $1 in

  --help|-h|-?)
    echo -e "\n${B}This script runs a pantheon backup for all sites in the sites directory. Just pass in the environment.${RE}\n(Assumes pantheon site names jcc-[site], i.e. jcc-colusa)\n\nUsage:\n\tscripts/fleet backup [env]"
    exit
    ;;
  *)
    env=$1
    ;;

esac

# A simple script to create a backup of all pantheon sites for a given env.
# Requires terminus and authentication.
# Usage:  scripts/backup.sh [develop | stage | live | epic-*]

for site in $sites
do
  site="jcc-${site}.${env}"
  echo -e "\n${B}Creating backup for ${site}${RE}"
  terminus backup:create ${site} &
done
