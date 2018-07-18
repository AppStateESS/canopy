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

function core_update(&$content, $version) {
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
    }
    return true;
}