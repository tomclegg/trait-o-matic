#!/bin/bash

set -e
. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

mkdir -p $WWW
mkdir -p $CONFIG
mkdir -p $TMP
mkdir -p $DATA

./setup-db.sh
./setup-www.sh
#./setup-external-data.sh

rsync -a $SOURCE/core/ $CORE/
if [ ! -L $CORE/config.py ]; then
  if [ -f $CORE/config.py ]; then
    mv -i $CORE/config.py $CONFIG/config.py
  fi
  ln -sn $CONFIG/config.py $CORE/config.py
fi

# Build python exts
(cd $CORE ; python setup.py build_ext --inplace)

cp -p $SOURCE/core/config.default.py $CONFIG/config.default.py
if [ ! -e $CONFIG/config.py -a ! -L $CONFIG/config.py ]
then
  dbpass=$(cat $CONFIG/dbpassword)
  [ $? = 0 ]
  sed -e "s/shakespeare/$dbpass/g" < $CONFIG/config.default.py > $CONFIG/config.py
  echo >&2 "*** "
  echo >&2 "*** Please edit $CONFIG/config.py to suit your installation."
  echo >&2 "*** "
else
  echo >&2 "*** "
  echo >&2 "*** Please ensure $CONFIG/config.py is up-to-date."
  echo >&2 "*** Latest defaults can be found at:"
  echo >&2 "***   $CONFIG/config.default.py"
  echo >&2 "*** "
fi
chmod 600 $CONFIG/config.py

cat >&2 <<EOF
***
*** If everything went well, you should see the Trait-o-matic web
*** interface at http://`hostname`/.
***
*** You still need to obtain and import the reference data before any
*** processing can occur.  See http://`hostname`/docs/.
***
EOF