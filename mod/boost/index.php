<?php

/**
 
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */
if (!defined('PHPWS_SOURCE_DIR')) {
    include '../../core/conf/404.html';
    exit();
}

\phpws\PHPWS_Core::requireConfig('boost');

if (DEITY_ACCESS_ONLY && !Current_User::isDeity()) {
    Current_User::disallow();
}

if (!Current_User::authorized('boost')) {
    Current_User::disallow();
}

if (!isset($_REQUEST['action'])) {
    \phpws\PHPWS_Core::errorPage(404);
}

$js = false;

$content = array();
\phpws\PHPWS_Core::initModClass('boost', 'Form.php');
\phpws\PHPWS_Core::initModClass('controlpanel', 'Panel.php');
\phpws\PHPWS_Core::initModClass('boost', 'Action.php');

$boostPanel = new PHPWS_Panel('boost');
$boostPanel->enableSecure();
Boost_Form::setTabs($boostPanel);

$vars = array('action' => 'admin', 'tab' => $boostPanel->getCurrentTab());
$backToBoost = PHPWS_Text::secureLink('Return to Boost',
                'boost', $vars);

switch ($_REQUEST['action']) {
    case 'admin':
        $content[] = Boost_Form::listModules(Boost_Form::boostTab($boostPanel));
        break;

    case 'check':
        $content[] = $backToBoost . '<br />';
        $content[] = Boost_Action::checkupdate($_REQUEST['opmod']);
        break;

    case 'check_all':
        Boost_Action::checkAll();
        $content[] = Boost_Form::listModules(Boost_Form::boostTab($boostPanel));
        break;

    case 'aboutView':
        \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');
        PHPWS_Boost::aboutView($_REQUEST['aboutmod']);
        break;

    case 'install':
        $js = javascriptEnabled();
        if (!$js) {
            $content[] = $backToBoost . '<br />';
        }

        $result = Boost_Action::installModule($_REQUEST['opmod']);
        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'An error occurred while installing this module.' .
                    ' ' . 'Please check your error logs.';
        } else {
            $content[] = $result;
        }
        break;

    case 'uninstall':
        $content[] = $backToBoost . '<br />';
        $content[] = '<br />';

        if (isset($_REQUEST['confirm']) && isset($_REQUEST['opmod']) && $_REQUEST['confirm'] == $_REQUEST['opmod']) {
            $content[] = Boost_Action::uninstallModule($_REQUEST['opmod']);
        } else {
            \phpws\PHPWS_Core::goBack();
        }
        break;

    case 'update_core':
        $content[] = $backToBoost . '<br />';
        $content[] = Boost_Action::updateModule('core');
        break;

    case 'update':
        $js = javascriptEnabled();
        if (!$js) {
            $content[] = $backToBoost . '<br />';
        } else {
            $content[] = sprintf('<p style="text-align : center"><input type="button" onclick="closeWindow(); return false" value="%s" /></p>',
                    'Close window');
        }
        $content[] = Boost_Action::updateModule($_REQUEST['opmod']);
        break;

    case 'show_dependency':
        $js = javascriptEnabled();
        $content[] = Boost_Action::showDependency($_REQUEST['opmod']);
        break;

    case 'show_depended_upon':
        $content[] = Boost_Action::showDependedUpon($_REQUEST['opmod']);
        break;
}// End area switch

if ($js) {
    javascript('close_refresh', array('use_link' => true));
    $content[] = sprintf('<p style="text-align : center"><input type="button" onclick="closeWindow(); return false" value="%s" /></p>',
            'Close window');
    Layout::nakedDisplay(implode('', $content));
} else {
    $boostPanel->setContent(implode('', $content));
    $finalContent = $boostPanel->display();
    Layout::add(PHPWS_ControlPanel::display($finalContent));
}
