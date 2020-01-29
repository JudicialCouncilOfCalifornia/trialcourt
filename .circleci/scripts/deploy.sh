#!/bin/bash

TIMESTAMP=$(date +'%y-%m-%dT%H:%m:%S')

git config --global user.email "$GIT_EMAIL"
git config --global user.name "Ch3-P0"

echo "\nClone artifact.\n"
mkdir -p data
cd data
git clone $ARTIFACT_GIT artifact
echo "\nCheckout $CIRCLE_BRANCH\n"
cd artifact
git fetch origin && git checkout $CIRCLE_BRANCH
git pull origin $CIRCLE_BRANCH
echo "\nSync to artifact.\n"
cd ../.. && composer -n artifact-sync
cd data/artifact
git add .
git commit -am "Built assets. $TIMESTAMP"
echo "\n@todo- Work out release taging.\n"

# Tag for master.
if [ $CIRCLE_BRANCH == 'master' ] ; then
  # Get latest pantheon_live_ tag.
  git fetch origin --tags
  pantheon_prefix='pantheon_live_'
  pantheon_current=$(git tag -l --sort=v:refname $pantheon_prefix* | tail -1)
  if [ -z $pantheon_current ] ; then
    # No current tag so start with 1.
    pantheon_new=1
  else
    pantheon_id=${pantheon_current#${pantheon_prefix}}
    pantheon_new=$(($pantheon_id+1))
  fi
  echo
  echo "Tagging master branch for production (Live): $pantheon_prefix$pantheon_new"
  git tag -a $pantheon_prefix$pantheon_new -m "Tagging new pantheon live release."
fi

echo
echo "Pushing $CIRCLE_BRANCH"
git push origin $CIRCLE_BRANCH -f --tags

echo
echo "If deployment was successful, post-code-update hook will handle importing config, updating db, and clearing caches."
