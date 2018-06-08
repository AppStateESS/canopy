<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

\phpws\PHPWS_Core::initModClass('search', 'User.php');

Search_User::searchBox();

if (isset($_SESSION['Search_Admin'])) {
    \phpws\PHPWS_Core::initModClass('search', 'Admin.php');
    Search_Admin::miniAdmin();
}
