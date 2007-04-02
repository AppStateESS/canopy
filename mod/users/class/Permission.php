<?php

/**
 * Class to control the user permissioning
 *
 * @version $Id$
 * @author Matt McNaney <matt at tux dot appstate dot edu>
 * @package User Module
 */

class Users_Permission {
    var $permissions = NULL;
    var $groups      = NULL;
    var $levels      = NULL;
  
    function Users_Permission($groups=NULL)
    {
        $this->groups = $groups;
    }

    function registerPermissions($module, &$content)
    {
        $tableName = Users_Permission::getPermissionTableName($module);
        if (!PHPWS_DB::isTable($tableName)) {
            return Users_Permission::createPermissions($module);
        }

        $file = sprintf('%smod/%s/boost/permission.php', PHPWS_SOURCE_DIR,
                        $module);
        if (!is_file($file)) {
            return NULL;
        }

        include_once $file;

        if (!isset($permissions) || !is_array($permissions)) {
            return TRUE;
        }

        $db = new PHPWS_DB($tableName);
        $columns = $db->getTableColumns();


        $columnSetting = 'smallint NOT NULL default \'0\'';
        translate('users');
        foreach ($permissions as $perm_name => $perm_proper) {
            if (in_array($perm_name, $columns)) {
                continue;
            }
            $result = $db->addTableColumn($perm_name, $columnSetting);
            if (PEAR::isError($result)) {
                $content[] = sprintf(_('Could not create "%s" permission column.'), $perm_name);
                PHPWS_Error::log($result);
            } else {
                $content[] = sprintf(_('"%s" permission column created.'), $perm_name);
            }
        }
        translate();
        return TRUE;
    }

    function allowedItem($module, $item_id, $itemname=NULL)
    {
        if (!isset($itemname)) {
            $itemname = $module;
        }

        // Get the permission level for the group
        $permissionLvl = $this->getPermissionLevel($module);

        switch ($permissionLvl) {
        case NO_PERMISSION:
            return FALSE;
            break;

        case UNRESTRICTED_PERMISSION:
            return TRUE;
            break;

        case RESTRICTED_PERMISSION:
            // If no items exist in the permission object, return FALSE
            if (!isset($this->permissions[$module]['items']) ||
                !isset($this->permissions[$module]['items'][$itemname])) {
                return FALSE;
            } elseif (in_array($item_id, $this->permissions[$module]['items'][$itemname])){
                return TRUE;
            } else {
                return FALSE;
            }
            break;
        }
    }

    function allow($module, $subpermission=NULL, $item_id=NULL, $itemname=NULL)
    {
        // If permissions object is not set, load it
        if (!isset($this->permissions[$module])) {
            $result = Users_Permission::loadPermission($module, $this->permissions);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        if ($this->getPermissionLevel($module) == NO_PERMISSION) {
            return FALSE;
        }
        if(!empty($this->permissions[$module]['permissions'])) {
            if (isset($subpermission)) {
                if (!isset($this->permissions[$module]['permissions'][$subpermission])) {
                    PHPWS_Error::log(USER_ERR_FAIL_ON_SUBPERM, 'users', 'allow', 'SubPerm: ' . $subpermission);
                    return FALSE;
                }

                $allow = $this->permissions[$module]['permissions'][$subpermission];
                if ((bool)$allow) {
                    if (isset($item_id)) {
                        // subpermission is set as is item id
                        return $this->allowedItem($module, $item_id, $itemname);
                    } else {
                        // subpermission is set and item id is not
                        return TRUE;
                    }
                } else {
                    // subpermission is not allowed
                    return FALSE;
                }
            } else {
                if (isset($item_id)) {
                    // subpermission is not set and item id is set
                    return $this->itemIsAllowed($module, $item_id, $itemname);
                } else {
                    // subpermission is not set and item id is not set
                    return TRUE;
                }
            }
        } elseif (empty($subpermission)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function getPermissionLevel($module)
    {
        if (!isset($this->permissions[$module])) {
            $result = Users_Permission::loadPermission($module, $this->permissions);
            if (PEAR::isError($result)) {
                return $result;
            }
        }

        return $this->permissions[$module]['permission_level'];
    }

    function loadPermission($module, &$permissions)
    {
        $groups = $this->groups;

        $permTable = Users_Permission::getPermissionTableName($module);

        if(!PHPWS_DB::isTable($permTable)) {
            $permissions[$module]['permission_level'] = UNRESTRICTED_PERMISSION;
            return TRUE;
        }

        $permDB = new PHPWS_DB($permTable);

        if (!empty($groups)) {
            $permDB->addWhere('group_id', $groups, 'in');
        }

        $permResult = $permDB->select();

        if (!isset($permResult)) {
            $permissions[$module]['permission_level'] = NO_PERMISSION;
            return TRUE;
        }

        $itemdb = new PHPWS_DB('phpws_key');
        $itemdb->addWhere('phpws_key_edit.group_id', $this->groups);
        $itemdb->addWhere('phpws_key_edit.key_id', 'phpws_key.id');
        $itemdb->addWhere('phpws_key.module', $module);

        $result = $itemdb->select();
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
        } elseif (!empty($result)) {
            foreach ($result as $key) {
                $itemList[$key['item_name']][] = $key['item_id'];
            }
        }

        $permissionSet = array();
        $permissionLevel = NO_PERMISSION;
        foreach ($permResult as $permission) {
            unset($permission['group_id']);

            if ($permissionLevel < $permission['permission_level']) {
                $permissionLevel = $permission['permission_level'];
            }

            unset($permission['permission_level']);
      
            foreach($permission as $name=>$value){
                if (!isset($permissionSet[$name]) || $permissionSet[$name] < $value) {
                    $permissionSet[$name] = $value;
                } elseif ($permissionSet[$name] < $value) {
                    $permissionSet[$name] = $value;
                }
            }
        }

        if (isset($itemList)) {
            $permissions[$module]['items'] = $itemList;
        } else {
            $permissions[$module]['items'] = NULL;
        }
        $permissions[$module]['permission_level'] = $permissionLevel;
        $permissions[$module]['permissions']      = $permissionSet;
        $this->levels[$module] = $permissionLevel;
        return TRUE;
    }

    function removePermissions($module)
    {
        $tableName = Users_Permission::getPermissionTableName($module);
        if (!PHPWS_DB::isTable($tableName)) {
            return FALSE;
        }
        $result = PHPWS_DB::dropTable($tableName, FALSE, FALSE);
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return $result;
        }

        return TRUE;
    }

    function createPermissions($module)
    {
        $permissions = NULL;
        $file = sprintf('%smod/%s/boost/permission.php', PHPWS_SOURCE_DIR,
                        $module);
        if (!is_file($file)) {
            return NULL;
        }

        include_once $file;

        $result = Users_Permission::createPermissionTable($module, $permissions);
        if (PEAR::isError($result)) {
            $errors[] = $result;
        }
      
        if (isset($errors)) {
            foreach ($errors as $error)
                PHPWS_Error::log($error);
            return FALSE;
        }

        return TRUE;
    }

    function createPermissionTable($module, $permissions=NULL)
    {
        $tableName = Users_Permission::getPermissionTableName($module);
        $columnSetting = 'smallint NOT NULL default \'0\'';

        if (PHPWS_DB::isTable($tableName)) {
            return PHPWS_Error::get(USER_ERR_PERM_TABLE, 'users', 'createPermissionTable', 'Table Name: ' . $tableName);
        }

        $DB = new PHPWS_DB($tableName);
    
        $columns['group_id'] = 'int NOT NULL default \'0\'';
        $columns['permission_level'] = 'smallint NOT NULL default \'0\'';

        if (isset($permissions)) {
            foreach ($permissions as $permission=>$description)
                $columns[$permission] = &$columnSetting;
        }

        $DB->addValue($columns);
        return $DB->createTable();
    }

    function getPermissionTableName($module)
    {
        return implode('', array($module, '_permissions'));    
    }

    function setPermissions($group_id, $module, $level, $subpermissions=NULL)
    {
        if (empty($group_id) || !is_numeric($group_id)) {
            return false;
        }

        $tableName = Users_Permission::getPermissionTableName($module);
        if (!PHPWS_DB::isTable($tableName)) {
            return;
        }

        $db = new PHPWS_DB($tableName);
        $db->addWhere('group_id', (int)$group_id);

        $db->delete();

        $db->resetWhere();

        $db->addValue('group_id', (int)$group_id);
        $columns = $db->getTableColumns();

        $db->addValue('permission_level', (int)$level);

        if ($level == NO_PERMISSION) {
            unset($subpermissions);
            Users_Permission::clearItemPermissions($module, $group_id);
        }

        if (isset($subpermissions)) {
            foreach ($columns as $colName){
                if ($colName == 'permission_level' || $colName == 'group_id') {
                    continue;
                }
        
                if (isset($subpermissions[$colName]) && (int)$subpermissions[$colName] == 1) {
                    $db->addValue($colName, 1);
                } else {
                    $db->addValue($colName, 0);
                }
            }
        }

        return $db->insert();
    }

    /**
     * Returns all groups that have restricted item permissions
     * for a specific module
     *
     * @param object  key          Key object for comparison
     * @param boolean edit_rights  If true, check the edit permissions as well
     */
    function getRestrictedGroups($key, $edit_rights=false)
    {
        $group_list = Users_Permission::getPermissionGroups($key, $edit_rights);
        if (empty($group_list) || PEAR::isError($group_list)) {
            return $group_list;
        } elseif (isset($group_list['restricted']['all'])) {
            return $group_list;
        }
    }
    
    /**
     * Returns an associative list of all groups and their levels of permission
     * in reference to the key passed to it
     */
    function getPermissionGroups($key, $edit_rights=false)
    {
        if ( empty($key) ||
             !PHPWS_Core::isClass($key, 'key') || 
             $key->isHomeKey() ||
             empty($key->module) ||
             ($edit_rights && empty($key->edit_permission) ) 
             ) {
            return NULL;
        }

        $permTable = Users_Permission::getPermissionTableName($key->module);

        if (!PHPWS_DB::isTable($permTable)) {
            return PHPWS_Error::get(USER_ERR_PERM_FILE, 'users', __CLASS__ . '::' . __FUNCTION__);
        }

        $db = new PHPWS_DB('users_groups');
        $db->addColumn('users_groups.*');
        $db->addColumn("$permTable.permission_level");
        $db->addWhere('id', "$permTable.group_id");
        $db->addWhere("$permTable.permission_level", 0, '>');
        
        $test_db = new PHPWS_DB($permTable);

        if ($edit_rights) {
            if (!$test_db->isTableColumn($key->edit_permission)) {
                return PHPWS_Error::get(KEY_PERM_COLUMN_MISSING, 'core',
                                        'Users_Permission::getRestrictedGroups',
                                        $key->edit_permission);
            }
            $db->addWhere($permTable . '.' . $key->edit_permission, 1);
        }
        
        $db->addOrder('name');
        $result = $db->select();
        if (empty($result) || PEAR::isError($result)) {
            return $result;
        }

        foreach ($result as $group) {
            if ($group['user_id']) {
                if ($group['permission_level'] == RESTRICTED_PERMISSION) {
                    $glist['restricted']['all'][]   =
                    $glist['restricted']['users'][] = $group;
                } else {
                    $glist['unrestricted']['users'][] =
                    $glist['unrestricted']['all'][]   = $group;
                }
                $glist['permitted']['users'][] = $group;
            } else {
                if ($group['permission_level'] == RESTRICTED_PERMISSION) {
                    $glist['restricted']['groups'][] = 
                    $glist['restricted']['all'][]    = $group;
                } else {
                    $glist['unrestricted']['groups'][] =
                    $glist['unrestricted']['all'][]    = $group;
                }
                $glist['permitted']['groups'][] = $group;
            }
            $glist['permitted']['all'][] = $group;
        }

        return $glist;
    }


    function getGroupList($groups)
    {
        PHPWS_Core::initModClass('users', 'Group.php');

        $db = new PHPWS_DB('users_groups');

        $db->addWhere('id', $groups);
        $result = $db->getObjects('PHPWS_Group');

        if (PEAR::isError($result)) {
            return $result;
        }

        foreach ($result as $group) {
            $inputs[$group->getId()] = $group->getName();
        }
        return $inputs;
    }



    function postViewPermissions(&$key)
    {
        if (!isset($_POST['view_permission'])) {
            return;
        }
        
        $key->restricted = (int)$_POST['view_permission'];
        
        if ( $key->restricted == 2 ) {
            $key->_view_groups = NULL;
            if ( isset($_POST['view_groups']) && is_array($_POST['view_groups']) ) {
                $key->_view_groups = &$_POST['view_groups'];
            }
        }
    }

    function postEditPermissions(&$key)
    {
        if (!isset($_POST['edit_groups']) || !is_array($_POST['edit_groups'])) {
            return;
        }

        $key->_edit_groups = &$_POST['edit_groups'];

        // if the key is view restricted we need to make sure
        // the people who edit can view the item
        if ($key->restricted == 2) {
            if (empty($key->_view_groups)) {
                $key->_view_groups = &$key->_edit_groups;
            } elseif (is_array($key->_view_groups)) {
                $key->_view_groups = array_merge($key->_view_groups, $key->_edit_groups);
                $key->_view_groups = array_unique($key->_view_groups);
            }
        }
    }

    function clearItemPermissions($module, $group_id)
    {
        $db = new PHPWS_DB('phpws_key_edit');
        $db->addWhere('group_id', $group_id);
        $db->addWhere('phpws_key.module', $module);
        $db->addWhere('key_id', 'phpws_key.id');
        return $db->delete();
    }


    function giveItemPermission($user_id, &$key)
    {
        $user = new PHPWS_User($user_id);
        $groups = $user->getGroups();

        if (empty($groups) || !is_array($groups)) {
            return;
        }

        if (empty($key->_edit_groups)) {
            $key->_edit_groups = $groups;
        } else {
            $key->_edit_groups = array_merge($key->_edit_groups, $groups);
        }
        return $key->save();
    }
}

?>
