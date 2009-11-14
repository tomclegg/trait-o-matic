#!/bin/sh

set -e
. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

if [ ! -z "$NO_CLONETRACK" ]
then
  exit
fi

origin=$(cat $SOURCE/.git/config | perl -ne '$x=0 if /^\[/; $x=1 if /^\[remote "origin"\]/; if ($x && /url\s*=\s*(\S+)/) { print "$1\n"; exit; }')

id=$(cat $SCRIPT_DIR/clonetrack.id || true)

curl -s -Furl="$BASE_URL" -Forigin="$origin" -Fid="$id" http://clonetrack.scalablecomputingexperts.com/update.php >$SCRIPT_DIR/clonetrack.id.$$

if [ "`wc -c < $SCRIPT_DIR/clonetrack.id.$$`" = 33 ]
then
  echo Updated clonetrack.scalablecomputingexperts.com.
  mv $SCRIPT_DIR/clonetrack.id.$$ $SCRIPT_DIR/clonetrack.id
fi

rm -f $SCRIPT_DIR/clonetrack.id.*

