<?php
/**
 
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */
require_once(PHPWS_SOURCE_DIR.'mod/branch/conf/defines.php');

if (isset($_REQUEST['module']) && $_REQUEST['module'] == 'branch') {
    \phpws\PHPWS_Core::initModClass('boost', 'Boost.php');
}
