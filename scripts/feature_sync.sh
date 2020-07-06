#!/usr/bin/env bash

set -e

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

SOURCE_SITE='@local.slo'
TARGET_SITE=$1

# Array of drupal multisite aliases to sync to.
SUB_SITES+=('@local.oc')
SUB_SITES+=('@local.napa')
SUB_SITES+=('@local.newsroom')

# Export any updated features from source.
echo -e "\n${G}Exporting feature changes from $SOURCE_SITE${RE}"
drush $SOURCE_SITE fu -y --bundle=jcc_tc

# Import feature changes to target or all sub sites.
if [ "$TARGET_SITE" ] ; then
  echo -e "${G}\nImporting feature changes to $TARGET_SITE${RE}"
  drush $TARGET_SITE fra -y --bundle=jcc_tc
else
  echo -e "\n${G}Importing feature changes to all subsites.${RE}"
  echo -e "\n${Y}The following sites must be installed locally with features enabled.${RE}"
  for SITE in ${SUB_SITES[@]} ; do
    echo $SITE
  done

  for SITE in ${SUB_SITES[@]} ; do
    echo -e "\nUpdating $SITE..."
    drush $SITE fra -y --bundle=jcc_tc
  done
fi
