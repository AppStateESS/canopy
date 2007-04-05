<?php

  /**
   * Contains forms for users and demographics
   *
   * @version $Id$
   * @author  Matt McNaney <matt at tux dot appstate dot edu>
   * @package Core
   */
define('AUTO_SIGNUP',    1);
define('CONFIRM_SIGNUP', 2);
// Needs addition
//define('APPROVE_SIGNUP', 3);

PHPWS_Core::initCoreClass('Form.php');

class User_Form {
    function logBox($logged=TRUE)
    {
        if (PHPWS_Settings::get('users', 'user_menu') == 'none') {
            return null;
        }

        if (Current_User::isLogged()) {
            $username = Current_User::getUsername();
            return User_Form::loggedIn();
        } else {
            if (PHPWS_Settings::get('users', 'hide_login')) {
                return NULL;
            } else {
                return User_Form::loggedOut();
            }
        }
        
        return $form;
    }


    function loggedIn()
    {
        PHPWS_Core::initCoreClass('Text.php');
        $template['GREETING'] = dgettext('users', 'Hello');
        $template['USERNAME'] = Current_User::getUsername();
        $template['DISPLAY_NAME'] = Current_User::getDisplayName();
        $template['MODULES'] = PHPWS_Text::moduleLink(dgettext('users', 'Control Panel'),
                                                      'controlpanel',
                                                      array('command'=>'panel_view'));
        $template['LOGOUT'] = PHPWS_Text::moduleLink(dgettext('users', 'Log Out'),
                                                     'users',
                                                     array('action'=>'user', 'command'=>'logout'));
        $template['HOME_USER_PANEL'] = $template['HOME'] = PHPWS_Text::moduleLink(dgettext('users', 'Home'));
    
        $usermenu = PHPWS_User::getUserSetting('user_menu');
        return PHPWS_Template::process($template, 'users', 'usermenus/' . $usermenu);
    }

    function loggedOut()
    {
        if (isset($_REQUEST['phpws_username'])) {
            $username = $_REQUEST['phpws_username'];
        } else {
            $username = NULL;
        }

        $form = new PHPWS_Form('User_Login_Box');
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'user');
        $form->addHidden('command', 'login');
        $form->addText('phpws_username', $username);
        $form->addPassword('phpws_password');
        $form->addSubmit('submit', LOGIN_BUTTON);

        $form->setLabel('phpws_username', dgettext('users', 'Username'));
        $form->setLabel('phpws_password', dgettext('users', 'Password'));
    
        $template = $form->getTemplate();

        $signup_vars = array('action'  => 'user',
                             'command' => 'signup_user');

        $template['HOME_LOGIN'] = $template['HOME'] = PHPWS_Text::moduleLink(dgettext('users', 'Home'));

        if (PHPWS_Settings::get('users', 'new_user_method')) {
            $template['NEW_ACCOUNT'] = PHPWS_Text::moduleLink(USER_SIGNUP_QUESTION, 'users', $signup_vars);
        }

        $usermenu = PHPWS_User::getUserSetting('user_menu');

        return PHPWS_Template::process($template, 'users', 'usermenus/' . $usermenu);
    }

    function setPermissions($id)
    {
        $group = new PHPWS_Group($id, FALSE);

        $modules = PHPWS_Core::getModules();

        foreach ($modules as $mod) {
            $preorder[$mod['title']] = $mod;
        }

        ksort($preorder);
        $modules = $preorder;

        $tpl = new PHPWS_Template('users');
        $tpl->setFile('forms/permissions.tpl');

        $group->loadPermissions(FALSE);

        foreach ($modules as $mod){
            $mod_template = User_Form::modulePermission($mod, $group);
            if ($mod_template == false) {
                continue;
            }

            $tpl->setCurrentBlock('module');
            $tpl->setData($mod_template);
            $tpl->parseCurrentBlock('module');
        }

        $form = new PHPWS_Form();
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postPermission');
        $form->addHidden('group_id', $id);
        $form->addSubmit('update', dgettext('users', 'Update'));
        $template = $form->getTemplate();

        $vars['action']   = 'admin';
        if (!$group->user_id) {

            $vars['group_id'] = $group->id;
            $vars['command']  = 'manageMembers';
            $links[] = PHPWS_Text::secureLink(dgettext('users', 'Members'), 'users', $vars);
            
            $vars['command']  = 'edit_group';
            $links[] = PHPWS_Text::secureLink(dgettext('users', 'Edit'), 'users', $vars);
            

        } else {
            $vars['user_id'] = $group->user_id;
            $vars['command'] = 'editUser';
            $links[] = PHPWS_Text::secureLink(dgettext('users', 'Edit'), 'users', $vars);
        }

        $template['LINKS'] = implode(' | ', $links);

        $tpl->setData($template);
        
        $content = $tpl->get();
        return $content;
    }


    function modulePermission($mod, &$group)
    {
        $file = PHPWS_SOURCE_DIR . 'mod/' . $mod['title'] . '/boost/permission.php';
        if (!is_file($file)) {
            return FALSE;
        }

        $template = NULL;

        if ($file == FALSE) {
            return $file;
        }

        include $file;

        if (!isset($use_permissions) || $use_permissions == FALSE) {
            return;
        }

        $labels[] = NO_PERM_NAME;
        $button[] = NO_PERMISSION;

        if (isset($item_permissions) && $item_permissions == TRUE) {
            $labels[] = PART_PERM_NAME;
            $button[] = RESTRICTED_PERMISSION;
        }

        $labels[] = FULL_PERM_NAME;
        $button[] = UNRESTRICTED_PERMISSION;

        $permCheck = $group->getPermissionLevel($mod['title']);

        $form = new PHPWS_Form;
        $name = 'module_permission[' . $mod['title'] .']';
        $form->addRadio($name, $button);
        $form->setLabel($name, $labels);
        $form->setMatch($name, $permCheck);
        $radio = $form->get($name, TRUE);

        foreach ($radio['elements'] as $key=>$val) {
            $template['PERMISSION_' . $key] = $val . $radio['labels'][$key];
        }

        if (isset($permissions)) {
            foreach ($permissions as $permName => $permProper){
                $form = new PHPWS_Form;

                $name = 'sub_permission[' . $mod['title'] . '][' . $permName . ']';
                $form->addCheckBox($name, 1);
                if ($group->allow($mod['title'], $permName)) {
                    $subcheck = 1;
                } else {
                    $subcheck = 0;
                }

                $form->setMatch($name, $subcheck);
                $form->setLabel($name, $permProper);

                $tags = $form->get($name, TRUE);
                $subpermissions[] = $tags['elements'][0] . ' ' . $tags['labels'][0];
            }

            $template['SUBPERMISSIONS'] = implode('<br />', $subpermissions);
        }

        $template['MODULE_NAME'] = $mod['proper_name'];

        return $template;
    }

    function manageUsers()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['USERNAME_LABEL'] = dgettext('users', 'Username');
        $pageTags['EMAIL_LABEL'] = dgettext('users', 'Email');
        $pageTags['LAST_LOGGED_LABEL'] = dgettext('users', 'Last Logged');
        $pageTags['ACTIVE_LABEL'] = dgettext('users', 'Active');
        $pageTags['ACTIONS_LABEL'] = dgettext('users', 'Actions');

        $pager = new DBPager('users', 'PHPWS_User');
        $pager->setDefaultLimit(10);
        $pager->setModule('users');
        $pager->setTemplate('manager/users.tpl');
        $pager->setLink('index.php?module=users&amp;action=admin&amp;tab=manage_users&amp;authkey=' . Current_User::getAuthKey());
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getUserTpl');
        $pager->addToggle('class="toggle1"');
        $pager->addToggle('class="toggle2"');
        $pager->setSearch('username', 'email');

        return $pager->get();
    }


    function manageGroups()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $pageTags['GROUPNAME'] = dgettext('users', 'Group Name');
        //    $pageTags['ACTIVE'] = dgettext('users', 'Active');
        $pageTags['MEMBERS_LABEL'] = dgettext('users', 'Members');
        $pageTags['ACTIONS_LABEL'] = dgettext('users', 'Actions');

        $pager = new DBPager('users_groups', 'PHPWS_Group');
        $pager->setModule('users');
        $pager->setTemplate('manager/groups.tpl');
        $pager->setLink('index.php?module=users&amp;action=admin&amp;tab=manage_groups&amp;authkey=' . Current_User::getAuthKey());
        $pager->addPageTags($pageTags);
        $pager->addRowTags('getTplTags');
        $pager->addToggle('class="toggle1"');
        $pager->addToggle('class="toggle2"');
        $pager->addWhere('user_id', 0);

        return $pager->get();
    }

    function manageMembers(&$group)
    {
        $form = new PHPWS_Form('memberList');
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postMembers');
        $form->addHidden('group_id', $group->getId());
        $form->addText('search_member');

        $form->setLabel('search_member', dgettext('users', 'Add Member'));
        $form->addSubmit('search', dgettext('users', 'Add'));

        $template['NAME_LABEL'] = dgettext('users', 'Group name');
        $template['GROUPNAME'] = $group->getName();

        if (isset($_POST['search_member'])) {
            $_SESSION['Last_Member_Search'] = preg_replace('/[\W]+/', '', $_POST['search_member']);
            $db = new PHPWS_DB('users_groups');
            $db->addWhere('name', $_SESSION['Last_Member_Search']);
            $db->addColumn('id');
            $result = $db->select('one');

            if (isset($result)) {
                if (PEAR::isError($result)) {
                    PHPWS_Error::log($result);
                }
                else {
                    $group->addMember($result);
                    $group->save();
                    unset($_SESSION['Last_Member_Search']);
                }

            }
        }

        if (isset($_SESSION['Last_Member_Search'])) {
            $result = User_Form::getLikeGroups($_SESSION['Last_Member_Search'], $group);
            if (isset($result)) {
                $template['LIKE_GROUPS'] = $result;
                $template['LIKE_INSTRUCTION'] = dgettext('users', 'Member not found.') . ' ' . dgettext('users', 'Closest matches below.');
            } else
                $template['LIKE_INSTRUCTION'] = dgettext('users', 'Member not found.') . ' ' . dgettext('users', 'No matches found.');
        }

        $template = $form->getTemplate(TRUE, TRUE, $template);

        $vars['action']   = 'admin';
        $vars['group_id'] = $group->id;
        $vars['command']  = 'edit_group';
        $links[] = PHPWS_Text::secureLink(dgettext('users', 'Edit'), 'users', $vars);

        $vars['command'] = 'setGroupPermissions';
        $links[] = PHPWS_Text::secureLink(dgettext('users', 'Permissions'), 'users', $vars);

        $template['LINKS'] = implode(' | ', $links);

        $template['CURRENT_MEMBERS_LBL'] = dgettext('users', 'Current Members');
        $template['CURRENT_MEMBERS'] = User_Form::getMemberList($group);
        $result =  PHPWS_Template::process($template, 'users', 'forms/memberForm.tpl');

        return $result;

    }


    function getMemberList(&$group)
    {
        PHPWS_Core::initCoreClass('Pager.php');
        $content = NULL;

        $result = $group->getMembers();
        unset($db);
        if ($result){
            $db = new PHPWS_DB('users_groups');
            $db->addColumn('name');
            $db->addColumn('id');
            $db->addWhere('id', $result, '=', 'or');

            $groupResult = $db->select();

            $count = 0;

            $vars['action'] = 'admin';
            $vars['command'] = 'dropMember';
            $vars['group_id'] = $group->getId();

            foreach ($groupResult as $item){
                $count++;
                $vars['member'] = $item['id'];
                $action = PHPWS_Text::secureLink(dgettext('users', 'Drop'), 'users', $vars, NULL, dgettext('users', 'Drop this member from the group.'));
                if ($count % 2) {
                    $template['STYLE'] = 'class="bg-light"';
                }
                else {
                    $template['STYLE'] = NULL;
                }
                $template['NAME'] = $item['name'];
                $template['ACTION'] = $action;

                $data[] = PHPWS_Template::process($template, 'users', 'forms/memberlist.tpl');
            }

            $pager = new PHPWS_Pager;
            $pager->setData($data);
            $pager->setLinkBack('index.php?module=users&amp;group=' . $group->getId() . '&amp;action=admin&amp;command=manageMembers');
            $pager->pageData();
            $content = $pager->getData();
        }

        if (!isset($content)) {
            $content = dgettext('users', 'No members.');
        }

        if (PEAR::isError($content)) {
            PHPWS_Error::log($content);
            return $content->getMessage();
        }
        return $content;
    }

    function userForm(&$user, $message=NULL)
    {
        $form = new PHPWS_Form;
        if ($user->getId() > 0) {
            $form->addHidden('user_id', $user->getId());
            $form->addSubmit('submit', dgettext('users', 'Update User'));
        } else {
            $form->addSubmit('submit', dgettext('users', 'Add User'));
        }

        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postUser');
        $form->addHidden('module', 'users');

        if (Current_User::allow('users', 'settings')) {
            $db = new PHPWS_DB('users_auth_scripts');
            $db->setIndexBy('id');
            $db->addColumn('id');
            $db->addColumn('display_name');
            $result = $db->select('col');
            if (PEAR::isError($result)) {
                PHPWS_Error::log($result);
            } else {
                $form->addSelect('authorize', $result);
                $form->setMatch('authorize', $user->authorize);
                $form->setLabel('authorize', dgettext('users', 'Authorization'));
            }
        }

        $form->addText('username', $user->getUsername());
        $form->addText('display_name', $user->display_name);
        $form->addPassword('password1');
        $form->addPassword('password2');
        $form->addText('email', $user->getEmail());
        $form->setSize('email', 30);

        $form->setLabel('email', dgettext('users', 'Email Address'));
        $form->setLabel('username', dgettext('users', 'Username'));
        $form->setLabel('display_name', dgettext('users', 'Display name'));
        $form->setLabel('password1', dgettext('users', 'Password'));

        if (isset($tpl)) {
            $form->mergeTemplate($tpl);
        }

        $template = $form->getTemplate();

        $vars['action'] = 'admin';
        $vars['user_id'] = $user->id;

        /*
        $vars['command'] = 'editUser';
        $links[] = PHPWS_Text::secureLink(dgettext('users', 'Edit'), 'users', $vars);
        */

        if ($user->id) {
            $vars['command'] = 'setUserPermissions';
            $links[] = PHPWS_Text::secureLink(dgettext('users', 'Permissions'), 'users', $vars);
        }

        if (isset($links)) {
            $template['LINKS'] = implode(' | ', $links);
        }

        if (isset($message)) {
            foreach ($message as $tag=>$error)
                $template[strtoupper($tag) . '_ERROR'] = $error;
        }

        return PHPWS_Template::process($template, 'users', 'forms/userForm.tpl');
    }

    function deify(&$user)
    {
        if (!$_SESSION['User']->isDeity() || ($user->getId() == $_SESSION['User']->getId())) {
            $content[] = dgettext('users', 'Only another deity can create a deity.');
        } else {
            $content[] = dgettext('users', 'Are you certain you want this user to have complete control of this web site?');

            $values['user']      = $user->getId();
            $values['action']    = 'admin';
            $values['command']   = 'deify';
            $values['authorize'] = '1';
            $content[] = PHPWS_Text::secureLink(dgettext('users', 'Yes, make them a deity.'), 'users', $values);
            $values['authorize'] = '0';
            $content[] = PHPWS_Text::secureLink(dgettext('users', 'No, leave them as a mortal.'), 'users', $values);
        }

        return implode('<br />', $content);
    }

    function mortalize(&$user)
    {
        if (!$_SESSION['User']->isDeity()) {
            $content[] = dgettext('users', 'Only another deity can create a mortal.');
        }
        elseif($user->getId() == $_SESSION['User']->getId()) {
            $content[] = dgettext('users', 'A deity can not make themselves mortal.');
        }
        else {
            $values['user']      = $user->getId();
            $values['action']    = 'admin';
            $values['command']   = 'mortalize';
            $values['authorize'] = '1';
            $content[] = PHPWS_Text::secureLink(dgettext('users', 'Yes, make them a mortal.'), 'users', $values);
            $values['authorize'] = '0';
            $content[] = PHPWS_Text::secureLink(dgettext('users', 'No, leave them as a deity.'), 'users', $values);
        }

        return implode('<br />', $content);
    }

    function groupForm(&$group)
    {
        $form = new PHPWS_Form('groupForm');
        $members = $group->getMembers();

        if ($group->getId() > 0) {
            $form->addHidden('group_id', $group->getId());
            $form->addSubmit('submit', dgettext('users', 'Update Group'));
        } else
            $form->addSubmit('submit', dgettext('users', 'Add Group'));

        $form->addHidden('module', 'users');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postGroup');

        $form->addText('groupname', $group->getName());
        $form->setLabel('groupname', dgettext('users', 'Group Name'));
        $template = $form->getTemplate();

        $vars['action']   = 'admin';
        $vars['group_id'] = $group->id;
        $vars['command']  = 'manageMembers';
        $links[] = PHPWS_Text::secureLink(dgettext('users', 'Members'), 'users', $vars);

        $vars['command'] = 'setGroupPermissions';
        $links[] = PHPWS_Text::secureLink(dgettext('users', 'Permissions'), 'users', $vars);

        $template['LINKS'] = implode(' | ', $links);

        $content = PHPWS_Template::process($template, 'users', 'forms/groupForm.tpl');

        return $content;
    }

    function memberForm()
    {
        $form->add('add_member', 'textfield');
        $form->add('new_member_submit', 'submit', dgettext('users', 'Add'));
    
        $template['CURRENT_MEMBERS'] = User_Form::memberListForm($group);
        $template['ADD_MEMBER_LBL'] = dgettext('users', 'Add Member');
        $template['CURRENT_MEMBERS_LBL'] = dgettext('users', 'Current Members');

        if (isset($_POST['new_member_submit']) && !empty($_POST['add_member'])) {
            $result = User_Form::getLikeGroups($_POST['add_member'], $group);
            if (isset($result)) {
                $template['LIKE_GROUPS'] = $result;
                $template['LIKE_INSTRUCTION'] = dgettext('users', 'Members found.');
            } else
                $template['LIKE_INSTRUCTION'] = dgettext('users', 'No matches found.');
        }
    }

    function memberListForm($group)
    {
        $members = $group->getMembers();
        if (!isset($members)) {
            return dgettext('users', 'None found');
        }

        $db = new PHPWS_DB('users_groups');
        foreach ($members as $id)
            $db->addWhere('id', $id);
        $db->addOrder('name');
        $db->setIndexBy('id');
        $result = $db->getObjects('PHPWS_Group');

        $tpl = new PHPWS_Template('users');
        $tpl->setFile('forms/memberlist.tpl');
        $count = 0;
        $form = new PHPWS_Form;

        foreach ($result as $group){
            $form->add('member_drop[' . $group->getId() . ']', 'submit', dgettext('users', 'Drop'));
            $dropbutton = $form->get('member_drop[' . $group->getId() .']');
            $count++;
            $tpl->setCurrentBlock('row');
            $tpl->setData(array('NAME'=>$group->getName(), 'DROP'=>$dropbutton));
            if ($count%2) {
                $tpl->setData(array('STYLE' => 'class="bg-light"'));
            }
            $tpl->parseCurrentBlock();
        }

        return $tpl->get();
    }


    function getLikeGroups($name, &$group)
    {
        $db = new PHPWS_DB('users_groups');
        $name = preg_replace('/[^\w]/', '', $name);
        $db->addWhere('name', "%$name%", 'LIKE');

        if (!is_null($group->getName())) {
            $db->addWhere('name', $group->getName(), '!=');
        }

        $members = $group->getMembers();
        if (isset($members)) {
            foreach ($members as $id)
                $db->addWhere('id', $id, '!=');
        }
        $db->setIndexBy('id');
        $result = $db->getObjects('PHPWS_Group');

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return NULL;
        } elseif (!isset($result)) {
              return NULL;
        }

        $tpl = new PHPWS_Template('users');
        $tpl->setFile('forms/likeGroups.tpl');
        $count = 0;

        $vars['action'] = 'admin';
        $vars['command'] = 'addMember';
        $vars['group_id'] = $group->getId();

        foreach ($result as $member){
            if (isset($members)) {
                if (in_array($member->getId(), $members)) {
                    continue;
                }
            }

            $vars['member'] = $member->getId();
            $link = PHPWS_Text::secureLink( dgettext('users', 'Add'), 'users', $vars, NULL, dgettext('users', 'Add this user to this group.'));

            $count++;
            $tpl->setCurrentBlock('row');
            $tpl->setData(array('NAME'=>$member->getName(), 'ADD'=>$link));
            if ($count%2) {
                $tpl->setData(array('STYLE' => 'class="bg-light"'));
            }
            $tpl->parseCurrentBlock();
        }

        $content = $tpl->get();
        return $content;
    }

    /**
     *  Form for adding and choosing default authorization scripts
     */
    function authorizationSetup()
    {
        $template = array();
        PHPWS_Core::initCoreClass('File.php');

        $auth_list = User_Action::getAuthorizationList();

        foreach ($auth_list as $auth){
            $file_compare[] = $auth['filename'];
        }

        $form = new PHPWS_Form;

        $form->addHidden('module', 'users');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postAuthorization');

        $file_list = PHPWS_File::readDirectory(PHPWS_SOURCE_DIR . 'mod/users/scripts/', FALSE, TRUE, FALSE, array('php'));

        if (!empty($file_list)) {
            $remaining_files = array_diff($file_list, $file_compare);
        } else {
            $remaining_files = NULL;
        }

        if (empty($remaining_files)) {
            $template['FILE_LIST'] = dgettext('users', 'No new scripts found');
        }
        else {
            $form->addSelect('file_list', $remaining_files);
            $form->reindexValue('file_list');
            $form->addSubmit('add_script', dgettext('users', 'Add Script File'));
        }

        $form->mergeTemplate($template);
        $form->addSubmit('submit', dgettext('users', 'Update Default'));
        $template = $form->getTemplate();

        $template['AUTH_LIST_LABEL'] = dgettext('users', 'Authorization Scripts');
        $template['DEFAULT_LABEL']   = dgettext('users', 'Default');
        $template['DISPLAY_LABEL']   = dgettext('users', 'Display Name');
        $template['FILENAME_LABEL']  = dgettext('users', 'Script Filename');
        $template['ACTION_LABEL']    = dgettext('users', 'Action');

        $default_authorization = PHPWS_User::getUserSetting('default_authorization');

        foreach ($auth_list as $authorize){
            $links = array();
            extract($authorize);
            if ($default_authorization == $id) {
                $checked = 'checked="checked"';
            }
            else {
                $checked = NULL;
            }

            $getVars['module']  = 'users';
            $getVars['action']  = 'admin';
            $getVars['command'] = 'dropScript';

            if ($filename != 'local.php' && $filename != 'global.php') {
                $vars['QUESTION'] = dgettext('users', 'Are you sure you want to drop this authorization script?');
                $vars['ADDRESS'] = sprintf('index.php?module=users&action=admin&command=dropAuthScript&script_id=%s&authkey=%s', $id, Current_User::getAuthKey());
                $vars['LINK'] = dgettext('users', 'Drop');
                $links[1] = javascript('confirm', $vars);
            }

            $getVars['command'] = 'editScript';
            // May enable this later. No need for an edit link right now.
            //            $links[2] = PHPWS_Text::secureLink(dgettext('users', 'Edit'), 'users', $getVars);

            $row['CHECK'] = sprintf('<input type="radio" name="default_authorization" value="%s" %s />', $id, $checked);
            $row['DISPLAY_NAME'] = $display_name;
            $row['FILENAME'] = $filename;
            if (!empty($links)) {
                $row['ACTION'] = implode(' | ', $links);
            } else {
                $row['ACTION'] = dgettext('users', 'None');
            }
      
            $template['auth-rows'][] = $row;
        }
        return PHPWS_Template::process($template, 'users', 'forms/authorization.tpl');
    }

    function settings()
    {
        PHPWS_Core::initModClass('help', 'Help.php');

        $content = array();

        $form = new PHPWS_Form('user_settings');
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'update_settings');
        $form->addSubmit('submit',dgettext('users', 'Update Settings'));

        $form->addText('site_contact', PHPWS_User::getUserSetting('site_contact'));
        $form->setLabel('site_contact', dgettext('users', 'Site contact email'));
        $form->setSize('site_contact', 40);

        $signup_modes = array(0, AUTO_SIGNUP, CONFIRM_SIGNUP);

        $signup_labels = array(dgettext('users', 'Not allowed'),
                               dgettext('users', 'Immediate'),
                               dgettext('users', 'Email Verification')
                               );

        /*
         // Add later
        $signup_labels = array(dgettext('users', 'Not allowed'),
                               dgettext('users', 'Immediate'),
                               dgettext('users', 'Email Verification'),
                               dgettext('users', 'Approval with Email Verification')
                               );
        $signup_modes = array(0, AUTO_SIGNUP, CONFIRM_SIGNUP, APPROVE_SIGNUP);
        */

        $form->addRadio('user_signup', $signup_modes);
        $form->setLabel('user_signup', $signup_labels);
        $form->addTplTag('USER_SIGNUP_LABEL', dgettext('users', 'User Signup Mode'));
        $form->setMatch('user_signup', PHPWS_User::getUserSetting('new_user_method'));
        if (extension_loaded('gd')) {
            $form->addCheckbox('graphic_confirm');
            $form->setLabel('graphic_confirm', dgettext('users', 'Use graphic authentication'));
            $form->setMatch('graphic_confirm', PHPWS_User::getUserSetting('graphic_confirm'));
        }

        // Replace below with a directory read
        $menu_options['none']        = dgettext('users', 'None');
        $menu_options['Default.tpl'] = 'Default.tpl';
        $menu_options['top.tpl']     = 'top.tpl';

        $form->addSelect('user_menu', $menu_options);
        $form->setMatch('user_menu', PHPWS_User::getUserSetting('user_menu'));
        $form->setLabel('user_menu', dgettext('users', 'User Menu'));

        $form->addCheckBox('hide_login', 1);
        $form->setMatch('hide_login', PHPWS_Settings::get('users', 'hide_login'));
        $form->setLabel('hide_login', dgettext('users', 'Hide login box'));
        $form->addTplTag('AFFIRM', dgettext('users', 'Yes'));

        $template = $form->getTemplate();
        return PHPWS_Template::process($template, 'users', 'forms/settings.tpl');
    }

    /**
     * Signup form for new users
     */
    function signup_form($user, $message=NULL)
    {
        $form = new PHPWS_Form;
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'user');
        $form->addHidden('command', 'submit_new_user');

        $form->addText('username', $user->getUsername());
        $form->setLabel('username', dgettext('users', 'Username'));

        $new_user_method =  PHPWS_User::getUserSetting('new_user_method');

        $form->addPassword('password1', $user->getPassword());
        $form->allowValue('password1');
        $form->setLabel('password1', dgettext('users', 'Password'));
        
        $form->addPassword('password2', $user->getPassword());
        $form->allowValue('password2');
        $form->setLabel('password2', dgettext('users', 'Confirm'));

        $form->addText('email', $user->getEmail());
        $form->setLabel('email', dgettext('users', 'Email Address'));
        $form->setSize('email', 40);

        $form->addText('confirm_phrase');
        $form->setLabel('confirm_phrase', dgettext('users', 'Confirm text'));
 
        if (PHPWS_User::getUserSetting('graphic_confirm') && extension_loaded('gd')) {
            $result = User_Form::confirmGraphic();
            if (PEAR::isError($result)) {
                PHPWS_Error::log($result);
            } else {
                $form->addTplTag('CONFIRM_INSTRUCTIONS', dgettext('users', 'Please type the word seen in the image.'));
                $form->addText('confirm_graphic');
                $form->setLabel('confirm_graphic', dgettext('users', 'Confirm Graphic'));
                $form->addTplTag('GRAPHIC', $result);
            }
        }

        $form->addSubmit('submit', dgettext('users', 'Sign up'));
 
        $template = $form->getTemplate();

        if (isset($message)) {
            foreach ($message as $tag=>$error)
                $template[$tag] = $error;
        }

        $result = PHPWS_Template::process($template, 'users', 'forms/signup_form.tpl');
        return $result;
    }

    function confirmGraphic()
    {
        PHPWS_Core::initCoreClass('Captcha.php');
        return Captcha::get();
    }

    function loginPage()
    {
        if (isset($_REQUEST['phpws_username'])) {
            $username = $_REQUEST['phpws_username'];
        } else {
            $username = NULL;
        }

        $form = new PHPWS_Form('User_Login_Main');
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'user');
        $form->addHidden('command', 'login');
        $form->addText('phpws_username', $username);
        $form->addPassword('phpws_password');
        $form->addSubmit('submit', LOGIN_BUTTON);

        $form->setLabel('phpws_username', dgettext('users', 'Username'));
        $form->setLabel('phpws_password', dgettext('users', 'Password'));

        $template = $form->getTemplate();

        $content = PHPWS_Template::process($template, 'users', 'forms/login_form.tpl');

        return $content;
    }


    function _getNonUserGroups()
    {
        $db = new PHPWS_DB('users_groups');
        $db->addOrder('name');
        $db->addWhere('user_id', 0);
        return $db->select();
    }


    /**
     * Creates the permission menu template
     */
    function permissionMenu(&$key, $popbox=FALSE)
    {
        $edit_groups = Users_Permission::getRestrictedGroups($key, TRUE);
        if (PEAR::isError($edit_groups)) {
            PHPWS_Error::log($edit_groups);
            $tpl['MESSAGE'] = $edit_groups->getMessage();
            return $tpl;
        }

        $view_groups = User_Form::_getNonUserGroups();

        $view_matches = $key->getViewGroups();
        $edit_matches = $key->getEditGroups();


        $edit_select = User_Form::_createMultiple($edit_groups['restricted']['all'], 'edit_groups', $edit_matches);
        $view_select = User_Form::_createMultiple($view_groups, 'view_groups', $view_matches);

        $form = new PHPWS_Form('choose_permissions');
        $form->addHidden('module', 'users');
        $form->addHidden('action', 'permission');
        $form->addHidden('key_id', $key->id);
        $form->addRadio('view_permission', array(0, 1, 2));
        $form->setExtra('view_permission', 'onchange="hideSelect(this.value)"');
        $form->setLabel('view_permission', array(dgettext('users', 'All visitors'),
                                                 dgettext('users', 'Logged visitors'),
                                                 dgettext('users', 'Specific group(s)')));
        $form->setMatch('view_permission', $key->restricted);
        $form->addSubmit(dgettext('users', 'Save permissions'));

        if ($popbox) {
            $form->addHidden('popbox', 1);
        }

        $tpl = $form->getTemplate();

        $tpl['TITLE'] = dgettext('users', 'Permissions');

        $tpl['EDIT_SELECT_LABEL'] = dgettext('users', 'Edit restrictions');
        $tpl['VIEW_SELECT_LABEL'] = dgettext('users', 'View restrictions');

        if ($edit_select) {
            $tpl['EDIT_SELECT'] = $edit_select;
        } else {
            $tpl['EDIT_SELECT'] = dgettext('users', 'No restricted edit groups found.');
        }

        if ($view_select) {
            $tpl['VIEW_SELECT'] = $view_select;
        } else {
            $tpl['VIEW_SELECT'] = dgettext('users', 'No groups found.');
        }

        if ($popbox) {
            $tpl['CANCEL'] = sprintf('<input type="button" value="%s" onclick="window.close()" />', dgettext('users', 'Cancel'));
        }

        if (isset($_SESSION['Permission_Message'])) {
            $tpl['MESSAGE'] = $_SESSION['Permission_Message'];
            unset($_SESSION['Permission_Message']);
        }

        return $tpl;
    }

    function _createMultiple($group_list, $name, $matches)
    {
        if (empty($group_list)) {
            return NULL;
        }
        if (!is_array($matches)) {
            $matches = NULL;
        }

        foreach ($group_list as $group) {
            if ($matches && in_array($group['id'], $matches)) {
                $match = 'selected="selected"';
            } else {
                $match = NULL;
            }

            if (!empty($group['user_id'])) {
                $users[] = sprintf('<option value="%s" %s>%s</option>', $group['id'], $match, $group['name']);
            } else {
                $groups[] = sprintf('<option value="%s" %s>%s</option>', $group['id'], $match, $group['name']);
            }
        }

        if (isset($groups)) {
            $select[] = sprintf('<optgroup label="%s">', dgettext('users', 'Groups'));
            $select[] = implode("\n", $groups);
            $select[] = '</optgroup>';
        } else {
            $groups = array();
        }

        if (isset($users)) {
            $select[] = sprintf('<optgroup label="%s">', dgettext('users', 'Users'));
            $select[] = implode("\n", $users);
            $select[] = '</optgroup>';
        } else {
            $users = array();
        }

        if (isset($select)) {
            return sprintf('<select size="5" multiple="multiple" id="%s" name="%s[]">%s</select>',
                           $name, $name, implode("\n", $select));
        } else {
            return NULL;
        }
        
    }

}

?>