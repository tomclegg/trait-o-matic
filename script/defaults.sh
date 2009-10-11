export SCRIPT_DIR="$(echo "$0" | sed -e 's/[^\/]*$//')"
SCRIPT_DIR=$(if [ ! -z "$SCRIPT_DIR" ]; then cd "$SCRIPT_DIR"; fi; pwd)
if [ -z "$CORE" ]; then export CORE=$HOME/core; fi
if [ -z "$WWW" ]; then export WWW=$HOME/www; fi
if [ -z "$TARGET" ]; then export TARGET=$HOME/www; fi
if [ -z "$CONFIG" ]; then export CONFIG=$HOME/config; fi
if [ -z "$DATA" ]; then export DATA=$HOME/data; fi
if [ -z "$TMP" ]; then export TMP=$HOME/tmp; fi
if [ -z "$SOURCE" ]; then SOURCE=$(dirname $(if [ ! -z $(dirname $0) -a $(dirname $0) != . ]; then cd $(dirname $0); fi; pwd)); fi
