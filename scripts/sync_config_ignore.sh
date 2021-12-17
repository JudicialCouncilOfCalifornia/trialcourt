#!/usr/bin/env bash

# This script will add all the files in jcc_tc2_all_immutable_config/config/
# install directory to the config .gitignore file and remove any committed files
# of the same name.

# NOTE: This is only a dry-run. It will indicate what it would do, but not
# actually do it. We need to make sure no sites are exceptions to this,
# otherwise, we need to be more specific about which config directories ignore
# these files.

BASEDIR=$(dirname $(dirname $(realpath $0)))

files=$(ls ${BASEDIR}/web/profiles/jcc_components_profile/modules/custom/jcc_tc2_all_immutable_config/config/install)

for file in $files
do
#  echo "Processing $file"
  exists=$(grep -xF $file ${BASEDIR}/config/.test)
  if [ ! $exists ] ;then
    echo Ignoring $file
    # echo $file >> ${BASEDIR}/config/.gitignore
    # git rm --cached ${BASEDIR}/config/*/${file};
  else
    echo NOT IGNORING $file
  fi
done
