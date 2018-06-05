<?php

/**
 * My Page for users, controls changing password, display name, etc.
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @version $Id: my_page.php 7776 2010-06-11 13:52:58Z jtickle $
 */
// Number of days a remember me cookie will last
if (!defined('REMEMBER_ME_LIFE')) {
    define('REMEMBER_ME_LIFE', 365);
}

function my_page()
{
    if (isset($_REQUEST['subcommand'])) {
        $subcommand = $_REQUEST['subcommand'];
    } else {
        $subcommand = 'updateSettings';
    }

    $user = $_SESSION['User'];

    $template['TITLE'] = 'Change my Settings';
    switch ($subcommand) {
        case 'updateSettings':
            if (isset($_GET['save'])) {
                $template['MESSAGE'] = 'User settings updated.';
            }

            $content = User_Settings::userForm($user);
            break;

        case 'postUser':
            User_Settings::rememberMe();
            User_Settings::setCP();
            $result = User_Action::postUser($user, FALSE);

            if (is_array($result)) {
                $content = User_Settings::userForm($user, $result);
            } else {
                if (PHPWS_Error::logIfError($user->save())) {
                    $content = 'An error occurred while updating your user account.';
                } else {
                    $_SESSION['User'] = $user;
                    \phpws\PHPWS_Core::reroute('index.php?module=users&action=user&tab=users&save=1');
                }
            }
            break;
    }

    $template['CONTENT'] = $content;

    return PHPWS_Template::process($template, 'users', 'my_page/main.tpl');
}

class User_Settings {

    public static function userForm(PHPWS_User $user, $message = NULL)
    {
        require_once PHPWS_SOURCE_DIR . 'core/class/Time.php';
        $form = new PHPWS_Form;

        $form->addHidden('module', 'users');
        $form->addHidden('action', 'user');
        $form->addHidden('command', 'my_page');
        $form->addHidden('subcommand', 'postUser');

        if (Current_User::allow('users') || $user->display_name == $user->username) {
            $form->addText('display_name', $user->display_name);
            $form->setClass('display_name', 'form-control');
            $form->setLabel('display_name', 'Display Name');
        } else {
            $form->addTplTag('DISPLAY_NAME_LABEL',
                    'Display Name');
            $tpl['DISPLAY_NAME'] = javascript('slider',
                    array('link' => $user->display_name,
                'id' => 'name-info',
                'message' => 'Once you change your display name, you may not change it again until reset by the site administrator.'));
        }

        if ($user->canChangePassword()) {
            $tpl['SHOW_PW'] = ' ';
        }

        $form->addText('email', $user->getEmail());
        $form->setSize('email', 40);
        $form->setLabel('email', 'Email Address');
        $form->setClass('email', 'form-control');

        if (isset($tpl)) {
            $form->mergeTemplate($tpl);
        }

        $tz_list = PHPWS_Time::getTZList();

        $timezones['server'] = dgettext('users', '-- Use server\'s time zone --');
        foreach ($tz_list as $tz) {
            if (!empty($tz['codes'])) {
                $timezones[$tz['id']] = sprintf('%s : %s', $tz['id'],
                        $tz['codes'][0]);
            } elseif (!empty($tz['city'])) {
                $timezones[$tz['id']] = sprintf('%s : %s', $tz['id'],
                        $tz['city'][0]);
            } else {
                $timezones[$tz['id']] = $tz['id'];
            }
        }

        if (isset($_REQUEST['timezone'])) {
            $user_tz = $_REQUEST['timezone'];
        } else {
            $user_tz =  \phpws\PHPWS_Cookie::read('user_tz');
        }

        $form->addSelect('timezone', $timezones);
        $form->setLabel('timezone', 'Time Zone');
        $form->setMatch('timezone', $user_tz);
        $form->setClass('timezone', 'form-control');

        if (isset($_REQUEST['dst']) && $_REQUEST['timezone'] != 'server') {
            $dst = $_REQUEST['dst'];
        } else {
            $dst =  \phpws\PHPWS_Cookie::read('user_dst');
        }

        $form->addCheckbox('dst', 1);
        $form->setMatch('dst', $dst);
        $form->setLabel('dst', 'Use Daylight Savings Time');

        if (isset($_POST['cp'])) {
            $cp = (int) $_POST['cp'];
        } else {
            $cp = (int)  \phpws\PHPWS_Cookie::read('user_cp');
        }

        if (Current_User::allowRememberMe()) {
            // User must authorize locally
            if ($_SESSION['User']->authorize == 1) {
                $form->addCheckbox('remember_me', 1);
                if (PHPWS_Cookie::read('remember_me')) {
                    $form->setMatch('remember_me', 1);
                }
                $form->setLabel('remember_me', 'Remember me');
            }
        }

        $form->addHidden('userId', $user->getId());
        $form->addSubmit('submit', 'Update my information');
        $form->setClass('submit', 'btn btn-primary');

        if (!DISABLE_TRANSLATION && !FORCE_DEFAULT_LANGUAGE) {
            $language_file = \phpws\PHPWS_Core::getConfigFile('users', 'languages.php');

            if ($language_file) {
                include $language_file;
                $form->addSelect('language', $languages);
                $form->setClass('language', 'form-control');
                $form->setLabel('language',
                        'Language preference');
                if (isset($_COOKIE['phpws_default_language'])) {
                    $language = preg_replace('/\W/', '',
                            $_COOKIE['phpws_default_language']);
                    $form->setMatch('language', $language);
                }
            }
        }

        $template = $form->getTemplate();

        if (isset($message)) {
            foreach ($message as $tag => $error) {
                $template[$tag] = $error;
            }
        }

        $template['ACCT_INFO'] = 'Account Information';
        $template['LOCAL_INFO'] = 'Localization';
        $template['PREF'] = 'Preferences';

        return PHPWS_Template::process($template, 'users',
                        'my_page/user_setting.tpl');
    }

    
    /**
     * @deprecated
     * @return type
     */
    public static function setTZ()
    {
        if ($_POST['timezone'] != 'server' && preg_match('/[^0-9\-]/',
                        $_POST['timezone'])) {
            return;
        }

        if ($_POST['timezone'] == 'server') {
             \phpws\PHPWS_Cookie::delete('user_tz');
             \phpws\PHPWS_Cookie::delete('user_dst');
            return;
        } else {
             \phpws\PHPWS_Cookie::write('user_tz', strip_tags($_POST['timezone']));
        }


        if (isset($_POST['dst'])) {
             \phpws\PHPWS_Cookie::write('user_dst', 1);
        } else {
             \phpws\PHPWS_Cookie::delete('user_dst');
        }
    }

    public static function setCP()
    {
        if (isset($_POST['cp'])) {
             \phpws\PHPWS_Cookie::write('user_cp', 1);
        } else {
             \phpws\PHPWS_Cookie::delete('user_cp');
        }
    }

    public static function rememberMe()
    {
        // User must authorize locally
        if (PHPWS_Settings::get('users', 'allow_remember') && $_SESSION['User']->authorize == 1) {
            if (isset($_POST['remember_me'])) {
                $db = new PHPWS_DB('user_authorization');
                $db->addColumn('password');
                $db->addWhere('username', $_SESSION['User']->username);
                $password = $db->select('one');
                if (empty($password)) {
                    return false;
                } elseif (PHPWS_Error::isError($password)) {
                    PHPWS_Error::log($password);
                    return false;
                }

                $remember['username'] = $_SESSION['User']->username;
                $remember['password'] = $password;
                $time_to_live = time() + (86400 * REMEMBER_ME_LIFE);
                 \phpws\PHPWS_Cookie::write('remember_me', serialize($remember),
                        $time_to_live);
            } else {
                 \phpws\PHPWS_Cookie::delete('remember_me');
            }
        }
    }

}
