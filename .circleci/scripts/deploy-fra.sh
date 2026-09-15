#!/bin/bash

set -e

DIR=$PWD

# Loop over files to find project- config files and deploy each.
# If this times out due to too many projects we may have to go back to calling
# this script multiple times from config.yml and passing the project name.
for name in "$@" ; do
  cd $DIR

  # Include project variables.
  . .circleci/scripts/project-${name}.sh

  # Leaving the old way here to roll back easily if loop method times out.
  # Remove the loop and name check condition, then call from config.yml
  # with multiple run steps passing project name like:
  # 'command: .circleci/scripts/deploy.sh slo'
  #
  # . .circleci/scripts/project-${1}.sh

  echo Deploying $SITE_CODE
  PANTHEON_ENV=$CIRCLE_BRANCH

  # Uncomment if Dev deployment is needed.
  # Skip deployment step for master branch if "LIVE" is set explicitly to false.
  # if [ "$CIRCLE_BRANCH" = "master" ] && [ "$LIVE" = false ]; then
  #  PANTHEON_ENV="dev"
  # fi

  # Tag for master.
  if [ $CIRCLE_BRANCH == 'master' ] && $LIVE ; then
    # For drush reset.
    PANTHEON_ENV=live
  fi

  # Disable strict host checking so we can run drush on all envs.
  echo -e "Host appserver.${PANTHEON_ENV}.${UUID}.drush.in\n\tStrictHostKeyChecking no\n\tServerAliveInterval 60\n\tServerAliveCountMax 30\n" >> ~/.ssh/config

  # echo
  # echo Clearing Cache for $PANTHEON_ENV
  # drush @${SITE_CODE}.${PANTHEON_ENV} cr

  # echo
  # echo Running Database Updates for $PANTHEON_ENV
  # drush @${SITE_CODE}.${PANTHEON_ENV} updb -y

  # echo
  # echo Importing Config for $PANTHEON_ENV
  # drush @${SITE_CODE}.${PANTHEON_ENV} cim -y

  # echo
  echo Importing Features for $PANTHEON_ENV

  # fra can spend well over ten minutes without writing a single line, and
  # CircleCI kills a step that produces no output for no_output_timeout. This
  # heartbeat breaks that silence so the step is judged on whether drush
  # actually finishes, not on how quiet it is while it works.
  ( while sleep 60; do echo "  ... fra still running for ${SITE_CODE} ($(date -u +%H:%M:%S) UTC)"; done ) &
  HEARTBEAT_PID=$!
  # Kill the heartbeat even if drush aborts the script.
  trap 'kill "$HEARTBEAT_PID" 2>/dev/null || true' EXIT

  set +e
  drush @${SITE_CODE}.${PANTHEON_ENV} fra --bundle=jcc_tc2 -y
  FRA_STATUS=$?
  set -e

  kill "$HEARTBEAT_PID" 2>/dev/null || true
  trap - EXIT

  if [ "$FRA_STATUS" -ne 0 ]; then
    echo "fra failed for ${SITE_CODE} (exit ${FRA_STATUS})"
    exit "$FRA_STATUS"
  fi

  # echo
  # echo Clearing Cache for $PANTHEON_ENV
  # drush @${SITE_CODE}.${PANTHEON_ENV} cr

done
