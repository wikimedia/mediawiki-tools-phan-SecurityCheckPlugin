#!/bin/bash
# If you want to see debug output
# call with SECCHECK_DEBUG=/dev/stderr
# environment variable.
#
# If you want to run a specific test
# then give it as first arg.
cd `dirname $0`/
testList=${1:-`ls integration`}
tmpFile=`mktemp testtmp.XXXXXXXX`
totalTests=0
failedTests=0
php=`which php7.0`
php=${php:-`which php`}

for i in $testList
do
	echo "Running test $i"
	totalTests=$((totalTests+1))
	$php ../vendor/etsy/phan/phan \
        	--project-root-directory "." \
        	--config-file "integration-test-config.php" \
        	--output "php://stdout" \
        	-l "integration/$i" | grep SecurityCheckTaintedOutput  > $tmpFile
	diff -u "integration/$i/expectedResults.txt" "$tmpFile"
	if [ $? -gt 0 ]
		then failedTests=$((failedTests+1))
	fi
done
rm $tmpFile
if [ $failedTests -gt 0 ]
	then echo $failedTests out of $totalTests failed.
		exit 1
	else echo "All $totalTests passed."
fi
