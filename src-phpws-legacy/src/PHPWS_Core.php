<?php

namespace phpws;

/**
 * Controls module manipulation
 *
 * Loads modules and their respective files.
 * @version $Id$
 * @author  Matt McNaney <mcnaney at gmail dot com>
 * @package Core
 */
if (!defined('FORCE_MOD_CONFIG')) {
    define('FORCE_MOD_CONFIG', true);
}

if (!defined('ALLOW_SCRIPT_TAGS')) {
    define('ALLOW_SCRIPT_TAGS', false);
}

if (!defined('LOG_DIRECTORY')) {
    define('LOG_DIRECTORY', PHPWS_SOURCE_DIR . 'logs/');
}

require_once PHPWS_SOURCE_DIR . 'core/inc/errorDefines.php';
\phpws\PHPWS_Core::initCoreClass('PHPWS_Error.php');

class PHPWS_Core
{

    /**
     * Gets all the modules from the module table
     */
    public static function getModules($active = true, $just_title = false)
    {
        if ($active) {
            $mods = \phpws2\ModuleRepository::getInstance()->getActiveModules();
        } else {
            $mods = \phpws2\ModuleRepository::getInstance()->getAllModules();
        }

        if ($just_title) {
            $titles = array();
            foreach ($mods as $mod) {
                $titles[] = $mod->getTitle();
            }
            return $titles;
        } else {
            $oldmods = array();
            foreach ($mods as $mod) {
                $oldmod = array();
                $oldmod['title'] = $mod->getTitle();
                $oldmod['proper_name'] = $mod->getProperName();
                $oldmod['priority'] = $mod->getPriority();
                $oldmod['active'] = $mod->isActive();
                $oldmod['version'] = $mod->getVersion();
                $oldmod['register'] = false;     // @deprecated please remove
                $oldmod['unregister'] = false;     // @deprecated please remove
                $oldmods[] = $oldmod;
            }
            return $oldmods;
        }
    }

    /**
     * Returns an associative array of all the modules in the
     * module table
     * Array is indexed with the module title. The value of each
     * row is the module's proper name
     */
    public static function getModuleNames()
    {
        $mods = \phpws2\ModuleRepository::getInstance()->getAllModules();

        foreach ($mods as $o) {
            $listing[$o->getTitle()] = $o->getProperName();
        }
        return $listing;
    }

    /**
     * Returns a module object based on core
     */
    public static function loadAsMod($use_file = true)
    {
        \phpws\PHPWS_Core::initCoreClass('Module.php');
        $core_mod = new \phpws\PHPWS_Module('core', $use_file);
        return $core_mod;
    }

    /**
     * Requires a module's class file once
     * Returns true is successful, false otherwise
     */
    public static function initModClass($module, $file)
    {
        $classFile = PHPWS_SOURCE_DIR . 'mod/' . $module . '/class/' . $file;

        // If the requested file doesn't exist, throw an exception
        if (!is_file($classFile)) {
            throw new \Exception(sprintf('Module class file not found: %s', $classFile));
        }

        require_once $classFile;
        return true;
    }

    /**
     * Requires a core class file once
     * Returns true is successful, false otherwise
     */
    public static function initCoreClass($file)
    {
        $classFile = PHPWS_SOURCE_DIR . 'src-phpws-legacy/src/' . $file;

        // If the requested file doesn't exist, throw an exception
        if (!is_file($classFile)) {
            throw new \Exception('Core class file not found: ' . $classFile);
        } else {
            require_once $classFile;
        }

        return true;
    }

    /**
     * Sets the last form post made to the website.
     * Works with isPosted
     * @deprecate
     */
    public static function setLastPost()
    {
        $key = \phpws\PHPWS_Core::_getPostKey();
        if (!\phpws\PHPWS_Core::isPosted()) {
            $_SESSION['PHPWS_LastPost'][] = $key;
            if (count($_SESSION['PHPWS_LastPost']) > MAX_POST_TRACK) {
                array_shift($_SESSION['PHPWS_LastPost']);
            }
        } elseif (isset($_SESSION['PHPWS_Post_Count'][$key])) {
            if (isset($_SESSION['PHPWS_Post_Count'][$key])) {
                $_SESSION['PHPWS_Post_Count'][$key] ++;
            } else {
                $_SESSION['PHPWS_Post_Count'][$key] = 1;
            }
        }
    }

    /**
     * Makes a post key to track past posts
     * Works with setLastPost and isPosted
     * @deprecate
     */
    public static function _getPostKey()
    {
        $key = serialize($_POST);
        if (isset($_FILES)) {
            foreach ($_FILES as $file) {
                extract($file);
                if (is_array($name)) {
                    $key .= current($name) . current($type) . current($size);
                } else {
                    $key .= $name . $type . $size;
                }
            }
        }

        return md5($key);
    }

    /**
     * Checks to see if the currently post is in the LastPost
     * session. If so, it returns true. Function can be used to
     * prevent double posts.
     * If return_count is true, it returns the number of attempts
     * made with the same post.
     * @deprecate
     */
    public static function isPosted($return_count = false)
    {
        if (!isset($_SESSION['PHPWS_LastPost']) || !isset($_POST)) {
            return false;
        }

        $key = \phpws\PHPWS_Core::_getPostKey();

        if (!isset($_SESSION['PHPWS_Post_Count'])) {
            $_SESSION['PHPWS_Post_Count'][$key] = 1;
        }

        $result = in_array($key, $_SESSION['PHPWS_LastPost']);

        if ($result && $return_count) {
            return $_SESSION['PHPWS_Post_Count'][$key];
        } else {
            return $result;
        }
    }

    public static function atHome()
    {
        return !isset($_REQUEST['module']);
    }

    public static function bookmark($allow_authkey = true)
    {
        $url = \phpws\PHPWS_Core::getCurrentUrl();

        if (!$allow_authkey && preg_match('/authkey=/', $url)) {
            $url = null;
        }

        $_SESSION['PHPWS_Bookmark'] = $url;
    }

    public static function returnToBookmark($clear_bm = true)
    {
        if (isset($_SESSION['PHPWS_Bookmark'])) {
            $bm = $_SESSION['PHPWS_Bookmark'];
            if ($clear_bm) {
                $_SESSION['PHPWS_Bookmark'] = null;
                unset($_SESSION['PHPWS_Bookmark']);
            }
            \phpws\PHPWS_Core::reroute($bm);
        } else {
            \phpws\PHPWS_Core::goBack();
        }
    }

    /**
     * Returns the user browser to the referer (last web page)
     */
    public static function goBack()
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            // prevent an endless loop
            $current_url = \phpws\PHPWS_Core::getCurrentUrl(false);
            if (strtolower($current_url) == strtolower($_SERVER['HTTP_REFERER'])) {
                \phpws\PHPWS_Core::home();
            } else {
                \phpws\PHPWS_Core::reroute($_SERVER['HTTP_REFERER']);
            }
        } else {
            \phpws\PHPWS_Core::home();
        }
    }

    /**
     * Sends the user to the home page (index.php)
     */
    public static function home()
    {
        \phpws\PHPWS_Core::reroute();
    }

    /**
     * Returns a url prefix dependent on the security
     */
    public static function getHttp()
    {
        return \Canopy\Server::getHttp();
    }

    /**
     * Sends a location header based on the relative link passed
     * to the function.
     */
    public static function reroute($address = NULL)
    {
        $current_url = \phpws\PHPWS_Core::getCurrentUrl();

        if ($current_url == $address) {
            return;
        }

        // Set last post since we will be skipping it
        //\phpws\PHPWS_Core::setLastPost();

        if (!preg_match('/^http/', $address)) {
            $address = preg_replace('@^/@', '', $address);
            $http = \phpws\PHPWS_Core::getHttp();

            $dirArray = explode('/', $_SERVER['PHP_SELF']);
            array_pop($dirArray);
            $dirArray[] = '';

            $directory = implode('/', $dirArray);
            $location = $http . $_SERVER['HTTP_HOST'] . $directory . $address;
        } else {
            $location = & $address;
        }

        $location = preg_replace('/&amp;/', '&', $location);
        header('Location: ' . $location);
        exit();
    }

    /**
     * Kills a current page session
     */
    public static function killSession($sess_name)
    {
        $_SESSION[$sess_name] = NULL;
        unset($_SESSION[$sess_name]);
    }

    /**
     * Kills all sessions currently loaded
     */
    public static function killAllSessions()
    {
        $_SESSION = array();
        unset($_SESSION);
        session_destroy();
    }

// END FUNC killAllSessions()

    /**
     * Returns true is a module is installed, false otherwise
     */
    public static function moduleExists($module_title)
    {
        return \phpws2\ModuleRepository::getInstance()->hasModule($module_title);
    }

    /**
     * Returns the currently active module
     */
    public static function getCurrentModule()
    {
        $active = \phpws2\ModuleRepository::getInstance()->getCurrentModule();
        if (is_null($active))
            return null;
        return $active->getTitle();
    }

    /**
     * Retrieves a module's config file path. If the file
     * does not exist, throws an exception otherwise.
     * Will look for a local copy of the file in the hub/branch config directory.
     */
    public static function getConfigFile($module, $file = NULL)
    {
        if (empty($file)) {
            $file = 'config.php';
        }

        $file = preg_replace('/[^\-\w\.\\\\\/]/', '', $file);
        $module = preg_replace('/[^\w\.]/', '', $module);

        if ($module == 'core') {
            $local_config = PHPWS_HOME_DIR . 'config/phpws/' . $file;
            $hub_config = PHPWS_SOURCE_DIR . 'config/phpws/' . $file;
            if (is_readable($local_config)) {
                return $local_config;
            } elseif (is_readable($hub_config)) {
                return $hub_config;
            }
            $file = PHPWS_SOURCE_DIR . 'src-phpws-legacy/config/' . $file;
        } else {
            $file = PHPWS_SOURCE_DIR . "mod/$module/conf/$file";
        }

        if (!is_file($file)) {
            throw new \Exception('getConfigFile could not find file: ' . $file);
        }

        return $file;
    }

    /**
     * Pseudoname of configRequireOnce
     */
    public static function requireConfig($module, $file = NULL, $exitOnError = true)
    {
        return \phpws\PHPWS_Core::configRequireOnce($module, $file, $exitOnError);
    }

    /**
     * Like requireConfig but for files in the inc directory
     */
    public static function requireInc($module, $file, $exitOnError = true)
    {
        if ($module == 'core') {
            $inc_file = PHPWS_SOURCE_DIR . 'core/inc/' . $file;
        } else {
            $inc_file = PHPWS_SOURCE_DIR . "mod/$module/inc/$file";
        }

        if (!is_file($inc_file)) {
            \phpws\PHPWS_Error::log(PHPWS_FILE_NOT_FOUND, 'core', 'requireInc', $inc_file);
            if ($exitOnError) {
                throw new \Exception(sprintf('Could not find inc file to require: %s', $inc_file));
            } else {
                return false;
            }
        } else {
            require_once $inc_file;
        }

        return true;
    }

    /**
     * Loads a config file via a require. If missing, shows error page.
     * If file is NULL, function assumes 'config.php'
     */
    public static function configRequireOnce($module, $file = NULL, $exitOnError = true)
    {
        if (empty($file)) {
            $file = 'config.php';
        }
        $config_file = \phpws\PHPWS_Core::getConfigFile($module, $file);

        if (empty($config_file) || !$config_file) {
            \phpws\PHPWS_Error::log(PHPWS_FILE_NOT_FOUND, 'core', 'configRequireOnce', $file);
            if ($exitOnError) {
                throw new \Exception(sprintf('Could not find config file to require: %s', $inc_file));
            } else {
                return $config_file;
            }
        } else {
            require_once $config_file;
        }

        return true;
    }

    /**
     * Uses the Pear log class to write a log file to the logs directory
     */
    public static function log($message, $filename, $type = NULL)
    {

        if (!is_writable(LOG_DIRECTORY)) {
            exit(_('Unable to write to log directory.'));
        }

        if (is_file(LOG_DIRECTORY . $filename) && !is_writable(LOG_DIRECTORY . $filename)) {
            exit(sprintf(_('Unable to write %s file.'), $filename));
        }

        $conf = array('mode' => LOG_PERMISSION, 'timeFormat' => LOG_TIME_FORMAT);

        if (\phpws\PHPWS_Core::isBranch()) {
            $branch_name = Branch::getCurrentBranchName();
            $message = '{' . $branch_name . '} ' . $message;
        } else {
            $message = '{HUB} ' . $message;
        }
        \Canopy\Log::logMessage($message, $filename);
    }

    /**
     * Routes the user to a HTML file. File depends on code passed to it.
     */
    public static function errorPage($code = NULL)
    {
        switch ($code) {
            case '400':
                header('HTTP/1.0 400 Bad Request');
                include PHPWS_SOURCE_DIR . 'src-phpws-legacy/resources/400.html';
                break;

            case '403':
                header('HTTP/1.0 403 Forbidden');
                include PHPWS_SOURCE_DIR . 'src-phpws-legacy/resources/403.html';
                break;

            case '404':
                header('HTTP/1.0 404 Not Found');
                include PHPWS_SOURCE_DIR . 'src-phpws-legacy/resources/404.html';
                break;

            case 'overpost':
                include PHPWS_SOURCE_DIR . 'src-phpws-legacy/resources/overpost.html';
                break;

            default:
                header('HTTP/1.1 503 Service Unavailable');
                include PHPWS_SOURCE_DIR . 'src-phpws-legacy/resources/error_page.html';
                break;
        }
        exit();
    }

    /**
     * Returns true if server OS is Windows
     */
    public static function isWindows()
    {
        if (isset($_SERVER['WINDIR']) || preg_match('/(microsoft|win32)/i', $_SERVER['SERVER_SOFTWARE'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * If a file is posted beyond php's posting limits, it will drop the
     * POST without an error message. checkOverPost sends the user to an
     * overpost error page.
     */
    public static function checkOverPost()
    {
        if (!isset($_GET['check_overpost'])) {
            return true;
        } elseif (empty($_POST) && isset($_SERVER['CONTENT_LENGTH'])) {
            Security::log(_('User tried to post a file beyond server limits.'));
            \phpws\PHPWS_Core::errorPage('overpost');
        }

        return true;
    }

    /**
     * Returns an array of the core modules. Set from the core_modules.php file.
     */
    public static function coreModList()
    {
        static $core_modules = NULL;

        if (is_array($core_modules)) {
            return $core_modules;
        }

        $file = \phpws\PHPWS_Core::getConfigFile('core', 'CoreModuleList.php');
        if (\phpws\PHPWS_Error::isError($file)) {
            return $file;
        }

        include $file;
        return $core_modules;
    }

    /**
     * Returns an array of all installed modules
     */
    public static function installModList($active_only = false)
    {
        return \phpws2\ModuleRepository::getInstance()->getActiveModules();
    }

    /**
     * Returns an array with containing all the values of
     * the passed object.
     */
    public static function stripObjValues($object, $strip_null = true)
    {
        $className = get_class($object);
        $classVars = get_class_vars($className);
        $var_array = NULL;

        if (!is_array($classVars)) {
            return \phpws\PHPWS_Error::get(PHPWS_CLASS_VARS, 'core', '\phpws\PHPWS_Core::stripObjValues', $className);
        }

        foreach ($classVars as $key => $value) {
            if ($strip_null && !isset($object->$key)) {
                continue;
            }
            $var_array[$key] = $object->$key;
        }

        return $var_array;
    }

    /**
     * Plugs an array of $variables into the $object. The associative array
     * keys must be identical to the object's variable names.
     *
     * 5/17/06 Removed the code that prevent private variables from loading.
     * Added 10/15/2008:
     * If arguments are sent in the third parameter, plugObject will call
     * the object's postPlug function and send those arguments to it.
     */
    public static function plugObject($object, $variables, $args = null)
    {
        $post_plug = isset($args) && method_exists($object, 'postPlug');

        $className = get_class($object);
        $classVars = get_class_vars($className);

        if (!is_array($classVars) || empty($classVars)) {
            return \phpws\PHPWS_Error::get(PHPWS_CLASS_VARS, 'core', '\phpws\PHPWS_Core::plugObject', $className);
        }

        if (isset($variables) && !is_array($variables)) {
            return \phpws\PHPWS_Error::get(PHPWS_WRONG_TYPE, 'core', __CLASS__ . '::' . __FUNCTION__, gettype($variables));
        }

        foreach ($classVars as $key => $value) {
            if (isset($variables[$key])) {
                if (preg_match('/^[aO]:\d+:/', $variables[$key])) {
                    $object->$key = unserialize($variables[$key]);
                } else {
                    $object->$key = $variables[$key];
                }
            }
        }

        if ($post_plug) {
            $object->postPlug($args);
        }
        return true;
    }

    /**
     * Returns the installation's home directory
     */
    public static function getHomeDir()
    {
        $address[] = $_SERVER['DOCUMENT_ROOT'];
        $address[] = dirname($_SERVER['PHP_SELF']);
        return implode('', $address) . '/';
    }

    /**
     * Returns the installations url address
     */
    public static function getHomeHttp($with_http = true, $with_directory = true, $with_slash = true)
    {
        $url = \Canopy\Server::getSiteUrl($with_http, $with_directory);
        if ($with_slash && !preg_match('/\/$/', $url)) {
            $url .= '/';
        }
        return $url;
    }

    /**
     * returns the full Canopy release version
     */
    public static function releaseVersion()
    {
        include PHPWS_SOURCE_DIR . 'src-phpws-legacy/version.php';
        return $version;
    }

    /**
     * Returns the core version.
     *
     * @param boolean get_file  If true, uses the boost.php file, if false
     *                          uses the database version.
     */
    public static function getVersionInfo($get_file = true)
    {
        $file = PHPWS_SOURCE_DIR . 'core/boost/boost.php';
        include $file;

        if (!$get_file) {
            if (!\phpws\PHPWS_DB::isTable('core_version')) {
                $version = '1.0.0';
            } else {
                $db = new PHPWS_DB('core_version');
                $db->addColumn('version');
                $version = $db->select('one');
            }
        }

        return array('proper_name' => $proper_name,
            'version' => $version,
            'version_http' => $version_http);
    }

    public static function isRewritten()
    {
        return strpos($_SERVER['REQUEST_URI'], $_SERVER['PHP_SELF']) === FALSE;
    }

    /**
     * Returns the url of the current page
     * If redirect is true and a redirect occurs at the root level,
     * index.php is returned.
     */
    public static function getCurrentUrl($relative = true, $use_redirect = true)
    {
        return \Canopy\Server::getCurrentUrl($relative, $use_redirect);
    }

    /**
     * Returns true if the site is a hub or if the site is
     * an allowed branch. If false is returned, the index file
     * drops the user to an error page. Also sets the Allow_Entry GLOBAL.
     */
    public static function checkBranch()
    {
        if (isset($GLOBALS['Allow_Entry'])) {
            return $GLOBALS['Allow_Entry'];
        } elseif (PHPWS_SOURCE_DIR == PHPWS_HOME_DIR) {
            $GLOBALS['Allow_Entry'] = true;
            return true;
        } else {
            if (!\phpws\PHPWS_Core::initModClass('branch', 'Branch.php')) {
                \phpws\PHPWS_Error::log(PHPWS_HUB_IDENTITY, 'core', 'Cannot load Branch class');
                return false;
            }
            if (\Branch::checkCurrentBranch()) {
                $GLOBALS['Allow_Entry'] = true;
                return true;
            } else {
                \phpws\PHPWS_Error::log(PHPWS_HUB_IDENTITY, 'core', 'Hash not found: ' . SITE_HASH . ' from ' . getcwd());
                return false;
            }
        }
    }

    /**
     * Will return true if the current process is a branch accessing the
     * hub files.
     * If a module needs to check if it is in the hub working on a branch, PHPWS_Boost::inBranch
     * should be used instead.
     */
    public static function isBranch()
    {
        if (!isset($GLOBALS['Is_Branch'])) {
            return false;
        }
        return $GLOBALS['Is_Branch'];
    }

    public static function allowScriptTags()
    {
        return ALLOW_SCRIPT_TAGS;
    }

    public static function getBaseURL()
    {
        return \phpws\PHPWS_Core::getHttp()
                . $_SERVER['HTTP_HOST']
                . preg_replace('/index.*/', '', $_SERVER['PHP_SELF']);
    }

    // TODO: A more formal and less nasty way to do this, issue #96
    public static function pushUrlHistory()
    {
        if (!isset($_SESSION['PHPWS_UrlHistory'])) {
            $_SESSION['PHPWS_UrlHistory'] = array();
        }

        array_push($_SESSION['PHPWS_UrlHistory'], \phpws\PHPWS_Core::getCurrentUrl());
    }

    // TODO: A more formal and less nasty way to do this, issue #96
    public static function popUrlHistory()
    {
        if (!isset($_SESSION['PHPWS_UrlHistory']) || count($_SESSION['PHPWS_UrlHistory']) == 0) {
            \phpws\PHPWS_Core::home();
        }

        \phpws\PHPWS_Core::reroute(array_pop($_SESSION['PHPWS_UrlHistory']));
    }

    /**
     * Trying to find out why Users changes authentication script. If cause
     * is found, this will be removed.
     */
    public static function trackAuthentication($message)
    {
        $url = \Canopy\Server::getCurrentUrl(false, false);
        $request = \Canopy\Server::getCurrentRequest();
        if ($request->isPost()) {
            $type = 'POST';
        } else {
            $type = 'GET';
        }
        $vars = $request->getVars();
        if (empty($vars)) {
            $variables = 'No variables';
        } else {
            foreach ($vars['vars'] as $k => $v) {
                if (is_array($v)) {
                    $v = serialize($v);
                }
                $varlist[] = "$k: $v";
            }
            $variables = implode("\n", $varlist);
        }
        $content = <<<EOF

MESSAGE: $message
REQUEST TYPE: $type
VARIABLES:
$variables
----
EOF;
        \PHPWS_Core::log($content, 'default_authorization.log');
    }

}

// End of core class
