#!/bin/bash

# A helper script to run lando commands on a given sub theme.
# i.e. lando tw jcc_slo  (This will start the watcher for jcc_slo).
# See .lando.yml for how this script is called.

# Reset in case getopts has been used previously in the shell.
OPTIND=1

GREEN="\033[32m"
YELLOW="\033[33m"
RESET="\033[0m"

build_all () {
  # Loop over sub themes and build them all.
  for d in /app/web/themes/custom/*/ ; do
    echo
    echo -e "${GREEN}Now building:${RESET} ${YELLOW} $d ${RESET}"
    echo
    npm install --prefix $d
    npm run production --prefix $d
  done
}

name_check () {
  if [ -z "$1" ] ;
  then
    echo "You need to indicate a theme_name for theme functions, or \"Theme Name\" for creating a sub theme."
    exit 1
  fi
}

show_help () {
  echo
  echo "This is a helper script for lando commands, for managing multiple themes."
  echo
  echo -e "You need to include a theme machine name for most functions or a theme 'human' name for creating a sub theme.\nAll themes should live in 'web/themes/custom'."
  echo
  echo " - lando ti [theme_name] - Will run 'npm install' and 'npm run build' on this theme."
  echo " - lando tw [theme_name] - Will run the watcher for theme development."
  echo " - lando tb [theme_name] - Will run 'npm run build' on this theme."
  echo -e " - lando ts [Theme Name] - Will create a new sub theme from jcc_base with the human name provided.\n    The machine name will be a created as [theme_name]"
}

while getopts "h?abiw:" opt; do
    case "$opt" in
    h|\?)
        show_help
        exit 0
        ;;
    a)
        build_all
        exit 0
        ;;
    b)
        name_check $2
        npm run production --prefix /app/web/themes/custom/$2
        exit 0
        ;;
    i)
        name_check $2
        npm install --prefix /app/web/themes/custom/$2
        exit 0
        ;;
    w)
        name_check $2
        npm run dev --prefix /app/web/themes/custom/$2
        exit 0
        ;;
    esac
done

shift $((OPTIND-1))
