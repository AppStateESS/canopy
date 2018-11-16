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

function layout_update(&$content, $version) {
    switch (1) {
        case version_compare($version, '2.7.1', '<'):
        $content[] = <<<EOF
<pre>
2.7.1 update
-------------
+ Changed default theme.
</pre>
EOF;
        case version_compare($version, '2.7.2', '<'):
        $content[] = <<<EOF
<pre>
2.7.2 update
-------------
+ Updated move script.
</pre>
EOF;
    }
    return true;
}