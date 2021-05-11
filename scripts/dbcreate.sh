#!/usr/bin/env bash

# Get the lando logger
. /helpers/log.sh

user_pass=drupal8

create_db () {
  lando_pink "Creating database '$1' in service: database"
  lando_pink "\tuser: $user_pass"
  lando_pink "\tpass: $user_pass"

  mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$1\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
  mysql -u root -e "CREATE USER IF NOT EXISTS '$user_pass@%' IDENTIFIED BY '$user_pass';"
  mysql -u root -e "GRANT ALL PRIVILEGES ON \`$1\`.* TO '$user_pass'@'%' IDENTIFIED by '$user_pass';"
}

if [ "$1" == "-i" ] ; then
  create_db $2
  exit
fi

for i in `ls -d /app/web/sites/* | grep -v '\.' | sed 's#/app/web/sites/##'`
do
  create_db $i
done
