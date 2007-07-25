<?php

/**
 * Administrative forms for the Access module
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */


class Access_Forms {

    function shortcuts()
    {
        if (!Current_User::allow('access')) {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initModClass('access', 'Shortcut.php');
        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = new DBPager('access_shortcuts', 'Access_Shortcut');
        $pager->setModule('access');
        $pager->setTemplate('forms/shortcut_list.tpl');
        $pager->setLink('index.php?module=access&amp;tab=shortcuts');
        $pager->addToggle('class="bgcolor1"');

        $form = new PHPWS_Form('shortcut_list');
        $form->addHidden('module', 'access');
        $form->addHidden('command', 'post_shortcut_list');


        $options['none'] = '';
        if (Current_User::allow('access', 'admin_options')) {
            $options['active'] = dgettext('access', 'Activate');
            $options['deactive'] = dgettext('access', 'Deactivate');
        }

        $options['delete'] = dgettext('access', 'Delete');
        $form->addSelect('list_action', $options);

        $page_tags = $form->getTemplate();

        $page_tags['KEYWORD_LABEL']  = dgettext('access', 'Keywords');
        $page_tags['URL_LABEL']      = dgettext('access', 'Url');
        $page_tags['ACTIVE_LABEL'] = dgettext('access', 'Active?');
        $page_tags['ACTION_LABEL']   = dgettext('access', 'Action');
        $page_tags['CHECK_ALL_SHORTCUTS'] = javascript('check_all', array('checkbox_name' => 'shortcut[]'));

        $js_vars['value']        = dgettext('access', 'Go');
        $js_vars['select_id']    = $form->getId('list_action');
        $js_vars['action_match'] = 'delete';
        $js_vars['message']      = dgettext('access', 'Are you sure you want to delete the checked shortcuts?');
        $page_tags['SUBMIT'] = javascript('select_confirm', $js_vars);

        $pager->addPageTags($page_tags);
        $pager->addRowTags('rowTags');

        $content = $pager->get();
        return $content;
    }

    function administrator()
    {
        if (!Current_User::allow('access', 'admin_options')) {
            Current_User::disallow();
            return;
        }
        if (!MOD_REWRITE_ENABLED) {
            $content[] = dgettext('access', 'You do not have mod rewrite enabled.');
            $content[] = dgettext('access', 'Open your config/core/config.php file in a text editor.');
            $content[] = dgettext('access', 'Set your "MOD_REWRITE_ENABLED" define equal to TRUE.');
            return implode('<br />', $content);
        } elseif (!Access::check_htaccess()) {
            if (!is_file('.htaccess')) {
                $content[] = dgettext('access', 'Your <b>.htaccess</b> file does not exist.');
                $content[] = dgettext('access', 'Go to the Update tab and try to create a new file.');
            } else {
                $content[] = dgettext('access', 'Your <b>.htaccess</b> file is not writable.');
                $content[] = dgettext('access', 'Look in your installation directory and give Apache write access.');
            }
            return implode('<br />', $content);
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'access');
        $form->addHidden('command', 'post_admin');

        $form->addCheckbox('rewrite_engine', 1);
        $form->setLabel('rewrite_engine', dgettext('access', 'Rewrite engine on'));
        if (PHPWS_Settings::get('access', 'rewrite_engine')) {
            $form->setMatch('rewrite_engine', 1);
        }

        $form->addCheckbox('shortcuts_enabled', 1);
        $form->setLabel('shortcuts_enabled', dgettext('access', 'Shortcuts enabled'));
        if (PHPWS_Settings::get('access', 'shortcuts_enabled')) {
            $form->setMatch('shortcuts_enabled', 1);
        }

        $form->addCheckbox('allow_deny_enabled', 1);
        $form->setLabel('allow_deny_enabled', dgettext('access', 'Allow/Deny enabled'));
        if (PHPWS_Settings::get('access', 'allow_deny_enabled')) {
            $form->setMatch('allow_deny_enabled', 1);
        }


        $form->addCheckBox('allow_file_update', 1);
        $form->setLabel('allow_file_update', dgettext('access', 'Allow file update'));
        if (PHPWS_Settings::get('access', 'allow_file_update')) {
            $form->setMatch('allow_file_update', 1);
        }


        $form->addSubmit(dgettext('access', 'Save settings'));
        $template = $form->getTemplate();

        $template['MOD_REWRITE_LABEL'] = dgettext('access', 'Mod Rewrite options');
        $template['HTACCESS_LABEL'] = dgettext('access', '.htaccess file options');

        return PHPWS_Template::process($template, 'access', 'forms/administrator.tpl');
    }


    function updateFile()
    {
        if (!Current_User::allow('access', 'admin_options')) {
            Current_User::disallow();
            return;
        }

        $form = new PHPWS_Form;
        $form->addHidden('module', 'access');
        $form->addHidden('command', 'post_update_file');
        $form->addSubmit(dgettext('access', 'Write .htaccess file'));

        $question = dgettext('access', 'Are you sure you want to restore the default .htaccess file?');
        $link = PHPWS_Text::linkAddress('access', array('command'=>'restore_default'), true);
        
        javascript('confirm');
        $form->addButton('restore', dgettext('access', 'Restore default .htaccess'));
        $form->setExtra('restore', sprintf('onclick="confirm_link(\'%s\', \'%s\')"',
                                           $question, $link));

        $template = $form->getTemplate();

        $template['INFO'] = dgettext('access', 'Your .htaccess file will contain the below:');

        $allow_deny = Access::getAllowDenyList();
        $template['HTACCESS'] = $allow_deny;
        $template['HTACCESS'] .= Access::getRewrite();

        $template['HTACCESS'] = str_replace('{', '&#123;', $template['HTACCESS']);
        $template['HTACCESS'] = str_replace('}', '&#125;', $template['HTACCESS']);

        if (is_file(PHPWS_HOME_DIR . '.htaccess')) {
            $template['CURRENT'] = file_get_contents(PHPWS_HOME_DIR . '.htaccess');
        } else {
            $template['CURRENT'] = dgettext('access', '.htaccess file is currently absent.');
            if (!is_writable(PHPWS_HOME_DIR)) {
                $template['CURRENT']  .= '<br />' . dgettext('access', 'Your installation directory must be writable if you want to create a new .htaccess file.');
            }
        }
        $template['CURRENT_LABEL'] = dgettext('access', 'Current .htaccess file');

        $template['CURRENT'] = str_replace('{', '&#123;', $template['CURRENT']);
        $template['CURRENT'] = str_replace('}', '&#125;', $template['CURRENT']);


        $content = PHPWS_Template::process($template, 'access', 'forms/update_file.tpl');
        return $content;
    }


    function denyAllowForm()
    {
        if (!Current_User::allow('access', 'admin_options')) {
            Current_User::disallow();
            return;
        }

        PHPWS_Core::initModClass('access', 'Allow_Deny.php');

        $form = new PHPWS_Form('allow_deny');
        $form->addHidden('module', 'access');
        $form->addHidden('command', 'post_deny_allow');

        $result = Access::getAllowDeny();
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
        }

        $form->addText('allow_address');
        $form->addText('deny_address');
        $form->addSubmit('add_allow_address', dgettext('access', 'Add allowed IP'));
        $form->addSubmit('add_deny_address', dgettext('access', 'Add denied IP'));

        $db = new PHPWS_DB('access_allow_deny');
        $result = $db->getObjects('Access_Allow_Deny');

        $options['none']      = dgettext('access', '-- Choose option --');
        $options['active']    = dgettext('access', 'Activate');
        $options['deactive']  = dgettext('access', 'Deactivate');
        $options['delete']    = dgettext('access', 'Delete');

        if (PHPWS_Settings::get('access', 'allow_all')) {
            $allow_all = TRUE;
            $options['allow_all'] = dgettext('access', 'Do not allow all');
        } else {
            $allow_all = FALSE;
            $options['allow_all'] = dgettext('access', 'Allow all');
        }

        $form->addSelect('allow_action', $options);

        unset($options['allow_all']);

        if (PHPWS_Settings::get('access', 'deny_all')) {
            $deny_all = TRUE;
            $options['deny_all'] = dgettext('access', 'Do not deny all');
        } else {
            $deny_all = FALSE;
            $options['deny_all'] = dgettext('access', 'Deny all');
        }
        $form->addSelect('deny_action', $options);

        $template = $form->getTemplate();

        if ($allow_all) {
            $template['ALLOW_ALL_MESSAGE'] = dgettext('access', 'You have "Allow all" enabled. All rows below will be ignored.');
        }

        if ($deny_all) {
            $template['DENY_ALL_MESSAGE'] = dgettext('access', 'You have "Deny all" enabled. All rows below will be ignored.');
        }

        $js_vars['value']        = dgettext('access', 'Go');
        $js_vars['action_match'] = 'delete';
        $js_vars['message']      = dgettext('access', 'Are you sure you want to delete the checked ips?');

        $js_vars['select_id']    = 'allow_deny_allow_action';
        $template['ALLOW_ACTION_SUBMIT'] = javascript('select_confirm', $js_vars);

        $js_vars['select_id']    = 'allow_deny_deny_action';
        $template['DENY_ACTION_SUBMIT'] = javascript('select_confirm', $js_vars);


        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return dgettext('access', 'An error occurred when trying to access the allowed and denied ip records. Please check your logs.');
        } elseif (empty($result)) {
            $template['DENY_MESSAGE']  = dgettext('access', 'No denied ip addresses found.');
            $template['ALLOW_MESSAGE'] = dgettext('access', 'No allowed ip addresses found.');
        } else {
            foreach ($result as $allow_deny) {
                $action = PHPWS_Text::secureLink(dgettext('access', 'Delete'), 'access', array('ad_id'=>$allow_deny->id, 'command'=>'delete_allow_deny'));
                if ($allow_deny->active) {
                    $active = dgettext('access', 'Yes');
                } else {
                    $active = dgettext('access', 'No');
                }

                if ($allow_deny->allow_or_deny) {
                    $check = sprintf('<input type="checkbox" name="allows[]" value="%s" />', $allow_deny->id);
                    $template['allow_rows'][] = array('ALLOW_CHECK'      => $check,
                                                      'ALLOW_IP_ADDRESS' => $allow_deny->ip_address,
                                                      'ALLOW_ACTIVE'     => $active,
                                                      'ALLOW_ACTION'     => $action);
                } else {
                    $check = sprintf('<input type="checkbox" name="denys[]" value="%s" />', $allow_deny->id);
                    $template['deny_rows'][] = array('DENY_CHECK'      => $check,
                                                     'DENY_IP_ADDRESS' => $allow_deny->ip_address,
                                                     'DENY_ACTIVE'     => $active,
                                                     'DENY_ACTION'     => $action);
                }
            }

            if (empty($template['allow_rows'])) {
                $template['ALLOW_MESSAGE'] = dgettext('access', 'No allowed ip addresses found.');
            }

            if (empty($template['deny_rows'])) {
                $template['DENY_MESSAGE'] = dgettext('access', 'No denied ip addresses found.');
            }
        }

        $template['CHECK_ALL_ALLOW'] = javascript('check_all', array('checkbox_name' => 'allows'));
        $template['CHECK_ALL_DENY'] = javascript('check_all', array('checkbox_name' => 'denys'));
        $template['ACTIVE_LABEL']     = dgettext('access', 'Active?');
        $template['ALLOW_TITLE']      = dgettext('access', 'Allowed IPs');
        $template['DENY_TITLE']       = dgettext('access', 'Denied IPs');
        $template['ACTION_LABEL']     = dgettext('access', 'Action');
        $template['IP_ADDRESS_LABEL'] = dgettext('access', 'IP Address');
        $template['WARNING']          = dgettext('access', 'Remember to "Update" your access file when finished changing IP rules.');


        return PHPWS_Template::process($template, 'access', 'forms/allow_deny.tpl');
    }

    function shortcut_menu()
    {
        PHPWS_Core::initModClass('access', 'Shortcut.php');
        @$sc_id = $_GET['sc_id'];

        if (!$sc_id) {
            @$key_id = $_GET['key_id'];
            if (!$key_id) {
                javascript('close_window');
                return;
            } else {
                $shortcut = new Access_Shortcut;
                $key = new Key($key_id);
                if (!$key->id) {
                    javascript('close_window');
                    return;
                }

                $shortcut->keyword = preg_replace('/[^\w\s\-]/', '', $key->title);
            }
        } else {
            $shortcut = new Access_Shortcut($sc_id);
            if (!$shortcut->id) {
                javascript('close_window');
                return;
            }
        }

        $form = new PHPWS_Form('shortcut_menu');
        $form->addHidden('module', 'access');
        $form->addHidden('command', 'post_shortcut');
        if (isset($key_id)) {
            $form->addHidden('key_id', $key_id);
        } else {
            $form->addHidden('sc_id', $shortcut->id);
        }

        $form->addText('keyword', $shortcut->keyword);
        $form->addSubmit('go', dgettext('access', 'Go'));
        $tpl = $form->getTemplate();

        $tpl['TITLE'] = dgettext('access', 'Shortcuts');
        $tpl['CLOSE'] = sprintf('<input type="button" value="%s" onclick="window.close();" />', dgettext('access', 'Cancel'));
        $content = PHPWS_Template::process($tpl, 'access', 'shortcut_menu.tpl');
        return $content;
    }


}

?>