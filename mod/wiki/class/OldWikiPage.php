<?php

/**
 * Wiki for phpWebSite
 *
 * See docs/CREDITS for copyright information
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package Wiki
 * @author Greg Meiste <greg.meiste+github@gmail.com>
 */

class OldWikiPage
{
    var $id          = 0;
    var $source_id   = 0;
    var $editor_id   = 0;
    var $title       = NULL;
    var $updated     = 0;
    var $pagetext    = NULL;
    var $comment     = NULL;
    var $vr_number   = 0;
    var $vr_current  = 0;


    function OldWikiPage($id=NULL)
    {
        if (isset($id) && is_numeric($id))
        {
            $this->setId($id);
            $db = new PHPWS_DB('wiki_pages_version');
            PHPWS_Error::logIfError($db->loadObject($this));
        }
    }

    function setId($id)
    {
        $this->id = (int)$id;
    }

    function getId()
    {
        return $this->id;
    }

    function getSourceId()
    {
        return $this->source_id;
    }

    function getEditorId()
    {
        return $this->editor_id;
    }

    function getEditor()
    {
        $db = new PHPWS_DB('users');
        $db->addWhere('id', $this->getEditorId());
        $db->addColumn('display_name');
        $result = $db->select('col');
        if (PHPWS_Error::logIfError($result))
        {
            return dgettext('wiki', 'N/A');
        }

        return $result[0];
    }

    function getTitle($format=TRUE)
    {
        if ($format)
        {
            return WikiManager::formatTitle($this->title);
        }

        return $this->title;
    }

    function getUpdated($format=WIKI_DATE_FORMAT)
    {
        return strftime($format, PHPWS_Time::getUserTime($this->updated));
    }

    function getPagetext($transform=TRUE)
    {
        if ($transform)
        {
            return WikiManager::transform($this->pagetext);
        }

        return $this->pagetext;
    }

    function getComment()
    {
        return PHPWS_Text::parseOutput($this->comment);
    }

    function getVrNumber()
    {
        return $this->vr_number;
    }

    function getVrCurrent()
    {
        return $this->vr_current;
    }


    function getAllowEdit()
    {
        $db = new PHPWS_DB('wiki_pages');
        $db->addWhere('id', $this->getSourceId());
        $db->addColumn('allow_edit');
        $result = $db->select('col');
        if (PHPWS_Error::logIfError($result))
        {
            return 0;
        }

        return $result[0];
    }

    function menu()
    {
        $links = NULL;

        if ((Current_User::allow('wiki', 'edit_page') ||
            (PHPWS_Settings::get('wiki', 'allow_page_edit') && Current_User::isLogged())) &&
            $this->getAllowEdit() && !$this->getVrCurrent())
        {
            $links .= PHPWS_Template::process(array('LINK'=>PHPWS_Text::secureLink(dgettext('wiki', 'Restore'), 'wiki',
                      array('page_op'=>'restore', 'page'=>$this->getTitle(FALSE), 'id'=>$this->getId()))),
                      'wiki', 'menu_item.tpl');
        }

        if (Current_User::allow('wiki', 'delete_page') && $this->getAllowEdit() && !$this->getVrCurrent())
        {
            $js_var['ADDRESS'] = PHPWS_Text::linkAddress('wiki', array('page_op'=>'removeold',
                                 'page'=>$this->getTitle(FALSE), 'id'=>$this->getId()), TRUE);
            $js_var['QUESTION'] = dgettext('wiki', 'Are you sure you want to remove this page revision?');
            $js_var['LINK'] = dgettext('wiki', 'Remove');
            $links .= PHPWS_Template::process(array('LINK'=>(Layout::getJavascript('confirm', $js_var))),
                      'wiki', 'menu_item.tpl');
        }

        $links .= PHPWS_Template::process(array('LINK'=>PHPWS_Text::moduleLink(dgettext('wiki', 'History'), 'wiki',
                  array('page_op'=>'history', 'page_id'=>$this->getSourceId()))), 'wiki', 'menu_item.tpl');

        $links .= PHPWS_Template::process(array('LINK'=>PHPWS_Text::moduleLink(dgettext('wiki', 'Back to Page'), 'wiki',
                  array('page'=>$this->getTitle(FALSE)))), 'wiki', 'menu_item.tpl');

        return $links;
    }

    function getDiffOptions()
    {
        $links = array();

        if (!$this->getVrCurrent())
        {
            $db = new PHPWS_DB('wiki_pages_version');
            $db->addWhere('source_id', $this->getSourceId());
            $links[] = PHPWS_Text::moduleLink(dgettext('wiki', 'Current'), 'wiki', array('page'=>$this->getTitle(FALSE),
            'page_op'=>'compare', 'oVer'=>$this->getVrNumber(), 'nVer'=>$db->count()));
        }

        $db2 = new PHPWS_DB('wiki_pages_version');
        $db2->addWhere('source_id', $this->getSourceId());
        $db2->addColumn('vr_number', 'min');
        if ($this->getVrNumber() != $db2->select('min'))
        {
            $links[] = PHPWS_Text::moduleLink(dgettext('wiki', 'Previous'), 'wiki', array('page'=>$this->getTitle(FALSE),
                       'page_op'=>'compare', 'oVer'=>($this->getVrNumber()-1), 'nVer'=>$this->getVrNumber()));
        }

        return implode(' | ', $links);
    }

    function view()
    {
        $tags = array();
        $tags['MENU'] = $this->menu();
        $tags['PAGETEXT'] = $this->getPagetext();
        $tags['MESSAGE'] = sprintf(dgettext('wiki', 'Revision as of %s'), $this->getUpdated());

        if (PHPWS_Settings::get('wiki', 'show_modified_info'))
        {
            $editor = $this->getEditor();
            if (Current_User::isLogged() && (Current_User::getId() != $this->getEditorId()))
            {
                PHPWS_Core::initModClass('notes', 'My_Page.php');
                PHPWS_Core::initModClass('notes', 'Note_Item.php');
                $editor = str_replace(dgettext('wiki', 'Send note'), $editor, Note_Item::sendLink($this->getEditorId()));
            }

            $tags['UPDATED_INFO'] = sprintf(dgettext('wiki', 'Last modified %1$s by %2$s'), $this->getUpdated(), $editor);
        }

        if (PHPWS_Settings::get('wiki', 'add_to_title'))
        {
            Layout::addPageTitle($this->getTitle());
        }

        return PHPWS_Template::process($tags, 'wiki', 'view.tpl');
    }

    function restore($hits)
    {
        if (!((Current_User::authorized('wiki', 'edit_page') ||
            (PHPWS_Settings::get('wiki', 'allow_page_edit') && Current_User::isLogged())) &&
            $this->getAllowEdit() && !$this->getVrCurrent()))
        {
            Current_User::disallow(dgettext('wiki', 'User attempted to restore previous page version.'));
            return;
        }

        PHPWS_Core::initModClass('version', 'Version.php');
        $version = new Version('wiki_pages', $this->getId());
        $version->source_data['hits'] = $hits;
        $version->source_data['comment'] = '[' . dgettext('wiki', 'Restored') . ']';
        $version->restore();

        WikiManager::sendMessage(dgettext('wiki', 'Page Restored'), array('page'=>$this->getTitle(FALSE)), FALSE);
    }

    function remove()
    {
        if (!(Current_User::authorized('wiki', 'delete_page') && $this->getAllowEdit() && !$this->getVrCurrent()))
        {
            Current_User::disallow(dgettext('wiki', 'User attempted to remove previous page version.'));
            return;
        }

        PHPWS_Core::initModClass('version', 'Version.php');
        $version = new Version('wiki_pages', $this->getId());
        $version->delete(FALSE);

        WikiManager::sendMessage(dgettext('wiki', 'Old revision removed'), array('page'=>$this->getTitle(FALSE)), FALSE);
    }

    function getHistoryTpl()
    {
        $vars['page'] = $this->getTitle(FALSE);
        $vars['page_op'] = 'viewold';
        $vars['id'] = $this->getId();
        $links[] = PHPWS_Text::moduleLink(dgettext('wiki', 'View'), 'wiki', $vars);

        if ((Current_User::allow('wiki', 'edit_page') ||
            (PHPWS_Settings::get('wiki', 'allow_page_edit') && Current_User::isLogged())) &&
            $this->getAllowEdit() && !$this->getVrCurrent())
        {
            $vars['page_op'] = 'restore';
            $links[] = PHPWS_Text::secureLink(dgettext('wiki', 'Restore'), 'wiki', $vars);
        }

        if (Current_User::allow('wiki', 'delete_page') && $this->getAllowEdit() && !$this->getVrCurrent())
        {
            $vars['page_op'] = 'removeold';
            $js_var['ADDRESS'] = PHPWS_Text::linkAddress('wiki', $vars, TRUE);
            $js_var['QUESTION'] = dgettext('wiki', 'Are you sure you want to remove this page revision?');
            $js_var['LINK'] = dgettext('wiki', 'Remove');
            $links[] = Layout::getJavascript('confirm', $js_var);
        }

        $template['ACTIONS']  = implode(' | ', $links);
        $template['VERSION']  = $this->getVrNumber();
        $template['UPDATED']  = $this->getUpdated();
        $template['EDITOR']   = $this->getEditor();
        $template['COMMENT']  = $this->getComment() . '';
        $template['DIFF']     = $this->getDiffOptions();

        return $template;
    }

    function getRecentChangesTpl()
    {
        $db = new PHPWS_DB('wiki_pages_version');
        $db->addWhere('source_id', $this->getSourceId());
        $db->addColumn('vr_number', 'min');
        if ($this->getVrNumber() != $db->select('min'))
        {
            $links[] = PHPWS_Text::moduleLink(dgettext('wiki', 'Diff'), 'wiki', array('page'=>$this->getTitle(FALSE),
                       'page_op'=>'compare', 'oVer'=>($this->getVrNumber()-1), 'nVer'=>$this->getVrNumber()));
        }

        $links[] = PHPWS_Text::moduleLink(dgettext('wiki', 'History'), 'wiki',
                                          array('page_op'=>'history', 'page_id'=>$this->getSourceId()));

        $template['VIEW']     = implode(' | ', $links);
        $template['PAGE']     = PHPWS_Text::moduleLink($this->getTitle(), 'wiki', array('page'=>$this->getTitle(FALSE)));
        $template['UPDATED']  = $this->getUpdated();
        $template['EDITOR']   = $this->getEditor();
        $template['COMMENT']  = $this->getComment() . '';

        return $template;
    }
}

?>