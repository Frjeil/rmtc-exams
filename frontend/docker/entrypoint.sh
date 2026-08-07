#!/bin/sh
set -e

cd /app

if [ ! -d node_modules ]; then
    npm install
fi

exec "$@"
