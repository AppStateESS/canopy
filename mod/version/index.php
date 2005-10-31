<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */
if (!defined('PHPWS_SOURCE_DIR')) {
    include '../../config/core/404.html';
    exit();
}

if (!Current_User::authorized('version')) {
    Current_User::disallow();
    return;
 }

PHPWS_Core::initModClass('version', 'Admin.php');

Version_Admin::main();


?>