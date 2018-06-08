<?php
/**
 
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

function filecabinet_unregister($module, &$content)
{
    $db = new PHPWS_DB('folders');
    $db->addValue('module_created', null);
    $db->addWhere('module_created', $module);
    PHPWS_Error::logIfError($db->update());
    $content[] = 'Unregistered from File Cabinet.';
    return true;
}
