#!/bin/bash

name=$1
BASEDIR=$(dirname $(dirname $(realpath $0)))

R="\033[31m"
G="\033[32m"
Y="\033[33m"
RE="\033[0m"

if [ -f data/logs/${name}/aggregate-logs/combined.logs ] ; then
  echo -e "\n${Y}Log data/logs/${name}/aggregate-logs/combined.logs already exists.${RE}\n"

  read -p "Replace? (y/N)" -n 1 -r
  echo    # (optional) move to a new line
  if [[ $REPLY =~ ^[Yy]$ ]] ; then
    . ${BASEDIR}/scripts/getlogs.sh ${name}
  fi
else
  . ${BASEDIR}/scripts/getlogs.sh ${name}
fi

if [ -z "$2" ]
  then
    echo -e "\nFREQUENT VISITOR IPs"
    cat data/logs/${name}/aggregate-logs/combined.logs | awk -F '\"' '{ print $8 }' | awk -F ',' '{print $1}' | sort | uniq -c | sort -frn | head -n 25
    echo -e "\nFREQUENT VISITED URLs"
    cat data/logs/${name}/aggregate-logs/combined.logs | awk -F '\"' '{print $2}' | sort | uniq -c | sort -nr | head
    echo -e "\nFREQUENT USER AGENTS"
    cat data/logs/${name}/aggregate-logs/combined.logs | awk -F '\"' '{print $6}' | sort | uniq -c | sort -nr | head
else
    echo -e "\nFREQUENT VISITOR IPs"
    tail -n $2 data/logs/${name}/aggregate-logs/combined.logs | awk -F '\"' '{ print $8 }' | awk -F ',' '{print $1}' | sort | uniq -c | sort -frn | head -n 25
    echo -e "\nFREQUENT VISITED URLs"
    tail -n $2  data/logs/${name}/aggregate-logs/combined.logs | awk -F '\"' '{print $2}' | sort | uniq -c | sort -nr | head
    echo -e "\nFREQUENT USER AGENTS"
    tail -n $2  data/logs/${name}/aggregate-logs/combined.logs | awk -F '\"' '{print $6}' | sort | uniq -c | sort -nr | head
fi
