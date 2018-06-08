<?php

/**
 
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */
\phpws\PHPWS_Core::initModClass('access', 'Access.php');


if (PHPWS_Settings::get('access', 'forward_ids')) {
    Access::autoForward();
}

if (isset($GLOBALS['Forward'])) {
    Access::forward();
}
