<?php

/**
 * Uninstall file for access
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

function access_uninstall(&$content)
{
    PHPWS_DB::dropTable('access_shortcuts');
    PHPWS_DB::dropTable('access_allow_deny');
    $content[] = 'Access tables removed.';
    return TRUE;
}
