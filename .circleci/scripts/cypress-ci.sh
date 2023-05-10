#!/bin/bash
set -e

# This will do testing against the deployed branch, so it
# should only be run on deployment branches, AFTER the deploy job.
# If multisites should be tested, use:
# --config baseUrl=${ENV}-jcc-${name}.pantheonsite.io
# and loop over them.
#
# My preference is to execute specifc specs rather than all and we can create
# different spec files for each multisite.

vrtFail=false
ENV=$CIRCLE_BRANCH
if [ "$CIRCLE_BRANCH" == "master" ] ; then
  ENV=live
fi

vrt_base() {
  for name in "$@" ; do
    vrtSpec=test/cypress/integration/${name}-vrt.spec.js

    echo -e "\nTaking base snapshots for ${name}."
    npx cypress run --spec ${vrtSpec} --env updateSnapshots=true --config baseUrl=https://live-jcc-${name}.pantheonsite.io
  done
}

while getopts "be:" opt; do
  case "$opt" in
  b)
    shift $((OPTIND-1))
    vrt_base $@
    exit 0
    ;;
  e)
    echo $OPTARG
    ENV=$OPTARG
    shift $((OPTIND-1))
    ;;
  esac
done

# Purge old test data.
echo -e "\nPurging old test data."
rm -rf test/cypress/reports test/cypress/screenshots test/cypress/videos

for name in "$@" ; do
  ciSpec=test/cypress/integration/${name}-ci.cy.js
  vrtSpec=test/cypress/integration/${name}-vrt.cy.js
  baseUrl=https://${ENV}-jcc-${name}.pantheonsite.io

  # Run CI Specs
  echo -e "\nRun CI spec for ${name}"
  if [ -f $ciSpec ] ; then
    echo "${ciSpec} ${baseUrl}"
    npx cypress run --spec ${ciSpec} --config baseUrl=${baseUrl}
  fi

  # Run Visual Regression Tests
#  echo -e "\nComparing snapshots to base for ${name}"
#  if [ -f $vrtSpec ] ; then
#    npx cypress run --spec ${vrtSpec} --config baseUrl=${baseUrl}
#  fi
#
#  if [ -d "test/cypress/snapshots/${name}-vrt.cy.js/__diff_output__" ] ; then
#    vrtFail=true
#  fi
done

# Merge Reports

npx mochawesome-merge test/cypress/reports/mocha/*.json > test/cypress/reports/mocha/index.json
npx marge test/cypress/reports/mocha/index.json -f cypress-report.html -o test/cypress/reports
