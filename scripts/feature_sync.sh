#!/usr/bin/env bash

# set -e

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

# Create array of drupal multisite aliases to sync to, from directories in
# sites directory.
for path in /app/web/sites/* ; do
  # Skip if not a directory.
  [ -d "${path}" ] || continue
  dirname="$(basename "${path}")"

  skip=("oc" "napa" "default")
  if [[ ! "${skip[@]}" =~ "${dirname}" ]]; then
    SUB_SITES+=("@local.${dirname}")
  fi
done

TARGET_FEATURE=""
OPTIONS=f:
LONGOPTS=feature:

# -regarding ! and PIPESTATUS see above
# -temporarily store output to be able to check for errors
# -activate quoting/enhanced mode (e.g. by writing out “--options”)
# -pass arguments only via   -- "$@"   to separate them correctly
! PARSED=$(getopt --options=$OPTIONS --longoptions=$LONGOPTS --name "$0" -- "$@")
if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
    # e.g. return value is 1
    #  then getopt has complained about wrong arguments to stdout
    exit 2
fi
# read getopt’s output this way to handle the quoting right:
eval set -- "$PARSED"

# now enjoy the options in order and nicely split until we see --
while true; do
    case "$1" in
        -f|--feature)
            TARGET_FEATURE="$2"
            shift 2
            ;;
        --)
            shift
            break
            ;;
        *)
            echo "Programming error"
            exit 3
            ;;
    esac
done

feature_import() {

  TARGET_SITE=$1

  # Export any updated features from source.
  echo -e "\n${Y}Make sure your features are updated and exported!${RE}"
  echo -e "\nFeatures will not be automaticaly exported by this process because we should make sure they are properly set to include the right configuration. See the documentation for more info. FEATURES.md"

  # Import feature changes to target or all sub sites.
  if [ "$TARGET_SITE" ] ; then
    if [ "$TARGET_FEATURE" ] ; then
      echo -e "${G}\nImporting $TARGET_FEATURE changes to $TARGET_SITE${RE}"
      drush @local.$TARGET_SITE fr $TARGET_FEATURE -y --bundle=jcc_tc
    else
      echo -e "${G}\nImporting feature changes to $TARGET_SITE${RE}"
      drush @local.$TARGET_SITE fra -y --bundle=jcc_tc
    fi
    echo -e "\nExporting config for @local.${TARGET_SITE}..."
    drush @local.$TARGET_SITE cex -y
  else
    echo -e "\n${G}Importing feature changes to all multisites.${RE}"
    echo -e "\n${Y}The following sites must be installed locally with Features module enabled.${RE}"
    for SITE in ${SUB_SITES[@]} ; do
      echo $SITE
    done

    for SITE in ${SUB_SITES[@]} ; do
      if [ "$TARGET_FEATURE" ] ; then
        echo -e "\nUpdating $TARGET_FEATURE of $SITE..."
        drush $SITE fr $TARGET_FEATURE -y --bundle=jcc_tc
      else
        echo -e "\nUpdating all features of $SITE..."
        drush $SITE fra -y --bundle=jcc_tc
      fi
      echo -e "\nExporting config for $SITE..."
      drush $SITE cex -y
    done
  fi
}

drush_fra_cex() {
  echo -e "\nUpdating $SITE..."
  drush $1 fra -y --bundle=jcc_tc
  echo -e "\nExporting config for $1..."
  drush $1 cex -y
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
  done

  wait
  echo -e "\n${G}Feature enable complete.${RE}"
}

drush_en_cex() {
  drush $1 en $2 -y
  drush $1 cex -y
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
