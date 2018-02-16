MediaWiki Security Check Plugin
===============================

This is a plugin to [Phan] to try and detect security issues
(such as [XSS]). It keeps track of any time a user can modify
a variable, and checks to see that such variables are
escaped before being output as html or used as an sql query, etc.

It is primarily intended for scanning MediaWiki extensions,
however it supports a generic mode which should work with
any PHP project.

This plugin should be considered beta quality. Generic mode isn't
well tested yet.

Usage
-----

### System requirements
* php = 7.0 (7.1 is not supported)
* Phan 0.8.0 [This has not been tested on any other version of phan]
* Lots of memory. Scanning MediaWiki seems to take about 3 minutes
and use about 2 GB of memory. Running out of memory may be a real issue
if you try and scan something from within a VM that has limited
memory. Small projects do not require so much memory

### Composer

  $ `composer require --dev mediawiki/phan-taint-check-plugin`

* For MediaWiki core, add the following to composer.json:

```json
  "scripts": {
     "seccheck": "seccheck-mw"
  }
```

* For a MediaWiki extension, add the following to composer.json:

```json
  "scripts": {
     "seccheck": "seccheck-mwext",
     "seccheck-fast": "seccheck-fast-mwext"
  }
```

* For a generic php project, add the following to composer.json:

```json
  "scripts": {
     "seccheck": "seccheck-generic"
  }
```

You can then run:

  $ `composer seccheck`

to run the security check. Note that false positives are disabled by default.
For MediaWiki extensions, this assumes the extension is installed in the
normal extension directory, and thus MediaWiki is in `../../`. If this is not
the case, then you need to specify the `MW_INSTALL_PATH` environment variable.

This plugin also provides variants seccheck-fast-mwext (Doesn't analyze
MediaWiki core. May miss some stuff related to hooks) and seccheck-slow-mwext
(Also analyzes vendor). seccheck-mwext will generally take about 3 minutes,
where seccheck-fast-mwext takes only about half a minute.

Additionally, if you want to do a really quick check, you can run the
seccheck-generic script from a mediawiki extension which will ignore all
MediaWiki stuff, making the check much faster (but misses many issues).

If you want to do custom configuration (to say exclude some directories), follow the instructions below unser Manually.

### Manual

For MediaWiki mode, add MediaWikiSecurityCheckPlugin.php to the
list of plugins in your Phan config.php file.

For generic mode, add GenericSecurityCheckPlugin.php to the list
of plugins in your phan config.php file.

Then run phan as you normally would:

  $ php7 /path/to/phan/phan -p

Plugin output
-------------

The plugin will output various issue types depending on what it
detects. The issue types it outputs are:

* SecurityCheckMulti - For when there are multiple types of security issues
  involved
* SecurityCheck-XSS
* SecurityCheck-SQLInjection
* SecurityCheck-ShellInjection
* SecurityCheck-PHPSerializeInjection - For when someone does `unserialize(
  $_GET['d'] );` This issue type seems to have a high false positive rate
  currently.
* SecurityCheck-CUSTOM1 - To allow people to have custom taint types
* SecurityCheck-CUSTOM2 - ditto
* SecurityCheck-OTHER - At the moment, this corresponds to things that don't
  have an escaping function to make input safe. e.g. `eval( $_GET['foo'] );
  require $_GET['bar'];`
* SecurityCheck-LikelyFalsePositive - A potential issue, but probably not.
  Mostly happens when the plugin gets confused.

The severity field is usually marked as `Issue::SEVERITY_NORMAL (5)`. False
positives get `Issue::SEVERITY_LOW (0)`. Issues that may result in server
compromise (as opposed to just end user compromise) such as shell or sql
injection are marked as `Issue::SEVERITY_CRITICAL (10)`.
SerializationInjection would normally be "critical" but its currently denoted
as a severity of NORMAL because the check seems to have a high false positive
rate at the moment.

You can use the `-y` command line option of Phan to filter by severity.

Limitations
-----------

There's much more than listed here, but some notable limitations/bugs:

* When an issue is output, the plugin tries to include details about what line
  originally caused the issue. Usually it works, but sometimes it gives
  misleading/wrong information
** In particular, with pass by reference parameters to MediaWiki hooks,
   sometimes the line number is the hook call in MediaWiki core, instead of
   the hook subscriber in the extension that caused the issue.
* Command line scripts cause XSS false positives
* The plugin won't recognize things that do custom escaping. If you have
  custom escaping methods, you may have to write a subclass of
  SecurityCheckPlugin in order for the plugin to recognize it.
* The plugin can only validate the fifth ($options) and sixth ($join_cond)
  of MediaWiki's IDatabase::select() if its provided directly as an array
  literal, or directly returned as an array literal from a getQueryInfo()
  method.
* Checking of HTMLForm field specifiers only works if they are specified
  as array literals

Customizing
-----------

The plugin supports being customized, by subclassing the [SecurityCheckPlugin]
class. For a complex example of doing so, see [MediaWikiSecurityCheckPlugin].

You can add pretty much arbitrary behavior here, but the primary thing you
would usually want to customize is adding information about how different
functions affect the taint of a variable.

To do this, you override the `getCustomFuncTaints()` method. This method
returns an associative array of fully qualified method names to an array
describing how the taint of the return value of the function in terms of its
arguments. The numeric keys correspond to the number of an argument, and an
'overall' key adds taint that is not present in any of the arguments.
Basically for each argument, the plugin takes the taint of the argument,
bitwise AND's it to its entry in the array, and then bitwise OR's the overall
key. If any of the keys in the array have an EXEC flags, then an issue is
immediately raised if the corresponding taint is fed the function (For
example, an output function).

For example, [htmlspecialchars] which removes html taint but leaves other taint
would look like

  'htmlspecialchars' => [
      self::YES_TAINT & ~self::HTML_TAINT,
      'overall' => self::NO_TAINT,
  ];

Environment variables
---------------------

The following environment variables affect the plugin. Normally you would not
have to adjust these.

* `SECURITY_CHECK_EXT_PATH` - Path to extension.json when in MediaWiki mode.
  If not set assumes the project root directory.
* `SECCHECK_DEBUG` - File to output extra debug information (If running from
  shell, /dev/stderr is convenient)

License
-------

[GNU General Public License, version 2 or later]

[Phan]: https://github.com/phan/phan
[XSS]: https://en.wikipedia.org/wiki/Cross-site_scripting
[SecurityCheckPlugin]: src/SecurityCheckPlugin.php
[MediaWikiSecurityCheckPlugin]: MediaWikiSecurityCheckPlugin.php
[htmlspecialchars]: https://secure.php.net/htmlspecialchars
[GNU General Public License, version 2 or later]: COPYING
