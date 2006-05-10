<?php

  /**
   * User instructions
   * 
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

PHPWS_Core::requireConfig('search');
class Search_User {

    function main()
    {
        if (!isset($_REQUEST['user'])) {
            PHPWS_Core::errorPage('404');
        }

        $command = $_REQUEST['user'];

        switch ($command) {
        case 'search':
            Search_User::searchPost();
            break;

        default:
            PHPWS_Core::errorPage('404');
            break;
        }
    }

    function searchBox()
    {
        if (SEARCH_DEFAULT) {
            $onclick = sprintf('onclick="if(this.value == \'%s\')this.value = \'\';"',
                               SEARCH_DEFAULT);
        }

        $form = & new PHPWS_Form('search_box');
        $form->setMethod('get');
        $form->addHidden('module', 'search');
        $form->addHidden('user', 'search');
        $form->addText('search', SEARCH_DEFAULT);
        $form->setLabel('search', _('Search'));

        if (isset($onclick)) {
            $form->setExtra('search', $onclick);
        }
        $form->addSubmit('go', _('Search'));

        $mod_list = Search_User::getModList();

        $form->addSelect('mod_title', $mod_list);
        
        $key = Key::getCurrent();

        if (!empty($key) && !$key->isDummy()) {
            $form->setMatch('mod_title', $key->module);
        } elseif (isset($_REQUEST['mod_title'])) {
            $form->setMatch('mod_title', $_REQUEST['mod_title']);
        }

        $template = $form->getTemplate();

        $content = PHPWS_Template::process($template, 'search', 'search_box.tpl');
        Layout::add($content, 'search', 'search_box');
    }

    function getModList()
    {
        $result = Key::modulesInUse();

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $result = NULL;
        }

        $mod_list = array('all'=> _('All modules'));

        if (!empty($result)) {
            $mod_list = array_merge($mod_list, $result);
        }

        return $mod_list;
    }

    function sendToAlternate($alternate, $search_phrase)
    {
        $file = PHPWS_Core::getConfigFile('search', 'alternate.php');
        if (!$file) {
            PHPWS_Core::errorPage();
            exit();
        }

        include($file);

        if (!isset($alternate_search_engine) || !is_array($alternate_search_engine) ||
            !isset($alternate_search_engine[$alternate])) {
            PHPWS_Core::errorPage();
            exit();
        }

        $gosite = &$alternate_search_engine[$alternate];

        $query_string = str_replace(' ', '+', $search_phrase);

        $site = urlencode(PHPWS_Core::getHomeHttp(FALSE, FALSE, FALSE));
        $url = sprintf($gosite['url'], $query_string, $site);

        header('location: ' . $url);
        exit();
    }

    function searchPost()
    {
        if (!isset($_REQUEST['search'])) {
            $search_phrase = NULL;
        } else {
            $search_phrase = Search::filterWords($_REQUEST['search']);
        }

        if (isset($_REQUEST['alternate']) && $_REQUEST['alternate'] != 'local') {
            Search_User::sendToAlternate($_REQUEST['alternate'], $search_phrase);
            exit();
        }

        $form = & new PHPWS_Form('search_box');
        $form->setMethod('get');
        $form->addHidden('module', 'search');
        $form->addHidden('user', 'search');
        $form->addSubmit(_('Search'));
        $form->addText('search', $search_phrase);
        $form->setSize('search', 40);
        $form->setLabel('search', _('Search for:'));

        $form->addCheck('exact_only', 1);
        $form->setLabel('exact_only', _('Exact matches only'));
        if (isset($_REQUEST['exact_only'])) {
            $exact_match = TRUE;
            $form->setMatch('exact_only', 1);
        } else {
            $exact_match = FALSE;
        }

        $mod_list = Search_User::getModList();
        $form->addSelect('mod_title', $mod_list);
        $form->setLabel('mod_title', _('Module list'));
        if (isset($_REQUEST['mod_title'])) {
            $form->setMatch('mod_title', $_REQUEST['mod_title']); 
        }

        $file = PHPWS_Core::getConfigFile('search', 'alternate.php');
        if ($file) {
            include($file);
            
            if (!empty($alternate_search_engine) && is_array($alternate_search_engine)) {
                $alternate_sites['local'] = _('Local');
                foreach ($alternate_search_engine as $title=>$altSite) {
                    $alternate_sites[$title] = $altSite['title'];
                }

                $form->addRadio('alternate', array_keys($alternate_sites));
                $form->setLabel('alternate', $alternate_sites);
                $form->setMatch('alternate', 'local');
            }
        }
        
        $template = $form->getTemplate();

        if (isset($_REQUEST['mod_title']) && $_REQUEST['mod_title'] != 'all') {
            $module = preg_replace('/\W/', '', $_REQUEST['mod_title']);
        } else {
            $module = NULL;
        }

        $template['SEARCH_LOCATION'] = _('Search location');
        $template['ADVANCED_LABEL'] = _('Advanced Search');

        $result = Search_User::getResults($search_phrase, $module, $exact_match);

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $template['SEARCH_RESULTS'] = _('A problem occurred during your search.');
        } elseif (empty($result)) {
            $template['SEARCH_RESULTS'] = _('No results found.');
        } else {
            $template['SEARCH_RESULTS'] = $result;
        }

        $template['SEARCH_RESULTS_LABEL'] = _('Search Results');

        $content = PHPWS_Template::process($template, 'search', 'search_page.tpl');

        Layout::add($content);
    }

    function getIgnore()
    {
        $db = & new PHPWS_DB('search_stats');
        $db->addWhere('ignored', 1);
        $db->addColumn('keyword');
        return $db->select('col');
    }

    function getResults($phrase, $module=NULL, $exact_match=FALSE)
    {
        PHPWS_Core::initModClass('search', 'Stats.php');

        $pageTags = array();
        $pageTags['MODULE_LABEL'] = _('Module');
        $pageTags['TITLE_LABEL']    = _('Title');

        $ignore = Search_User::getIgnore();
        if (PEAR::isError($ignore)) {
            PHPWS_Error::log($ignore);
            $ignore = NULL;
        }

        if (empty($phrase)) {
            return FALSE;
        }

        $words = explode(' ', $phrase);

        if (!empty($ignore)) {
            $words_removed = array_intersect($words, $ignore);

            if (!empty($words_removed)) {
                $pageTags['REMOVED_LABEL'] = _('The following search words were ignored');
                $pageTags['IGNORED_WORDS'] = implode(' ', $words_removed);
                foreach ($words_removed as $remove) {
                    $key = array_search($remove, $words);
                    unset($words[$key]);
                }
            }
        }

        if (empty($words)) {
            return FALSE;
        }

        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = & new DBPager('phpws_key', 'Key');
        $pager->setModule('search');
        $pager->setTemplate('search_results.tpl');
        $pager->addToggle('class="bgcolor1"');
        $pager->addRowTags('getTplTags');
        $pager->addPageTags($pageTags);

        foreach ($words as $keyword) {
            if (strlen($keyword) < SEARCH_MIN_WORD_LENGTH) {
                continue;
            }

            if ($exact_match) {
                $keyword = "%$keyword %";
            } else {
                $keyword = "%$keyword%";
            }

            $pager->addWhere('search.keywords', $keyword, 'like', 'or', 1);
        }

        $pager->setEmptyMessage(_('Nothing found'));
        $pager->db->setGroupConj(1, 'AND');

        if ($module) {
            $pager->addWhere('search.module', $module);
            Key::restrictView($pager->db, $module);
        } else {
            Key::restrictView($pager->db);
        }

        $result = $pager->get();

        Search_Stats::record($words, $pager->total_rows, $exact_match);

        return $result;
    }


}

?>