#!/usr/bin/env bash
set -eo pipefail

conflicted="$( grep -rE --exclude-dir={vendor,node_modules} --include=\*.{module,inc,php,js,css,html,htm,profile,install,yml,md} "^[\<]{7}" )"
if [[ -n "$conflicted" ]] ; then exit 1 ; else exit 0 ; fi


