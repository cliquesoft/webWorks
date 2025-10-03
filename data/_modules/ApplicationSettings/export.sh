#!/bin/bash

# WARNING: all output from this script should be redirected in its call; stdin & stderr
# WARNING: this script is run from './code', so everything is relative to it

# Check for the passed username
if [ "$1" == "" ]; then
	echo "ERROR: No username was passed to this script."
	exit 1
fi

# Obtain Server and Database Info
DB_HOST="$(cat ../../sqlaccess | grep DB_HOST | sed "s/');$//;s/.*','//")"
DB_NAME="$(cat ../../sqlaccess | grep DB_NAME | sed "s/');$//;s/.*','//")"
DB_ROUN="$(cat ../../sqlaccess | grep DB_ROUN | sed "s/');$//;s/.*','//")"
DB_ROPW="$(cat ../../sqlaccess | grep DB_ROPW | sed "s/');$//;s/.*','//")"
DIRTEMP="$(cat ../data/_modules/ApplicationSettings/config.php | grep sDirTemp | sed "s/';$//;s/.*='//")"

# Dump the SQL Database Contents
mysqldump --opt --user="${DB_ROUN}" --password="${DB_ROPW}" --host="${DB_HOST}" "${DB_NAME}" >"${DIRTEMP}/backup.sql" && php -f ./ApplicationSettings.php export "$1"

