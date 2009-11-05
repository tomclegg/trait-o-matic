#!/bin/bash

set -e
set -o pipefail

if [ -e $DATA/mysql.stamp ]; then
  exit 0
fi

if [ ! -e $CONFIG/dbpassword ]
then
  (
    umask 077
    head -c 2000 /dev/urandom | tr -dc A-Za-z0-9 | head -c 8 > $CONFIG/dbpassword
  )
fi

dbpass=$(cat $CONFIG/dbpassword)
[ $? = 0 ]

cat >&2 <<EOF
***
*** When prompted, please enter your MySQL root password.
***
EOF
cat $SCRIPT_DIR/setup.sql | sed -e "s/shakespeare/$dbpass/g" | mysql -uroot -p
touch $DATA/mysql.stamp
