<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

function users_register($module, &$content)
{
    translate('users');
    PHPWS_Core::initModClass('users', 'Permission.php');
    PHPWS_Core::initModClass('users', 'My_Page.php');

    $no_permissions = $no_my_page = FALSE;

    $result = Users_Permission::createPermissions($module);
  
    if (is_null($result)){
        PHPWS_Boost::addLog('users', _('Permissions file not found.'));
        $content[] =  _('Permissions file not found.');
        $no_permissions = TRUE;
    } elseif (PEAR::isError($result)) {
        $content[] = _('Permissions table not created successfully.');
        PHPWS_Error::log($result);
        return FALSE;
    } else {
          $content[] = _('Permissions table created successfully.');
    }

    $result = My_Page::registerMyPage($module);
    if (PEAR::isError($result)){
        PHPWS_Boost::addLog('users', _('A problem occurred when trying to register this module to My Page.'));
        $content[] = _('A problem occurred when trying to register this module to My Page.');
        return FALSE;
    } elseif ($result != FALSE) {
          $content[] = _('My Page registered to Users module.');
    } else {
        $no_my_page = TRUE;
    }
    translate();
    // If the module doesn't have permissions or a My Page
    // then don't register the module
    if ($no_permissions && $no_my_page) {
        return FALSE;
    } else {
        return TRUE;
    }
}

?>