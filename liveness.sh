#!/bin/bash

LAST_MODIFIED=$(date -r $LOG_FILE_PATH +%s)
CURRENT=$(date +%s)

# If the log file hasn't been modified in the last 2 minutes, liveness fails.
if [ $(($LAST_MODIFIED + 120)) -ge $CURRENT ]
then
	echo 0
else
	echo -1
fi