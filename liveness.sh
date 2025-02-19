#!/bin/bash

HEARTBEAT_FILE="heartbeat.log"

# Check if the heartbeat file exists
if [ -f "$HEARTBEAT_FILE" ]; then
    # Get the last modified time of the file in seconds since epoch
    LAST_MODIFIED=$(stat -c %Y "$HEARTBEAT_FILE")
    # Get the current time in seconds since epoch
    CURRENT=$(date +%s)

    # If the file was modified in the last 120 seconds, exit with success
    if [ $((CURRENT - LAST_MODIFIED)) -le 120 ]; then
        exit 0
    fi
fi

# If the file doesn't exist or wasn't modified recently, exit with failure
exit 1
