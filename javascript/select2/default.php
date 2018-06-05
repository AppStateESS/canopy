<?php

/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function select2Script()
{
    $script = '<script type="text/javascript" src="' . PHPWS_SOURCE_HTTP . 'javascript/select2/select2.min.js"></script>';
    \Layout::addJSHeader($script);
    \Layout::addToStyleList('javascript/select2/select2.min.css');
    \Layout::addToStyleList('javascript/select2/select2-bootstrap.css');
}
select2Script();