#!/usr/bin/env bash
#
# build script on clean checkout
#

if [ ! -d vendor ]; then
    echo "installing build dependencies..."
    composer install
fi

if [ ! -d vendor ]; then
    echo "build dependencies not installed!"
    exit 1
fi

php -f build.php
