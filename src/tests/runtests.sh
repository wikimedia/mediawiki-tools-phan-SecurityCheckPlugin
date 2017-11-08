testList=${1:-`ls integration`}
tmpFile=`mktemp testtmp.XXXXXXXX`


for i in $testList
do
	echo "Running test $i"
	php7.0 /Users/bawolff/src/phan/phan \
        	--project-root-directory "." \
        	--config-file "config.php" \
        	--output "php://stdout" \
        	-l "integration/$i" | tee ${DEBUG:-/dev/null} | grep SecurityCheckTaintedOutput  > $tmpFile
	diff -u "integration/$i/expectedResults.txt" "$tmpFile"
done
rm $tmpFile
