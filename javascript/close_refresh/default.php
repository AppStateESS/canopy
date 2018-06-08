<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

$default['timeout'] = 0;
$default['refresh'] = 1;
$default['set_timeout'] = ' ';

if (isset($data['use_link'])) {
    unset($default['set_timeout']);
}
