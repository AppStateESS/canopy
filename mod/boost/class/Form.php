<?php
  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

class Boost_Form {

    function boostTab(&$panel){
        if (!isset($_REQUEST['tab']))
            return $panel->getCurrentTab();
        else
            return $_REQUEST['tab'];
    }

    function setTabs(&$panel){
        $link = _('index.php?module=boost&amp;action=admin');
    
        $core_links['title'] = _('Core Modules');
        $other_links['title'] = _('Other Modules');

        $other_links['link'] = $core_links['link']  = $link;

        $tabs['core_mods'] = $core_links;
        $tabs['other_mods'] = $other_links;

        $panel->quickSetTabs($tabs);
    }

    function listModules($type){
        Layout::addStyle('boost');
        PHPWS_Core::initCoreClass('Module.php');
        PHPWS_Core::initCoreClass('Text.php');
        PHPWS_Core::initCoreClass('File.php');
        PHPWS_Core::initModClass('boost', 'Boost.php');

        $allow_update = TRUE;

        $dir_content = array();
        if (!PHPWS_Boost::checkDirectories($dir_content)) {
            $tpl['DIRECTORIES'] = implode('<br />', $dir_content);
            $allow_update = FALSE;
        }

        $core_mods      = PHPWS_Core::coreModList();
        $installed_mods = PHPWS_Core::installModList();

        if (PHPWS_Core::isBranch()) {
            $branch_mods = Branch::getBranchMods();
            if (empty($branch_mods)) {
                $dir_mods = array();
            } else {
                $dir_mods = $branch_mods;
            }
        } else {
            $dir_mods = PHPWS_Boost::getAllMods();
        }

        $dir_mods = array_diff($dir_mods, $core_mods);

        if ($type == 'core_mods') {
            $allowUninstall = FALSE;
            $modList = $core_mods;

            $core_file = new PHPWS_Module('core');
            $core_db   = new PHPWS_Module('core', false);

            $template['TITLE']   = $core_db->proper_name;
            $template['VERSION'] = $core_db->version;

            if (isset($_SESSION['Boost_Needs_Update']['core'])) {
                $link_title = $_SESSION['Boost_Needs_Update']['core'];
                if (version_compare($core_file->version, $_SESSION['Boost_Needs_Update']['core'], '<')) {
                    $link_title = sprintf(_('%s - New'), $link_title);
                }
            } else {
                $link_title = _('Check');
            }


            $link_command['opmod'] = 'core';            
            $link_command['action'] = 'check';
            $template['LATEST'] = PHPWS_Text::secureLink($link_title, 'boost', $link_command);

            if (version_compare($core_db->version, $core_file->version, '<')) {
                if ($core_file->checkDependency()) {
                    $link_command['action'] = 'update_core';
                    $core_links[] = PHPWS_Text::secureLink(_('Update'), 'boost', $link_command);
                } else {
                    $link_command['action'] = 'show_dependency';
                    $core_links[] = PHPWS_Text::secureLink(_('Missing dependency'), 'boost', $link_command);
                }

                $template['VERSION'] =sprintf('%s &gt; %s', $core_db->version, $core_file->version); 
                $template['COMMAND'] = implode(' | ', $core_links);
            } else {
                $template['COMMAND'] = _('None');
            }


            $template['ROW']     = 1;
            $tpl['mod-row'][] = $template;
        } else {
            $allowUninstall = TRUE;
            $modList = $dir_mods;
        }

        $tpl['TITLE_LABEL']   = _('Module Title');
        $tpl['COMMAND_LABEL'] = ('Commands');
        $tpl['ABOUT_LABEL']   = _('More information');
        $tpl['VERSION_LABEL'] = _('Current version');
        
        if ($type == 'core_mods' && Current_User::isDeity() && DEITIES_CAN_UNINSTALL) {
            $tpl['WARNING'] = _('WARNING: Only deities can uninstall core modules. Doing so may corrupt your installation!');
        }

        if (empty($modList)) {
            return _('No modules available.');
        }

        sort($modList);
        $count = 1;

        foreach ($modList as $title) {
            $links = array();
            $template = $link_command = NULL;
            $link_command['opmod'] = $title;

            $mod = new PHPWS_Module($title);

            if (!$mod->isFullMod()) {
                continue;
            }
            $proper_name = $mod->getProperName();
            if (!isset($proper_name)) {
                $proper_name = $title;
            }

            $template['VERSION'] = $mod->version;
            $template['TITLE'] = $proper_name;
            $template['ROW'] = ($count % 2) + 1;

            $version_check = $mod->getVersionHttp();
            
            if (isset($version_check)){
                if (isset($_SESSION['Boost_Needs_Update'][$mod->title])) {
                    $link_title = $_SESSION['Boost_Needs_Update'][$mod->title];
                    if (version_compare($mod->version, $_SESSION['Boost_Needs_Update'][$mod->title], '<')) {
                        $link_title = sprintf(_('%s - New'), $link_title);
                    }
                } else {
                    $link_title = _('Check');
                }
                
                $link_command['action'] = 'check';
                $template['LATEST'] = PHPWS_Text::secureLink($link_title, 'boost', $link_command);
            }

            if (!$mod->isInstalled()) {
                if ($mod->checkDependency()) {
                    $link_title = _('Install');
                    $link_command['action'] = 'install';
                } else {
                    $link_title = _('Missing dependency');
                    $link_command['action'] = 'show_dependency';
                }
                $links[] = PHPWS_Text::secureLink($link_title, 'boost', $link_command);
            } else {
                if ($mod->needsUpdate()) {
                    $db_mod = new PHPWS_Module($mod->title, false);
                    $template['VERSION'] = $db_mod->version . ' &gt; ' . $mod->version;
                    if ($mod->checkDependency()) {
                        if ($title == 'boost') {
                            $tpl['WARNING'] = _('Boost requires updating! You should do so before any other module!');
                        }
                        $link_title = _('Update');
                        $link_command['action'] = 'update';
                    } else {
                        $link_title = _('Missing dependency');
                        $link_command['action'] = 'show_dependency';
                    }
                    $links[] = PHPWS_Text::secureLink($link_title, 'boost', $link_command);
                }

                if ($type != 'core_mods' || Current_User::isDeity() && DEITIES_CAN_UNINSTALL) {
                    if ($dependents = $mod->isDependedUpon()) {
                        $link_command['action'] = 'show_depended_upon';
                        $depend_warning = sprintf(_('This module is depended upon by: %s'), implode(', ', $dependents));
                        $links[] = PHPWS_Text::secureLink(_('Depended upon'), 'boost', $link_command, NULL, $depend_warning);
                    } else {
                        $uninstallVars = array('opmod'=>$title, 'action'=>'uninstall');
                        $js['QUESTION'] = _('Are you sure you want to uninstall this module? All data will be deleted.');
                        $js['ADDRESS'] = PHPWS_Text::linkAddress('boost', $uninstallVars, TRUE);
                        $js['LINK'] = _('Uninstall');
                        $links[] = javascript('confirm', $js);
                    }
                }
            }

            if ($mod->isAbout()){
                $address = PHPWS_Text::linkAddress('boost',
                                                   array('action' => 'aboutView', 'aboutmod'=> $mod->title),
                                                   true);
                $aboutView = array('label'=>_('About'), 'address'=>$address);
                $template['ABOUT'] = Layout::getJavascript('open_window', $aboutView);
            }

            if (!empty($links)) {
                $template['COMMAND'] = implode(' | ', $links);
            } else {
                $template['COMMAND'] = _('None');
            }

            $tpl['mod-row'][] = $template;
            $count++;
        }

        $tpl['CHECK_FOR_UPDATES'] = PHPWS_Text::secureLink(_('Check all'), 'boost',
                                                           array('action' => 'check_all', 'tab' => $type));
        $tpl['LATEST_LABEL'] = _('Latest version');
        $result = PHPWS_Template::process($tpl, 'boost', 'module_list.tpl');
        return $result;
    }
}

?>