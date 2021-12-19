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
echo -e "\n${B}Updating Inyo config .gitignore.${RE}\n"

for file in $files
do
  config_exists=$(grep -xF $file ${BASEDIR}/config/config-inyo/.gitignore)
  negated_config_exists=$(grep -xF !${file} ${BASEDIR}/config/config-inyo/.gitignore)

  if [ ! $config_exists ] && [ ! $negated_config_exists ];then
    echo -e "${Y}Ignoring ${file} ${RE}"
    echo $file >> ${BASEDIR}/config/config-inyo/.gitignore
  fi
done

echo -e "${B}Updating fleet config .gitignore.${RE}\n"

for dir in $config_dirs
do
  extensions=${dir}core.extension.yml
  if [ -f "${extensions}" ]; then
    if [[ $(grep jcc_tc2_all_immutable_config $extensions) ]]; then
      echo -e "${GB}Updating ${dir}.gitignore ${RE}"
      # Copy the .gitignore from inyo.
      if [[ "${BASEDIR}/config/config-inyo/" != "$dir" ]]; then
        cp -f ${BASEDIR}/config/config-inyo/.gitignore $dir
      fi
    else
      echo -e "${RB}$dir does not use immutable ${RE}"
    fi
  else
    echo -e "${P}$dir is not a valid directory. ${RE}"
  fi
done
