#!/bin/bash
# 
# chkconfig: 2345 20 80 
# description: Trait-o-matic service

SERVER=~trait/trait-o-matic/core/server.py
SERVER_PID=~trait/tmp/server.pid

start() {
	if [ -f "$SERVER_PID" ] && [ -n "$(ps -p $(< $SERVER_PID) -o cmd=)" ]; then
		echo "Trait-o-matic server already running"
	else
		echo "Starting Trait-o-matic server..."
		python $SERVER &> /dev/null &
		echo $! > $SERVER_PID
	fi
}

stop() {
	echo "Stopping Trait-o-matic server..."
	kill $(< $SERVER_PID)
	rm $SERVER_PID
}

case "$1" in
	start)
		start
		;;
	stop)
		stop
		;;
	restart)
		stop
		start
		;;
	*)
		echo "Usage: $0 start|stop|restart"
		exit 1
		;;
esac
exit $?
