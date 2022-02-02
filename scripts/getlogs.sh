#!/bin/bash

name=$1
ENV=live
BASEDIR=$(dirname $(dirname $(realpath $0)))
. ${BASEDIR}/.circleci/scripts/project-${name}.sh

########### Additional settings you don't have to change unless you want to ###########
# OPTIONAL: Set AGGREGATE_NGINX to true if you want to aggregate nginx logs.
#  WARNING: If set to true, this will potentially create a large file
AGGREGATE_NGINX=true
# if you just want to aggregate the files already collected, set COLLECT_LOGS to FALSE
COLLECT_LOGS=true
# CLEANUP_AGGREGATE_DIR removes all logs except combined.logs from aggregate-logs directory.
CLEANUP_AGGREGATE_DIR=true

if [ $COLLECT_LOGS == true ]; then
echo "Getting logs of ${SITE_CODE}. Beginning the process..."
rm -rf data/logs/${name}
mkdir -p data/logs/${name}
for app_server in $(dig +short -4 appserver.$ENV.$UUID.drush.in);
do
    echo "get -R logs \"data/logs/${name}/app_server_$app_server\"" | sftp -o Port=2222 "$ENV.$UUID@$app_server"
done

# Include MySQL logs
for db_server in $(dig +short -4 dbserver.$ENV.$UUID.drush.in);
do
    echo "get -R logs \"data/logs/${name}/db_server_$db_server\"" | sftp -o Port=2222 "$ENV.$UUID@$db_server"
done
else
echo 'skipping the collection of logs..'
fi

if [ $AGGREGATE_NGINX == true ]; then
echo 'AGGREGATE_NGINX set to $AGGREGATE_NGINX. Starting the process of combining nginx-access logs...'
mkdir data/logs/${name}/aggregate-logs

for d in $(ls -d data/logs/${name}/app*/nginx); do
    for f in $(ls -f "$d"); do
    if [[ $f == "nginx-access.log" ]]; then
        cat "$d/$f" >> data/logs/${name}/aggregate-logs/nginx-access.log
        cat "" >> data/logs/${name}/aggregate-logs/nginx-access.log
    fi
    if [[ $f =~ \.gz ]]; then
        cp -v "$d/$f" data/logs/${name}/aggregate-logs/
    fi
    done
done

echo "unzipping nginx-access logs in aggregate-logs directory..."
for f in $(ls -f data/logs/${name}/aggregate-logs); do
    if [[ $f =~ \.gz ]]; then
    gunzip data/logs/${name}/aggregate-logs/"$f"
    fi
done

echo "combining all nginx access logs..."
for f in $(ls -f data/logs/${name}/aggregate-logs); do
    cat data/logs/${name}/aggregate-logs/"$f" >> data/logs/${name}/aggregate-logs/combined.logs
done
echo "the combined logs file can be found in data/logs/${name}/aggregate-logs/combined.logs"
else
echo "AGGREGATE_NGINX set to $AGGREGATE_NGINX. So we're done."
fi

if [ $CLEANUP_AGGREGATE_DIR == true ]; then
echo 'CLEANUP_AGGREGATE_DIR set to $CLEANUP_AGGREGATE_DIR. Cleaning up the aggregate-logs directory'
find ./data/logs/${name}/aggregate-logs/ -name 'nginx-access*' -exec rm {} \;
fi
