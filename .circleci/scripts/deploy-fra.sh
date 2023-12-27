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
  echo -e "Host appserver.${PANTHEON_ENV}.${UUID}.drush.in\n\tStrictHostKeyChecking no\n" >> ~/.ssh/config

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
  drush @${SITE_CODE}.${PANTHEON_ENV} fra --bundle=jcc_tc2 -y

  # echo
  # echo Clearing Cache for $PANTHEON_ENV
  # drush @${SITE_CODE}.${PANTHEON_ENV} cr

done
