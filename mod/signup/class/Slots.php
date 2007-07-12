<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

class Signup_Slot {

    var $id         = 0;
    var $sheet_id   = 0;
    var $title      = null;
    var $openings   = 0;
    var $s_order    = 1;

    var $_peeps     = null;

    function Signup_Slot($id=0)
    {
        if ($id) {
            $this->id = (int)$id;
            $this->init();
        }
    }

    function getOpenings()
    {
        $db = new PHPWS_DB('');
    }

    function init()
    {
        $db = new PHPWS_DB('signup_slots');
        $result = $db->loadObject($this);
        if (PHPWS_Error::logIfError($result) || !$result) {
            $this->id = 0;
            return false;
        }
        return true;
    }

    function loadPeeps($registered=true)
    {
        PHPWS_Core::initModClass('signup', 'Peeps.php');

        $db = new PHPWS_DB('signup_peeps');
        $db->addWhere('slot_id', $this->id);
        if ($registered) {
            $db->addWhere('registered', 1);
        } else {
            $db->addWhere('registered', 0);
        }

        $db->addOrder('last_name');
        $peeps = $db->getObjects('Signup_Peep');

        if (PHPWS_Error::logIfError($peeps)) {
            return false;
        } else {
            $this->_peeps = & $peeps;
            return true;
        }
    }

    function setOpenings($openings)
    {
        $this->openings = (int)$openings;
    }

    function setSheetId($sheet_id)
    {
        if (!is_numeric($sheet_id)) {
            return false;
        } else {
            $this->sheet_id = (int)$sheet_id;
            return true;
        }
    }

    function setTitle($title)
    {
        $this->title = strip_tags($title);
    }

    function save()
    {
        if (!$this->sheet_id) {
            return PHPWS_Error::get(SU_NO_SHEET_ID, 'signup', 'Signup_Slot::save');
        }

        $db = new PHPWS_DB('signup_slots');
        if (!$this->id) {
            $db->addWhere('sheet_id', $this->sheet_id);
            $db->addColumn('s_order', 'max');
            $max = $db->select('one');
            if (PHPWS_Error::isError($max)) {
                return $max;
            }
            if ($max >= 1) {
                $this->s_order = $max + 1;
            } else {
                $this->s_order = 1;
            }
            $db->reset();
        }
        return $db->saveObject($this);
    }

    function slotLinks()
    {
        $vars['slot_id'] = $this->id;

        $total_peeps = count($this->_peeps);
        if ($total_peeps < $this->openings) {
            $vars['aop']      = 'add_slot_peep';
            $jsadd['label']   = dgettext('signup', 'Add applicant');
            $jsadd['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
            $jsadd['width'] = 350;
            $jsadd['height'] = 380;
            $links[] = javascript('open_window', $jsadd);
        }

        if (empty($this->_peeps)) {
            $vars['aop'] = 'delete_slot';
            $jsconf['QUESTION'] = dgettext('signup', 'Are you certain you want to delete this slot?');
            $jsconf['ADDRESS'] = PHPWS_Text::linkAddress('signup', $vars, true);
            $jsconf['LINK'] = dgettext('signup', 'Delete slot');
            $links[] = javascript('confirm', $jsconf);
        }

        return implode(' | ', $links);
    }

    function showPeeps()
    {
        $jsconf['QUESTION'] = dgettext('signup', 'Are you sure you want to delete this person from their signup slot?');
        $jsconf['LINK'] = dgettext('signup', 'Delete');
        $jspop['label']   = dgettext('signup', 'Edit');

        for ($i = 0; $i < $this->openings; $i++) {
            $subtpl = null;
            if (isset($this->_peeps[$i])) {
                $peep = & $this->_peeps[$i];
                $subtpl['FIRST_NAME'] = $peep->first_name;
                $subtpl['LAST_NAME'] = $peep->last_name;
                $subtpl['EMAIL'] = $peep->getEmail();
                $subtpl['PHONE'] = $peep->getPhone();

                $vars['peep_id'] = $peep->id;
                $vars['aop']     = 'edit_slot_peep';
                $jspop['address'] = PHPWS_Text::linkAddress('signup', $vars, true);

                $links[] = javascript('open_window', $jspop);

                $vars['aop']     = 'delete_slot_peep';
                $jsconf['ADDRESS'] = PHPWS_Text::linkAddress('signup', $vars, true);
                $links[] = javascript('confirm', $jsconf);

                $links[] = '<select><option>Move it</option></select>';
                $subtpl['ACTION'] = implode(' | ', $links);
            }
            if ($subtpl) {
                $tpl['peep-row'][] = $subtpl;
            }
        }

        if (!empty($tpl['peep-row'])) {
            $tpl['NAME_LABEL']   = dgettext('signup', 'Name');
            $tpl['EMAIL_LABEL']  = dgettext('signup', 'Email');
            $tpl['PHONE_LABEL']  = dgettext('signup', 'Phone');
            $tpl['ACTION_LABEL'] = dgettext('signup', 'Action');
        }

        return PHPWS_Template::process($tpl, 'signup', 'peeps.tpl');
    }

    function viewTpl()
    {
        $tpl['TITLE'] = $this->title;
        $tpl['OPENINGS'] = sprintf(dgettext('signup', 'Total openings: %s'), $this->openings);
        $this->loadPeeps();

        $left = $this->openings - count($this->_peeps);
        $tpl['LEFT'] = sprintf(dgettext('signup', 'Slots left: %s'), $left);

        $tpl['PEEPS'] = $this->showPeeps();
        $tpl['LINKS'] = $this->slotLinks();

        return $tpl;
    }

    
}
?>