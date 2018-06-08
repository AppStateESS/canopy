<?php
/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

// Destroy unused sessions
if (\phpws\PHPWS_Core::getCurrentModule() != 'users'){
    \phpws\PHPWS_Core::killSession('Member_Pager');
    \phpws\PHPWS_Core::killSession('All_Demo');
    \phpws\PHPWS_Core::killSession('User_Manager');
    \phpws\PHPWS_Core::killSession('Group_Manager');
}

Current_User::permissionMenu();
