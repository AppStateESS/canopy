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
function filecabinet_update(&$content, $version)
{
    $home_dir = PHPWS_Boost::getHomeDir();
    switch ($version) {
        case version_compare($version, '2.8.0', '<'):
            $content[] = '<pre>File Cabinet versions prior to 2.8.0 should be updated in phpWebSite</pre>';
            return false;
            
        case version_compare($version, '2.9.0', '<'):
            $content[] = $content[] = <<<EOF
<pre>2.9.0 changes
----------------
+ Updated to work with Canopy
</pre>
EOF;
            break;
    }
    return true;
}
