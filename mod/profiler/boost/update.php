<?php

  /**
   * @version $Id$
   * @author Matthew McNaney <mcnaney at gmail dot com>
   */

function profiler_update(&$content, $currentVersion)
{
    switch ($currentVersion) {
    case version_compare($currentVersion, '0.3.0', '<'):
        $content[] = 'This package does not update versions under 0.3.0';
        return false;

    case version_compare($currentVersion, '0.3.1', '<'):
        $content[] = '<pre>
0.3.1 changes
----------------
+ Added translate functions.
</pre>
';

    }     
    
    return true;
}

?>