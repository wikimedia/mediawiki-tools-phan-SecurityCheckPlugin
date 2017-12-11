#!/bin/bash
dir=`dirname $0`
cd "$dir/../"
progressBar="$@"
# This doesn't really work from composer
if [ -t 2 ]
	then progressBar="-p" "$@"
fi

echo Starting phan

php7.0 "./vendor/etsy/phan/phan" \
        --project-root-directory . \
        --config-file "tests/general-phan-config.php" \
        --output "php://stdout" \
        $progressBar
echo
