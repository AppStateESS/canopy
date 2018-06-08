<?php
/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

function controlpanel_register($module, &$content)
{
    \phpws\PHPWS_Core::initModClass('controlpanel', 'ControlPanel.php');

    $result = PHPWS_ControlPanel::registerModule($module, $content);
    return $result;
}
