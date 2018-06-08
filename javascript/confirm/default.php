<?php
/**
 
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

if (isset($data['question'])) {
    $data['QUESTION'] = $data['question'];
}

if (isset($data['link'])) {
    $data['LINK'] = $data['link'];
}

if (isset($data['address'])) {
    $data['ADDRESS'] = $data['address'];
}

if (isset($data['type'])) {
    if ($data['type'] == 'button') {
        $bodyfile = PHPWS_SOURCE_DIR . 'javascript/confirm/body2.js';
    }
}

$data['QUESTION'] = preg_replace("/(?<!\\\)'/", "\'", $data['QUESTION']);
$data['QUESTION'] = str_replace('"', '&quot;', $data['QUESTION']);
