#!/bin/bash
dir=`dirname $0`
cd "$dir/../"
php7.0 "./vendor/etsy/phan/phan" \
        --project-root-directory . \
        --config-file "tests/general-phan-config.php" \
        --output "php://stdout" \
        "${@}"
echo
