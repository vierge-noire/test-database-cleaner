#!/bin/bash

DRIVER=$1;

echo "Starting PHPUNIT tests"
export DB_DRIVER=$DRIVER

# Test Cases where tables get dropped are put separately,
# since they are giving a hard time to the fixtures
# These can be put all together again once the migrations
# get required in the dependencies
./vendor/bin/phpunit

#./vendor/bin/phpstan analyse --memory-limit=-1