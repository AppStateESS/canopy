<?php

/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */
\phpws\PHPWS_Core::initModClass('boost', 'Boost.php');

class Boost_Action {

    public static function checkupdate($mod_title)
    {
        \phpws\PHPWS_Core::initCoreClass('Module.php');
        $module = new PHPWS_Module($mod_title);

        $file = $module->getVersionHttp();
        if (empty($file)) {
            return 'Update check file not found.';
        }

        $full_xml_array = PHPWS_Text::xml2php($file, 2);

        if (empty($full_xml_array)) {
            return 'Update check file not found.';
        }
        $version_info = PHPWS_Text::tagXML($full_xml_array);

        $template['LOCAL_VERSION_LABEL'] = 'Local version';
        $template['LOCAL_VERSION'] = $module->getVersion();
        $template['STABLE_VERSION_LABEL'] = 'Current stable version';
        if (!isset($version_info['VERSION'])) {
            $template['STABLE_VERSION'] = 'Source XML error';
            $version_info['VERSION'] = $module->getVersion();
        } else {
            $_SESSION['Boost_Needs_Update'][$mod_title] = $version_info['VERSION'];
            $template['STABLE_VERSION'] = $version_info['VERSION'];
        }

        if (version_compare($version_info['VERSION'], $module->getVersion(), '>')) {
            $template['CHANGES_LABEL'] = 'Changes';
            $template['CHANGES'] = '<pre>' . htmlspecialchars($version_info['CHANGES']) . '</pre>';
            $template['UPDATE_AVAILABLE'] = 'An update is available!' . '<br />';
            $template['UPDATE_PATH_LABEL'] = 'Download here';
            $template['UPDATE_PATH'] = '<a href="' . $version_info['DOWNLOAD'] . '">' . $version_info['DOWNLOAD'] . '</a>';
            $template['MD5_LABEL'] = 'MD5 Sum';
            $template['MD5'] = $version_info['MD5SUM'];

            if (isset($version_info['DEPENDENCY'][0]['MODULE'])) {
                $template['DEPENDENCY_LABEL'] = 'Dependencies';
                $template['DEP_TITLE_LABEL'] = 'Module title';
                $template['DEP_VERSION_LABEL'] = 'Version required';
                $template['DEP_STATUS_LABEL'] = 'Status';

                foreach ($version_info['DEPENDENCY'][0]['MODULE'] as $dep_mod) {
                    $check_mod = new PHPWS_Module($dep_mod['TITLE'], false);

                    if ($check_mod->_error) {
                        $status = 'Not installed';
                        $row['DEP_STATUS_CLASS'] = 'red';
                    } elseif (version_compare($check_mod->version,
                                    $dep_mod['VERSION'], '<')) {
                        $status = 'Needs upgrading';
                        $row['DEP_STATUS_CLASS'] = 'red';
                    } else {
                        $status = 'Passed!';
                        $row['DEP_STATUS_CLASS'] = 'green';
                    }
                    $row['DEP_TITLE'] = $dep_mod['PROPERNAME'];
                    $row['DEP_VERSION'] = $dep_mod['VERSION'];
                    $row['DEP_ADDRESS'] = sprintf('<a href="%s">%s</a>',
                            $dep_mod['URL'], 'Download');
                    $row['DEP_STATUS'] = $status;
                    $template['dependent-mods'][] = $row;
                }
            }
        } else {
            $template['NO_UPDATE'] = 'No update required.';
        }

        $template['TITLE'] = 'Module' . ': ' . $module->getProperName(TRUE);
        return PHPWS_Template::process($template, 'boost', 'check_update.tpl');
    }

    public static function installModule($module_title)
    {
        \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');

        $boost = new PHPWS_Boost;
        $boost->loadModules(array($module_title));
        return $boost->install();
    }

    public static function uninstallModule($module_title)
    {
        \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');

        $boost = new PHPWS_Boost;
        $boost->loadModules(array($module_title));

        $content = $boost->uninstall();

        return $content;
    }

    public function updateCore()
    {
        \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');
        $content[] = 'Updating core';

        require_once PHPWS_SOURCE_DIR . 'core/boost/update.php';

        $ver_info = \phpws\PHPWS_Core::getVersionInfo(false);

        $content[] = 'Processing update file.';
        $result = core_update($content, $ver_info['version']);

        if ($result === true) {
            $db = new PHPWS_DB('core_version');
            $file_ver = \phpws\PHPWS_Core::getVersionInfo();
            $db->addValue('version', $file_ver['version']);
            $result = $db->update();
            if (PHPWS_Error::isError($result)) {
                PHPWS_Error::log($result);
                $content[] = 'An error occurred updating the core.';
            } else {
                $content[] = 'Core successfully updated.';
            }
        } elseif (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'An error occurred updating the core.';
        } else {
            $content[] = 'An error occurred updating the core.';
        }

        return implode('<br />', $content);
    }

    public static function updateModule($module_title)
    {
        \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');
        $boost = new PHPWS_Boost;
        $boost->loadModules(array($module_title), FALSE);

        $content = array();

        $boost->updateBranches($content);
        $boost->update($content);
        return implode('<br />', $content);
    }

    public static function showDependedUpon($base_mod)
    {
        \phpws\PHPWS_Core::initCoreClass('Module.php');
        $module = new PHPWS_Module($base_mod);
        $dependents = $module->isDependedUpon();
        if (empty($dependents)) {
            return 'This module does not have dependents.';
        }

        $template['TITLE'] = sprintf('%s Dependencies',
                $module->getProperName());
        $content[] = PHPWS_Text::backLink() . '<br />';
        $content[] = 'The following modules depend on this module to function:';
        foreach ($dependents as $mod) {
            $dep_module = new PHPWS_Module($mod);
            $content[] = $dep_module->getProperName();
        }

        $content[] = PHPWS_Boost::uninstallLink($base_mod);
        $template['CONTENT'] = implode('<br />', $content);

        return PHPWS_Template::process($template, 'boost', 'main.tpl');
    }

    public static function showDependency($base_module_title)
    {
        \phpws\PHPWS_Core::initCoreClass('Module.php');
        $module = new PHPWS_Module($base_module_title);
        $depend = $module->getDependencies();
        $template['TITLE'] = sprintf('%s Module Dependencies',
                $module->getProperName());

        $template['MODULE_NAME_LABEL'] = 'Module Needed';
        $template['VERSION_NEEDED_LABEL'] = 'Version required';
        $template['CURRENT_VERSION_LABEL'] = 'Current Version';
        $template['URL_LABEL'] = 'Module Web Site';
        $template['STATUS_LABEL'] = 'Status';

        foreach ($depend['MODULE'] as $module) {
            $pass = TRUE;
            $tpl = array();

            $mod_obj = new PHPWS_Module($module['TITLE'], FALSE);

            $tpl['MODULE_NAME'] = $module['PROPERNAME'];
            $tpl['VERSION_NEEDED'] = $module['VERSION'];

            if ($mod_obj->isInstalled()) {
                $tpl['CURRENT_VERSION'] = $mod_obj->getVersion();
            } else {
                $pass = FALSE;
                $tpl['CURRENT_VERSION'] = 'Not installed';
            }

            if ($pass && version_compare($module['VERSION'],
                            $mod_obj->getVersion(), '>')) {
                $pass = FALSE;
            }

            $tpl['URL'] = sprintf('<a href="%s" target="_blank">%s</a>',
                    $module['URL'], 'More info');

            if ($pass) {
                $tpl['STATUS_GOOD'] = 'Passed!';
            } else {
                $tpl['STATUS_BAD'] = 'Failed';
            }
            $template['module-row'][] = $tpl;
        }

        return PHPWS_Template::process($template, 'boost', 'dependency.tpl');
    }

    /**
     * Checks all modules for update status
     */
    public static function checkAll()
    {
        \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');
        $all_mods = PHPWS_Boost::getAllMods();
        if (empty($all_mods)) {
            return;
        }

        \phpws\PHPWS_Core::initCoreClass('Module.php');

        $all_mods[] = 'core';

        if (!ini_get('allow_url_fopen')) {
            return false;
        }

        foreach ($all_mods as $mod_title) {
            $module = new PHPWS_Module($mod_title);
            $file = $module->getVersionHttp();
            if (empty($file)) {
                continue;
            }

            $full_xml_array = PHPWS_Text::xml2php($file, 2);

            if (empty($full_xml_array)) {
                continue;
            }

            $version_info = PHPWS_Text::tagXML($full_xml_array);
            if (empty($version_info) || empty($version_info['VERSION'])) {
                continue;
            }

            $_SESSION['Boost_Needs_Update'][$mod_title] = $version_info['VERSION'];
        }
    }

    /**
     * deprecated
     */
    public function copyLocal($module_title)
    {
        return true;
    }

    // deprecated
    public function copyCore()
    {
        return true;
    }

}

