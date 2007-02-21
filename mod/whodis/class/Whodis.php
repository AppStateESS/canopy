<?php
  /**
   * @version $Id$
   * @author Matthew McNaney <mcnaney at appstate dot edu>
   */

class Whodis {

    function record()
    {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referrer = & $_SERVER['HTTP_REFERER'];
            if (Whodis::passFilters($referrer)) {
                PHPWS_Core::initModClass('whodis', 'Whodis_Referrer.php');
                
                $whodis = new Whodis_Referrer;
                $result = $whodis->save($referrer);
                if (PEAR::isError($result)) {
                    PHPWS_Error::log($result);
                }
            }
        }
    }

    function passFilters(&$referrer)
    {
        $home_url = PHPWS_Core::getHomeHttp();
        $preg_match = str_replace('/', '\/', ($home_url));
        
        if (preg_match('/^' . $preg_match . '/', $referrer)) {
            return false;
        }

        $db = new PHPWS_DB('whodis_filters');
        $db->addColumn('filter');
        $filters = $db->select('col');

        if (empty($filters)) {
            return true;
        } elseif (PEAR::isError($filters)) {
            PHPWS_Error::log($filters);
            return true;
        }

        foreach ($filters as $flt) {
            if (preg_match("/$flt/", $referrer)) {
                return false;
            }
        }
        return true;
    }

    function purge()
    {
        $db = new PHPWS_DB('whodis');
        $go = false;
        if (!empty($_POST['days_old'])) {
            $days = (int)$_POST['days_old'];
            $updated = mktime() - (86400 * $days);
            $db->addWhere('updated', $updated, '<', null, 1);
            $go = true;
        }

        if (!empty($_POST['visit_limit'])) {
            $db->addWhere('visits', (int)$_POST['visit_limit'], '<=', 'and', 1);
            $go = true;
        }

        if (isset($_POST['delete_checked']) && !empty($_POST['referrer'])) {
            if(is_array($_POST['referrer'])) {
                $db->addWhere('id', $_POST['referrer'], 'in', 'or', 2);
                $db->setGroupConj(2, 'or');
            }
            $go = true;
        }

        if (!$go) {
            return false;
        }

        return $db->delete();
    }

    function admin()
    {
        if (!Current_User::allow('whodis')) {
            Current_User::disallow();
        }

        translate('whodis');
        if (isset($_REQUEST['op'])) {
            switch ($_REQUEST['op']) {
            case 'purge':
                Whodis::purge();
                PHPWS_Core::goBack();
                break;

            case 'filters':
                Whodis::filters();
                break;

            case 'filters_option':
                Whodis::filterOption();
                PHPWS_Core::goBack();
                break;

            case 'list':
            default:
                Whodis::listReferrers();
            }
        } else {
            Whodis::listReferrers();
        }
    }

    function filterOption()
    {
        if (isset($_POST['add_filter_button']) && !empty($_POST['add_filter'])) {
            $filter = preg_replace('/[^\w\.-\s]/', '', strip_tags($_POST['add_filter']));
            if (!empty($filter)) {
                $db = new PHPWS_DB('whodis_filters');
                $db->addValue('filter', $filter);
                $result = $db->insert();
                if (PEAR::isError($result)) {
                    PHPWS_Error::log($result);
                }
            }
        } elseif (isset($_POST['delete_checked'])) {
            if (!empty($_POST['filter_pick']) && is_array($_POST['filter_pick'])) {
                $db = new PHPWS_DB('whodis_filters');
                $db->addWhere('id', $_POST['filter_pick']);
                $result = $db->delete();
                if (PEAR::isError($result)) {
                    PHPWS_Error::log($result);
                }
            }
        }
    }

    function filters()
    {
        PHPWS_Core::initCoreClass('DBPager.php');

        $form = new PHPWS_Form('filter');
        $form->addHidden('module', 'whodis');
        $form->addHidden('op', 'filters_option');
        $form->addText('add_filter');
        $form->setSize('add_filter', 30, 60);
        $form->addSubmit('add_filter_button', _('Add filter'));
        $form->addSubmit('delete_checked', _('Delete checked'));

        $page_tags = $form->getTemplate();

        $page_tags['CHECK_ALL'] = javascript('check_all', array('checkbox_name'=>'filter_pick[]'));
        $pager = new DBPager('whodis_filters');
        $pager->setModule('whodis');
        $pager->setTemplate('filter.tpl');
        $pager->setSearch('filter');

        $vars['op'] = 'list';
        $links[] = PHPWS_Text::moduleLink(_('Referrers'), 'whodis', $vars);

        $vars['op'] = 'filters';
        $links[] = PHPWS_Text::moduleLink(_('Filters'), 'whodis', $vars);

        $page_tags['ADMIN_LINKS']  = implode(' | ', $links);
        $page_tags['FILTER_LABEL'] = _('Filters');

        $limits[4]  = 10;
        $limits[9]  = 25;
        $limits[16] = 50;
        $pager->setLimitList($limits);
	$pager->setDefaultLimit(25);
        $pager->addRowFunction(array('Whodis', 'checkbox'));

        $pager->addPageTags($page_tags);
        $pager->setDefaultOrder('filter');
        $content = $pager->get();

        Layout::add(PHPWS_Controlpanel::display($content));
        
    }

    function checkbox($values)
    {
        return array('FILTER_PICK' => sprintf('<input type="checkbox" name="filter_pick[]" value="%s" />', $values['id']));
    }

    function listReferrers()
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        PHPWS_Core::initModClass('whodis', 'Whodis_Referrer.php');

        $form = new PHPWS_Form('purge');
        $form->addHidden('module', 'whodis');
        $form->addHidden('op', 'purge');
        $days = array(0     => _('- Referrer age -'),
                      1     => _('1 day old'),
                      3     => _('3 days old'),
                      7     => _('1 week old'),
                      14    => _('2 weeks old'),
                      30    => _('1 month old'),
                      90    => _('3 months old'),
                      365   => _('1 year old'),
                      'all' => _('Everything'));

        $form->addSelect('days_old', $days);

        $form->addText('visit_limit');
        $form->setSize('visit_limit', 4, 4);
        $form->addSubmit(_('Purge'));
        $form->setLabel('visit_limit', _('Visits'));
        $form->addSubmit('delete_checked', _('Delete checked'));

        $page_tags = $form->getTemplate();

        $page_tags['CHECK_ALL'] = javascript('check_all', array('checkbox_name'=>'referrer[]'));

        $pager = new DBPager('whodis', 'Whodis_Referrer');
        $pager->setModule('whodis');
        $pager->setTemplate('admin.tpl');
        $pager->setSearch('url');

        $vars['op'] = 'list';
        $links[] = PHPWS_Text::moduleLink(_('Referrers'), 'whodis', $vars);

        $vars['op'] = 'filters';
        $links[] = PHPWS_Text::moduleLink(_('Filters'), 'whodis', $vars);
        $page_tags['ADMIN_LINKS']   = implode(' | ', $links);

        $page_tags['URL_LABEL']     = _('Referrer');
        $page_tags['CREATED_LABEL'] = _('First visit');
        $page_tags['UPDATED_LABEL'] = _('Last visit');
        $page_tags['VISITS_LABEL']  = _('Total visits');

        $limits[4]  = 10;
        $limits[9]  = 25;
        $limits[16] = 50;
        $pager->setLimitList($limits);
	$pager->setDefaultLimit(25);

        $pager->addPageTags($page_tags);
        $pager->addRowTags('getTags');
        $pager->setOrder('updated', 'desc', true);
        $content = $pager->get();
        Layout::add(PHPWS_Controlpanel::display($content));
    }

}
?>