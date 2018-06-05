<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

if (!defined('PHPWS_SOURCE_DIR')) {
    include '../../core/conf/404.html';
    exit();
}

\phpws\PHPWS_Core::initModClass('access', 'Access.php');
if (Current_User::authorized('access')) {
    Access::main();
} else {
    Current_User::disallow();
    exit();
}
