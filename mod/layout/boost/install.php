<?php
/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @version $Id$
 */

function layout_install(&$content, $branchInstall=FALSE)
{
    $page_title = 'Change this Site Name in Layout Meta Tags';
    $default_theme = 'bootstrap';

    if (!isset($error)) {
        $db = new PHPWS_DB('layout_config');
        $db->addValue('default_theme', $default_theme);
        $db->addValue('page_title', $page_title);
        $db->update();
        $content[] = 'Layout settings updated.';
        return true;
    } else {
        return $error;
    }
}
