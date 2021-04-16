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
TARGET_SITE=""
FEATURE_BUNDLE="jcc_tc2"
OPTIONS=f:
LONGOPTS=feature:

# -regarding ! and PIPESTATUS see above
# -temporarily store output to be able to check for errors
# -activate quoting/enhanced mode (e.g. by writing out “--options”)
# -pass arguments only via   -- "$@"   to separate them correctly
# ! PARSED=$(getopt --options=$OPTIONS --longoptions=$LONGOPTS --name "$0" -- "$@")
# if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
#     # e.g. return value is 1
#     #  then getopt has complained about wrong arguments to stdout
#     exit 2
# fi
# # read getopt’s output this way to handle the quoting right:
# eval set -- "$PARSED"

# PARSE THE ARGZZ
while (( "$#" )); do
    case "$1" in
        -b|--bundle)
            FEATURE_BUNDLE="$2"
            shift 2
            ;;
        -c|--command)
            command="$2"
            shift 2
            ;;
        -f|--feature)
            TARGET_FEATURE="$2"
            shift 2
            ;;
        -s|--site)
            TARGET_SITE="$2"
            shift 2
            ;;
        --)
            shift
            break
            ;;
        *)
            shift
            ;;
    esac
done

feature_import() {

  # Export any updated features from source.
  echo -e "\n${Y}Make sure your features are updated and exported!${RE}"
  echo -e "\nFeatures will not be automaticaly exported by this process because we should make sure they are properly set to include the right configuration. See the documentation for more info. FEATURES.md"

  # Import feature changes to target or all sub sites.
  if [ "$TARGET_SITE" ] ; then
    echo -e "${G}\nRunning updates in case config should be deleted on $TARGET_SITE${RE}"
    drush @local.$TARGET_SITE updb -y

    if [ "$TARGET_FEATURE" ] ; then
      echo -e "${G}\nImporting $TARGET_FEATURE changes to $TARGET_SITE${RE}"
      drush @local.$TARGET_SITE fr $TARGET_FEATURE -y --bundle=$FEATURE_BUNDLE
    else
      echo -e "${G}\nImporting feature changes to $TARGET_SITE${RE}"
      drush @local.$TARGET_SITE fra -y --bundle=$FEATURE_BUNDLE
    fi

    echo -e "\nExporting config for @local.${TARGET_SITE}..."
    drush @local.$TARGET_SITE cex -y
  else
    echo -e "\n${G}Importing feature changes from Bundle: $FEATURE_BUNDLE to all multisites.${RE}"
    echo -e "\n${Y}The following sites must be installed locally with Features module enabled.${RE}"

    for SITE in ${SUB_SITES[@]} ; do
      echo $SITE
    done

    for SITE in ${SUB_SITES[@]} ; do
      echo -e "${G}\nRunning updates in case config should be deleted on $SITE${RE}"
      drush $SITE updb -y

      if [ "$TARGET_FEATURE" ] ; then
        echo -e "\nUpdating $TARGET_FEATURE of $SITE..."
        drush $SITE fra $TARGET_FEATURE -y --bundle=$FEATURE_BUNDLE
      else
        echo -e "\nUpdating all features of $SITE from Bundle: $FEATURE_BUNDLE"
        drush $SITE fra -y --bundle=$FEATURE_BUNDLE
      fi

      echo -e "\nExporting config for $SITE..."
      drush $SITE cex -y
    done
  fi
}

drush_fra_cex() {
  echo -e "\nUpdating $SITE..."
  drush $1 fra -y --bundle=$FEATURE_BUNDLE
  echo -e "\nExporting config for $1..."
  drush $1 cex -y
}

feature_enable(){
  if [ ! $1 ] ; then
    echo -e "\n${R}No feature module indicated.${RE}"
    echo -e "${Y}Include the feature module machine name, i.e. -f jcc_tc_announcements${RE}"
    exit 1
  fi

  echo -e "\n${G}Enabling $1 on all multisites.${RE}"
  echo -e "\n${Y}The following sites must be installed locally with Features module enabled.${RE}"
  for SITE in ${SUB_SITES[@]} ; do
    echo $SITE
  done

  for SITE in ${SUB_SITES[@]} ; do
    echo -e "\n${G}Enabling $1 on ${SITE}.${RE}"
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

if [ "$command" == 'docs' ] ; then
  echo -e "\n${G}feature:[command] -b -f -s${RE}"
  echo -e "\n${Y}This command needs work. It's a little tricky right now with two separate groups of sites running different feature bundles, we can't roll out all features to all sites.\n\t@todo: Refactor this for more concise commands. \n\t@todo: Hurry to migrate old sites to the new profile/feature set.\n\t@todo: Develop process for feature syncing in stage environment so developers don't need to have every multisite running locally to sync feature config.${RE}"
  echo -e "${R}feature:enable${RE} will not really work effectively for the reasons mentioned above."
  echo -e "${G}feature:sync${RE} can be used for mass deployment via the -b [bundle] option as only sites with features of the specified bundle will have related feature config imported."
  echo -e "\t-b|--bundle\tjcc_tc or jcc_tc2 (default)"
  echo -e "\t-f|--feature\tThe target feature to import config"
  echo -e "\t-s|--site\tThe target site to import config"
  echo -e "\n${G}More Info:${RE}"
  echo https://github.com/JudicialCouncilOfCalifornia/trialcourt/blob/master/FEATURES.md
  echo
  exit 0
fi

if [ "$command" == 'enable' ] ; then
  feature_enable $TARGET_FEATURE
  exit 0
fi

if [ "$command" == 'sync' ] ; then
  feature_import
  exit 0
fi

echo -e "\n${R}No such command: ${command}${RE}\n"
