#!/bin/bash
#
# Run phan's own test suite, removing tests we don't care about and injecting taint-check with horrible hacks
set -euxo pipefail

# Skip test when php ast extension is not loaded
if ! php -m | grep -q "^ast$"; then
    exit 0
fi

if [[ ! -d vendor/phan/phan/tests ]]; then
    rm -rf vendor/phan
    composer update --prefer-source --quiet #Suppress composer output, rely on the exit below
fi

TESTDIR="phan-tests"
mv ./phpunit.xml.dist phpunit-OLD.xml
cp composer.json composer-OLD.json

function restoreEverything() {
    rm -rf $TESTDIR ./phan .phan phpunit.xml
    mv phpunit-OLD.xml phpunit.xml.dist
    mv composer-OLD.json composer.json
    composer dump-autoload
}
trap "restoreEverything" exit

cp -r vendor/phan/phan/tests/ $TESTDIR
cp -r vendor/phan/phan/.phan .
cp vendor/phan/phan/{phan,phpunit.xml} .

# Here comes the hack, doo da doo doo...
# Exclude test for tools, fixer, and running phan on phan itself
DISCARD_TESTS="(__FakeSelfTest|__FakeSelfFallbackTest|__FakeToolTest|__FakePhantasmTest|__FakeFixerTest)"
sed -r -i "s/.*$DISCARD_TESTS.*//" "$TESTDIR/run_all_tests"

# Fix paths
sed -r -i "s/tests\/run_test/$TESTDIR\/run_test/" "$TESTDIR/run_all_tests"
sed -r -i "s/cd tests\//cd $TESTDIR\//" "$TESTDIR/run_test"
sed -r -i "s/require_once __DIR__ . '\/src/require_once __DIR__ . '\/vendor\/phan\/phan\/src/" ./phan
sed -r -i "s/tests\//$TESTDIR\//" phpunit.xml
sed -r -i "s/.\/tests\//.\/$TESTDIR\//" "$TESTDIR/bootstrap.php"
sed -r -i "s/\/src\/Phan/\/vendor\/phan\/phan\/src\/Phan/" "$TESTDIR/bootstrap.php"
sed -r -i "s/'\/src/'\/vendor\/phan\/phan\/src/" $TESTDIR/Phan/Language/UnionTypeTest.php

# Enable verbose output, e.g. to know what tests were skipped
sed -i 's/verbose="false"/verbose="true"/' phpunit.xml
# Remove tests we don't care about
EXCLUDE_PHPUNIT_TESTS="(ForkPoolTest|SoapTest)"
sed -r -i "s/<file>.+$EXCLUDE_PHPUNIT_TESTS.*<\/file>//" phpunit.xml
rm -r $TESTDIR/Phan/Language/Internal
rm -rf $TESTDIR/Phan/{CLITest.php,PluginV3Test.php,ForkPoolTest.php,SoapTest.php,Internal,LanguageServer}
# Tests that rely on paths
rm $TESTDIR/files/src/0545_require_testing.php $TESTDIR/Phan/Language/FileRefTest.php
# Taint-check analyses the Group class earlier, thus avoiding a false positive issue from phan alone
sed -r -i ':a;N;$!ba;s/src\/\S+41 PhanPluginNonBoolInLogicalArith[^\n]+\n//' $TESTDIR/plugin_test/expected/160_useless_return.php.expected
# Taint-check analyses debug_trace_nonpure() earlier, and knows it returns a string
sed -r -i 's/(src\/\S+:(30|37)) PhanPartialTypeMismatchReturn.+/&\n\1 PhanTypeInvalidLeftOperandOfNumericOp Invalid operator: left operand of * is string (expected number)/' $TESTDIR/plugin_test/expected/152_phan_pure_annotation.php.expected


# Phan uses autoload-dev for test classes
sed -r -i "s/\"SecurityCheckPlugin\\\\\\\\\": \"src\"/\0, \"Phan\\\\\\\\Tests\\\\\\\\\": \"$TESTDIR\/Phan\"/" composer.json
composer dump-autoload

SECCHECK_ISSUES="'SecurityCheck-DoubleEscaped', 'SecurityCheck-SQLInjection', 'SecurityCheck-XSS', "
SECCHECK_ISSUES+="'SecurityCheck-ShellInjection', 'SecurityCheck-PHPSerializeInjection', 'SecurityCheck-CUSTOM1', 'SecurityCheck-CUSTOM2', ";
SECCHECK_ISSUES+="'SecurityCheck-PathTraversal', 'SecurityCheck-RCE', 'SecurityCheck-ReDoS', ";
SECCHECK_ISSUES+="'SecurityCheckMulti', 'SecurityCheck-LikelyFalsePositive',";
INNER_TEST_DIRS="misc/rewriting_test misc/fallback_test misc/config_override_test misc/empty_methods_plugin_test plugin_test real_types_test infer_missing_types_test"
for DIR in $INNER_TEST_DIRS ; do
    CFG_PATH="$TESTDIR/$DIR/.phan/config.php"
    JUMP='';
    if [[ $DIR == "misc"* ]]; then
        JUMP='..\/'
    fi

    # Load taint-check
    if grep -q "plugins['\"] => \[" $CFG_PATH; then
        sed -r -i "s/'plugins['\"] => \[/\0\n'.\/$JUMP..\/..\/MediaWikiSecurityCheckPlugin.php',/" $CFG_PATH
    else
        sed -r -i "s/^return \[/\0 'plugins' => [ '.\/$JUMP..\/..\/MediaWikiSecurityCheckPlugin.php' ],/" $CFG_PATH
    fi

    # Exclude taint-check issues
    if grep -q "suppress_issue_types['\"] => \[" $CFG_PATH; then
        sed -r -i "s/suppress_issue_types['\"] => \[/\0 $SECCHECK_ISSUES'/" $CFG_PATH
    else
        sed -r -i "s/^return \[/\0 'suppress_issue_types' => [ $SECCHECK_ISSUES ],/" $CFG_PATH
    fi
done

# And also the generic config used for "base" tests. Note, some tests don't use a config file at all, so we can't
# easily inject taint-check there.
sed -r -i "s/^return \[/\0 'plugins' => [ '.\/MediaWikiSecurityCheckPlugin.php' ],/" $TESTDIR/.phan_for_test/config.php
sed -r -i "s/'suppress_issue_types' => \[/\0 $SECCHECK_ISSUES/" $TESTDIR/.phan_for_test/config.php

# Some are different...
BASE_TESTS='PHP70Test PHP72Test PHP73Test PHP74Test PHP80Test'
for BASE_TEST in $BASE_TESTS ; do
    if grep -q "'plugins' =>" $TESTDIR/Phan/$BASE_TEST.php; then
        sed -r -i "s/'plugins' => \[/\0'.\/MediaWikiSecurityCheckPlugin.php',/" $TESTDIR/Phan/$BASE_TEST.php
    else
        sed -r -i "s/OVERRIDES = \[/\0'plugins' => ['.\/MediaWikiSecurityCheckPlugin.php'],/" $TESTDIR/Phan/$BASE_TEST.php
    fi
    if grep -q "suppress_issue_types['\"] => \[" $TESTDIR/Phan/$BASE_TEST.php; then
        sed -r -i "s/suppress_issue_types['\"] => \[/\0 $SECCHECK_ISSUES'/" $TESTDIR/Phan/$BASE_TEST.php
    else
        sed -r -i "s/OVERRIDES = \[/\0 'suppress_issue_types' => [ $SECCHECK_ISSUES ],/" $TESTDIR/Phan/$BASE_TEST.php
    fi
done

export PHAN_TEST_PARALLEL=0
./$TESTDIR/run_all_tests
