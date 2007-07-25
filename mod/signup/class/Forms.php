<?php
  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

class Signup_Forms {
    var $signup = null;
    function get($type)
    {
        switch ($type) {
        case 'new':
        case 'edit_sheet':
            if (empty($this->signup->sheet)) {
                $this->signup->loadSheet();
            }
            $this->editSheet();
            break;

        case 'list':
            $this->signup->panel->setCurrentTab('list');
            $this->listSignup();
            break;

        case 'edit_slots':
            $this->editSlots();
            break;

        case 'edit_peep':
            $this->editPeep();
            break;

        case 'edit_slot_popup':
            $this->editSlotPopup();
            break;

        case 'user_signup':
            $this->userSignup();
            break;

        case 'report':
            $this->report();
            break;

        }

    }

    function editPeep()
    {
        $peep = & $this->signup->peep;

        $form = new PHPWS_Form;
        $form->addHidden('module', 'signup');
        $form->addHidden('aop', 'post_peep');

        if ($peep->id) {
            $form->addSubmit(dgettext('signup', 'Update'));
            $form->addHidden('peep_id', $peep->id);
            $this->signup->title = dgettext('signup', 'Update applicant');
        } else {
            $form->addSubmit(dgettext('signup', 'Add'));
            $this->signup->title = dgettext('signup', 'Add applicant');
        }

        $form->addHidden('sheet_id', $this->signup->sheet->id);
        $form->addHidden('slot_id', $this->signup->slot->id);

        $form->addText('first_name', $peep->first_name);
        $form->setLabel('first_name', dgettext('signup', 'First name'));

        $form->addText('last_name', $peep->last_name);
        $form->setLabel('last_name', dgettext('signup', 'Last name'));

        $form->addText('email', $peep->email);
        $form->setLabel('email', dgettext('signup', 'Email address'));

        $form->addText('phone', $peep->getPhone());
        $form->setLabel('phone', dgettext('signup', 'Phone number'));

        $form->addText('organization', $peep->organization);
        $form->setLabel('organization', dgettext('signup', 'Organization'));
        
        $tpl = $form->getTemplate();

        $tpl['CLOSE'] = javascript('close_window');
            
        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'edit_peep.tpl');
    }

    function editSlotPopup()
    {
        $form = new PHPWS_Form;
        $form->addHidden('module', 'signup');
        $form->addHidden('aop', 'post_slot');
        $form->addHidden('sheet_id', $this->signup->sheet->id);
        if ($this->signup->slot->id) {
            $this->signup->title = sprintf(dgettext('signup', 'Update %s slot'), $this->signup->sheet->title);
            $form->addHidden('slot_id', $this->signup->slot->id);
            $form->addSubmit(dgettext('signup', 'Update'));
        } else {
            $this->signup->title = sprintf(dgettext('signup', 'Add slot to %s'), $this->signup->sheet->title);
            $form->addSubmit(dgettext('signup', 'Add'));
        }

        $form->addText('title', $this->signup->slot->title);
        $form->setSize('title', 40);
        $form->setLabel('title', dgettext('signup', 'Title'));

        $form->addText('openings', $this->signup->slot->openings);
        $form->setSize('openings', 5);
        $form->setLabel('openings', dgettext('signup', 'Number of openings'));

        $tpl = $form->getTemplate();

        $tpl['CLEAR'] = javascript('close_window');

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'edit_slot.tpl');
    }

    function editSlots()
    {
        $this->signup->title = sprintf(dgettext('signup', 'Slot setup for %s'), $this->signup->sheet->title);

        $vars['aop'] = 'edit_slot_popup';
        $vars['sheet_id'] = $this->signup->sheet->id;
        $vars['slot_id'] = 0;
        $js['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
        $js['label'] = dgettext('signup', 'Add slot');
        $tpl['ADD_SLOT'] = javascript('open_window', $js);

        $slots = $this->signup->sheet->getAllSlots();

        if (PHPWS_Error::logIfError($slots)) {
            $this->signup->content = dgettext('signup', 'An error occurred when accessing this sheet\'s slots.');
            return;
        }

        if ($slots) {
            foreach ($slots as $slot) {
                $tpl['current-slots'][] = $slot->viewTpl();
            }
        } else {
            $tpl['EMPTY'] = dgettext('signup', 'Click on "Add slot" to allow applicants to sign up.');
        }

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'slot_setup.tpl');
    }

    function editSheet()
    {
        $form = new PHPWS_Form('signup_sheet');
        $sheet = & $this->signup->sheet;

        $form->addHidden('module', 'signup');
        $form->addHidden('aop', 'post_sheet');
        if ($sheet->id) {
            $form->addHidden('id', $sheet->id);
            $form->addSubmit(dgettext('signup', 'Update'));
            $this->signup->title = dgettext('signup', 'Update signup sheet');
            $form->addTplTag('EDIT_SLOT', $this->signup->sheet->editSlotLink());
        } else {
            $form->addSubmit(dgettext('signup', 'Create'));
            $this->signup->title = dgettext('signup', 'Create signup sheet');
        }

        $form->addText('title', $sheet->title);
        $form->setLabel('title', dgettext('signup', 'Title'));

        $form->addTextArea('description', $sheet->description);
        $form->setLabel('description', dgettext('signup', 'Description'));

        // Functionality not finished. Hide for now.

        /*
        $form->addText('start_time', $sheet->getStartTime());
        $form->setLabel('start_time', dgettext('signup', 'Start signup'));
        */

        $form->addText('end_time', $sheet->getEndTime());
        $form->setLabel('end_time', dgettext('signup', 'Close signup'));

        /*
        $js_vars['date_name'] = 'start_time';
        $js_vars['type'] = 'text';
        $js_vars['form_name'] = 'signup_sheet';
        $form->addTplTag('ST_JS', javascript('js_calendar', $js_vars));
        $js_vars['date_name'] = 'end_time';
        $form->addTplTag('ET_JS', javascript('js_calendar', $js_vars));
        */

        $tpl = $form->getTemplate();

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'edit_sheet.tpl');
    }

    function report()
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        PHPWS_Core::initModClass('signup', 'Peeps.php');

        $pager = new DBPager('signup_peeps', 'Signup_Peep');
        $pager->addWhere('sheet_id', $this->signup->sheet->id);
        $pager->addWhere('registered', 1);
        $pager->setModule('signup');
        $pager->setTemplate('applicants.tpl');
        $pager->addRowTags('rowtags');

        $vars['sheet_id'] = $this->signup->sheet->id;
        $vars['aop'] = 'csv_applicants';
        $page_tags['CSV'] = PHPWS_Text::secureLink(dgettext('signup', 'CSV file'), 'signup', $vars);

        $vars['aop'] = 'print_applicants';
        $js['label'] = dgettext('signup', 'Print list');
        $js['width'] = '1024';
        $js['height'] = '768';
        $js['menubar'] = 'yes';
        $js['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
        $page_tags['PRINT'] = javascript('open_window', $js);


        $vars['aop'] = 'slot_listing';
        $js['label'] = dgettext('signup', 'Slot listing');
        $js['menubar'] = 'yes';
        $js['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
        $page_tags['SLOT_LISTING'] = javascript('open_window', $js);


        $page_tags['LAST_NAME_LABEL'] = dgettext('signup', 'Last name');
        $page_tags['FIRST_NAME_LABEL'] = dgettext('signup', 'First name');
        $page_tags['EMAIL_LABEL'] = dgettext('signup', 'Email');
        $page_tags['PHONE_LABEL'] = dgettext('signup', 'Phone');
        $page_tags['ORGANIZATION_LABEL'] = dgettext('signup', 'Organization');

        $pager->addPageTags($page_tags);
        $pager->setSearch('last_name', 'first_name', 'organization');

        $limits[25]  = 25;
        $limits[50]  = 50;
        $limits[100] = 100;
        $pager->setLimitList($limits);


        $this->signup->title = sprintf(dgettext('signup', '%s Participants'), $this->signup->sheet->title);
        $this->signup->content = $pager->get();
    }




    function listSignup()
    {
        $ptags['TITLE_HEADER'] = dgettext('signup', 'Title');

        PHPWS_Core::initModClass('signup', 'Sheet.php');
        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = new DBPager('signup_sheet', 'Signup_Sheet');
        $pager->setModule('signup');
        $pager->setTemplate('sheet_list.tpl');
        $pager->addRowTags('rowTag');
        $pager->addPageTags($ptags);

        $this->signup->content = $pager->get();
        $this->signup->title = dgettext('signup', 'Signup Sheets');
    }

    function userSignup()
    {
        if (!$this->signup->sheet->id) {
            PHPWS_Core::errorPage('404');
        }

        $sheet = & $this->signup->sheet;
        $peep  = & $this->signup->peep;

        if ($sheet->end_time < mktime()) {
            $this->signup->title = dgettext('signup', 'Sorry');
            $this->signup->content = dgettext('signup', 'We are no longer accepting applications.');
            return;
        }

        $slots = $sheet->getAllSlots();
        $slots_filled = $sheet->totalSlotsFilled();

        if (empty($slots)) {
            $this->signup->title = dgettext('signup', 'Sorry');
            $this->signup->content = dgettext('signup', 'There is a problem with this signup sheet. Please check back later.');
            return;
        }

        foreach ($slots as $slot) {
            // if the slots are filled, don't offer it
            if ( $slots_filled && isset($slots_filled[$slot->id])) {
                $filled = & $slots_filled[$slot->id];
                if ($filled >= $slot->openings) {
                    continue;
                } else {
                    $openings_left = $slot->openings - $filled;
                }
            } else {
                $openings_left = & $slot->openings;
            }

            $options[$slot->id] = sprintf('%s (%s openings)', $slot->title, $openings_left);
        }

        if (!isset($options)) {
            $tpl['MESSAGE'] = dgettext('signup', 'Sorry, but all available slots are full. Please check back later for possible cancellations.');
        } else {
            $form = new PHPWS_Form('slots');
            $form->useFieldset();
            $form->setLegend(dgettext('signup', 'Signup form'));
            $form->addHidden('module', 'signup');
            $form->addHidden('uop', 'slot_signup');
            $form->addHidden('sheet_id', $this->signup->sheet->id);

            $form->addSelect('slot_id', $options);
            $form->setLabel('slot_id', dgettext('signup', 'Available slots'));

            $form->addText('first_name', $peep->first_name);
            $form->setLabel('first_name', dgettext('signup', 'First name'));

            $form->addText('last_name', $peep->last_name);
            $form->setLabel('last_name', dgettext('signup', 'Last name'));

            $form->addText('email', $peep->email);
            $form->setSize('email', 30);
            $form->setLabel('email', dgettext('signup', 'Email address'));

            $form->addText('phone', $peep->getPhone());
            $form->setSize('phone', 15);
            $form->setLabel('phone', dgettext('signup', 'Phone number'));

            $form->addSubmit(dgettext('signup', 'Submit'));
            
            $tpl = $form->getTemplate();
        }


        $this->signup->title = & $sheet->title;
        $tpl['MESSAGE'] = $this->signup->message;

        $tpl['DESCRIPTION'] = $sheet->getDescription();
        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'signup_form.tpl');
        $this->signup->sheet->flag();
    }

}

?>