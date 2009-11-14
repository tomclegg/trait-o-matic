#!/bin/sh

set -e

. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

if [ `tr -dc . </etc/hostname |wc -c` -lt 2 ]
then
  cat >&2 <<EOF
***
Warning: /etc/hostname does not contain two dots.  It might not be a
fully qualified host name.
EOF
fi

if ! grep -wq `cat /etc/hostname` /etc/hosts
then
  cat >&2 <<EOF
***
Warning: /etc/hosts does not list an IP address for `cat /etc/hostname`.
Consider adding it.
EOF
fi

chmod +x $SOURCE/.git/hooks/post-update

for hook in post-commit post-merge post-receive
do
  if [ ! -e $SOURCE/.git/hooks/$hook ]
  then
    echo '#!/bin/sh' > $SOURCE/.git/hooks/$hook
  fi
  if ! grep -qx 'git update-server-info' $SOURCE/.git/hooks/$hook
  then
    echo >&2 "Adding 'git update-server-info' to git $hook hook."
    chmod +x $SOURCE/.git/hooks/$hook
    echo git update-server-info | tee -a $SOURCE/.git/hooks/$hook >/dev/null
  fi
done
