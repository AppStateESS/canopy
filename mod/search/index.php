<?php

/**
 * Steering file
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

if (!defined('PHPWS_SOURCE_DIR')) {
    include '../../core/conf/404.html';
    exit();
}

if (isset($_REQUEST['tab']) || isset($_REQUEST['command'])) {
    \phpws\PHPWS_Core::initModClass('search', 'Admin.php');
    Search_Admin::main();
} else {
    Search_User::main();
}
