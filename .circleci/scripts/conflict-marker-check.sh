#!/usr/bin/env bash

set -e

# Exit script if you try to use an uninitialized variable.
set -o nounset

# Exit script if a statement returns a non-true return value.
set -o errexit

# Use the error status of the first failure, rather than that of the last item in a pipeline.
set -o pipefail

conflicted="$( grep -qlrE --exclude-dir={vendor,node_modules} --include=\*.{module,inc,php,js,css,html,htm,profile,install,yml,md} "^[\<]{7}" )"
if [[ -n "$conflicted" ]] ; then echo found; fi


