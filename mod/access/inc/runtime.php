<?php

/**
 * Description
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 */

// If no one else has set $_REQUEST['module'] by this point and Core wants us to 
// forward, 404.
if(!empty($GLOBALS['Forward']) && !array_key_exists('module', $_REQUEST)) {
    \phpws\PHPWS_Core::errorPage(404);
}

