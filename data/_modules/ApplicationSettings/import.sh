#!/bin/bash

# WARNING: all output from this script should be redirected in its call; stdin & stderr
# WARNING: this script is run from './code', so everything is relative to it
# WARNING: this script assumes the file to process is already in DIRTEMP/import

# Check for the passed username
if [ "$1" == "" ]; then
	echo "ERROR: No username was passed to this script."
	exit 1
fi

# Check for the passed filename
if [ "$2" == "" ]; then
	echo "ERROR: No filename was passed to this script."
	exit 2
fi

# Obtain Server and Database Info
DB_HOST="$(cat ../../sqlaccess | grep DB_HOST | sed "s/');$//;s/.*','//")"
DB_NAME="$(cat ../../sqlaccess | grep DB_NAME | sed "s/');$//;s/.*','//")"
DB_RWUN="$(cat ../../sqlaccess | grep DB_RWUN | sed "s/');$//;s/.*','//")"
DB_RWPW="$(cat ../../sqlaccess | grep DB_RWPW | sed "s/');$//;s/.*','//")"
DIRTEMP="$(cat ../data/_modules/ApplicationSettings/config.php | grep sDirTemp | sed "s/';$//;s/.*='//")"

# Check the staging directory exists
if [ ! -e "${DIRTEMP}/import" ]; then
	echo "ERROR: The staging directory does not exist."
	exit 3
fi

# decompress the tgz file if that's the format that was sent
if [ "${2: -4}" == '.tgz' ]; then
	tar zxf "${DIRTEMP}/import/${2}" -C "${DIRTEMP}/import" && rm "${DIRTEMP}/import/${2}"
fi

# import each .sql file in the import directory
IFS='$\n'
for FILE in "$(ls -1 ${DIRTEMP}/import/*.sql)"; do
	# Import the SQL Database Contents
	( mysql --user="${DB_RWUN}" --password="${DB_RWPW}" --host="${DB_HOST}" "${DB_NAME}" <"${FILE}" && php -f ./ApplicationSettings.php import "$1" "${FILE}" ) && rm -f "${FILE}" || exit 1
done

# cleanup
rm -Rf "${DIRTEMP}/import"

