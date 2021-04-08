#!/usr/bin/env bash

BASEDIR=$(dirname $(dirname $(realpath $0)))
env=$1

# Include color file.
. ${BASEDIR}/scripts/colors.sh

if [ "$env" == "" ] ; then
  echo -e "\n${R}This script runs a pantheon backup for all sites in the sites directory. Just pass in the environment.${RE}\n(Assumes pantheon site names jcc-[site], i.e. jcc-colusa)\nUsage:\n\tscripts/backup.sh [develop|stage|live|epic-*]"
  exit
fi

# A simple script to create a backup of all pantheon sites for a given env.
# Requires terminus and authentication.
# Usage:  scripts/backup.sh [develop | stage | live | epic-*]

for i in `ls -d $BASEDIR/web/sites/* | grep -v '\.' | sed "s#${BASEDIR}/web/sites/##"`
do
  site="jcc-${i}.${env}"
  echo -e "\n${B}Creating backup for ${site}${RE}"
  terminus backup:create ${site} &
done
