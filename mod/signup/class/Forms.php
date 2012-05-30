<?php
/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

class Signup_Forms {
    public $signup = null;

    public function get($type)
    {
        switch ($type) {
            case 'new':
                if (Current_User::isRestricted('signup')) {
                    $this->signup->title   = dgettext('signup', 'Sorry');
                    $this->signup->content = dgettext('signup', 'You do not have permission for this action.');
                    return;
                }
            case 'edit_sheet':
                if (empty($this->signup->sheet)) {
                    $this->signup->loadSheet();
                }
                if (!Current_User::allow('signup', 'edit_sheet', $this->signup->sheet->id, 'sheet')) {
                    Current_User::disallow();
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

            case 'edit_peep_popup':
                $this->editPeepPopup();
                break;

            case 'user_signup':
                $this->userSignup();
                break;

            case 'report':
                $this->report();
                break;

            case 'email_applicants':
                $this->emailApplicants();
                break;
        }
    }

    public function emailApplicants()
    {
        $email = & $this->signup->email;

        $form = new PHPWS_Form('email');
        $form->addHidden('module', 'signup');
        $form->addHidden('aop', 'post_email');
        $form->addHidden('sheet_id', $this->signup->sheet->id);

        if (!empty($_REQUEST['search'])) {
            $form->addHidden('search', $_REQUEST['search']);
        }

        $form->addText('subject', $email['subject']);
        $form->setLabel('subject', dgettext('signup', 'Subject'));
        $form->setSize('subject', 30);

        $form->addText('from', $email['from']);
        $form->setLabel('from', dgettext('signup', 'From'));
        $form->setSize('from', 30);
        
        $form->addTextArea('message', $email['message']);
        $form->setLabel('message', dgettext('signup', 'Message'));
        $form->setCols('message', 50);

        $form->addSubmit(dgettext('signup', 'Send emails'));

        $tpl = $form->getTemplate();
        
        if (!empty($_REQUEST['search'])) {
            $tpl['WARNING_LABEL'] = dgettext('signup','Note:');
            $tpl['WARNING'] = dgettext('signup', 'This email will only go out to the people specified by the prior search');
        }

        $this->signup->title = sprintf(dgettext('signup', 'Email %s applicants'), $this->signup->sheet->title);
        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'email_form.tpl');
    }


    public function editPeep()
    {
        $peep = $this->signup->peep;

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

        if (!empty($this->signup->sheet->extra1)) {
            $form->addText('extra1', $peep->extra1);
            $form->setLabel('extra1', $this->signup->sheet->extra1);
        }

        if (!empty($this->signup->sheet->extra2)) {
            $form->addText('extra2', $peep->extra2);
            $form->setLabel('extra2', $this->signup->sheet->extra2);
        }

        if (!empty($this->signup->sheet->extra3)) {
            $form->addText('extra3', $peep->extra3);
            $form->setLabel('extra3', $this->signup->sheet->extra3);
        }


        $tpl = $form->getTemplate();

        $tpl['CLOSE'] = sprintf('<input type="button" value="%s" />', dgettext('signup', 'Close'));
        $tpl['CLOSE'] = javascript('close_refresh', array('use_link'=>1));

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'edit_peep.tpl');
    }


    public function editSlotPopup()
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

        javascript('close_refresh', array('use_link'=>1));
        $tpl['CLEAR'] = sprintf('<input type="button" value="%s" onclick="closeWindow(); return false" />',
        dgettext('signup', 'Close'));

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'edit_slot.tpl');
    }


    public function editPeepPopup()
    {
        $slot = & $this->signup->slot;
        $slot->loadPeeps();
        if (!$slot->id || PHPWS_Error::logIfError($slot)) {
            $this->signup->content = dgettext('signup', 'An error occurred when accessing this sheet\'s slots.');
            return;
        }
        $tpl = $slot->viewTpl();

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'peep_pop.tpl');
    }

    public function editSlots()
    {
        $this->signup->title = sprintf(dgettext('signup', 'Slot setup for %s'), $this->signup->sheet->title);
        $form = new PHPWS_Form('seach_users');
        $form->setMethod('get');
        $form->addHidden('module', 'signup');
        $form->addHidden('aop', 'edit_slots');
        $form->addHidden('sheet_id', $this->signup->sheet->id);
        if (!empty($_GET['search'])) {
            $search = $_GET['search'];
            $this->signup->message = dgettext('signup', 'The name you searched for is in these slots.');
        } else {
            $search = null;
        }
        $form->addText('search', $search);
        $form->setLabel('search', dgettext('signup', 'Search slots'));
        $tpl = $form->getTemplate();

        $vars['aop'] = 'edit_slot_popup';
        $vars['sheet_id'] = $this->signup->sheet->id;
        $vars['slot_id'] = 0;
        $js['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
        $js['label'] = dgettext('signup', 'Add slot');
        $tpl['ADD_SLOT'] = javascript('open_window', $js);

        $vars['aop'] = 'reset_slot_order';
        $tpl['RESET'] = PHPWS_Text::secureLink(dgettext('signup', 'Reset order'), 'signup', $vars);

        $vars['aop'] = 'alpha_order';
        $tpl['ALPHA'] = PHPWS_Text::secureLink(dgettext('signup', 'Alphabetic order'), 'signup', $vars);

        $slots = $this->signup->sheet->getAllSlots(false, $search);

        if ($slots) {
            foreach ($slots as $slot) {
                $tpl['slot-list'][] = $slot->listTpl();
            }
        } else {
            $this->signup->message = dgettext('signup', 'No slots found.');
        }

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'slot_setup.tpl');
    }


    public function editSheet()
    {
        $form = new PHPWS_Form('signup_sheet');
        $sheet = & $this->signup->sheet;

        $form->addHidden('module', 'signup');
        $form->addHidden('aop', 'post_sheet');
        if ($sheet->id) {
            $form->addHidden('sheet_id', $sheet->id);
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

        $form->addText('contact_email', $sheet->contact_email);
        $form->setLabel('contact_email', dgettext('signup', 'Contact email'));

        $form->addCheck('multiple', 1);
        $form->setMatch('multiple', $sheet->multiple);
        $form->setLabel('multiple', dgettext('signup', 'Allow multiple signups'));

        // Functionality not finished. Hide for now.
        /*
        $form->addText('start_time', $sheet->getStartTime());
        $form->setLabel('start_time', dgettext('signup', 'Start signup'));
        */

        $form->addText('extra1', $sheet->extra1);
        $form->setSize('extra1', 40);
        $form->setLabel('extra1', dgettext('signup', 'Extra information 1'));

        $form->addText('extra2', $sheet->extra2);
        $form->setSize('extra2', 40);
        $form->setLabel('extra2', dgettext('signup', 'Extra information 2'));

        $form->addText('extra3', $sheet->extra3);
        $form->setSize('extra3', 40);
        $form->setLabel('extra3', dgettext('signup', 'Extra information 3'));

        $form->addText('end_time', $sheet->getEndTime());
        $form->setClass('end_time', 'datepicker');
        $form->setLabel('end_time', dgettext('signup', 'Close signup'));

        $js_vars['type'] = 'text_clock';
        $js_vars['form_name'] = 'signup_sheet';

        $js_vars['date_name'] = 'start_time';
        $form->addTplTag('ST_JS', javascript('js_calendar', $js_vars));

        $js_vars['date_name'] = 'end_time';
        $form->addTplTag('ET_JS', javascript('js_calendar', $js_vars));

        $tpl = $form->getTemplate();

        $tpl['EXTRA_NOTE'] = dgettext('signup', 'Blank extra fields will not appear on signup.');
        // Explain the purpose of the 'extra' fields to the user.
        $tpl['FIELDSET_NOTE'] = dgettext('signup', 'The signup form already asks for the user\'s first and last name, email address, and phone number. <br>If there is any other information you want to ask for, specify it here. These fields are not required.');

        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'edit_sheet.tpl');
    }

    public function report()
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        PHPWS_Core::initModClass('signup', 'Peeps.php');

        $pager = new DBPager('signup_peeps', 'Signup_Peep');
        $pager->addWhere('sheet_id', $this->signup->sheet->id);
        $pager->addWhere('registered', 1);
        $pager->setModule('signup');
        $pager->setTemplate('applicants.tpl');
        $pager->addRowTags('rowtags');
        $pager->addSortHeader('phone', dgettext('signup', 'Phone'));
        $pager->addSortHeader('last_name', dgettext('signup', 'Last name'));
        $pager->addSortHeader('first_name', dgettext('signup', 'First name'));
        $pager->addSortHeader('email', dgettext('signup', 'Email'));

        $vars['sheet_id'] = $this->signup->sheet->id;
        $vars['aop'] = 'csv_applicants';
        $page_tags['CSV'] = PHPWS_Text::secureLink(dgettext('signup', 'CSV file'), 'signup', $vars);

        $vars['aop'] = 'slot_listing';
        $js['label'] = dgettext('signup', 'Slot listing');
        $js['menubar'] = 'yes';
        $js['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
        $page_tags['SLOT_LISTING'] = javascript('open_window', $js);

        $vars['aop'] = 'print_applicants';

        if (!empty($pager->search)) {
            if (isset($pager->searchColumn)) {
                $vars['search'] = implode('+', $pager->searchColumn);
            } else {
                $vars['search'] = $pager->search;
            }
        }

        if ($pager->orderby) {
            $vars['orderby'] = $pager->orderby;
            $vars['orderby_dir'] = $pager->orderby_dir;
        }

        $js['label'] = dgettext('signup', 'Print list');
        $js['width'] = '1024';
        $js['height'] = '768';
        $js['menubar'] = 'yes';
        $js['address'] = PHPWS_Text::linkAddress('signup', $vars, true);
        $page_tags['PRINT'] = javascript('open_window', $js);

        $vars['aop'] = 'email_applicants';
        $page_tags['EMAIL'] = PHPWS_Text::secureLink(dgettext('signup', 'Email'), 'signup', $vars, null, "Email all people in the search results");

        $page_tags['EXTRA_LABEL'] = dgettext('signup', 'Extra details');
        
        // Add a link that takes the user back to the signup sheets page, skipping all the re-sorts
        $vars['aop'] = 'menu';
        $vars['tab'] = 'list';
        $page_tags['BACK'] = PHPWS_Text::secureLink(dgettext('signup','Back to Signup Sheets'), 'signup', $vars);

        $pager->addPageTags($page_tags);
        $pager->setSearch('last_name', 'first_name', 'organization');

        $limits[25]  = 25;
        $limits[50]  = 50;
        $limits[100] = 100;
        $pager->setLimitList($limits);


        $this->signup->title = sprintf(dgettext('signup', '%s Participants'), $this->signup->sheet->title);
        $this->signup->content = $pager->get();
    }

    public function listSignup()
    {
        $ptags['TITLE_HEADER'] = dgettext('signup', 'Title');
        $ptags['START_TIME_HEADER'] = dgettext('signup', 'Start Time');
        $ptags['END_TIME_HEADER'] = dgettext('signup', 'End Time');

        PHPWS_Core::initModClass('signup', 'Sheet.php');
        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = new DBPager('signup_sheet', 'Signup_Sheet');
        $pager->setModule('signup');
        $pager->setTemplate('sheet_list.tpl');
        $pager->addRowTags('rowTag');
        $pager->addPageTags($ptags);
        Key::restrictEdit($pager->db, 'signup', 'edit_sheet');

        $this->signup->content = $pager->get();
        $this->signup->title = dgettext('signup', 'Signup Sheets');
    }

    public function userSignup()
    {
        if (!$this->signup->sheet->id) {
            PHPWS_Core::errorPage('404');
        }

        $sheet = $this->signup->sheet;
        $peep  = $this->signup->peep;

        if (Current_User::isLogged() && empty($peep->email)) {
            $peep->email = Current_User::getEmail();
        }

        if ($sheet->end_time < time()) {
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

        $this->signup->title = & $sheet->title;

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

            $options[$slot->id] = sprintf(dngettext('signup', '%s (%s opening)', '%s (%s openings)', $openings_left), $slot->title, $openings_left);
        }

        if (!isset($options)) {
            $this->signup->content = dgettext('signup', 'Sorry, but all available slots are full. Please check back later for possible cancellations.');
            return;
        } else {
            $form = new PHPWS_Form('slots');
            $form->useFieldset();
            $form->setLegend(dgettext('signup', 'Signup form'));
            $form->addHidden('module', 'signup');
            $form->addHidden('uop', 'slot_signup');
            $form->addHidden('sheet_id', $this->signup->sheet->id);

            $form->addSelect('slot_id', $options);
            $form->setLabel('slot_id', dgettext('signup', 'Available slots'));
            $form->setMatch('slot_id', $peep->slot_id);

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

            if (!empty($this->signup->sheet->extra1)) {
                $form->addText('extra1', $peep->extra1);
                $form->setLabel('extra1', $this->signup->sheet->extra1);
            }

            if (!empty($this->signup->sheet->extra2)) {
                $form->addText('extra2', $peep->extra2);
                $form->setLabel('extra2', $this->signup->sheet->extra2);
            }

            if (!empty($this->signup->sheet->extra3)) {
                $form->addText('extra3', $peep->extra3);
                $form->setLabel('extra3', $this->signup->sheet->extra3);
            }

            $form->addSubmit(dgettext('signup', 'Submit'));

            $tpl = $form->getTemplate();
        }

        $tpl['DESCRIPTION'] = $sheet->getDescription();
        $this->signup->content = PHPWS_Template::process($tpl, 'signup', 'signup_form.tpl');
        $this->signup->sheet->flag();
    }

}

?>
