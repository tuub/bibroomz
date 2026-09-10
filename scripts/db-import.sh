#!/bin/bash
# Runs on the CI runner. Streams a DB dump from the source host straight into a restore on the
# target host over SSH, without touching this machine's disk.

set -e
set -o pipefail

# shellcheck disable=SC2029 # $SOURCE_SSH_GIT_DIR/$TARGET_SSH_GIT_DIR must expand locally, not remotely
ssh "${SOURCE_SSH_USER}@${SOURCE_SSH_HOSTNAME}" "cd $SOURCE_SSH_GIT_DIR && scripts/db-dump.sh" \
    | ssh "${TARGET_SSH_USER}@${TARGET_SSH_HOSTNAME}" "cd $TARGET_SSH_GIT_DIR && scripts/db-restore.sh"
