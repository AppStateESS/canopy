<?php

/**
 * Controls the general user functionality of the module
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

PHPWS_Core::requireInc('webpage', 'error_defines.php');
PHPWS_Core::initModClass('webpage', 'Volume.php');

class Webpage_User {
    function main($command=NULL)
    {
        if (empty($command)) {
            if (isset($_REQUEST['wp_user'])) {
                $command = $_REQUEST['wp_user'];
            } else {
                PHPWS_Core::errorPage(404);
                exit();
            }
        }

        switch ($command) {
        case 'view':
            if (!isset($_REQUEST['id'])) {
                PHPWS_Core::errorPage(404);
                exit();
            }

            $volume = new Webpage_Volume($_GET['id']);
            $volume->loadKey();
            if (!$volume->_key->allowView()) {
                Current_User::requireLogin();
                //                PHPWS_Core::errorPage(404);
            }
            @$page = $_GET['page'];
            Layout::add($volume->view($page));
            PHPWS_Core::initModClass('menu', 'Menu.php');
            break;

        default:
            echo $command;
            break;
        }

    }

    function showFeatured()
    {
        if (isset($_REQUEST['module'])) {
            return NULL;
        }

        $db = new PHPWS_DB('webpage_featured');
        $db->addColumn('webpage_volume.*');
        $db->addWhere('webpage_featured.id', 'webpage_volume.id');
        $db->addOrder('webpage_featured.vol_order');
        $result = $db->getObjects('webpage_volume');
        if (empty($result)) {
            return null;
        } elseif (PEAR::isError($result)) {
            PHPWS_Error::log($result);
        } else {
            foreach ($result as $volume) {
                $key = new Key($volume->key_id);
                if (!$key->allowView()) {
                    continue;
                }
                $tpl['TITLE'] = $volume->getTitle();
                $tpl['SUMMARY'] = $volume->getSummary();

                if (Current_User::allow('webpage', 'featured') && Current_User::isUnrestricted('users')) {
                    $vars['volume_id'] = $volume->id;
                    $vars['wp_admin'] = 'drop_feature';
                    $links[1] = PHPWS_Text::secureLink(_('Drop'), 'webpage', $vars);

                    $vars['wp_admin'] = 'up_feature';
                    $links[2] = PHPWS_Text::secureLink(_('Up'), 'webpage', $vars);

                    $vars['wp_admin'] = 'down_feature';
                    $links[3] = PHPWS_Text::secureLink(_('Down'), 'webpage', $vars);

                    $tpl['LINKS'] = implode(' | ', $links);
                }
                $template['volume'][] = $tpl;
            }
        }
        $template['FEATURED_TITLE'] = _('Featured pages');


        $content = PHPWS_Template::process($template, 'webpage', 'featured.tpl');
        Layout::add($content, 'webpage', 'featured');
    }

    function showFrontPage()
    {
        if (isset($_REQUEST['module'])) {
            return NULL;
        }

        PHPWS_Core::initModClass('webpage', 'Volume.php');

        $db = new PHPWS_DB('webpage_volume');
        $db->addWhere('frontpage', 1);
        $db->addWhere('approved', 1);
        Key::restrictView($db, 'webpage');
        $result = $db->getObjects('Webpage_Volume');

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return NULL;
        }

        if (empty($result)) {
            return NULL;
        }

        foreach ($result as $volume) {
            $volume->loadPages();
            Layout::add($volume->view(), 'webpage', 'page_view', TRUE);
        }
    }
}


?>