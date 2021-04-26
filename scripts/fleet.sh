#!/usr/bin/env bash

BASEDIR=$(dirname $(dirname $(realpath $0)))

command=$1
sites=$(ls -d $BASEDIR/web/sites/* | grep -v '\.' | sed "s#${BASEDIR}/web/sites/##")

# Include color file.
. ${BASEDIR}/scripts/colors.sh

case $command in

  "db-syncdown")
    shift
    . ${BASEDIR}/scripts/fleet/db-syncdown.sh
    lando=true
    ;;

  "backup")
    shift
    . ${BASEDIR}/scripts/fleet/backup.sh
    ;;

  *)
    echo -e "\n${R}Valid commands are:${RE}"
    echo -e "\tbackup [env]"
    echo -e "\tdb-syncdown [env]"
    ;;
esac

if [[ $lando == "true" ]] ; then
  lando="lando ssh -c"
fi

echo -e "\n${Y}Processes may be running in parallel in the background.${RE}\n"
echo -e "Use ${P}${lando} ps${RE} to see running processes; stdout will still be displayed."
