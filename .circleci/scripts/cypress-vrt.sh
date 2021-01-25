#!/bin/bash

# This will do Visual Regression Testing against the deployed branch, so it
# should only be run on deployment branches, AFTER the deploy job.
# If multisites should be tested, loop over them like in deploy.sh

# Take base snapshots.
npx cypress run --spec test/cypress/integration/visualRegression.spec.js --env updateSnapshots=true --config baseUrl=https://live-jcc-tc.pantheonsite.io

# Compare snapshots for this branch.
npx cypress run --spec test/cypress/integration/visualRegression.spec.js --config baseUrl=https://${CIRCLE_BRANCH}-jcc-tc.pantheonsite.io --env failOnSnapshotDiff=false

# Message Slack with diffs.
if [ -d "test/cypress/snapshots/visualRegression.spec.js/__diff_output__" ] ; then

  DIFFS=${CIRCLE_BUILD_URL}/artifacts/${CIRCLE_NODE_INDEX}/vrt_diff

read -r -d '' MESSAGE <<-EOF
{
  "blocks": [
    {
      "type": "header",
      "text": {
        "type": "plain_text",
        "text": "Cypress Tests",
        "emoji": true
      }
    },
    {
      "type": "section",
      "text": {
        "type": "mrkdwn",
        "text": "VRT for **${CIRCLE_BRANCH}** had failures :cry:"
      },
      "accessory": {
        "type": "button",
        "text": {
          "type": "plain_text",
          "text": "See Diffs :eyes:",
          "emoji": true
        },
        "value": "diffs",
        "url": "${DIFFS}",
        "action_id": "button-action"
      }
    }
  ]
}
EOF

  if [ $CALVIN_SLACK ] ; then
    curl -s -i -d "$MESSAGE" $CALVIN_SLACK #> /dev/null
    echo -e "\nPinged Slack.\n"
  fi
fi
