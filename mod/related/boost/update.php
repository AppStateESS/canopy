<?php

  /**
   * @author Matthew McNaney
   * @version $Id$
   */

function related_update(&$content, $version)
{
    switch ($version) {
    case version_compare($version, '0.1.0', '<'):
        $content[] = 'Made links XHTML compatible.';

    case version_compare($version, '0.1.1', '<'):
        Key::registerModule('related');
        $content[] = 'Register module to key.';

    case version_compare($version, '0.1.2', '<'):
        $content[] = '<pre>
0.1.2 changes
--------------
+ Added translate functions
</pre>';

    case version_compare($version, '0.1.3', '<'):
        $content[] = '<pre>
0.1.3 changes
--------------
+ Added German translation.
+ Updated translation functions.
</pre>';

    case version_compare($version, '0.1.4', '<'):
        $content[] = '<pre>
0.1.4 changes
--------------
+ Added uninstall.php file.
+ Removed uninstall.sql.
</pre>';

    case version_compare($version, '0.1.5', '<'):
        $content[] = '<pre>
0.1.5 changes
--------------
+ Updated English translation.
</pre>';

    }

    return true;
}

?>