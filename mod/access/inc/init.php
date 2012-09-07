<?php

/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
PHPWS_Core::initModClass('access', 'Access.php');


if (PHPWS_Settings::get('access', 'forward_ids')) {
    Access::autoForward();
}

if (isset($GLOBALS['Forward'])) {
    Access::forward();
}
?>