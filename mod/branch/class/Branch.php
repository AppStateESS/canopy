<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */
class Branch
{

    public $id = null;
    public $branch_name = null;
    public $directory = null;
    public $url = null;
    public $site_hash = null;
    public $dsn = null;

    public function __construct($id = 0, $load_dsn = false)
    {
        $this->site_hash = md5(rand());
        if (!$id) {
            return;
        }

        $this->id = (int) $id;
        $this->init();
    }

    /**
     * Loads the branch's DSN into the objects
     *
     * @return boolean  True on success, false on failure
     */
    public function loadDSN()
    {
        $config_file = $this->getBranchConfig();

        $config_contents = file_get_contents($config_file);
        $config = explode("\n", $config_contents);

        foreach ($config as $row) {
            if (preg_match('/phpws_dsn/i', $row) && preg_match('/^define/i',
                            $row)) {
                $sub = explode(',', $row);
                $this->dsn = preg_replace("@'|\);$@", '', trim($sub[1]));
            }

            if (!empty($this->dsn)) {
                return true;
            }
        }

        if (isset($this->dsn)) {
            return true;
        } else {
            return false;
        }
    }

    public function init()
    {
        $db = new PHPWS_DB('branch_sites');
        $result = $db->loadObject($this);
        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            return $result;
        }
    }

    public function setBranchName($branch_name)
    {
        $this->branch_name = $branch_name;
        $db = new PHPWS_DB('branch_sites');
        $db->addWhere('branch_name', $branch_name);
        $db->addWhere('id', $this->id, '!=');
        $result = $db->select();
        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            return FALSE;
        } elseif ($result) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * using this method for config file name
     * @return unknown_type
     */
    public function getBranchConfig()
    {
        $name = preg_replace('/\W/', '-', $this->branch_name);
        return $this->directory . 'config/core/config.php';
    }

    public function save()
    {
        if (!preg_match('/\/$/', $this->directory)) {
            $this->directory .= '/';
        }

        $db = new PHPWS_DB('branch_sites');
        return $db->saveObject($this);
    }

    public function getUrl()
    {
        if (!preg_match('/^(http(s){0,1}:\/\/)/', $this->url)) {
            $http = 'http://' . $this->url;
        } else {
            $http = &$this->url;
        }
        return sprintf('<a href="%s">%s</a>', $http,
                PHPWS_Text::shortenUrl($http));
    }

    public function createDirectories()
    {
        if (!mkdir($this->directory . 'files/')) {
            return FALSE;
        }

        if (!mkdir($this->directory . 'images/')) {
            return FALSE;
        }

        if (!mkdir($this->directory . 'images/ckeditor/')) {
            return FALSE;
        }

        if (!mkdir($this->directory . 'admin/')) {
            return FALSE;
        }

        if (!mkdir($this->directory . 'config/')) {
            return FALSE;
        }

        if (!mkdir($this->directory . 'config/core/')) {
            return FALSE;
        }
        if (!mkdir($this->directory . 'secure/')) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Returns an associative array for the branch list page
     */
    public function getTpl()
    {
        $tpl['URL'] = $this->getUrl();

        $links[] = PHPWS_Text::secureLink(Icon::show('edit'), 'branch',
                        array('command' => 'edit_branch', 'branch_id' => $this->id));

        $js['question'] = 'Removing this branch will make it inaccessible.\nThe database and files will remain behind.\nIf you are sure you want to remove the branch, type the branch name:';
        $js['address'] = sprintf('index.php?module=branch&command=remove_branch&branch_id=%s&authkey=%s',
                $this->id, Current_User::getAuthKey());
        $js['value_name'] = 'branch_name';
        $js['link'] = Icon::show('delete');

        $links[] = javascript('prompt', $js);

        $tpl['DIRECTORY'] = sprintf('<abbr title="%s">%s</abbr>',
                $this->directory, PHPWS_Text::shortenUrl($this->directory));
        $tpl['ACTION'] = implode(' ', $links);
        return $tpl;
    }

    public static function getHubDSN()
    {
        $handle = @fopen(PHPWS_SOURCE_DIR . 'config/core/config.php', 'r');
        if ($handle) {
            $search_for = '^define\(\'PHPWS_DSN\',';
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);
                $buffer = str_replace(' ', '', $buffer);
                if (preg_match('/' . $search_for . '/', $buffer)) {
                    $dsn = preg_replace('/^define\(\'PHPWS_DSN\',\'(.*)\'\);/Ui',
                            '\\1', $buffer);
                    return trim($dsn);
                    break;
                }
            }
            return null;
        } else {
            return null;
        }
    }

    /**
     * Makes a connection to the hub database. Used when currently using a
     * branch connection.
     */
    public static function loadHubDB()
    {
        if (!isset($GLOBALS['PHPWS_DB'])) {
            \phpws\PHPWS_DB::loadDB();
        }
        $GLOBALS['Branch_Temp']['dsn'] = $GLOBALS['PHPWS_DB']['dsn'];
        $dsn = Branch::getHubDSN();
        if (empty($dsn)) {
            throw new \Exception('Could not get hub DSN');
        }

        PHPWS_DB::loadDB($dsn);
        \phpws2\Database::phpwsDSNLoader($dsn);
    }

    /**
     * Connects currently constructed branch to its database
     * Not called statically.
     */
    public function loadBranchDB()
    {
        if (empty($this->dsn)) {
            return false;
        }
        \phpws2\Database::phpwsDSNLoader($this->dsn);
        return PHPWS_DB::loadDB($this->dsn, false, false);
    }

    /**
     * Restores the branch connection after calling the loadHubDB
     */
    public static function restoreBranchDB()
    {
        if (isset($GLOBALS['Branch_Temp'])) {
            $dsn = null;
            extract($GLOBALS['Branch_Temp']);
            PHPWS_DB::loadDB($dsn);
            \phpws2\Database::phpwsDSNLoader($dsn);
        } else {
            PHPWS_DB::loadDB(PHPWS_DSN);
            \phpws2\Database::phpwsDSNLoader(PHPWS_DSN);
        }
    }

    /**
     * Returns the name of the branch saved in $_SESSION['Approved_Branch'].
     * For full information, use Branch::getCurrentBranch
     */
    public static function getCurrentBranchName()
    {
        if (!isset($_SESSION['Approved_Branch'])) {
            if (!Branch::checkCurrentBranch()) {
                return null;
            }
        }
        return $_SESSION['Approved_Branch']['branch_name'];
    }

    /**
     * Checks the Approved_Branch session and returns true
     * if set. If not set, the function builds the session.
     * If the branch site is approved, the row result is copied to
     * the session and true is returned. If not approved, false is
     * set to the session and false is returned.
     * This function DOES NOT assist with hub updating branch modules.
     */
    public static function checkCurrentBranch()
    {
        if (isset($_SESSION['Approved_Branch'])) {
            return (bool) $_SESSION['Approved_Branch'];
        }
        self::loadHubDB();

        if (!PHPWS_DB::isConnected()) {
            $_SESSION['Approved_Branch'] = FALSE;
            return FALSE;
        }

        $db = new PHPWS_DB('branch_sites');
        $db->addWhere('site_hash', SITE_HASH);
        $result = $db->select('row');
        self::restoreBranchDB();
        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $_SESSION['Approved_Branch'] = FALSE;
            self::restoreBranchDB();
            return false;
        } elseif (empty($result)) {
            $_SESSION['Approved_Branch'] = FALSE;
            self::restoreBranchDB();
            return false;
        } else {
            $_SESSION['Approved_Branch'] = $result;
            self::restoreBranchDB();
            return true;
        }
    }

    /**
     * Returns an array of information pulled from the
     * $_SESSION['Approved_Branch'] variable. This session is
     * set in checkCurrentBranch.
     */
    public static function getCurrentBranch()
    {
        if (!isset($_SESSION['Approved_Branch'])) {
            if (!Branch::checkCurrentBranch()) {
                return null;
            }
        }
        return $_SESSION['Approved_Branch'];
    }

    public static function getCurrentBranchId()
    {
        if (!isset($_SESSION['Approved_Branch'])) {
            if (!Branch::checkCurrentBranch()) {
                return null;
            }
        }
        return $_SESSION['Approved_Branch']['id'];
    }

    public function getHubDB()
    {
        $dsn = Branch::getHubDSN();
        if (empty($dsn)) {
            return FALSE;
        }

        $connection = DB::connect($dsn);

        if (PHPWS_Error::isError($connection)) {
            PHPWS_Error::log($connection);
            return FALSE;
        }
        return $connection;
    }

    public static function getCurrent()
    {
        if (!isset($_SESSION['Approved_Branch'])) {
            return FALSE;
        } else {
            return $_SESSION['Approved_Branch'];
        }
    }

    /**
     * Deletes a branch from the hub's database
     */
    public function delete()
    {
        $db = new PHPWS_DB('branch_sites');
        $db->addWhere('id', $this->id);
        $result = $db->delete();
        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            return false;
        }
        return true;
    }

}
