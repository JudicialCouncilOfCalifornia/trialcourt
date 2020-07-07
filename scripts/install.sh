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
  \n Run this command with a short project code as an argument i.e. slo
  \n ${Y}lando multisite slo${RE}"
  exit 1
fi

if [ -d /app/web/sites/${NEW} ] ; then
  echo -e "\n${R}Aborting. This directory already exists: /app/web/sites/${NEW}${RE}"
  exit 1
fi

if grep -Fq "${NEW}.lndo.site" /app/.lando.yml
then
  echo -e "\nProxy Detected in .lando.yml"
else
  echo -e "\n - ${R}No proxy ${RE}${NEW}.lndo.site${R} detected in .lando.yml.${RE}"
  NOPROXY=true
fi

if grep -Fq "db${NEW}" /app/.lando.yml
then
  echo -e "\nDatabase Services for db${NEW} detected in .lando.yml"
else
  echo -e "\n - ${R}No Database Services for ${RE}db${NEW}${R} detected in .lando.yml${RE}"
  NODB=true
fi

if $NODB || $NOPROXY
then
  echo -e "\nMake sure you update the .lando.yml file and run ${Y}lando rebuild${RE}.\n"
  exit 1
fi

echo -e "\n${G}Creating new multisite: ${NEW}${RE}\n"

echo "\$sites['${NEW}.lndo.site'] = '${NEW}';" >> /app/web/sites/sites.php
cp -r /app/web/sites/default /app/web/sites/$NEW
rm -rf /app/web/sites/${NEW}/files
mkdir /app/config/config-${NEW}-local

# Replace variables in settings.local.php
sed -i "s/'host' => 'database'/'host' => 'db${NEW}'/g" /app/web/sites/${NEW}/settings.local.php
sed -i "s/sites\/default\/services\.local\.yml/sites\/${NEW}\/services\.local\.yml/g" /app/web/sites/${NEW}/settings.local.php

# Replace variables in settings.pantheon.php
sed -i "s/CONFIG_SYNC_DIRECTORY => '..\/config\/config-default'/CONFIG_SYNC_DIRECTORY => '..\/config\/config-${NEW}'/g" /app/web/sites/${NEW}/settings.pantheon.php

sed -i "s/sites\/default\/files/sites\/default\/files\/${NEW}/g" /app/web/sites/${NEW}/settings.pantheon.php

# Replace variables in settings.php
sed -i "s/'..\/config\/config-default'/'..\/config\/config-${NEW}'/g" /app/web/sites/${NEW}/settings.php

sed -i "s/'..\/config\/config-default-local'/'..\/config\/config-${NEW}-local'/g" /app/web/sites/${NEW}/settings.php

sed -i "s/sites\/default\/files/sites\/default\/files\/${NEW}/g" /app/web/sites/${NEW}/settings.php

echo -e "\n${G}Multisite configured... now running installation. This will take a while...${RE}"

cd /app
drush si -l ${NEW}.lndo.site -vvv --site-name="SITE NAME" --account-mail="jcc@example.com" --account-name="JCC" --account-mail="jcc@example.com"

if [ ! -d /app/web/themes/custom/jcc_${NEW} ] ; then
  echo -e "\nCreating subtheme jcc_${NEW}"
  drush --include=/app/web/themes/custom/jcc_base jcc_base:create jcc_${NEW}

  echo -e "\nSetting new theme as default."
  drush then jcc_${NEW} -l ${NEW}.lndo.site
  drush config-set system.theme default jcc_${NEW} -y -l ${NEW}.lndo.site
fi

echo -e "\nExporting config..."
drush cex -y -l ${NEW}.lndo.site

if [ -f /app/config/config-${NEW}/config_split.config_split.local.yml ] ; then
  echo -e "\nUpdating config_split.local."
  sed -i "s/\/config\/config-default-local/\/config\/config-${NEW}-local/g" /app/config/config-${NEW}/config_split.config_split.local.yml
fi

if [ -f /app/config/config-${NEW}/locale.settings.yml ] ; then
  echo -e "\nUpdating locale.settings."
  sed -i "s/sites\/default\/files/sites\/default\/files\/${NEW}/g" /app/config/config-${NEW}/locale.settings.yml
fi

drush uli -l ${NEW}.lndo.site --druplicon

echo -e "\n${G}Installation complete!${RE}
  If everything worked, you should be able to access it with the link above."

