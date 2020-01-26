#!/bin/bash
dir=`dirname $0`
cd "$dir/../"

echo Starting phan

php "./vendor/phan/phan/phan" \
        --project-root-directory . \
        --config-file "tests/general-phan-config.php" \
        --output "php://stdout" \
        --long-progress-bar $@
result=$?
echo
exit $result
