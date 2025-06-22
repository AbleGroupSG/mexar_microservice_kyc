#!/bin/bash
set -e

# only run this if the file does not exist avoiding re-initialization
if [ ! -f /var/lib/mysql/ibdata1 ]; then
    echo "🔧 Ensuring ownership of /var/lib/mysql (first init)..."
    chown -R mysql:mysql /var/lib/mysql
fi

# call the original entrypoint
exec /usr/local/bin/docker-entrypoint.sh "$@"