<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @version $Id$
 */

function controlpanel_unregister($module, &$content)
{
    \phpws\PHPWS_Core::initModClass('controlpanel', 'ControlPanel.php');
    return PHPWS_ControlPanel::unregisterModule($module, $content);
}

