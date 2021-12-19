#!/usr/bin/env bash

# This script will add all the files in jcc_tc2_all_immutable_config/config/
# install directory to the config .gitignore file for the compatible sites.
# Any previously committed versions of these files should have been removed from
# the repo with git rm --cached.

BASEDIR=$(dirname $(dirname $(realpath $0)))

. ${BASEDIR}/scripts/colors.sh

files=$(ls ${BASEDIR}/web/profiles/jcc_components_profile/modules/custom/jcc_tc2_all_immutable_config/config/install)
config_dirs=$(ls -d ${BASEDIR}/config/*/)

# Update inyo config as source of truth for other conforming sites.
echo -e "\n${B}Updating Default config .gitignore.${RE}\n"

for file in $files
do
  config_exists=$(grep -xF $file ${BASEDIR}/config/config-default/.gitignore)
  negated_config_exists=$(grep -xF !${file} ${BASEDIR}/config/config-default/.gitignore)

  if [ ! $config_exists ] && [ ! $negated_config_exists ];then
    echo -e "${Y}Ignoring ${file} ${RE}"
    echo $file >> ${BASEDIR}/config/config-default/.gitignore
  fi
done

echo -e "${B}Updating fleet config .gitignore.${RE}\n"

for dir in $config_dirs
do
  extensions=${dir}core.extension.yml
  if [ -f "${extensions}" ]; then
    if [[ $(grep jcc_tc2_all_immutable_config $extensions) ]]; then
      echo -e "${GB}Updating ${dir}.gitignore ${RE}"
      # Copy the .gitignore from default.
      if [[ "${BASEDIR}/config/config-default/" != "$dir" ]]; then
        cp -f ${BASEDIR}/config/config-default/.gitignore $dir
      fi
    else
      echo -e "${RB}$dir does not use immutable ${RE}"
    fi
  else
    echo -e "${P}$dir is not a valid directory. ${RE}"
  fi
done

# We want devs to be able to use Inyo for development without the complexity
# of updating the feature. Leads, or whoever does the deployment will be
# required to update the feature module from the config in Inyo, and run this
# script. Therefore, we want to keep all the config for Inyo.
rm -f ${BASEDIR}/config/config-inyo/.gitignore
