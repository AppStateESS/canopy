<?php

/**
 * Setup class controls the first-time installation of Canopy
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>

 */
if (strstr($_SERVER['SCRIPT_FILENAME'], '\\')) {
    define('DIRECTORY_SLASH', '\\');
} else {
    define('DIRECTORY_SLASH', '/');
}

class Setup
{

    public $phpws_version = null;

    /**
     * Array of error messages from form submission
     * @var array
     */
    public $messages = null;
    public $title = null;
    public $content = null;

    /**
     * How far along the setup has progressed
     * @var string
     */
    public $step = '0';

    public function __construct()
    {
        include PHPWS_SOURCE_DIR . 'src-phpws-legacy/version.php';
        $this->phpws_version = $version;
        if (isset($_REQUEST['step'])) {
            $this->setStep($_REQUEST['step']);
        }
    }

    public function setStep($step)
    {
        $this->step = (int) $step;
    }

    public function configExists()
    {
        return is_file(PHPWS_SOURCE_DIR . 'config/core/config.php');
    }

    public function initConfigSet()
    {
        $this->setConfigSet('cache_directory', '/tmp/');
        if (!isset($_SESSION['configSettings'])) {
            $_SESSION['configSettings']['database'] = false;
            // Could use some windows checking here
            $this->setConfigSet('dbname', DEFAULT_DBNAME);
            $this->setConfigSet('dbuser', DEFAULT_DBUSER);
            $this->setConfigSet('dbport', DEFAULT_DBPORT);
            $this->setConfigSet('dbhost', DEFAULT_DBHOST);
            $this->setConfigSet('dbtype', DEFAULT_DBTYPE);
        }
    }

    public function createConfig()
    {
        $this->initConfigSet();

        if (!isset($_SESSION['configSettings']['database']) || $_SESSION['configSettings']['database'] == false) {
            $this->databaseConfig();
        } else {
            $configDir = PHPWS_SOURCE_DIR . 'config/core/';
            if (is_file($configDir . 'config.php')) {
                $this->content[] = 'Your configuration file already exists.';
                $this->content[] = 'Remove the following file and refresh to continue:';
                $this->content[] = '<pre>' . $configDir . 'config.php</pre>';
            } elseif ($this->writeConfigFile()) {
                \phpws\PHPWS_Core::killSession('configSettings');
                $this->content[] = 'Your configuration file was written successfully!' . '<br />';
                $this->content[] = '<a href="index.php?step=3">' . 'Move on to Step 3' . '</a>';
            } else {
                $this->content[] = 'Your configuration file could not be written into the following directory:';
                $this->content[] = "<pre>$configDir</pre>";
                $this->content[] = 'Please check your directory permissions and try again.';
                $this->content[] = '<a href="help/permissions.' . DEFAULT_LANGUAGE . '.txt">' . 'Permission Help' . '</a>';
            }
        }
        $this->title = 'Create Configuration File';
    }

    /**
     * Writes the config/core/config.php file. Assumes one does not already
     * exist
     * @return unknown_type
     */
    public function writeConfigFile()
    {
        require_once 'File.php';

        $location = PHPWS_SOURCE_DIR . 'config/core/';

        if (!file_exists($location)) {
            // Try to make configuration directory
            $result = mkdir($location);
            if ($result === false) {
                $this->messages[] = 'Cannot create core configuration directory ' . $location;
                return false;
            }
        }

        if (!is_writable($location)) {
            $this->messages[] = 'Cannot write file to ' . $location;
            return false;
        }

        $filename = $location . 'config.php';
        if (is_file($filename)) {
            $this->messages[] = 'Configuration file already exists.';
            return false;
        }

        $config_file[] = '<?php';
        $config_file[] = sprintf("define('PHPWS_SOURCE_DIR', '%s');",
                PHPWS_SOURCE_DIR);
        $config_file[] = sprintf("define('PHPWS_HOME_DIR', '%s');",
                PHPWS_SOURCE_DIR);

        $config_file[] = sprintf("define('SITE_HASH', '%s');", md5(rand()));
        $config_file[] = sprintf("define('PHPWS_DSN', '%s');",
                $_SESSION['configSettings']['dsn']);
        if (!file_put_contents($filename, implode("\n", $config_file))) {
            return false;
        } else {
            $source_http = sprintf("<?php\ndefine('PHPWS_SOURCE_HTTP', '//%s');\n?>",
                    str_replace('setup/', '',
                            \phpws\PHPWS_Core::getHomeHttp(false)));
            if (is_file($location . 'source.php')) {
                $this->messages[] = 'source.php already exists! New copy saved as source.new.php';
                return file_put_contents($location . 'source.new.php',
                        $source_http);
            } else {
                return file_put_contents($location . 'source.php', $source_http);
            }
        }
    }

    private function postUser()
    {
        $_SESSION['User']->deity = true;
        $_SESSION['User']->authorize = 1;
        $_SESSION['User']->approved = 1;

        // all is well
        $aiw = true;
        if (empty($_POST['username']) || preg_match('/[^\w\.\-]/',
                        $_POST['username'])) {
            $aiw = false;
            $this->messages[] = 'Username is improperly formatted.';
        } else {
            $_SESSION['User']->username = $_POST['username'];
        }

        if (empty($_POST['pass1']) || $_POST['pass1'] != $_POST['pass2'] || strlen($_POST['pass1']) < 4) {
            $aiw = false;
            $this->messages[] = 'Password is not acceptable.';
        } else {
            $_SESSION['User']->setPassword($_POST['pass1']);
        }

        if (empty($_POST['email'])) {
            $aiw = false;
            $this->messages[] = 'Please enter an email address.';
        } else {
            $_SESSION['User']->setEmail($_POST['email']);
        }

        return $aiw;
    }

    public function postConfig()
    {
        $check = true;
        $currentPW = $this->getConfigSet('dbpass');

        if (!empty($_POST['dbuser'])) {
            $this->setConfigSet('dbuser', $_POST['dbuser']);
        } else {
            $this->messages['dbuser'] = 'Missing a database user name.';
            $check = false;
        }

        if (!empty($_POST['dbpass'])) {
            if (preg_match('/[^\w\s\.!\?]/', $_POST['dbpass'])) {
                $this->messages['dbpass'] = 'Database password may contain alphanumeric characters, punctuation, spaces and underscores only.';
                $check = false;
            } else {
                $this->setConfigSet('dbpass', $_POST['dbpass']);
            }
        } elseif (empty($currentPW)) {
            $this->messages['dbpass'] = 'Missing a database password.';
            $check = false;
        }

        $this->setConfigSet('dbhost', $_POST['dbhost']);

        if (!empty($_POST['dbname'])) {
            $this->setConfigSet('dbname', $_POST['dbname']);
        } else {
            $this->messages['dbname'] = 'Missing a database name.';
            $check = false;
        }

        $this->setConfigSet('dbtype', $_POST['dbtype']);
        $this->setConfigSet('dbport', $_POST['dbport']);

        if (!$check) {
            return false;
        }

        if (CHECK_DB_CONNECTION == false) {
            return true;
        }

        // previously, this error was silenced. Now, all errors produce exceptions
        // causing this previous code to blow up. We catch it now and test
        // for an unknown database. Unfortunately we don't have a real exception
        // code, so we have to make do.
        try {
            $checkConnection = $this->testDBConnect();
        } catch (\Exception $e) {
            $message = $e->getMessage();
            // tests for missing database in mysql and postgresql
            if (preg_match('@(42000|1049|08006)@', $message)) {
                $checkConnection = -1;
            } else {
                throw $e;
            }
        }

        if ($checkConnection == 1) {
            // Database already exists and is empty.
            $this->messages[] = 'Database found.';
            return true;
        } elseif ($checkConnection == 2) {
            $sub[] = 'Canopy was able to connect, but the database already contained tables.';
            $sub[] = 'Create a new database, remove all tables from the database you want to use.';
            $_SESSION['configSettings']['database'] = false;
            $this->messages['main'] = implode('<br />', $sub);
            return false;
        } elseif ($checkConnection == -1) {
            if ($this->createDatabase()) {
                //Database created successfully, move on to creating core
                $this->messages[] = 'Database created.';
                return true;
            } else {
                $this->databaseConfig();
            }
        } else {
            $this->messages[] = 'Unable to connect to the database with the information provided.';
            $this->messages[] = '<a href="help/database.' . DEFAULT_LANGUAGE . '.txt" target="index">' . 'Database Help' . '</a>';
            return false;
        }
    }

    public function createDatabase()
    {
        $dsn = $this->getPDODSN(false);
        $dbuser = $this->getConfigSet('dbuser');
        $dbpass = $this->getConfigSet('dbpass');
        try {
            $db = new \PDO($this->getPDODSN(false), $dbuser, $dbpass);
        } catch (\Exception $e) {
            PHPWS_Error::log($e->getMessage());
            $this->messages[] = 'Unable to connect.';
            $this->messages[] = 'Check your configuration settings.';
            return false;
        }

        $sql = 'CREATE DATABASE ' . $this->getConfigSet('dbname');
        try {
            $result = $db->exec($sql);
        } catch (\Exception $e) {
            PHPWS_Error::log($e->getMessage());
            $this->messages[] = 'Unable to create the database.';
            $this->messages[] = 'You will need to create it manually and rerun the setup.';
            return false;
        }


        $this->setConfigSet('dsn', $this->getDoctrineDSN());
        $_SESSION['configSettings']['database'] = true;

        return true;
    }

    public function getDoctrineDSN($includeDB = true)
    {
        $dbtype = $this->getConfigSet('dbtype');
        $dbuser = $this->getConfigSet('dbuser');
        $dbpass = $this->getConfigSet('dbpass');
        $dbhost = $this->getConfigSet('dbhost');
        $dbport = $this->getConfigSet('dbport');
        if ($includeDB) {
            $dbname = $this->getConfigSet('dbname');
            if (empty($dbhost)) {
                $dbhost = 'localhost';
            }
        } else {
            $dbname = $dbhost = null;
        }

        $dsn = $dbtype . '://' . $dbuser . ':' . $dbpass . '@' . $dbhost . '/' . $dbname;
        if (!empty($dbport)) {
            $dsn .= ':' . $dbport;
        }
        return $dsn;
    }

    public function getPDODSN($includeDB = true)
    {
        $dbtype = $this->getConfigSet('dbtype');
        $dbhost = $this->getConfigSet('dbhost');
        $dbport = $this->getConfigSet('dbport');
        if ($includeDB) {
            $dbname = 'dbname=' . $this->getConfigSet('dbname') . ';';
        } else {
            $dbname = null;
        }
        if (empty($dbhost)) {
            $dbhost = 'host=localhost;';
        } else {
            $dbhost = 'host=' . $dbhost . ';';
        }

        $dsn = $dbtype . ':' . $dbname . $dbhost;
        return $dsn;
    }

    /**
     * Performs database check and returns integer based on result
     *
     * -1 : Config file exists, database does not
     *  0 : Config file exists but could not connect to the database
     *  1 : all is well, continue with install
     *  2 : Config file and database already present. Stop install.
     *
     * @param type $dsn
     * @return int
     * @throws \Exception
     */
    public function testDBConnect($dsn = null)
    {
        // if no dsn, we assume input is coming from the form
        // We do not use getDSN because we have to use the PDO connect
        // which has a different format from Doctrine's DSN because
        // of course it does.
        if (empty($dsn)) {
            $dbuser = $this->getConfigSet('dbuser');
            $dbpass = $this->getConfigSet('dbpass');
            try {
                $connection = new \PDO($this->getPDODSN(false), $dbuser, $dbpass);
            } catch (\Exception $e) {
                if (preg_match('/\[28000\]|\[1045\]|08006/', $e->getMessage())) {
                    return 0;
                } else {
                    throw $e;
                }
            }
        }
        $dsn = $this->getDoctrineDSN();
        $tdb = new \phpws\FakeMDB2;
        $mdb2_connection = $tdb->connect($dsn);
        if ($mdb2_connection->isConnected()) {
            $tables = $mdb2_connection->listTables();
            if (!empty($tables) && count($tables)) {
                return 2;
            } else {
                $this->setConfigSet('dsn', $dsn);
                return 1;
            }
        } else {
            return -1;
        }
    }

    public function getMDB2Connection()
    {
        $dsn = $this->getDoctrineDSN();
        $db = new \phpws\FakeMDB2;
        $mdb2_connection = $db->connect($dsn);
    }

    public function setConfigSet($setting, $value)
    {
        $_SESSION['configSettings'][$setting] = $value;
    }

    public function getConfigSet($setting)
    {
        if (!isset($_SESSION['configSettings']) || !isset($_SESSION['configSettings'][$setting])) {
            return NULL;
        }

        return $_SESSION['configSettings'][$setting];
    }

    public function databaseConfig()
    {
        $form = new PHPWS_Form();
        $form->addHidden('step', '2');

        $databases = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL');

        $formTpl['DBTYPE_DEF'] = 'Canopy supports MySQL and PostgreSQL. Choose the type your server currently is running.';

        $formTpl['DBUSER_DEF'] = 'This is the user name that Canopy will use to access its database.'
                . ' <br /><i>' . 'Note: it is a good idea to give each Canopy installation its own user.' . '</i>';
        if (isset($this->messages['dbuser'])) {
            $formTpl['DBUSER_ERR'] = $this->messages['dbuser'];
        }

        $formTpl['DBPASS_DEF'] = dgettext('core',
                'Enter the database\'s user password here.');
        if (isset($this->messages['dbpass'])) {
            $formTpl['DBPASS_ERR'] = $this->messages['dbpass'];
        }

        $formTpl['DBHOST_DEF'] = 'If your database is on the same server as your Canopy installation, leave this as &#x22;localhost&#x22;.'
                . '<br />' . 'Otherwise, enter the ip or dns to the database server.';

        $formTpl['DBPORT_DEF'] = 'If your host specification requires access via a specific port, enter it here.';

        $formTpl['DBNAME_DEF'] = dgettext('core',
                        'The database\'s name into which you are installing Canopy.')
                . '<br /><i>' . 'Note: if you have not made this database yet, you should do so before continuing.' . '</i>';
        if (isset($this->messages['dbname'])) {
            $formTpl['DBNAME_ERR'] = $this->messages['dbname'];
        }
        $formTpl['TITLE'] = 'Database configuration';
        $form->addSelect('dbtype', $databases);
        $form->setMatch('dbtype', $this->getConfigSet('dbtype'));
        $form->setLabel('dbtype', 'Database Type');

        $form->addText('dbuser', $this->getConfigSet('dbuser'));
        $form->setSize('dbuser', 20);
        $form->setLabel('dbuser', 'Database User');

        $form->addPassword('dbpass', $this->getConfigSet('dbpass'));
        $form->allowValue('dbpass');
        $form->setSize('dbpass', 20);
        $form->setLabel('dbpass', 'Database Password');

        $form->addText('dbhost', $this->getConfigSet('dbhost'));
        $form->setSize('dbhost', 20);
        $form->setLabel('dbhost', 'Host Specification');

        $form->addText('dbport', $this->getConfigSet('dbport'));
        $form->setSize('dbport', 6);
        $form->setLabel('dbport', 'Host Specification Port');

        $form->addText('dbname', $this->getConfigSet('dbname'));
        $form->setSize('dbname', 20);
        $form->setLabel('dbname', 'Database Name');

        $form->mergeTemplate($formTpl);

        $form->addSubmit('default_submit', 'Continue');
        $this->content = $this->createForm($form, 'databaseConfig.tpl');
        $this->title = 'Configure Canopy';
        $this->display();
    }

    public function createForm($form, $tplFile)
    {
        $template = $form->getTemplate();
        $tpl = new PHPWS_Template;
        $tpl->setFile("setup/templates/$tplFile", true);
        $tpl->setData($template);

        return $tpl->get();
    }

    public function show($content, $title = NULL, $forward = false)
    {
        include 'src-phpws-legacy/src/version.php';
        $tpl = new PHPWS_Template;
        $tpl->setFile('setup/templates/setup.tpl', true);
        if (!isset($title)) {
            $title = sprintf('Canopy %s Setup', $version);
        }

        if ($forward && AUTO_FORWARD) {
            $time = 2;
            $address = 'index.php?step=3';
            $setupData['META'] = sprintf('<meta http-equiv="refresh" content="%s; url=%s" />',
                    $time, $address);
        }
        $setupData['TITLE'] = $title;
        $setupData['MAIN_CONTENT'] = implode('<br />', $content);
        $tpl->setData($setupData);
        return $tpl->get();
    }

    /**
     * Checks to see if the server is using sessions OR if the user has cookies enabled.
     * The session is established when the step is not set or 1 (the beginning). Also, starts the
     * session.
     * The next visit (step > 1) will determine whether they may continue.
     * @return void
     */
    public function checkSession()
    {
        session_name('never_ever_name_your_session_this');
        session_start();
        if ($this->step == 0) {
            $_SESSION['session_check'] = true;
            return;
        }

        // step > 2; check for session
        if (!isset($_SESSION['session_check'])) {
            $this->content[] = 'Canopy depends on sessions to move data between pages.';
            $this->content[] = sprintf('<a href="help/sessions.%s.txt">%s</a>',
                    DEFAULT_LANGUAGE, 'Sessions Help');
            $this->content[] = sprintf('If you think your sessions are working properly, %syou can click here return to the beginning%s.',
                    '<a href="index.php">', '</a>');
            $this->title = 'There is a problem with your sessions.';
            $this->display();
        }
    }

    public function welcome()
    {
        unset($_SESSION['Boost']);
        // If the config file is already created, need to check why
        if (CONFIG_CREATED) {
            switch ($this->testDBConnect(PHPWS_DSN)) {
                case '2':
                    $this->content[] = 'Canopy configuration file and database have been found. We are assuming your installation is complete.';
                    $this->content[] = 'You should move or delete the setup directory.';
                    $this->content[] = 'If you are returning here from a previous incomplete installation, you will need to clear the database of all tables and try again.';
                    $this->title = 'There is a problem with your database';
                    $this->display();
                    exit();

                case '-1':
                    $this->content[] = dgettext('core',
                            'The Canopy configuration file exists but it\'s specified database does not.');
                    $this->content[] = 'Create the database set in the config file or delete the config file.';
                    $this->title = 'There is a problem with your database';
                    $this->display();
                    exit();

                case '0':
                    $this->content[] = dgettext('core',
                            'The Canopy configuration file exists but we could not connect to it\'s specified database.');
                    $this->content[] = 'Check your dsn settings or delete the config file.';
                    $this->title = 'There is a problem with your database';
                    $this->display();
                    exit();

                case '1':
                    // install the core as the config exists and the database is empty
                    $this->step = '4';
                    $this->goToStep();
                    break;
            }
        } else {
            // create config file
            $this->step = 1;
        }
        $this->goToStep();
    }

    public function createUser()
    {
        if (!isset($_SESSION['User'])) {
            $_SESSION['User'] = new PHPWS_User;
        }
        $form = new PHPWS_Form;
        $form->addHidden('step', 5);
        $form->addText('username', $_SESSION['User']->username);
        $form->setLabel('username', 'Username');

        $form->addText('email', $_SESSION['User']->email);
        $form->setLabel('email', 'Email');

        $form->addPassword('pass1');
        $form->setLabel('pass1', 'Password');

        $form->addPassword('pass2');
        $form->setLabel('pass2', 'Confirm');
        $form->addSubmit('Create my account');
        $this->title = 'Please create your new user account';
        $this->content[] = $this->createForm($form, 'new_user.html');
        $this->display();
    }

    public function createCore()
    {
        require_once('File.php');
        $this->content[] = 'Importing core database file.';

        $db = new PHPWS_DB;
        $result = $db->importFile('core/boost/install.sql');

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $this->content[] = 'Some errors occurred while creating the core database tables.';
            $this->content[] = 'Please check your error log file.';
            return false;
        }

        require_once PHPWS_SOURCE_DIR . 'core/boost/install.php';
        core_install();

        if ($result == true) {
            $db = new PHPWS_DB('core_version');
            include(PHPWS_SOURCE_DIR . 'core/boost/boost.php');
            $db->addValue('version', $version);
            $result = $db->insert();

            if (PHPWS_Error::isError($result)) {
                PHPWS_Error::log($result);
                $this->content[] = 'Some errors occurred while creating the core database tables.';
                $this->content[] = 'Please check your error log file.';
                return false;
            } else {
                $this->content[] = 'Core installation successful.';
                return true;
            }
        }
    }

    private function installModules($modules)
    {
        if (!isset($_SESSION['Boost'])) {
            $_SESSION['Boost'] = new PHPWS_Boost;
            $_SESSION['Boost']->loadModules($modules);
        }
        $result = $_SESSION['Boost']->install(false);

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $this->content[] = 'An error occurred while trying to install your modules.'
                    . ' ' . 'Please check your error logs and try again.';
            return true;
        } else {
            $this->content[] = $result;
        }

        $result = $_SESSION['Boost']->isFinished();
        unset($_SESSION['Boost']);
        return $result;
    }

    private function installContentModules()
    {
        $modules[] = 'block';
        $modules[] = 'menu';
        $modules[] = 'pagesmith';
        return $this->installModules($modules);
    }

    public function installCoreModules()
    {
        $modules = \phpws\PHPWS_Core::coreModList();
        return $this->installModules($modules);
    }

    public function finish()
    {
        $this->content[] = '<hr />';
        $this->content[] = 'Installation of Canopy is complete.' . '<br />';
        $this->content[] = 'If you experienced any error messages, check your error.log file.' . '<br />';
        if (CHECK_DIRECTORY_PERMISSIONS) {
            $this->content[] = 'Check Directory Permissions is enabled so be sure to secure your config and templates directories!';
            $this->content[] = 'If you do not change it now, your next page will be an error screen.';
        } else {
            $this->content[] = 'After you finish installing your modules in Boost, you should make your config and template directories non-writable.';
        }
        $this->content[] = '<a href="../index.php">' . 'Go to my new website!' . '</a>' . '<br />';
    }

    public function display()
    {
        $tpl = new PHPWS_Template;
        $tpl->setFile("setup/templates/setup.tpl", true);
        if (is_array($this->content)) {
            $content = PHPWS_Text::tag_implode('p', $this->content);
        } else {
            $content = & $this->content;
        }

        if (!empty($this->messages)) {
            if (is_array($this->messages)) {
                $message = PHPWS_Text::tag_implode('p', $this->messages);
            } else {
                $message = & $this->messages;
            }
        } else {
            $message = null;
        }

        $tpl->setData(array('TITLE' => $this->title, 'CONTENT' => $content, 'MESSAGE' => $message));

        echo $tpl->get();
        exit();
    }

    /**
     * Checks various server settings prior to starting installation. Some end the installation; others
     * just return notices.
     * @return void
     */
    public function checkServerSettings()
    {
        $allow_install = true;

        // Settings were checked, return without issue.
        if (isset($_SESSION['server_passed'])) {
            return;
        }

        $test['session_auto_start']['pass'] = !(bool) ini_get('session.auto_start'); // need 0
        $test['session_auto_start']['fail'] = 'session.auto_start must be set to 0 for Canopy to work. Please review your php.ini file.';
        $test['session_auto_start']['name'] = 'Session auto start disabled';
        $test['session_auto_start']['crit'] = true;

        $test['pear_files']['pass'] = is_file('vendor/autoload.php');
        $test['pear_files']['fail'] = 'Install composer and run it in the root of your installation directory.';
        $test['pear_files']['name'] = 'Composer installed';
        $test['pear_files']['crit'] = true;

        $test['gd']['pass'] = extension_loaded('gd');
        $test['gd']['fail'] = sprintf('You need to compile the %sGD image library%s into PHP.',
                '<a href="http://www.libgd.org/Main_Page">', '</a>');
        $test['gd']['name'] = 'GD graphic libraries installed';
        $test['gd']['crit'] = true;

        $test['image_dir']['pass'] = is_dir('images/') && is_writable('images/');
        $test['image_dir']['fail'] = sprintf('%s directory does not exist or is not writable.',
                PHPWS_SOURCE_DIR . 'images/');
        $test['image_dir']['name'] = 'Image directory ready';
        $test['image_dir']['crit'] = true;

        $test['conf']['pass'] = is_dir('config/core/') && is_writable('config/core/');
        $test['conf']['fail'] = sprintf('%s directory does not exist or is not writable.',
                PHPWS_SOURCE_DIR . 'config/core/');
        $test['conf']['name'] = 'Config directory ready';
        $test['conf']['crit'] = true;

        $test['file_dir']['pass'] = is_dir('files/') && is_writable('files/');
        $test['file_dir']['fail'] = sprintf('%s directory does not exist or is not writable.',
                PHPWS_SOURCE_DIR . 'files/');
        $test['file_dir']['name'] = 'File directory ready';
        $test['file_dir']['crit'] = true;

        $test['log_dir']['pass'] = is_dir('logs/') && is_writable('logs/');
        $test['log_dir']['fail'] = sprintf('%s directory does not exist or is not writable.',
                PHPWS_SOURCE_DIR . 'logs/');
        $test['log_dir']['name'] = 'Log directory ready';
        $test['log_dir']['crit'] = true;

        $test['ffmpeg']['pass'] = is_file('/usr/bin/ffmpeg');
        $test['ffmpeg']['fail'] = 'You do not appear to have ffmpeg installed. File Cabinet will not be able to create thumbnail images from uploaded videos';
        $test['ffmpeg']['name'] = 'FFMPEG installed';
        $test['ffmpeg']['crit'] = false;

        $test['mime_type']['pass'] = function_exists('finfo_open') || function_exists('mime_content_type') || !ini_get('safe_mode');
        $test['mime_type']['fail'] = 'Unable to detect MIME file type. You will need to compile finfo_open into PHP.';
        $test['mime_type']['name'] = 'MIME file type detection';
        $test['mime_type']['crit'] = true;

        if (preg_match('/-/', PHP_VERSION)) {
            $phpversion = substr(PHP_VERSION, 0, strpos(PHP_VERSION, '-'));
        } else {
            $phpversion = PHP_VERSION;
        }

        $test['php_version']['pass'] = version_compare($phpversion, '5.1.0',
                '>=');
        $test['php_version']['fail'] = sprintf('Your server must run PHP version 5.1.0 or higher. You are running version %s.',
                $phpversion);
        $test['php_version']['name'] = 'PHP 5 version check';
        $test['php_version']['crit'] = true;

        $memory_limit = (int) ini_get('memory_limit');

        $test['memory']['pass'] = ($memory_limit > 8);
        $test['memory']['fail'] = 'Your PHP memory limit is less than 8MB. You may encounter problems with the script at this level.';
        $test['memory']['fail'] .= dgettext('core',
                'We suggest raising the limit in your php.ini file or uncommenting the "ini_set(\'memory_limit\', \'10M\');" line in your config/core/config.php file after installation.');
        $test['memory']['name'] = 'Memory limit exceeded';
        $test['memory']['crit'] = false;

        $test['globals']['pass'] = !(bool) ini_get('register_globals');
        $test['globals']['fail'] = 'You have register_globals enabled. You should disable it.';
        $test['globals']['name'] = 'Register globals disabled';
        $test['globals']['crit'] = false;

        $test['magic_quotes']['pass'] = true;
        $test['magic_quotes']['fail'] = 'Magic quotes is enabled. Please disable it in your php.ini file.';
        $test['magic_quotes']['name'] = 'Magic quotes disabled';
        $test['magic_quotes']['crit'] = true;

        foreach ($test as $test_section => $val) {
            if (!$val['pass']) {
                if ($val['crit']) {
                    $crit[] = $val['fail'];
                    $allow_install = false;
                } else {
                    $warn[] = $val['fail'];
                }
            }
        }

        $content = array();

        if (!$allow_install) {
            $this->title = 'Cannot install Canopy because of the following reasons:';
            $this->content = '<ul>' . PHPWS_Text::tag_implode('li', $crit) . '</ul>';
            $this->display();
        } else {
            $_SESSION['server_passed'] = true;
        }
    }

    function goToStep()
    {
        switch ($this->step) {
            case '0':
                $this->welcome();
                break;

            case '1':
                $this->createConfig();
                break;

            case '2':
                if (!$this->postConfig()) {
                    // create config file and database
                    $this->createConfig();
                } else {
                    if ($this->writeConfigFile()) {
                        // config written, need to reload page for new defines
                        header('location: index.php?step=3');
                        exit();
                    } else {
                        echo implode('<br />', $this->messages);
                    }
                    exit();
                }
                break;

            case '3':
                if ($this->createCore()) {
                    if ($this->installCoreModules()) {
                        $this->content[] = 'Core modules installed successfully.';
                        $this->content[] = sprintf('<a href="index.php?step=4">%s</a>',
                                'Click to continue');
                    }
                }
                break;

            case '4':
                $this->createUser();
                break;

            case '5':
                if ($this->postUser()) {
                    $db = new PHPWS_DB('users');
                    $result = $db->select();
                    if (empty($result)) {
                        $_SESSION['User']->setDisplayName('Administrator');
                        $_SESSION['User']->save();
                        $this->content[] = 'New user created successfully.';
                        $this->step = 6;
                        $this->goToStep();
                        break;
                    } elseif (PHPWS_Error::isError($result)) {
                        PHPWS_Error::log($result);
                        $this->content[] = 'Sorry an error occurred. Please check your logs.';
                    } else {
                        $this->content[] = 'Cannot create a new user. Initial user already exists.';
                        $this->display();
                    }
                } else {
                    $this->createUser();
                }
                break;

            case '6':
                $this->content[] = 'Starting modules installed.';
                $this->content[] = 'The site should be ready for you to use. Go to Boost to add more.';
                //                    $this->content[] = sprintf('<a href="%s">%s</a>', PHPWS_SOURCE_HTTP, 'Continue to your new site...');
                $this->content[] = sprintf('<a href="../">%s</a>',
                        'Continue to your new site...');
                unset($_SESSION['configSettings']);
                unset($_SESSION['User']);
                unset($_SESSION['session_check']);
                $this->display();
                break;

            case '7':
                $dsn = $this->getDSN(2);
                $this->setConfigSet('dsn', $dsn);
                $_SESSION['configSettings']['database'] = true;
                if ($this->writeConfigFile()) {
                    // config written, need to reload page for new defines
                    header('location: index.php?step=3');
                    exit();
                } else {
                    $this->step = 2;
                    $this->createConfig();
                }
        }
        $this->display();
    }

}
