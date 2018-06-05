<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @version $Id$
 */

if (!Current_User::authorized('branch')) {
    Current_User::disallow();
}

\phpws\PHPWS_Core::initModClass('branch', 'Branch_Admin.php');
$branch_admin = new Branch_Admin;
$branch_admin->main();
