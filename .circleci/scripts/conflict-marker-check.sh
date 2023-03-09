#!/usr/bin/env bash
set -e

conflicted="$( grep -icrE --exclude-dir={vendor,node_modules} --include=\*.{module,inc,php,js,css,html,htm,profile,install,yml,md} "^[\<]{7}" )"
if [[ -n "$conflicted" ]] ; then false; fi


