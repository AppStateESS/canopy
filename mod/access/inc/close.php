<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

if (!isset($_SESSION['Access_Allow_Deny'])) {
    \phpws\PHPWS_Core::initModClass('access', 'Access.php');
    Access::allowDeny();
}

if (!$_SESSION['Access_Allow_Deny']) {
    \phpws\PHPWS_Core::initModClass('access', 'Access.php');
    Access::denied();
}


if (Current_User::allow('access')) {
    $key = \Canopy\Key::getCurrent();
    if (!empty($key) && !$key->isDummy()) {
        \phpws\PHPWS_Core::initModClass('access', 'Access.php');
        Access::shortcut($key);
    }
}
