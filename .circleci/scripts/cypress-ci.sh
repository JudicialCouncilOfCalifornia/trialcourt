#!/bin/bash

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
  ciSpec=test/cypress/integration/${name}-ci.spec.js
  vrtSpec=test/cypress/integration/${name}-vrt.spec.js
  baseUrl=https://${ENV}-jcc-${name}.pantheonsite.io

  # Run CI Specs
  echo -e "\nRun CI spec for ${name}"
  if [ -f $ciSpec ] ; then
    echo "${ciSpec} ${baseUrl}"
    npx cypress run --spec ${ciSpec} --config baseUrl=${baseUrl}
  fi

  # Run Visual Regression Tests
  echo -e "\nComparing snapshots to base for ${name}"
  if [ -f $vrtSpec ] ; then
    npx cypress run --spec ${vrtSpec} --config baseUrl=${baseUrl}
  fi

  if [ -d "test/cypress/snapshots/${name}-vrt.spec.js/__diff_output__" ] ; then
    vrtFail=true
  fi
done

# Merge Reports
npx mochawesome-merge test/cypress/reports/mocha/*.json > test/cypress/reports/mocha/index.json
npx marge test/cypress/reports/mocha/index.json -f cypress-report.html -o test/cypress/reports

# Message Slack with artifacts.

# Set an empty diff message block.
read -r -d '' reportBlock <<-EOF
{
  "type": "section",
  "text": {
    "type": "mrkdwn",
    "text": "End to End Report: Not Available :confounded:"
  }
}
EOF

if [ -f "test/cypress/reports/cypress-report.html" ] ; then
  # Set the artifact url.
  report=${CIRCLE_BUILD_URL}/artifacts/${CIRCLE_NODE_INDEX}/reports/cypress-report.html
  # We have a report so set the reportBlock with the new message and link.
  read -r -d '' reportBlock <<-EOF
{
  "type": "section",
  "text": {
    "type": "mrkdwn",
    "text": "End to End Report"
  },
  "accessory": {
    "type": "button",
    "text": {
      "type": "plain_text",
      "text": "View Report :page_facing_up:",
      "emoji": true
    },
    "value": "report",
    "url": "${report}",
    "action_id": "button-action"
  }
}
EOF

fi

# Set an empty diff message block.
read -r -d '' diffBlock <<-EOF
{
  "type": "section",
  "text": {
    "type": "mrkdwn",
    "text": "Visual Regression Testing: No Diffs :tada:"
  }
}
EOF

if [[ "$vrtFail" == true && -f "test/cypress/reports/cypress-report.html" ]] ; then
  # We have diffs so set the diffBlock with the new message and link.
  read -r -d '' diffBlock <<-EOF
{
  "type": "section",
  "text": {
    "type": "mrkdwn",
    "text": "Visual Regression Testing"
  },
  "accessory": {
    "type": "button",
    "text": {
      "type": "plain_text",
      "text": "See Diffs :eyes:",
      "emoji": true
    },
    "value": "diffs",
    "url": "${report}",
    "action_id": "button-action"
  }
}
EOF

fi

read -r -d '' MESSAGE <<-EOF
{
  "blocks": [
    {
      "type": "header",
      "text": {
        "type": "plain_text",
        "text": "Cypress Tests: ${baseUrl}",
        "emoji": true
      }
    },
    ${reportBlock},
    ${diffBlock}
  ]
}
EOF

if [ $SLACK_WEBHOOK ] ; then
  curl -s -i -d "$MESSAGE" $SLACK_WEBHOOK #> /dev/null
  echo -e "\nPinged Slack.\n"
fi
