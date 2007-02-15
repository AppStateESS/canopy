<?php
  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

define('BRANCH_NO_CONNECTION',       1);
define('BRANCH_CONNECT_NO_DB',       2);
define('BRANCH_CONNECT_WITH_TABLES', 3);
define('BRANCH_CONNECT_SUCCESS',     4);
define('BRANCH_CONNECT_BAD_DB',      5);

PHPWS_Core::initModClass('branch', 'Branch.php');

class Branch_Admin {
    // Contains the panel object
    var $panel   = null;

    var $title   = null;

    var $message = null;

    // contains the current content. piped into panel
    var $content = null;

    // currently 
    var $branch  = null;

    var $error   = null;

    // database creation form variables
    var $createdb    = 0;
    var $dbname      = null;
    var $dbuser      = null;
    var $dbpass      = null;
    var $dbhost      = 'localhost';
    var $dbport      = null;
    var $dbtype      = null;
    var $dbprefix    = null;
    var $dsn         = null; // full dsn

    var $create_step = 1;

    var $db_list    = null;

    function Branch_Admin()
    {
        $this->db_list = array ('mysql' =>'MySQL',
                                'ibase' =>'InterBase',
                                'mssql' =>'Microsoft SQL Server',
                                'msql'  =>'Mini SQL',
                                'oci8'  =>'Oracle 7/8/8i',
                                'odbc'  =>'ODBC',
                                'pgsql' =>'PostgreSQL',
                                'sybase'=>'SyBase',
                                'fbsql' =>'FrontBase',
                                'ifx'   =>'Informix');

        if (isset($_SESSION['branch_create_step'])) {
            $this->create_step = $_SESSION['branch_create_step'];
        }

        if (isset($_SESSION['branch_dsn'])) {
            $dsn = &$_SESSION['branch_dsn'];
            $this->dbname   = $dsn['dbname'];
            $this->dbuser   = $dsn['dbuser'];
            $this->dbpass   = $dsn['dbpass'];
            $this->dbhost   = $dsn['dbhost'];
            $this->dbport   = $dsn['dbport'];
            $this->dbtype   = $dsn['dbtype'];
            $this->dbprefix = $dsn['dbprefix'];
        }

        if (isset($_REQUEST['branch_id'])) {
            $this->branch = new Branch($_REQUEST['branch_id']);
        } else {
            $this->branch = new Branch;
        }
    }


    function main()
    {
        $content = null;
        // Create the admin panel
        $this->cpanel();

        // Direct the path command
        $this->direct();

        // Display the results
        $this->displayPanel();
    }

    /**
     * Directs the administrative choices
     * Content is displayed in main
     */
    function direct()
    {
        translate('branch');
        if (!@$command = $_REQUEST['command']) {
            $command = $this->panel->getCurrentTab();
        }

        switch ($command) {
        case 'new':
            $this->resetAdmin();
            $this->edit_db();
            break;

        case 'edit':
            // editing existing branch
            if (empty($this->branch->id)) {
                $this->content = _('Incorrect or missing branch id.');
            }
            break;

        case 'list':
            // list all branches in the system
            $this->listBranches();
            break;

        case 'post_db':
            // post a new or updated branch to the system
            if (isset($_POST['plug'])) {
                // user is going to use the hub dsn information
                $this->plugHubValues();
                $this->edit_db();
            } else {
                if (!$this->post_db()) {
                    $this->edit_db();
                } else {
                    $this->testDB();
                }
            }
            break;

        case 'edit_branch':
            $this->edit_basic();
            break;

        case 'branch_modules':
            $this->edit_modules();
            break;

        case 'save_branch_modules':
            if ($this->saveBranchModules()) {
                $this->message = _('Module list saved successfully.');
            } else {
                $this->message = _('An error occurred when trying to save the module list.');
            }
            $this->edit_modules();
            break;

        case 'post_basic':
            if (!$this->branch->id) {
                $new_branch = true;
            } else {
                $new_branch = false;
            }
            if (!$this->post_basic()) {
                $this->edit_basic();
            } else {
                $result = $this->branch->save();
                if (PEAR::isError($result)) {
                    PHPWS_Error::log($result);
                    $this->title = _('An error occurred while saving your branch.');
                    $this->content = $result->getMessage();
                    return;
                }

                if ($new_branch) {
                    if ($this->branch->createDirectories()) {
                        $this->setCreateStep(3);
                        $this->title = _('Create branch directories');
                        $this->message[] = _('Branch created successfully.');
                        $vars['command'] = 'install_branch_core';
                        $vars['branch_id'] = $this->branch->id;
                        $this->content = PHPWS_Text::secureLink(_('Continue to install branch core'), 'branch', $vars); 
                    } else {
                        $this->title = _('Unable to create branch directories.');
                        $this->content = _('Sorry, but Branch failed to make the proper directories.');
                    }
                } else {
                    $this->listBranches();
                }
            }
            break;

        case 'install_branch_core':
            $this->install_branch_core();
            break;

        case 'core_module_installation':
            $result =  $this->core_module_installation();
            if ($result) {
                $this->content[] = _('All done!');
                $this->content[] = PHPWS_Text::secureLink(_('Set up allowed modules'),
                                                          'branch', array('command' => 'branch_modules',
                                                                          'branch_id' => $this->branch->id));
                $this->resetAdmin();
            }
            break;

        case 'remove_branch':
            if ( isset($_REQUEST['branch_id']) && isset($_REQUEST['branch_name']) &&
                 $this->branch->branch_name === $_REQUEST['branch_name'] ) {
                $this->branch->delete();
            }

            $this->listBranches();
            break;
        }// end of the command switch
        translate();
    }

    function install_branch_core()
    {
        translate('branch');
        PHPWS_Core::initCoreClass('File.php');
        $content = array();

        $this->title = _('Install branch core');
        $dsn = $this->getDSN();
        if (empty($dsn)) {
            $this->content[] = _('Unable to get database connect information. Please try again.');
            return false;
        }

        if (!PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'admin/', $this->branch->directory . 'admin/')) {
            $this->content[] = _('Failed to copy admin file to branch.');
            return false;
        } else {
            $this->content[] = _('Copied admin file to branch.');
        }


        if (!PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'themes/', $this->branch->directory . 'themes/')) {
            $this->content[] = _('Failed to copy theme files to branch.');
            return false;
        } else {
            $this->content[] = _('Copied themes to branch.');
        }

        if (!PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'images/core/', $this->branch->directory . 'images/core/')) {
            $this->content[] = _('Failed to copy core images to branch.');
            return false;
        } else {
            $this->content[] = _('Copied core images.');
        }

        if (!PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'config/core/', $this->branch->directory . 'config/core/')) {
            $this->content[] = _('Failed to copy core config files to branch.');
            return false;
        } else {
            $this->content[] = _('Copied config files to branch.');
            @unlink($this->branch->directory . 'config.php');
        }

        if (!PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'javascript/', $this->branch->directory . 'javascript/')) {
            $this->content[] = _('Failed to copy javascript files to branch.');
            return false;
        } else {
            $this->content[] = _('Copied javascript files to branch.');
        }


        $stats = sprintf('<?php include \'%sphpws_stats.php\' ?>', PHPWS_SOURCE_DIR);
        $index_file = sprintf('<?php include \'%sindex.php\'; ?>', PHPWS_SOURCE_DIR);
        file_put_contents($this->branch->directory . 'phpws_stats.php', $stats);
        file_put_contents($this->branch->directory . 'index.php', $index_file);
        
        if (!$this->copy_config()) {
            $this->content[] = _('Failed to create config.php file in the branch.');
            return false;
        } else {
            $this->content[] = _('Config file created successfully.');
        }

        $result = $this->create_core();

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $this->content[] = _('Core SQL import failed.');
            return false;
        } else {
            $this->content[] = _('Core SQL import successful.');
        }
        $link = _('Core installed successfully. Continue to core module installation.');
        $vars['command']   = 'core_module_installation';
        $vars['branch_id'] = $this->branch->id;
        $this->content[] = PHPWS_Text::secureLink($link, 'branch', $vars);
        translate();
        return true;
    }

    function create_core()
    {
        $db = new PHPWS_DB;
        $loaddb = $db->loadDB($this->getDSN(), $this->dbprefix);
        if (PEAR::isError($loaddb)) {
            return $loaddb;
        }

        $result = $db->importFile(PHPWS_SOURCE_DIR . 'core/boost/install.sql');

        if ($result == TRUE) {
            $db->setTable('core_version');
            include(PHPWS_SOURCE_DIR . 'core/boost/boost.php');
            $db->addValue('version', $version);
            $result = $db->insert();
            $db->disconnect();
            if (PEAR::isError($result)) {
                PHPWS_Error::log($result);
                return $result;
            }
            return true;
        } else {
            $db->disconnect();
            return $result;
        }
    }

    function copy_config()
    {
        $template['source_dir']      = PHPWS_SOURCE_DIR;
        $template['home_dir']        = $this->branch->directory;
        $template['site_hash']       = $this->branch->site_hash;
        $template['dsn']             = $this->getDSN();
        $template['cache_directory'] = CACHE_DIRECTORY;
        if (!empty($this->dbprefix)) {
            $template['dbprefix']     = $this->dbprefix;
        }

        // if windows installation, comment out linux pear path
        if (PHPWS_Core::isWindows()) {
            $template['LINUX_PEAR'] = '//';
        } else {
            $template['WINDOWS_PEAR'] = '//';
        }

        $file_content = PHPWS_Template::process($template, 'branch', 'config.tpl');

        $file_directory = $this->branch->directory . 'config/core/config.php';
        
        return @file_put_contents($file_directory, $file_content);
    }

    function post_basic()
    {
        translate('branch');
        PHPWS_Core::initCoreClass('File.php');
        $result = true;

        if (empty($this->branch->dbname) && isset($this->dbname)) {
            $this->branch->dbname = $this->dbname;
        }

        $this->branch->directory = $_POST['directory'];
        if (!preg_match('/\/$/', $this->branch->directory)) {
            $this->branch->directory .= '/';
        }

        if (!is_dir($this->branch->directory)) {
            $this->message[] = _('Branch directory does not exist.');
            $directory = explode('/', $this->branch->directory);
            // removes item after the /
            array_pop($directory);
            // removes the last directory
            array_pop($directory);
            $write_dir = implode('/', $directory);

            // only writes directory on new branches
            if (!$this->branch->id) {
                if (is_writable($write_dir)) {
                    if(@mkdir($this->branch->directory)) {
                        $this->message[] = _('Directory creation successful.');
                    } else {
                        $this->message[] = _('Unable to create the directory. You will need to create it manually.');
                        return false;
                    }
                } else {
                    $this->message[] = _('Unable to create the directory. You will need to create it manually.');
                    $result = false;
                }
            }
        } elseif (!is_writable($this->branch->directory)) {
            $this->message[] = _('Directory exists but is not writable.');
            $result = false;
        } elseif(!$this->branch->id && PHPWS_File::listDirectories($this->branch->directory)) {
            $this->message[] = _('Directory exists but already contains files.');
            $result = false;
        }

        if (empty($_POST['branch_name'])) {
            $this->message[] = _('You must name your branch.');
            $result = false;
        } elseif (!$this->branch->setBranchName($_POST['branch_name'])) {
            $this->message[] = _('You may not use that branch name.');
            $result = false;
        }

        if (empty($_POST['url'])) {
            $this->message[] = _('Enter your site\'s url address.');
            $result = false;
        } else {
            $this->branch->url = $_POST['url'];
        }

        if (empty($_POST['site_hash'])) {
            $this->message[] = _('Your branch site must have a site_hash.');
            $result = false;
        } else {
            $this->branch->site_hash = $_POST['site_hash'];
        }
        translate();
        return $result;
    }


    function core_module_installation()
    {
        translate('branch');
        if (!isset($_SESSION['Boost'])){
            $modules = PHPWS_Core::coreModList();
            $_SESSION['Boost'] = new PHPWS_Boost;
            $_SESSION['Boost']->loadModules($modules);
        }

        PHPWS_DB::loadDB($this->getDSN(), $this->dbprefix);

        $this->title = _('Installing core modules');

        $result = $_SESSION['Boost']->install(false, true, $this->branch->directory);

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $this->content[] = _('An error occurred while trying to install your modules.') 
                . ' ' . _('Please check your error logs and try again.');
            return true;
        } else {
            $this->content[] = $result;
        }

        PHPWS_DB::loadDB();
        translate();
        return $_SESSION['Boost']->isFinished();
    }

    /**
     * sets the current 'step' the user is in for the creation
     * of a new branch
     */
    function setCreateStep($step)
    {
        $_SESSION['branch_create_step'] = $step;
        $this->create_step = $step;
    }

    /**
     * saves a workable dsn line for use in the creation of the branch
     */
    function saveDSN()
    {
        $_SESSION['branch_dsn'] = array('dbtype'   => $this->dbtype,
                                        'dbuser'   => $this->dbuser,
                                        'dbpass'   => $this->dbpass,
                                        'dbhost'   => $this->dbhost,
                                        'dbport'   => $this->dbport,
                                        'dbname'   => $this->dbname,
                                        'dbprefix' => $this->dbprefix);
    }

    /**
     * if the dsn variable is set, returns it. Otherwise, it attempts to create
     * the dsn line from variables in the object. If the variables are not
     * set, it returns null
     */
    function &getDSN($dbname=true)
        {
            if (isset($this->dbuser)) {
                $dsn =  sprintf('%s://%s:%s@%s',
                                $this->dbtype,
                                $this->dbuser,
                                $this->dbpass,
                                $this->dbhost);
            
                if ($this->dbport) {
                    $dsn .= ':' . $this->dbport;
                }

                if ($dbname) {
                    $dsn .= '/' . $this->dbname;
                }
                $GLOBALS['Branch_DSN'] = $dsn;
                return $dsn;
            } else {
                return null;
            }

        }

    function cpanel()
    {
        translate('branch');
        PHPWS_Core::initModClass('controlpanel', 'Panel.php');
        $newLink = 'index.php?module=branch&amp;command=new';
        $newCommand = array ('title'=>_('New'), 'link'=> $newLink);
        
        $listLink = 'index.php?module=branch&amp;command=list';
        $listCommand = array ('title'=>_('List'), 'link'=> $listLink);

        $tabs['new'] = &$newCommand;
        $tabs['list'] = &$listCommand;

        $panel = new PHPWS_Panel('branch');
        $panel->quickSetTabs($tabs);
        $panel->enableSecure();
        $panel->setModule('branch');
        $this->panel = &$panel;
        translate();
    }
    
    /**
     * Displays the content variable in the control panel
     */
    function displayPanel()
    {
        $template['TITLE']   = $this->title;
        if ($this->message) {
            if (is_array($this->message)) {
                $template['MESSAGE'] = implode('<br />', $this->message);
            } else {
                $template['MESSAGE'] = $this->message;
            }
        }

        if (is_array($this->content)) {
            $template['CONTENT'] = implode('<br />', $this->content);
        } else {
            $template['CONTENT'] = $this->content;
        }
        $content = PHPWS_Template::process($template, 'branch', 'main.tpl');
        
        $this->panel->setContent($content);
        Layout::add(PHPWS_ControlPanel::display($this->panel->display()));
    }

    /**
     * resets the branch creation process
     */
    function resetAdmin()
    {
        unset($_SESSION['branch_create_step']);
        unset($_SESSION['branch_dsn']);
        unset($_SESSION['Boost']);
        $this->Branch_Admin();
    }


    /**
     * Once the database information has been posted successfully,
     * testDB determines if the database connection can be made and,
     * if so, if there a database to which to connect. If not, then
     * it creates the database (if specified)
     */
    function testDB()
    {
        translate('branch');
        $connection = $this->checkConnection();
        PHPWS_DB::loadDB();
        switch ($connection) {
        case BRANCH_CONNECT_NO_DB:
            // connection made, but database does not exist
            if (isset($_POST['createdb'])) {
                $result = $this->createDB();
                if (PEAR::isError($result)) {
                    $this->message[] = _('An error occurred when trying to connect to the database.');
                    $this->edit_db();
                } elseif ($result) {
                    $this->message[] = _('Database created successfully.');
                    $this->setCreateStep(2);
                    $this->saveDSN();
                    $this->edit_basic();
                } else {
                    $this->message[] = _('Unable to create the database. You will need to create it manually.');
                    $this->edit_db();
                }
            } else {
                $this->message[] = _('Connected successfully, but the database does not exist.');
                $this->edit_db();
            }
            break;

        case BRANCH_NO_CONNECTION:
            // Failed connection
            $this->message[] = _('Could not connect to the database.');
            $this->edit_db();
            break;

        case BRANCH_CONNECT_SUCCESS:
            // connection successful
            $this->setCreateStep(2);
            $this->saveDSN();
            $this->message[] = _('Connection successful. Database available.');
            $this->edit_basic();
            break;

        case BRANCH_CONNECT_WITH_TABLES:
            $this->message[] = _('Connected successfully, but this database already contains tables.');
            $this->edit_db();
            break;
        }
        translate();
    }

    function edit_basic()
    {
        translate('branch');
        $branch = & $this->branch;

        $form = new PHPWS_Form('branch-form');
        $form->addHidden('module', 'branch');
        $form->addHidden('command', 'post_basic');

        if ($branch->id) {
            $this->title = _('Edit branch');
            $form->addHidden('branch_id', $this->branch->id);
            $form->addSubmit('submit', _('Update'));
        } else {
            $this->title = _('Create branch information');
            $form->addSubmit('submit', _('Continue...'));
        }

        $form->addText('branch_name', $branch->branch_name);
        $form->setLabel('branch_name', _('Branch name'));

        $form->addText('directory', $branch->directory);
        $form->setSize('directory', 50);
        $form->setLabel('directory', _('Directory'));

        $form->addText('url', $branch->url);
        $form->setSize('url', 50);
        $form->setLabel('url', _('URL'));

        $form->addText('site_hash', $branch->site_hash);
        $form->setSize('site_hash', 40);
        $form->setLabel('site_hash', _('ID hash'));
        $template = $form->getTemplate();
        $template['BRANCH_LEGEND'] = _('Branch specifications');
        $this->content = PHPWS_Template::process($template, 'branch', 'edit_basic.tpl');
        translate();
    }

    /**
     * Form to create or edit a branch
     */
    function edit_db()
    {
        translate('branch');
        $this->title = _('Setup branch database');
        $form = new PHPWS_Form('branch-form');
        $form->addHidden('module', 'branch');
        $form->addHidden('command', 'post_db');

        $form->addCheck('createdb', $this->createdb);
        $form->setLabel('createdb', _('Create new database'));
        
        $form->addSelect('dbtype', $this->db_list);
        $form->setMatch('dbtype', $this->dbtype);
        $form->setLabel('dbtype', _('Database syntax'));
        
        $form->addText('dbname', $this->dbname);
        $form->setLabel('dbname', _('Database name'));
        
        $form->addText('dbuser', $this->dbuser);
        $form->setLabel('dbuser', _('Permission user'));
        
        $form->addPassword('dbpass', $this->dbpass);
        $form->allowValue('dbpass');
        $form->setLabel('dbpass', _('User password'));

        $form->addText('dbprefix', $this->dbprefix);
        $form->setLabel('dbprefix', _('Table prefix'));
        $form->setSize('dbprefix', 5, 5);
        
        $form->addText('dbhost', $this->dbhost);
        $form->setLabel('dbhost', _('Database Host'));
        $form->setSize('dbhost', 40);
        
        $form->addText('dbport', $this->dbport);
        $form->setLabel('dbport', _('Connection Port'));
        
        $form->addTplTag('DB_LEGEND', _('Database information'));
        
        $form->addSubmit('plug', _('Use hub values'));
        $form->addSubmit('submit', _('Continue...'));
        
        $template = $form->getTemplate();

        $this->content = PHPWS_Template::process($template, 'branch', 'edit_db.tpl');
        translate();
    }

    function checkConnection()
    {
        $dsn1 =  sprintf('%s://%s:%s@%s',
                         $this->dbtype,
                         $this->dbuser,
                         $this->dbpass,
                         $this->dbhost);
        
        if ($this->dbport) {
            $dsn1 .= ':' . $this->dbport;
        }

        $dsn2 = $dsn1 . '/' . $this->dbname;

        $connection = DB::connect($dsn1);
        
        if (PEAR::isError($connection)) {
            // basic connection failed
            PHPWS_Error::log($connection);
            return BRANCH_NO_CONNECTION;
        } else {
            $connection2 = DB::connect($dsn2);

            if (PEAR::isError($connection2)) {
                // check to see if the database does not exist
                // mysql delivers the first error, postgres the second
                if ($connection2->getCode() == DB_ERROR_NOSUCHDB ||
                    $connection2->getCode() == DB_ERROR_CONNECT_FAILED) {
                    return BRANCH_CONNECT_NO_DB;
                } else {
                    // connection failed with db name
                    PHPWS_Error::log($connection2);
                    return BRANCH_CONNECT_BAD_DB;
                }
            } else {
                $tables = $connection2->getlistOf('tables');
                if (!empty($tables)) {
                    // connect was successful but database already contains tables
                    $connection2->disconnect();
                    return BRANCH_CONNECT_WITH_TABLES;
                } else {
                    // connection successful, table exists and is empty
                    $connection2->disconnect();
                    return BRANCH_CONNECT_SUCCESS;
                }
            }
        }
    }

    /**
     * copies the db form settings into the object
     */
    function post_db()
    {
        translate('branch');
        $result = true;
        $this->dbuser   = $_POST['dbuser'];
        $this->dbpass   = $_POST['dbpass'];
        $this->dbhost   = $_POST['dbhost'];
        $this->dbtype   = $_POST['dbtype'];
        $this->dbport   = $_POST['dbport'];
        $this->dbprefix = $_POST['dbprefix'];

        $this->dbname = $_POST['dbname'];
        if (!PHPWS_DB::allowed($this->dbname)) {
            $this->message[] = _('This database name is not allowed.');
            $result = false;
        }

        if (empty($this->dbname)) {
            $this->message[] = _('You must type a database name.');
            $result = false;
        }

        if (empty($this->dbuser)) {
            $this->message[] = _('You must type a database user.');
            $result = false;
        }

        if (preg_match('/\W/', $this->dbprefix)) {
            $content[] = _('Table prefix must be alphanumeric characters or underscores only');
            $result = false;
        }
        translate();
        return $result;
    }

    /**
     * Grabs the current database values from the hub installation
     */
    function plugHubValues()
    {
        $dsn = & $GLOBALS['PHPWS_DB']['connection']->dsn;

        $this->dbuser = $dsn['username'];
        $this->dbpass = $dsn['password'];
        $this->dbhost = $dsn['hostspec'];
        if ($dsn['port']) {
            $this->dbport = $dsn['port'];
        } else {
            $this->dbport = null;
        }

        $this->dbprefix = PHPWS_DB::getPrefix();
        // dsn also contains dbsyntax
        $this->dbtype = $dsn['phptype'];
    }

    /**
     * Creates a new database with the dsn information
     */
    function createDB()
    {
        $dsn = $this->getDSN(false);
        if (empty($dsn)) {
            return false;
        }
        $db = & DB::connect($dsn);

        if (PEAR::isError($db)) {
            PHPWS_Error::log($db);
            return $db;
        }

        $result = $db->query('CREATE DATABASE ' . $this->dbname);
        if (PEAR::isError($result)) {
            PHPWS_Error::log($db);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Form that allows the hub admin determine which modules a 
     * branch can install.
     */
    function edit_modules()
    {
        translate('branch');
        PHPWS_Core::initCoreClass('File.php');
        $this->title = sprintf(_('Module access for "%s"'), $this->branch->branch_name);

        $content = null;

        $core_mods = PHPWS_Core::coreModList();
        $all_mods = PHPWS_File::readDirectory(PHPWS_SOURCE_DIR . 'mod/', true);
        $all_mods = array_diff($all_mods, $core_mods);

        foreach ($all_mods as $key => $module) {
            if (is_file(PHPWS_SOURCE_DIR . 'mod/' . $module . '/boost/boost.php')) {
                $dir_mods[] = $module;
            }
        }

        $db = new PHPWS_DB('branch_mod_limit');
        $db->addWhere('branch_id', $this->branch->id);
        $db->addColumn('module_name');
        $branch_mods = $db->select('col');

        unset($dir_mods[array_search('branch', $dir_mods)]);
        sort($dir_mods);
        $form = new PHPWS_Form('module_list');
        $form->useRowRepeat();

        $form->addHidden('module', 'branch');
        $form->addHidden('command', 'save_branch_modules');
        $form->addHidden('branch_id', $this->branch->id);

        $form->addCheck('module_name', $dir_mods);
        $form->setLabel('module_name', $dir_mods);
        if (!empty($branch_mods)) {
            $form->setMatch('module_name', $branch_mods);
        }

        $form->addSubmit('submit', _('Save'));

        $form->addTplTag('CHECK_ALL', javascript('check_all', array('checkbox_name' => 'module_name')));

        $template = $form->getTemplate();

        $template['DIRECTIONS'] = _('Unchecked modules cannot be installed on this branch.');

        $content = PHPWS_Template::process($template, 'branch', 'module_list.tpl');
        $this->content = & $content;
        translate();
    }

    /**
     * Lists the branches on the system
     */
    function listBranches()
    {
        translate('branch');
        $page_tags['BRANCH_NAME_LABEL'] = _('Branch name');
        $page_tags['DIRECTORY_LABEL']   = _('Directory');
        $page_tags['URL_LABEL']         = _('Url');
        $page_tags['ACTION_LABEL']      = _('Action');

        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = new DBPager('branch_sites', 'Branch');
        $pager->setModule('branch');
        $pager->setTemplate('branch_list.tpl');
        $pager->addPageTags($page_tags);
        $pager->addToggle('class="toggle1"');
        $pager->addRowTags('getTpl');
        $this->title = _('Branch List');
        $this->content = $pager->get();
        translate();
    }

    function saveBranchModules()
    {
        $db = new PHPWS_DB('branch_mod_limit');
        $db->addWhere('branch_id', (int)$_POST['branch_id']);
        $db->delete();
        $db->reset();

        if (empty($_POST['module_name']) || !is_array($_POST['module_name'])) {
            return;
        }
        
        foreach ($_POST['module_name'] as $module) {
            $db->addValue('branch_id', (int)$_POST['branch_id']);
            $db->addValue('module_name', $module);
            $result = $db->insert();
            if (PEAR::isError($result)) {
                PHPWS_Error::log($result);
                return false;
            }
            $db->reset();
        }
        return true;
    }

    function getBranches($load_db_info=false)
    {
        $db = new PHPWS_DB('branch_sites');
        $result = $db->getObjects('Branch');
        if (PEAR::isError($result) || !$load_db_info || empty($result)) {
            return $result;
        }
        foreach ($result as $branch) {
            if ($branch->loadDSN()) {
                $new_result[] = $branch;
            }
        }

        if (isset($new_result)) {
            return $new_result;
        } else {
            return $result;
        }
    }

}

?>