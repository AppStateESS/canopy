<?php

/**
 * Crutch display of old modules
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

Layout::keyDescriptions();
Layout::showKeyStyle();
if (defined('LAYOUT_CHECK_COOKIE') && LAYOUT_CHECK_COOKIE) {
    check_cookie();
}
echo Layout::display();
