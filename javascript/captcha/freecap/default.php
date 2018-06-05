<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

if (!Captcha::isGD()) {
    $data['warning'] = _('Your GD graphic library is not loaded. Captcha will not function.');
}

$data['question'] = _('Please copy the word in the above image.');
$data['sid'] = md5(SITE_HASH . $_SERVER['REMOTE_ADDR']);
$_SESSION['ld'] = PHPWS_HOME_DIR;