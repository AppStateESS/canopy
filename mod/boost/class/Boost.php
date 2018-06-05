<?php

/**
 * Controls the installation, update, and uninstallation
 * of modules in Canopy
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @version $Id$
 */
\phpws\PHPWS_Core::initCoreClass('Module.php');
\phpws\PHPWS_Core::configRequireOnce('boost', 'config.php');

define('BOOST_NEW', 0);
define('BOOST_START', 1);
define('BOOST_PENDING', 2);
define('BOOST_DONE', 3);

if (!defined('BOOST_BACKUP_DIRECTORIES')) {
    define('BOOST_BACKUP_DIRECTORIES', true);
}

if (!defined('BOOST_BACKUP_FILES')) {
    define('BOOST_BACKUP_FILES', true);
}

class PHPWS_Boost
{
    public $modules = NULL;
    public $status = NULL;
    public $current = NULL;
    public $installedMods = NULL;

    public function addModule($module)
    {
        if (!is_object($module) || strtolower(get_class($module)) != 'phpws_module') {
            return PHPWS_Error::get(BOOST_ERR_NOT_MODULE, 'boost', 'setModule');
        }

        $this->modules[$module->title] = $module;
    }

    public function loadModules($modules, $file = true)
    {
        foreach ($modules as $title) {
            $mod = new PHPWS_Module(trim($title), $file);
            $this->addModule($mod);
            $this->setStatus($title, BOOST_NEW);
        }
    }

    public function isFinished()
    {
        if (in_array(BOOST_NEW, $this->status) || in_array(BOOST_START, $this->status) || in_array(BOOST_PENDING, $this->status)) {
            return false;
        }

        return true;
    }

    public function currentDone()
    {
        return ($this->status[$this->current] == BOOST_DONE) ? true : false;
    }

    public function getRegisteredModules($module)
    {
        $db = new PHPWS_DB('modules');
        $db->addWhere('registered.module', $module->title);
        $db->addWhere('title', 'registered.registered');
        return $db->getObjects('PHPWS_Module');
    }

    public function getInstalledModules()
    {
        $db = new PHPWS_DB('modules');
        $db->addColumn('title');
        $modules = $db->getObjects('PHPWS_Module');
        return $modules;
    }

    public function setStatus($title, $status)
    {
        $this->status[trim($title)] = $status;
    }

    public function getStatus($title)
    {
        if (!isset($this->status[$title])) {
            return NULL;
        }

        return $this->status[$title];
    }

    public function setCurrent($title)
    {
        $this->current = $title;
    }

    public function getCurrent()
    {
        return $this->current;
    }

    public function isModules()
    {
        return isset($this->modules);
    }

    /**
     * Updated: 19 Feb 2010
     * Boost used to allow pausing for entering information. This was removed.
     * @param $inBoost
     * @param $inBranch
     * @param $home_dir
     * @return unknown_type
     */
    public function install($inBoost = true, $inBranch = false, $home_dir = NULL)
    {
        $content = array();
        $dir_content = array();

        if ($inBranch && !empty($home_dir)) {
            $GLOBALS['boost_branch_dir'] = $home_dir;
        }

        if (!$this->checkDirectories($dir_content, null, false)) {
            return implode('<br />', $dir_content);
        }

        if (!$this->isModules()) {
            return PHPWS_Error::get(BOOST_NO_MODULES_SET, 'boost', 'install');
        }

        $last_mod = end($this->modules);
        foreach ($this->modules as $title => $mod) {
            $title = trim($title);
            if ($this->getStatus($title) == BOOST_DONE) {
                continue;
            }

            if ($this->getCurrent() != $title && $this->getStatus($title) == BOOST_NEW) {
                $this->setCurrent($title);
                $this->setStatus($title, BOOST_START);
            }

            $content[] = 'Installing' . ' - ' . $mod->getProperName();

            if ($this->getStatus($title) == BOOST_START && $mod->isImportSQL()) {
                $content[] = 'Importing SQL install file.';
                $db = new PHPWS_DB;
                $result = $db->importFile($mod->getDirectory() . 'boost/install.sql');

                if (PHPWS_Error::isError($result)) {
                    PHPWS_Error::log($result);
                    $this->addLog($title, 'Database import failed.');
                    $content[] = 'An import error occurred.';
                    $content[] = 'Check your logs for more information.';
                    return implode('<br />', $content) . '<br />' . implode('<br />', $content);
                } else {
                    $content[] = 'Import successful.';
                }
            }

            try {
                $result = $this->onInstall($mod, $content);
                if ($result === true) {
                    $this->setStatus($title, BOOST_DONE);
                    $this->createDirectories($mod, $content, $home_dir);
                    $this->registerModule($mod, $content);
                    $continue = true;
                } elseif ($result === -1) {
                    // No installation file (install.php) was found.
                    $this->setStatus($title, BOOST_DONE);
                    $this->createDirectories($mod, $content, $home_dir);
                    $this->registerModule($mod, $content);
                    $continue = true;
                } elseif (PHPWS_Error::isError($result)) {
                    $content[] = 'There was a problem in the installation file:';
                    $content[] = '<b>' . $result->getMessage() . '</b>';
                    $content[] = '<br />';
                    PHPWS_Error::log($result);
                    $continue = false;
                }
            } catch (\Exception $e) {
                $content[] = implode('<br />', $content);
                $content[] = 'There was a problem in the installation file:';
                $content[] = '<b>' . $e->getMessage() . '</b>';
                $content[] = '<br />';
                \phpws2\Error::log($e);
                $continue = false;
            }
            // in case install changes translate directory
        }

        if ($last_mod->title == $title) {
            // H0120
            $content[] = 'Installation complete!';
            $this->addLog($title, str_replace("\n\n\n", "\n", implode("\n", str_replace('<br />', "\n", $content))));
        }
        return implode('<br />', $content);
    }

    public function onInstall($mod, &$installCnt)
    {
        $onInstallFile = $mod->getDirectory() . 'boost/install.php';
        $installFunction = $mod->title . '_install';
        if (!is_file($onInstallFile)) {
            $this->addLog($mod->title, 'Installation file not implemented.');
            return -1;
        }

        if ($this->getStatus($mod->title) == BOOST_START) {
            $this->setStatus($mod->title, BOOST_PENDING);
        }

        include_once($onInstallFile);

        if (function_exists($installFunction)) {
            $installCnt[] = 'Processing installation file.';
            return $installFunction($installCnt);
        } else {
            return true;
        }
    }

    public function onUpdate($mod, &$content)
    {
        $onUpdateFile = $mod->getDirectory() . 'boost/update.php';
        $updateFunction = $mod->title . '_update';
        $currentVersion = $mod->getVersion();
        if (!is_file($onUpdateFile)) {
            $this->addLog($mod->title, 'No update file found.');
            return -1;
        }

        if ($this->getStatus($mod->title) == BOOST_START) {
            $this->setStatus($mod->title, BOOST_PENDING);
        }

        include_once($onUpdateFile);

        if (function_exists($updateFunction)) {
            $content[] = 'Processing update file.';
            return $updateFunction($content, $currentVersion);
        } else {
            return true;
        }
    }

    public function uninstall()
    {
        PHPWS_Cache::clearCache();
        $content = array();
        if (!$this->isModules()) {
            return PHPWS_Error::get(BOOST_NO_MODULES_SET, 'boost', 'install');
        }

        foreach ($this->modules as $title => $mod) {
            unset($GLOBALS['Modules'][$title]);
            $title = trim($title);
            if ($this->getStatus($title) == BOOST_DONE) {
                continue;
            }

            if ($this->getCurrent() != $title && $this->getStatus($title) == BOOST_NEW) {
                $this->setCurrent($title);
                $this->setStatus($title, BOOST_START);
            }

            // H 0120
            $content[] = 'Uninstalling' . ' - ' . $mod->getProperName();
            // $content[] = '<b>' . 'Uninstalling' . ' - ' . $mod->getProperName() .'</b>';

            if ($this->getStatus($title) == BOOST_START && $mod->isImportSQL()) {
                $uninstall_file = $mod->getDirectory() . 'boost/uninstall.sql';
                if (!is_file($uninstall_file)) {
                    $content[] = 'Uninstall SQL not found.';
                } else {
                    $content[] = 'Importing SQL uninstall file.';
                    $result = PHPWS_Boost::importSQL($uninstall_file);

                    if (PHPWS_Error::isError($result)) {
                        PHPWS_Error::log($result);

                        $content[] = 'An import error occurred.';
                        $content[] = 'Check your logs for more information.';
                        return implode('<br />', $content);
                    } else {
                        $content[] = 'Import successful.';
                    }
                }
            }

            $result = (bool) $this->onUninstall($mod, $content);

            // ensure translate path

            if ($result === true) {
                $this->setStatus($title, BOOST_DONE);
                $this->removeDirectories($mod, $content);
                $this->unregisterModule($mod, $content);
                $this->removeDependencies($mod);
                $this->removeKeys($mod);
                // H 0120
                // $content[] = '<hr />';
                $content[] = 'Finished uninstalling module!';
                break;
            } elseif ($result == -1) {
                $this->setStatus($title, BOOST_DONE);
                $this->removeDirectories($mod, $content);
                $this->unregisterModule($mod, $content);
                $this->removeDependencies($mod);
                $this->removeKeys($mod);
            } elseif ($result === false) {
                $this->setStatus($title, BOOST_PENDING);
                break;
            } elseif (PHPWS_Error::isError($result)) {
                $content[] = 'There was a problem in the installation file:';
                $content[] = '<b>' . $result->getMessage() . '</b>';
                $content[] = '<br />';
                PHPWS_Error::log($result);
            }
        }
        // H 0120 + place into boost log also
        $this->addLog($title, implode("\n", str_replace('<br />', "\n", $content)));
        return implode('<br />', $content);
    }

    public function removeDependencies($mod)
    {
        $db = new PHPWS_DB('dependencies');
        $db->addWhere('source_mod', $mod->title);
        $db->delete();
    }

    public function removeKeys($mod)
    {
        $db = new PHPWS_DB('phpws_key_edit');
        $db->addWhere('key_id', 'phpws_key.id');
        $db->addWhere('phpws_key.module', $mod->title);
        $db->delete();

        $db = new PHPWS_DB('phpws_key_view');
        $db->addWhere('key_id', 'phpws_key.id');
        $db->addWhere('phpws_key.module', $mod->title);
        $db->delete();

        $db->reset();
        $db->setTable('phpws_key');
        $db->addWhere('module', $mod->title);
        return $db->delete();
    }

    public function onUninstall($mod, &$uninstallCnt)
    {
        $onUninstallFile = $mod->getDirectory() . 'boost/uninstall.php';
        $uninstallFunction = $mod->title . '_uninstall';
        if (!is_file($onUninstallFile)) {
            $uninstallCnt[] = 'Uninstall file not found.';
            $this->addLog($mod->title, 'No uninstall file found.');
            return -1;
        }

        if ($this->getStatus($mod->title) == BOOST_START) {
            $this->setStatus($mod->title, BOOST_PENDING);
        }

        include_once($onUninstallFile);

        if (function_exists($uninstallFunction)) {
            $uninstallCnt[] = 'Processing uninstall file.';
            return $uninstallFunction($uninstallCnt);
        } else {
            $this->addLog($mod->title, sprintf('Uninstall function "%s" was not found.', $uninstallFunction));
            return true;
        }
    }

    public function update(&$content)
    {
        if (!$this->isModules()) {
            return PHPWS_Error::get(BOOST_NO_MODULES_SET, 'boost', 'update');
        }

        if (!$this->checkDirectories($content, null, false)) {
            return false;
        }

        foreach ($this->modules as $title => $mod) {
            if (isset($mod->_error)) {
                if ($mod->_error->code == PHPWS_NO_MOD_FOUND) {
                    $content[] = 'Module is not installed.';
                    $result = true;
                    continue;
                }
            }
            $updateMod = new PHPWS_Module($mod->title);
            if (version_compare($updateMod->getVersion(), $mod->getVersion(), '=')) {
                $content[] = 'Module does not require updating.';
                $result = false;
                continue;
            }

            $title = trim($title);

            if ($this->getStatus($title) == BOOST_DONE) {
                continue;
            }

            if ($this->getCurrent() != $title && $this->getStatus($title) == BOOST_NEW) {
                $this->setCurrent($title);
                $this->setStatus($title, BOOST_START);
            }

            $content[] = 'Updating' . ' - ' . $mod->getProperName();
            $result = $this->onUpdate($mod, $content);

            if ($result === true) {
                $this->setStatus($title, BOOST_DONE);
                $newMod = new PHPWS_Module($mod->title);
                $newMod->save();
                break;
            } elseif ($result === -1) {
                $newMod = new PHPWS_Module($mod->title);
                $newMod->save();
                $this->setStatus($title, BOOST_DONE);
            } elseif ($result === false) {
                $this->setStatus($title, BOOST_PENDING);
                break;
            } elseif (PHPWS_Error::isError($result)) {
                $content[] = 'There was a problem in the update file:';
                $content[] = $result->getMessage();
                $content[] = '<br />';
                PHPWS_Error::log($result);
            }
        }

        if (isset($result) && ($result === true || $result == -1)) {
            $content[] = 'Update complete!';
            return true;
        } else {
            $content[] = 'Update not completed.';
            return false;
        }
    }

    public function createDirectories($mod, &$content, $homeDir = NULL, $overwrite = false)
    {
        \phpws\PHPWS_Core::initCoreClass('File.php');
        if (!isset($homeDir)) {
            $homeDir = $this->getHomeDir();
        }

        if ($mod->isFileDir()) {
            $filesDir = $homeDir . 'files/' . $mod->title;
            if (!is_dir($filesDir)) {
                $content[] = 'Creating files directory for module.';
                $this->addLog($mod->title, 'Created directory' . ' ' . $filesDir);
                mkdir($filesDir);
            }
        }

        if ($mod->isImageDir()) {
            $imageDir = $homeDir . 'images/' . $mod->title;
            if (!is_dir($imageDir)) {
                $this->addLog($mod->title, 'Created directory' . ' ' . $imageDir);
                $content[] = 'Creating image directory for module.';
                mkdir($imageDir);
            }
        }
    }

    public function removeDirectories($mod, &$content, $homeDir = NULL)
    {
        \phpws\PHPWS_Core::initCoreClass('File.php');
        if (!isset($homeDir)) {
            $this->getHomeDir();
        }

        $imageDir = $homeDir . 'images/' . $mod->title . '/';
        if ($mod->isImageDir() && is_dir($imageDir)) {
            $content[] = sprintf('Removing directory %s', $imageDir);
            $this->addLog($mod->title, sprintf('Removing directory %s', $imageDir));
            if (!PHPWS_File::rmdir($imageDir)) {
                $content[] = 'Failure to remove directory.';
                $this->addLog($mod->title, sprintf('Unable to remove directory %s', $imageDir));
            }
        }

        $fileDir = $homeDir . 'files/' . $mod->title . '/';
        if ($mod->isFileDir() && is_dir($fileDir)) {
            $content[] = sprintf('Removing directory %s', $fileDir);
            $this->addLog($mod->title, sprintf('Removing directory %s', $fileDir));
            if (!PHPWS_File::rmdir($fileDir)) {
                $content[] = 'Failure to remove directory.';
                $this->addLog($mod->title, sprintf('Unable to remove directory %s', $fileDir));
            }
        }
    }

    public function registerMyModule($mod_to_register, $mod_to_register_to, &$content, $unregister_first = true)
    {
        $register_mod = new PHPWS_Module($mod_to_register);
        $register_to_mod = new PHPWS_Module($mod_to_register_to);
        if ($unregister_first) {
            $result = PHPWS_Boost::unregisterModToMod($register_to_mod, $register_mod, $content);
        }
        $result = PHPWS_Boost::registerModToMod($register_to_mod, $register_mod, $content);
        return $result;
    }

    public function registerModule($module, &$content)
    {
        $content[] = 'Registering module to core.';

        $db = new PHPWS_DB('modules');
        $db->addWhere('title', $module->title);
        $db->delete();
        $db->resetWhere();
        if (!$module->getProperName()) {
            $module->setProperName($module->getProperName(true));
        }

        $result = $module->save();

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'An error occurred during registration.';
            $content[] = 'Check your logs for more information.';
        } else {
            $content[] = 'Registration successful.';

            if ($module->isRegister()) {
                $selfselfResult = $this->registerModToMod($module, $module, $content);
                $otherResult = $this->registerOthersToSelf($module, $content);
            }

            $selfResult = $this->registerSelfToOthers($module, $content);
        }
        $filename = sprintf('%smod/%s/inc/key.php', PHPWS_SOURCE_DIR, $module->title);
        if (is_file($filename)) {
            $content[] = 'Registered to Key.';
            \Canopy\Key::registerModule($module->title);
        }

        $content[] = '<br />';
        return $result;
    }

    public function unregisterModule($module, &$content)
    {
        $content[] = 'Unregistering module from core.';

        $db = new PHPWS_DB('modules');
        $db->addWhere('title', $module->title);
        $result = $db->delete();

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'An error occurred while unregistering.';
            $content[] = 'Check your logs for more information.';
        } else {
            $content[] = 'Unregistering module from Boost was successful.';

            $result = PHPWS_Settings::unregister($module->title);
            if (PHPWS_Error::isError($result)) {
                PHPWS_Error::log($result);
                $content[] = dgettext('boost', 'Module\'s settings could not be removed. See your error log.');
            } else {
                $content[] = dgettext('boost', 'Module\'s settings removed successfully.');
            }

            if (\Canopy\Key::unregisterModule($module->title)) {
                $content[] = 'Key unregistration successful.';
            } else {
                $content[] = 'Some key unregistrations were unsuccessful. Check your logs.';
            }

            if ($module->isUnregister()) {
                $selfselfResult = $this->unregisterModToMod($module, $module, $content);
                $otherResult = $this->unregisterOthersToSelf($module, $content);
            }

            $selfResult = $this->unregisterSelfToOthers($module, $content);
            $result = $this->unregisterAll($module);
        }

        return $result;
    }

    public function getRegMods()
    {
        $db = new PHPWS_DB('modules');
        $db->addWhere('register', 1);
        return $db->getObjects('PHPWS_Module');
    }

    public function getUnregMods()
    {
        $db = new PHPWS_DB('modules');
        $db->addWhere('unregister', 1);
        return $db->getObjects('PHPWS_Module');
    }

    public function setRegistered($module, $registered)
    {
        $db = new PHPWS_DB('registered');
        $db->addValue('registered_to', $registered);
        $db->addValue('module', $module);
        $result = $db->insert();
        if (PHPWS_Error::logIfError($result)) {
            return $result;
        } else {
            return (bool) $result;
        }
    }

    public function unsetRegistered($module, $registered)
    {
        $db = new PHPWS_DB('registered');
        $db->addWhere('registered_to', $registered);
        $db->addWhere('module', $module);
        $result = $db->delete();

        if (PHPWS_Error::logIfError($result)) {
            return $result;
        } else {
            return (bool) $result;
        }
    }

    public function isRegistered($module, $registered)
    {
        $db = new PHPWS_DB('registered');
        $db->addWhere('registered_to', $registered);
        $db->addWhere('module', $module);
        $result = $db->select('one');
        if (PHPWS_Error::isError($result)) {
            return $result;
        } else {
            return (bool) $result;
        }
    }

    /**
     * Registers a module ($register_mod) TO another module ($register_to_mod)
     * In other words, the first parameter is going to perform
     * an action on the second parameter
     */
    public function registerModToMod($register_to_mod, $register_mod, &$content)
    {
        $registerFile = $register_to_mod->getDirectory() . 'boost/register.php';
        if (!is_file($registerFile)) {
            return PHPWS_Error::get(BOOST_NO_REGISTER_FILE, 'boost', 'registerModToMod', $registerFile);
        }

        if (PHPWS_Boost::isRegistered($register_to_mod->title, $register_mod->title)) {
            return NULL;
        }

        include_once $registerFile;

        $registerFunc = $register_to_mod->title . '_register';

        if (!function_exists($registerFunc)) {
            return PHPWS_Error::get(BOOST_NO_REGISTER_FUNCTION, 'boost', 'registerModToMod', $registerFile);
        }

        $result = $registerFunc($register_mod->title, $content);

        if (PHPWS_Error::isError($result)) {
            $content[] = sprintf('An error occurred while registering the %s module.', $register_mod->getProperName());
            $content[] = PHPWS_Boost::addLog($register_mod->title, $result->getMessage());
            $content[] = PHPWS_Error::log($result);
        } elseif ($result == true) {
            PHPWS_Boost::setRegistered($register_to_mod->title, $register_mod->title);
            $content[] = sprintf(dgettext('boost', "%1\$s successfully registered to %2\$s."), $register_mod->getProperName(true), $register_to_mod->getProperName(true));
        }
        return true;
    }

    public function unregisterModToMod($unregister_from_mod, $register_mod, &$content)
    {
        $unregisterFile = $unregister_from_mod->getDirectory() . 'boost/unregister.php';

        if (!is_file($unregisterFile)) {
            return NULL;
        }

        include_once $unregisterFile;

        $unregisterFunc = $unregister_from_mod->title . '_unregister';

        if (!function_exists($unregisterFunc)) {
            return NULL;
        }

        $result = $unregisterFunc($register_mod->title, $content);

        if (PHPWS_Error::isError($result)) {
            $content[] = sprintf('An error occurred while unregistering the %s module.', $register_mod->getProperName());
            PHPWS_Error::log($result);
            PHPWS_Boost::addLog($register_mod->title, $result->getMessage());
        } elseif ($result == true) {
            PHPWS_Boost::unsetRegistered($unregister_from_mod->title, $register_mod->title);
            $content[] = sprintf(dgettext('boost', "%1\$s successfully unregistered from %2\$s."), $register_mod->getProperName(true), $unregister_from_mod->getProperName(true));
        }
    }

    /**
     * Registered the installed module to other modules already present
     *
     */
    public function registerSelfToOthers($module, &$content)
    {
        $content[] = 'Registering this module to other modules.';

        $modules = PHPWS_Boost::getRegMods();

        if (!is_array($modules)) {
            return;
        }

        foreach ($modules as $register_mod) {
            $register_mod->init();
            if ($register_mod->isRegister()) {
                PHPWS_Error::logIfError($this->registerModToMod($register_mod, $module, $content));
            }
        }
    }

    public function unregisterSelfToOthers($module, &$content)
    {
        $content[] = 'Unregistering this module from other modules.';

        $modules = PHPWS_Boost::getUnregMods();

        if (!is_array($modules)) {
            return;
        }

        foreach ($modules as $register_mod) {
            $register_mod->init();

            if ($register_mod->isUnregister()) {
                PHPWS_Error::logIfError($this->unregisterModToMod($register_mod, $module, $content));
            }
        }
    }

    /**
     * Registers other modules to the module currently getting installed.
     */
    public function registerOthersToSelf($module, &$content)
    {
        $content[] = 'Registering other modules to this module.';

        $modules = PHPWS_Boost::getInstalledModules();
        if (!is_array($modules)) {
            return;
        }

        foreach ($modules as $register_mod) {
            $register_mod->init();
            PHPWS_Error::logIfError($this->registerModToMod($module, $register_mod, $content));
        }
    }

    public function unregisterOthersToSelf($module, &$content)
    {
        $content[] = 'Unregistering other modules from this module.';

        $modules = PHPWS_Boost::getRegisteredModules($module);

        if (PHPWS_Error::isError($modules)) {
            return $modules;
        } elseif (empty($modules) || !is_array($modules)) {
            return true;
        }

        foreach ($modules as $register_mod) {
            $register_mod->init();
            PHPWS_Error::logIfError($this->unregisterModToMod($module, $register_mod, $content));
        }
    }

    public function unregisterAll($module)
    {
        $db = new PHPWS_DB('registered');
        $db->addWhere('registered_to', $module->title);
        $db->addWhere('module', $module->title, '=', 'or');
        return $db->delete();
    }

    public function importSQL($file)
    {
        require_once 'File.php';

        if (!is_file($file)) {
            return PHPWS_Error::get(BOOST_ERR_NO_INSTALLSQL, 'boost', 'importSQL', 'File: ' . $file);
        }

        $sql = File::readAll($file);
        $db = new PHPWS_DB;
        $result = $db->import($sql);
        return $result;
    }

    public static function addLog($module, $message)
    {
        $message = 'Module' . ' - ' . $module . ' : ' . $message;
        \phpws\PHPWS_Core::log($message, 'boost.log');
    }

    public static function aboutView($module)
    {
        \phpws\PHPWS_Core::initCoreClass('Module.php');
        $mod = new PHPWS_Module($module);
        $file = $mod->getDirectory() . 'boost/about.html';

        if (is_file($file)) {
            include $file;
        } else {
            echo 'The About file is missing for this module.';
        }
        exit();
    }

    /**
     * Copy of the setup function of the same name
     * This one also checks the write and read capabilities of
     * the log files.
     */
    public static function checkDirectories(&$content, $home_dir = null, $check_branch = true)
    {
        $errorDir = true;
        if (empty($home_dir)) {
            $home_dir = PHPWS_Boost::getHomeDir();
        }

        $directory[] = $home_dir . 'images/';
        $directory[] = $home_dir . 'files/';
        $directory[] = LOG_DIRECTORY;

        foreach ($directory as $id => $check) {
            if (!is_dir($check)) {
                $dirExist[] = $check;
            } elseif (!is_writable($check)) {
                $writableDir[] = $check;
            }
        }

        if (isset($dirExist)) {
            $content[] = 'The following directories need to be created:';
            $content[] = implode("\n", $dirExist);
            $errorDir = false;
        }

        if (isset($writableDir)) {
            $content[] = 'The following directories are not writable:';
            $content[] = implode(chr(10), $writableDir);
            $errorDir = false;
        }

        $files = array('boost.log', 'error.log');
        foreach ($files as $log_name) {
            if (is_file('logs/' . $log_name) && (!is_readable('logs/' . $log_name) || !is_writable('logs/' . $log_name))) {
                $content[] = sprintf('Your logs/%s file must be readable and writable.', $log_name);
                $errorDir = false;
            }
        }

        if (!isset($GLOBALS['Boost_Ready'])) {
            $GLOBALS['Boost_Ready'] = $errorDir;
        }

        if (!$errorDir) {
            $GLOBALS['Boost_Current_Directory'] = false;
        }
        if ($check_branch && !\phpws\PHPWS_Core::isBranch() && \phpws\PHPWS_Core::moduleExists('branch')) {
            $db = new PHPWS_DB('branch_sites');
            $db->addColumn('branch_name');
            $db->addColumn('directory');
            $result = $db->select();
            if (!empty($result)) {
                if (PHPWS_Error::logIfError($result)) {
                    $content[] = 'An error occurred when tryingt to access your branch site listing.';
                    $content[] = 'Branches could not be checked.';
                    return $errorDir;
                }
                foreach ($result as $branch) {
                    $contentTmp = array();
                    if (!PHPWS_Boost::checkDirectories($contentTmp, $branch['directory'], false)) {
                        $content[] = sprintf('Checking branch "%s"', $branch['branch_name']);
                        foreach ($contentTmp as $tmp)
                            $content[] = $tmp;
                        $content[] = '';
                        $errorDir = false;
                    }
                }
            }
        }

        return $errorDir;
    }

    public static function getHomeDir()
    {
        if (isset($GLOBALS['boost_branch_dir'])) {
            return $GLOBALS['boost_branch_dir'];
        } else {
            return getcwd() . '/';
        }
    }

    public function checkLocalRoot($local_root)
    {
        if (is_dir($local_root)) {
            if (!is_writable($local_root)) {
                return false;
            } else {
                return true;
            }
        }

        if (mkdir($local_root)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Backs up a file by removing the extension, padding the word
     * 'backup' then putting the ext back.
     */
    public function backupFile($filename)
    {
        $aFile = explode('/', $filename);
        $file_alone = array_pop($aFile);

        $file_alone = time() . '_' . $file_alone;
        $new_filename = implode('/', $aFile) . '/' . $file_alone;
        return @copy($filename, $new_filename);
    }

    public function updateBranches(&$content)
    {
        if (!\phpws\PHPWS_Core::moduleExists('branch')) {
            return true;
        }

        \phpws\PHPWS_Core::initModClass('branch', 'Branch_Admin.php');
        $branches = Branch_Admin::getBranches(true);
        if (empty($branches)) {
            return true;
        }

        $keys = array_keys($this->status);
        foreach ($branches as $branch) {
            $GLOBALS['Boost_In_Branch'] = $branch;
            // used as the "local" directory in updateFiles
            $GLOBALS['boost_branch_dir'] = $branch->directory;

            if (PHPWS_Error::isError($branch->loadBranchDB())) {
                $content[] = 'Problem connecting to the branch. May be too many connections.';
                continue;
            }

            // create a new boost based on the branch database
            $branch_boost = new PHPWS_Boost;
            $branch_boost->loadModules($keys, false);

            $content[] = '<hr />';
            $content[] = sprintf('Updating branch %s', $branch->branch_name);

            $result = $branch_boost->update($content);
            if (PHPWS_Error::isError($result)) {
                PHPWS_Error::log($result);
                $content[] = 'Unable to update branch.';
            }
        }
        Branch::loadHubDB();
        $GLOBALS['Boost_In_Branch'] = false;
    }

    public static function getAllMods()
    {
        $all_mods = PHPWS_File::readDirectory(PHPWS_SOURCE_DIR . 'mod/', TRUE);
        foreach ($all_mods as $key => $module) {
            if (is_file(PHPWS_SOURCE_DIR . 'mod/' . $module . '/boost/boost.php')) {
                $dir_mods[] = $module;
            } elseif (is_file(PHPWS_SOURCE_DIR . 'mod/' . $module . '/conf/boost.php')) {
                $GLOBALS['Boost_Old_Mods'][] = $module;
            }
        }
        return $dir_mods;
    }

    /**
     * Returns the current branch object or true if Boost is
     * installing/updating/uninstalling a branch site from the hub.
     * If a module needs to check if it is running from a branch,
     * \phpws\PHPWS_Core::isBranch should be used.
     * @param boolean return_object : If true, return current branch object
     */
    public static function inBranch($return_object = false)
    {
        if (isset($GLOBALS['Boost_In_Branch'])) {
            if ($return_object) {
                return $GLOBALS['Boost_In_Branch'];
            } else {
                return true;
            }
        } else {
            return false;
        }
    }

    public static function uninstallLink($module)
    {
        $uninstallVars = array('opmod' => $module, 'action' => 'uninstall');
        $js['question'] = 'Are you sure you want to uninstall this module? All data will be deleted.';
        $js['question'] .= '\n' . sprintf('If sure, please type the name of the module below: %s', $module);
        $js['address'] = PHPWS_Text::linkAddress('boost', $uninstallVars, TRUE);
        $js['value_name'] = 'confirm';
        $js['link'] = 'Uninstall';
        return javascript('prompt', $js);
    }

    /**
     * Used to copy files down to local directories and branches. After
     * Canopy 1.7.0, no longer required. Kept here to prevent prior
     * updates from breaking.
     * @param mixed $dummy1
     * @param mixed $dummy2
     * @param mixed $dummy3
     */
    public static function updateFiles($dummy1 = null, $dummy2 = null, $dummy3 = null)
    {
        return true;
    }

}

