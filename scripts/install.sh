#!/bin/bash

# A helper script for installing new multisites.

# set -e

# Reset in case getopts has been used previously in the shell.
OPTIND=1

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

NEW=$1
NOPROXY=false
NODB=false

if [ "$NEW" = '' ] ; then
  echo -e "\n${R}No new site code provided.${RE}
  \n Run this command with a project code as an argument i.e. slo or madera
  \n ${Y}lando multisite slo${RE}"
  exit 1
fi

if [ -d /app/web/sites/${NEW} ] ; then
  echo -e "\n${R}Aborting. This directory already exists: /app/web/sites/${NEW}${RE}"
  exit 1
fi

echo -e "\n${G}Creating new multisite: ${NEW}${RE}\n"

cp -r /app/web/sites/default /app/web/sites/$NEW
rm -rf /app/web/sites/${NEW}/files
mkdir /app/config/config-${NEW}-local

# Replace variables in settings.local.php
sed -i "s/'database' => 'default'/'database' => '${NEW}'/g" /app/web/sites/${NEW}/settings.local.php
sed -i "s/sites\/default\/services\.local\.yml/sites\/${NEW}\/services\.local\.yml/g" /app/web/sites/${NEW}/settings.local.php

# Replace variables in settings.pantheon.php
sed -i "s/CONFIG_SYNC_DIRECTORY => '..\/config\/config-default'/CONFIG_SYNC_DIRECTORY => '..\/config\/config-${NEW}'/g" /app/web/sites/${NEW}/settings.pantheon.php

sed -i "s/sites\/default\/files/sites\/default\/files\/${NEW}/g" /app/web/sites/${NEW}/settings.pantheon.php

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
drush si -l ${NEW}.lndo.site -vvv -y --site-name="SITE NAME" --account-mail="jcc@example.com" --account-name="JCC" --account-mail="jcc@example.com"

echo -e "\nExporting config and enabling features..."
# Enable the main feature and ensure it's imported.
drush en jcc_tc2_all_immutable_config -l ${NEW}.lndo.site
drush fra -y -l ${NEW}.lndo.site --bundle=jcc_tc2
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

drush uli -l ${NEW}.lndo.site --druplicon

echo -e "\n${G}Installation complete!${RE}
  If everything worked, you should be able to access it with the link above."

