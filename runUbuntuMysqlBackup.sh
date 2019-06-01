#!/bin/bash

#
# NOTES: THIS FILE PRODUCES 11 ERROR MESSAGES, HOWEVER IT WORKS
#        CANNOT FIND OUT HIW TO TURN OFF INSPECTION, SO I IGNORED IT
#

# setup mysqldump variables
MYFOLDER=/srv/backup
MYFILE=osmap-$(date +"%Y-%m-%dCET%H%M").sql
MYDATABASE=XXX
MYUSER=XXX
MYPASSWORD=XXX

# backup database to file (capture error messages)
MYERROR=$((mysqldump -u ${MYUSER} -p${MYPASSWORD} ${MYDATABASE} > ${MYFOLDER}/${MYFILE}) 2>&1)

if [[ $MYERROR =~ "error" ]]
then
    echo "${MYERROR}"
else
    echo "Backed up to  ${MYFOLDER}/${MYFILE}"
fi
# eof