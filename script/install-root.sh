#!/bin/bash

set -e

. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

sudo ./prereqs-ubuntu.sh
./setup.sh
