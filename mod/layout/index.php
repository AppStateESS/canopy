<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

if (!defined('PHPWS_SOURCE_DIR')) {
    Error::errorPage(403);
}

if ($_REQUEST['module'] != 'layout' || !isset($_REQUEST['action'])) {
    Error::errorPage('404');
}


if ($_REQUEST['action'] == 'ckeditor') {
    Layout::ckeditor();
    exit();
}

if (!Current_User::allow('layout')) {
    Current_User::disallow();
}

\phpws\PHPWS_Core::initModClass('layout', 'LayoutAdmin.php');

switch ($_REQUEST['action']){
    case 'admin':
        Layout_Admin::admin();
        break;

    default:
        \phpws\PHPWS_Core::errorPage('404');
} // END action switch
