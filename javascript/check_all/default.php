<?php

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */
$data['uncheck_label'] = 'Uncheck all';
$data['check_label'] = 'Check all';

$type = isset($data['type']) ? $data['type'] : 1;
switch ($type) {
    case 'checkbox':
        $data['input_type'] = 'checkbox';
        break;

    default:
        $data['input_type'] = 'button';
        break;
}