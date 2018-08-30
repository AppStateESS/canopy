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

function users_update(&$content, $version)
{
    switch (1) {
        case  version_compare($version, '2.8.3', '<'):
            $content[] = <<<EOF
<pre>
2.8.3 update
-------------
+ Fixed FontAwesome icon.
+ Sort group member names.
</pre>
EOF;
    }
    return true;
}