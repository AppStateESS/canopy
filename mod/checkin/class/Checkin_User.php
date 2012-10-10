<?php
/**
 * Handles the user interaction with checkin
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */

PHPWS_Core::initModClass('checkin', 'Checkin.php');

class Checkin_User extends Checkin {

    public function checkinForm() {
        $form = new PHPWS_Form('checkin');
        $form->turnOffAutoComplete();
        $form->setProtected(false);
        $form->addHidden('module', 'checkin');
        $form->addHidden('uop', 'post_checkin');

        $form->addText('first_name', @trim($_POST['first_name']));
        $form->setLabel('first_name', dgettext('checkin', 'First name'));
        $form->setRequired('first_name');

        $form->addText('last_name', @trim($_POST['last_name']));
        $form->setLabel('last_name', dgettext('checkin', 'Last name'));
        $form->setRequired('last_name');

        if (PHPWS_Settings::get('checkin', 'email')) {
            $form->addText('email', @trim($_POST['email']));
            $form->setLabel('email', dgettext('checkin', 'Email address'));
            $form->setRequired('email');
        }
        
        // If gender is requested
        if (PHPWS_Settings::get('checkin', 'gender')) {
            $sex = array('male'=>'Male', 'female'=>'Female');
            $form->addRadioAssoc('gender', $sex);
            $form->addTplTag('GENDER_LABEL', dgettext('checkin', 'Gender'));

        }

        // If birthdate is requested
        if (PHPWS_Settings::get('checkin', 'birthdate')) {
            /*
             * Minimum representable date is 12-13-1901, and instead of doing 
             * lots of math to ensure that all selected dates in 1901 are after
             * 12-13-1901, just make the minimum year always be 1902
             */
            $yearsPrior = date('Y', time()) - 1902; // current year - minimum full year (1902)
            $form->dateSelect('birthdate', 0, '%B', $yearsPrior, 0);
            $form->addTplTag('BIRTHDATE_LABEL', dgettext('checkin', 'Date of birth'));
        }

        $reasons = $this->getReasons();

        if (!empty($reasons)) {
            $reasons = array_reverse($reasons, true);
            $reasons[0] = dgettext('checkin', '-- Please choose a reason from the list below --');
            $reasons = array_reverse($reasons, true);

            $form->addSelect('reason_id', $reasons);
            $form->setLabel('reason_id', dgettext('checkin', 'Reason for visit'));
        }
        $form->addSubmit(dgettext('checkin', 'Check in'));

        $tpl = $form->getTemplate();
        $this->title =  dgettext('checkin', 'Please check in using the form below');
        $this->content = PHPWS_Template::process($tpl, 'checkin', 'signin.tpl');
        if (!Current_User::isLogged() && PHPWS_Settings::get('checkin', 'collapse_signin')) {
            Layout::collapse();
        }
    }

    public function main()
    {
        $tpl['TITLE'] = & $this->title;
        if (is_array($this->message)) {
            $tpl['MESSAGE'] = implode('<br />', $this->message);
        } else {
            $tpl['MESSAGE'] = $this->message;
        }

        $tpl['CONTENT'] = & $this->content;

        return PHPWS_Template::process($tpl, 'checkin', 'main.tpl');
    }

    public function process($command=null)
    {
        if (empty($command)) {
            @$command = $_REQUEST['uop'];
        }

        switch ($command) {
            case 'checkin_form':
                $this->checkinForm();
                break;

            case 'error':
                $this->title = dgettext('checkin', 'Sorry');
                $this->content = dgettext('checkin', 'An error is preventing your account to save. Please alert the office.');
                Layout::metaRoute('index.php', 5);
                break;

            case 'thank':
                $this->title = dgettext('checkin', 'Thank you');
                $this->loadReason();
                $this->content = $this->reason->message;
                Layout::metaRoute('index.php', 5);
                break;

            case 'post_checkin':
                if ($this->postCheckin()) {
                    if (PHPWS_Error::logIfError($this->visitor->save())) {
                        PHPWS_Core::reroute('index.php?module=checkin&uop=error');
                    } else {
                        PHPWS_Core::reroute('index.php?module=checkin&uop=thank&reason_id=' . $_POST['reason_id']);
                    }
                } else {
                    $this->checkinForm();
                }
                break;

            default:
                PHPWS_Core::errorPage('404');
        }
        Layout::add($this->main());
    }

    public function postCheckin()
    {
        $this->loadVisitor();

        $this->visitor->firstname = ucwords(trim($_POST['first_name']));
        $this->visitor->lastname  = ucwords(trim($_POST['last_name']));
        
        // If set to ask for birthdate, save visitor's birthdate
        if (PHPWS_Settings::get('checkin', 'birthdate')) {
            if (PHPWS_Form::testDate('birthdate')) {
                $this->visitor->birthdate = PHPWS_Form::getPostedDate('birthdate');
            } else {
                $this->message[] = dgettext('checkin', 'Please enter a valid birthdate');
            }
        }
        
        // If set to ask for gender, save visitor's gender
        if (PHPWS_Settings::get('checkin', 'gender')) {
            if (isset($_POST['gender'])) {
                $this->visitor->gender = $_POST['gender'];
            } else {
                $this->message[] = dgettext('checkin', 'Please enter a gender');
            }
        }

        if (isset($_POST['reason_id'])) {
            if ($_POST['reason_id'] == 0) {
                $this->message[] = dgettext('checkin', 'Please enter the reason for your visit.');
            }
            $this->visitor->reason    = (int)$_POST['reason_id'];
        }

        if (empty($this->visitor->firstname)) {
            $this->message[] = dgettext('checkin', 'Please enter your first name.');
        }

        if (empty($this->visitor->lastname)) {
            $this->message[] = dgettext('checkin', 'Please enter your last name.');
        }

        if (!empty($_POST['email'])) {
            $this->visitor->email = strip_tags(trim($_POST['email']));
        }

        $this->visitor->assign();

        return empty($this->message);
    }
}

?>
