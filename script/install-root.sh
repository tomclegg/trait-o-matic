#!/bin/bash

set -e

. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

sudo $SCRIPT_DIR/prereqs-ubuntu.sh
$SCRIPT_DIR/setup.sh
