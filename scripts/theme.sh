#!/bin/bash

# A helper script to run lando commands on a given sub theme.
# i.e. lando tw jcc_slo  (This will start the watcher for jcc_slo).
# See .lando.yml for how this script is called.

set -e

# Reset in case getopts has been used previously in the shell.
OPTIND=1

GREEN="\033[32m"
YELLOW="\033[33m"
RESET="\033[0m"

PIDS=""

build_all () {
  # Loop over sub themes and build them all.
  for d in $PWD/web/themes/custom/*/ ; do
    echo
    echo -e "${GREEN}Now building:${RESET} ${YELLOW} $d ${RESET}"
    echo
    install_and_build $d &
    PIDS+=" $!"
  done

  for p in $PIDS; do
    if wait $p; then
      echo "Process $p success"
    else
      return 1
    fi
  done
  echo -e "${GREEN}BUILD COMPLETE.${RESET}"
}

install_and_build () {
  npm install --prefix $1
  npm run production --prefix $1
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
  echo " - lando tb:all - Will run 'npm run build' on all themes in the themes/custom directory."
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
        # Check for jcc_ prefix. If not there add it and also run the jcc_base
        # build. Happens when theme.sh -b <theme> is called from CI config.yml.
        # Previously, every project would build every theme. This limits it to
        # only the base theme and the project theme, at least.
        export NODE_OPTIONS=--openssl-legacy-provider
        if [[ $2 != jcc_* ]] ; then
          theme_name="jcc_$2"
          echo "No jcc_ prefix detected. Building jcc_base first."
          npm run production --prefix $PWD/web/themes/custom/jcc_base
        else
          theme_name=$2
        fi
        npm run production --prefix $PWD/web/themes/custom/$theme_name
        exit 0
        ;;
    i)
        name_check $2
        # Check for jcc_ prefix. If not there add it and also run the jcc_base
        # build. Happens when theme.sh -b <theme> is called from CI config.yml.
        # Previously, every project would build every theme. This limits it to
        # only the base theme and the project theme, at least.
        export NODE_OPTIONS=--openssl-legacy-provider
        if [[ $2 != jcc_* ]] ; then
          theme_name="jcc_$2"
          echo "No jcc_ prefix detected. Installing jcc_base first."
          npm install --prefix $PWD/web/themes/custom/jcc_base
        else
          theme_name=$2
        fi
        npm install --prefix $PWD/web/themes/custom/$theme_name
        exit 0
        ;;
    w)
        name_check $2
        npm run watch --prefix $PWD/web/themes/custom/$2
        exit 0
        ;;
    esac
done

shift $((OPTIND-1))
