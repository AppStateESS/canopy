<?php

/**
 * MIT License
 * Copyright (c) 2018 Electronic Student Services @ Appalachian State University
 *
 * See LICENSE file in root directory for copyright and distribution permissions.
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license https://opensource.org/licenses/MIT
 */
function core_update(&$content, $version)
{
    switch (1) {
        case version_compare($version, '3.0.1', '<'):
            $content[] = <<<EOF
<pre>
3.0.1 updates
--------------
+ Removed old phpdoc version comments.
+ Removed translation files
+ Removed JQUERY_LATEST setting from defines
+ Removed images directory .gitignore file.
+ Changed pull-right, pull-left to float-right, float-left respectively.
+ Layout: added hideDefault method to hide the sidebar/DEFAULT theme variable.
+ Boost: Removed the version check. It was defunct.
+ Updated all dependency.xml files
+ Changed XMLParser to use the http 1.1 protocol.
+ Updated Pager class to use Bootstrap.
+ CKEditor can have style sheets inserted via a javascript constant "themeCSS".
+ Removed and corrected various styles in CKEditor .
+ Changed window.load to document.ready   to work with updated JQuery.
+ File Cabinet: Removed fa-lg classes from icons.
+ Renamed default theme to bootstrap4-default to prevent issues with npm.
</pre>
EOF;
        case version_compare($version, '3.0.2', '<'):
            $content[] = <<<EOF
<pre>
3.0.2 updates
---------------------------
+ Fixed Font Awesome Icons.
</pre>
EOF;
        case version_compare($version, '3.0.3', '<'):
            $content[] = <<<EOF
<pre>
3.0.3 updates
---------------------------
+ Fixed img-responsive classes in File Cabinet
</pre>
EOF;
        case version_compare($version, '3.0.4', '<'):
            $content[] = <<<EOF
<pre>
3.0.4 updates
---------------------------
+ Database.Engine.mysql.Datatype.Integer Changed unsigned to default false
+ Content modules no longer a part of the install.
+ Removed calls to bootstrap.css
+ Added sha1Random method in HashVar Variable.
</pre>
EOF;
        case version_compare($version, '3.0.5', '<'):
            $content[] = <<<EOF
<pre>
3.0.5 updates
---------------------------
+ Rewrote setVars in Data class.
+ Added needsUpdate and loadFileVersion functions to Module. Allows a break
  if the update will break the current version before getting run.
+ Rewrote forwarding logic. Faulty get urls won't force a forward.
</pre>
EOF;
        case version_compare($version, '3.0.6', '<'):
            $content[] = <<<EOF
<pre>
3.0.6 updates
---------------------------
+ Fixed setSize method in Varchar and Decimal.
+ Removed opentracing from composer.json.
+ Fixed Json error view exception.
+ DataDog implementation.
+ Fixed logout script.
+ Added SHOW_MINIADMIN define.
+ Hide user drop down if show login box not checked.
</pre>
EOF;
        case version_compare($version, '3.0.7', '<'):
            $content[] = <<<EOF
<pre>
3.0.7 updates
---------------------------
+ DB::selectInto returns a null result if it failed.
+ Uncaught exception shows correct backtrace.
+ Fixed float variable/datatype interaction.
+ IntegerVar forces int type into the value.
+ New sequence inserts force a table truncate.
+ Fixed: ArrayVar sets encoded JSON
+ Fixed: error message in Cabinet namespaced wrong.
+ Fixed: missing PDO check on commit function in DB
+ Fixed: Errors use 500 HTTP response.
</pre>
EOF;
        case version_compare($version, '3.0.8', '<'):
            $content[] = <<<EOF
<pre>
3.0.8 updates
---------------------------
+ Fixed Missing json_encode options and depth.
</pre>
EOF;
    }
    return true;
}
