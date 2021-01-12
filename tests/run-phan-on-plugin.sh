#!/bin/bash
dir=`dirname $0`
cd "$dir/../"

echo Starting phan

php "./vendor/phan/phan/phan" \
        --project-root-directory . \
        --config-file "tests/self-phan-config.php" \
        --output "php://stdout" \
        --long-progress-bar $@
result=$?
echo
exit $result
