#!/bin/bash

# A helper script for installing new multisites.

# set -e

# Reset in case getopts has been used previously in the shell.
OPTIND=1
BASEDIR=$(dirname $(dirname $(realpath $0)))

. ${BASEDIR}/scripts/colors.sh

NEW=$1
THEME=$2
ADMTHEME="gin"

if [ "$NEW" = '' ]; then
  echo -e "\n${R}No new site name provided.${RE}
  \n Run the command as follows with the site name argument.
  \n ${Y}lando multisite inyo${RE}
  \n If you need to specify a different theme from the default, include theme name as an additional argument.
  \n ${Y}lando multisite inyo jcc_components${RE}"
  exit 1
fi

if [ -d /app/web/sites/${NEW} ] ; then
  echo -e "\n${R}Aborting. This directory already exists: /app/web/sites/${NEW}${RE}"
  exit 1
fi

echo -e "\n${G}Creating new multisite: ${NEW}${RE}\n"

# Use default theme if not using alternate
if [ "$THEME" = '' ]; then
  THEME="jcc_elevated"
fi

cp -r /app/web/sites/default /app/web/sites/$NEW
rm -rf /app/web/sites/${NEW}/files
mkdir /app/config/config-${NEW}-local

# Replace variables in settings.local.php
sed -i "s/'database' => 'default'/'database' => '${NEW}'/g" /app/web/sites/${NEW}/settings.local.php
sed -i "s/sites\/default\/services\.local\.yml/sites\/${NEW}\/services\.local\.yml/g" /app/web/sites/${NEW}/settings.local.php

# Replace variables in settings.pantheon.php
sed -i "s/CONFIG_SYNC_DIRECTORY => '..\/config\/config-default'/CONFIG_SYNC_DIRECTORY => '..\/config\/config-${NEW}'/g" /app/web/sites/${NEW}/settings.pantheon.php

sed -i "s/sites\/default\/files\/private\/default/sites\/default\/files\/private\/${NEW}/g" /app/web/sites/${NEW}/settings.pantheon.php

# Replace variables in settings.php
sed -i "s/'..\/config\/config-default'/'..\/config\/config-${NEW}'/g" /app/web/sites/${NEW}/settings.php

sed -i "s/'..\/config\/config-default-local'/'..\/config\/config-${NEW}-local'/g" /app/web/sites/${NEW}/settings.php

sed -i "s/sites\/default\/files/sites\/default\/files\/${NEW}/g" /app/web/sites/${NEW}/settings.php

# For solr config.
sed -i "s/jcc-prod/jcc-${NEW}-live/g" /app/web/sites/${NEW}/settings.php
sed -i "s/jcc-stage/jcc-${NEW}-stage/g" /app/web/sites/${NEW}/settings.php
sed -i "s/jcc-dev/jcc-${NEW}-develop/g" /app/web/sites/${NEW}/settings.php
sed -i "s/sandbox-1/jcc-${NEW}-sandbox-1/g" /app/web/sites/${NEW}/settings.php

# Install site.
echo -e "\n${G}Multisite configured... now running installation. This will take a while...${RE}"

cd /app
drush si -l ${NEW}.lndo.site -vvv -y --site-name="SITE NAME" --site-mail="no-reply@courtinfo.ca.gov" --account-name="JCC" --account-mail="webdev@jud.ca.gov"

echo -e "\nExporting config and enabling features..."
# Enable the main feature and ensure it's imported.
drush en jcc_tc2_all_immutable_config -l ${NEW}.lndo.site -y
drush fra -y -l ${NEW}.lndo.site --bundle=jcc_tc2
echo -e "\nSet ${THEME} as default theme."
drush config:set system.theme default ${THEME} -l @local.${NEW} -y
echo -e "\nSet ${ADMTHEME} as admin theme."
drush config:set system.theme admin ${ADMTHEME} -l @local.${NEW} -y
# Export to capture all initial config.
drush cex -y -l ${NEW}.lndo.site

if [ -f /app/config/config-${NEW}/config_split.config_split.local.yml ] ; then
  echo -e "\nUpdating config_split.local."
  sed -i "s/\/config\/config-default-local/\/config\/config-${NEW}-local/g" /app/config/config-${NEW}/config_split.config_split.local.yml
fi

if [ -f /app/config/config-${NEW}/locale.settings.yml ] ; then
  echo -e "\nUpdating locale.settings."
  sed -i "s/sites\/default\/files\/default/sites\/default\/files\/${NEW}/g" /app/config/config-${NEW}/locale.settings.yml
fi

if [ -f ${BASEDIR}/scripts/users.sh ] ; then
  echo -e "\n${B}Adding users.${RE}"
  # This script should contain drush commands to create new users and assign roles.
  . ${BASEDIR}/scripts/users.sh
fi

drush uli -l ${NEW}.lndo.site --druplicon

echo -e "\n${G}Installation complete!${RE}
  If everything worked, you should be able to access it with the link above."

