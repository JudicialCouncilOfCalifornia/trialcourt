#!/usr/bin/env bash

# set -e

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

# Array of drupal multisite aliases to sync to.
SUB_SITES+=('@local.oc')
SUB_SITES+=('@local.napa')
SUB_SITES+=('@local.newsroom')

feature_import() {

  TARGET_SITE=$1

  # Export any updated features from source.
  echo -e "\n${Y}Make sure your features are updated and exported!${RE}"
  echo -e "\nFeatures will not be automaticaly exported by this process because we should make sure they are properly set to include the right configuration. See the documentation for more info. FEATURES.md"

  # Import feature changes to target or all sub sites.
  if [ "$TARGET_SITE" ] ; then
    drush @local.$TARGET_SITE cr
    echo -e "${G}\nImporting feature changes to $TARGET_SITE${RE}"
    drush @local.$TARGET_SITE fra -y --bundle=jcc_tc
    echo -e "\nExporting config for @local.${TARGET_SITE}..."
    drush @local.$TARGET_SITE cex -y
    drush @local.$TARGET_SITE cr
  else
    echo -e "\n${G}Importing feature changes to all multisites.${RE}"
    echo -e "\n${Y}The following sites must be installed locally with Features module enabled.${RE}"
    for SITE in ${SUB_SITES[@]} ; do
      echo $SITE
    done

    for SITE in ${SUB_SITES[@]} ; do
      drush $SITE cr
      echo -e "\nUpdating $SITE..."
      drush $SITE fra -y --bundle=jcc_tc
      echo -e "\nExporting config for $SITE..."
      drush $SITE cex -y
      drush $SITE cr
    done
  fi
}

feature_enable(){
  if [ ! $1 ] ; then
    echo -e "\n${R}No feature module indicated.${RE}"
    echo -e "${Y}Include the feature module machine name, i.e. jcc_tc_announcements${RE}"
    exit 1
  fi

  echo -e "\n${G}Enabling $1 on all multisites.${RE}"
  echo -e "\n${Y}The following sites must be installed locally with Features module enabled.${RE}"
  for SITE in ${SUB_SITES[@]} ; do
    echo $SITE
  done

  for SITE in ${SUB_SITES[@]} ; do
    drush $SITE en $1 -y
    drush $SITE cex -y
    drush $SITE cr
  done
}

if [ "$1" == 'docs' ] ; then
  more FEATURES.md
  clear
  echo
  echo https://github.com/JudicialCouncilOfCalifornia/trialcourt/blob/master/FEATURES.md
  echo
  exit 0
fi

if [ "$1" == 'enable' ] ; then
  feature_enable $2
  exit 0
fi

if [ "$1" == 'sync' ] ; then
  feature_import $2
  exit 0
fi

echo -e "\n${R}No such command: ${1}${RE}\n"
