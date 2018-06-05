<?php
/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @version $Id$
 */

function branch_uninstall(&$content)
{
    PHPWS_DB::dropTable('branch_sites');
    return TRUE;
}
