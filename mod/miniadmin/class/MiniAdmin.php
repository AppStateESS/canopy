<?php

  /**
   * Simple class to add a module's administrator commands to a box
   *
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

PHPWS_Core::requireConfig('miniadmin');

if (!defined('MINIADMIN_TEMPLATE')) {
    define('MINIADMIN_TEMPLATE', 'mini_admin.tpl');
 }

class MiniAdmin {
    function add($module, $links)
    {
        if (is_array($links)) {
            foreach ($links as $link) {
                MiniAdmin::add($module, $link);
            }
            return true;
        }

        $GLOBALS['MiniAdmin'][$module][] = $links;
        return true;
    }

    function get()
    {
        $modlist = PHPWS_Core::getModuleNames();

        if (!isset($GLOBALS['MiniAdmin'])) {
            return NULL;
        }

        $oTpl = new PHPWS_Template('miniadmin');
        $oTpl->setFile(MINIADMIN_TEMPLATE);

        $tpl['MINIADMIN_TITLE'] = dgettext('miniadmin', 'MiniAdmin');
        foreach ($GLOBALS['MiniAdmin'] as $module => $links) {
            if (!isset($modlist[$module])) {
                continue;
            }
            foreach ($links as $link) {
                $oTpl->setCurrentBlock('links');
                $oTpl->setData(array('LINE_MODULE' => $modlist[$module],
                                     'ADMIN_LINK' => PHPWS_Text::fixAmpersand($link)));
                $oTpl->parseCurrentBlock();
            }
            $oTpl->setCurrentBlock('module');
            $oTpl->setData(array('MODULE' => $modlist[$module]));
            $oTpl->parseCurrentBlock();
        }
        $oTpl->setData($tpl);
        $content = $oTpl->get();
        
        Layout::set($content, 'users', 'mini_admin');
    }
}

?>