#!/usr/bin/env bash
set -e

conflicted="$( grep -lrE --exclude-dir={vendor,node_modules} --include=\*.{module,inc,php,js,css,html,htm,profile,install,yml,md} "^[\<]{7}" )"
if [[ -n "$conflicted" ]] ; then exit 1; fi


