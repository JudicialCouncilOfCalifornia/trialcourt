#!/bin/bash

set -e

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

# Remove @ to use alias in file name.
# Command will not break if @ is not in the parameter.
ALIAS=$1
REMOVE='@'
SITE=${ALIAS#$REMOVE}

cd /app
mkdir -p data

if [ -f data/${SITE}-$(date +%Y-%m-%d).sql.gz ] ; then
  echo -e "\n${Y}File data/${SITE}-$(date +%Y-%m-%d).sql.gz already exists.${RE}\n"

  read -p "Replace? (y/N)" -n 1 -r
  echo    # (optional) move to a new line
  if [[ ! $REPLY =~ ^[Yy]$ ]] ; then
    echo -e "\nNevermind."
    exit 1
  fi
 fi

echo -e "\nGetting db for @${SITE}..."
drush @${SITE} sql-dump --gzip > data/${SITE}-$(date +%Y-%m-%d).sql.gz
