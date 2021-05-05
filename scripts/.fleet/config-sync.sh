#!/usr/bin/env bash

show_help() {
  name="config-sync"
  description="Deploys featurized config to all sites and exports their new config.\n\t\t-b\tjcc_tc (legacy) or jcc_tc2 (default)\n\t\t-e\tThe environment (${R}EXPERIMENTAL and DANGEROUS!${RE} Do not use on live.)\n\t\t-f\tThe target feature to import config\n\t\t-s\tThe target site to import config\n\t\t${G}More Info:${RE} https://github.com/JudicialCouncilOfCalifornia/trialcourt/blob/master/FEATURES.md"
  usage="scripts/fleet config-sync [-b -e -f -s]"
  # Use this exact template in all show_help functions for consistentency.
  echo -e "\n${G}${name}${RE}\t${Y}Usage:${RE}\t${usage}"
  echo -e "\n\t\t${description}"
}

feature_sync() {

  [[ "$env" == "local" ]] && alias="@local.${site}" || alias="@${site}.${env}"

  echo -e "${G}\nRunning updates in case config should be deleted on $alias${RE}"
  lando drush $alias updb -y

  if [ "$TARGET_FEATURE" ] ; then
    echo -e "\nUpdating $TARGET_FEATURE of $alias..."
    lando drush $alias fra $TARGET_FEATURE -y --bundle=$FEATURE_BUNDLE
  else
    echo -e "\nUpdating all features of $alias from Bundle: $FEATURE_BUNDLE"
    lando drush $alias fra -y --bundle=$FEATURE_BUNDLE
  fi

  if [[ "$env" != "local" ]] ; then
    echo -e "\n${Y}EXPERIMENTAL${E} Attempting to pull config from sync environment to localfor $alias..."
    echo "lando drush $alias cex --destination=${BASEDIR}/config/config-${site}"
  fi

  echo -e "\nExporting config for $alias..."
  lando drush $alias cex -y
}

do_command() {
  echo -e "\n${G}Importing feature changes from Bundle: $FEATURE_BUNDLE to all multisites.${RE}"
  echo -e "\n${Y}The sites must be installed at ${env} with Features module enabled.${RE}"
  echo -e "\n${Y}Make sure your features are updated and exported!${RE}"
  echo -e "\nFeatures will not be automaticaly exported by this process because\n we should make sure they are properly set to include the right\n configuration. See the documentation for more info. FEATURES.md"

  if [[ ! -z "$TARGET_SITE" ]] ; then
    site=$TARGET_SITE
    feature_sync
    exit
  fi

  for site in $sites
  do
    feature_sync
  done
}

TARGET_FEATURE=""
FEATURE_BUNDLE="jcc_tc2"
env="local"

while getopts ":b:e:f:hs:" opt; do
  case "$opt" in
    b)
      FEATURE_BUNDLE=$OPTARG
      ;;
    e)
      env=$OPTARG
      echo -e "\n${Y}Should probably do something to ensure/enforce and worthy environment.${RE}"
      ;;
    f)
      TARGET_FEATURE=$OPTARG
      ;;
    h)
      show_help
      help="true"
      ;;
    s)
      TARGET_SITE=$OPTARG
      ;;
    \?)
      echo "Invalid option: $OPTARG" 1>&2
      ;;
    :)
      echo "Invalid option: $OPTARG requires an argument" 1>&2
      ;;
  esac
done

shift $((OPTIND -1))

if [[ "$help" != "true" ]] ; then
  do_command
fi
