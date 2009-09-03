#!/bin/bash

set -e

./setup-www.sh
#./setup-external-data.sh

SOURCE=$HOME/trait-o-matic
if cp -i $SOURCE/core/config.default.py $SOURCE/core/config.py
then
  echo >&2 "*** "
  echo >&2 "*** Please edit $SOURCE/core/config.py to suit your installation."
  echo >&2 "*** "
else
  echo >&2 "*** "
  echo >&2 "*** Please ensure $SOURCE/core/config.py is up-to-date."
  echo >&2 "*** Latest defaults can be found at:"
  echo >&2 "***   $SOURCE/core/config.default.py"
  echo >&2 "*** "
fi
