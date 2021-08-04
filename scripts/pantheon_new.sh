#!/usr/bin/env bash

BASEDIR=$(dirname $(dirname $(realpath $0)))
name=$1
label=$2

# Include color file.
. ${BASEDIR}/scripts/colors.sh

# Create new pantheon project.
echo -e "\n${B}Creating new Pantheon site ${RE}jcc-${name} \"$label\""
terminus site:create jcc-${name} "$label" empty --org=judicial-council-of-california

# Get the id
id=$(terminus site:info jcc-$name --fields=id | grep ID | awk '{print $2}')

# Create .circleci/scripts/project-[site].sh file for deployment. using id and name
echo -e "\n${B}Creating deploy script for $name${RE}"
cat << EOF > ${BASEDIR}/.circleci/scripts/project-${name}.sh
# Pantheon Project UUID.
UUID=$id
SITE_CODE=$name
# If false, pantheon will deploy to dev. If true it will get the Live tag.
LIVE=true
# If false, deployments will be skipped for the master branch for this site.
DEPLOY_MASTER=true
EOF

# Update .circleci/config.yml to include the new site in the matrix.
# Find the string that includes the site array for the 'deploy' job.
matrix=$(cat ${BASEDIR}/.circleci/config.yml | grep -A 3 deploy | grep "site: \[")

echo -e "\n${B}Updating ${RE}.circlci/config.yml ${B}to include ${name}${RE}"

if [[ "$matrix" == *"\"$name\""* ]]; then
  echo -e "${R}${name} already exists in matrix. Has this site already been configured?${RE}"
else
  echo -e "${G}Adding ${name} to the matrix.${RE}"
  # Trim the [] characters because it breaks string replacement.
  matrix="${matrix%%]*}"
  matrix="${matrix##*[}"
  # Append the new site name to the list.
  new_matrix="${matrix%%]*}, \"${name}\""
  # Replace the old list with the new list.
  sed -i '' -e 's/'"$matrix"'/'"$new_matrix"'/g' ${BASEDIR}/.circleci/config.yml
fi

# Create drush alias for [site]
echo -e "\n${B}Creating drush alias for ${name}.sites.yml${RE}"
cat << EOF > ${BASEDIR}/drush/sites/${name}.site.yml
'*':
  host: appserver.\${env-name}.${id}.drush.in
  user: \${env-name}.${id}
  uri: \${env-name}-jcc-${name}.pantheonsite.io
  ssh:
    tty: false
    options: '-p 2222 -o "AddressFamily inet"'
EOF

# Initialize Live Environment to work around the pantheon.yml hotfix issue.
# https://pantheon.io/docs/pantheon-yml#deploying-hotfixes
# Our deployment process bypasses Pantheon's serial workflow and deploys to
# Live by tagging the production repo master branch with `pantheon_live_N`
# ourselves, which prevents changes in the pantheon.yml file from being
# initialized on the live environment.
#
# This set up step clones the production repo from Pantheon, syncs our code
# the way it would on deployment, then uses terminus to deploy it through the
# Pantheon workflow. After the Live environment is initialized, our parallel
# workflow will deploy without issue.

# Switch to git mode.
terminus connection:set jcc-${name}.dev git

# Disable strict host checking so we can pull/push code.
echo -e "Host codeserver.dev.${id}.drush.in\n\tStrictHostKeyChecking no\n" >> ~/.ssh/config

# Clone the pantheon repo.
rm -rf data/artifact
git clone ssh://codeserver.dev.${id}@codeserver.dev.${id}.drush.in:2222/~/repository.git data/artifact
cp pantheon.yml data/artifact/pantheon.yml

cd data/artifact
git add .
git commit -m "Initialize environments with pantheon.yml"
git push origin master

echo "waiting..."
sleep 5

echo -e "\n${P}Initializing test environment.${RE}"
terminus env:deploy jcc-${name}.test --note="Initialize from pantheon_new.sh"
echo "\n${P}Initializing live environment.${RE}"
terminus env:deploy jcc-${name}.live --note="Initialize from pantheon_new.sh"

# Create multidevs
echo -e "\n${P}Creating multidev environments develop and stage for ${RE}jcc-${name}"
terminus multidev:create jcc-${name}.live develop
terminus multidev:create jcc-${name}.live stage

# Complete message and next steps.
echo -e "\n${G}*** COMPLETE ***${RE}"
echo -e "\n\t-If everything was successful, you should have a new project set up on Pantheon with multidev environments ${P}develop ${RE}and ${P}stage${RE} for testing."
echo -e "\t-This process should have also updated the configuration and files for deploying and managing this website."
echo -e "\t-If you have not done so already, you should run: ${B}lando multisite ${name}${RE} to set this site up locally for development."
echo -e "\t-Once your local is configured, you should export your configuration and commit it to your feature branch."
echo -e "\t-Then dump your databse with ${B}lando @local.${name} sql-dump > data/${name}-install.sql${RE}"
echo -e "\t-Install that database dump into your Pantheon environments via the Pantheon dashboard: ${B}https://dashboard.pantheon.io${RE}"
