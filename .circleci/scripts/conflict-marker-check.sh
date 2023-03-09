#!/bin/bash
set -e

grep -qlrE --exclude-dir={vendor,node_modules} --include=\*.{module,inc,php,js,css,html,htm,profile,install,yml,md} "^[\<]{7}"
if [ $? -eq 0 ] ; then exit 1 ; else exit 0 ; fi

