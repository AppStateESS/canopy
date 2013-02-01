<?php

/**
 * @version $Id$
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
$default['question'] = _('Change to');
$default['answer'] = '';
$default['value_name'] = 'prompt';
$default['link'] = 'Prompt!';
if (isset($data['type'])) {
    if ($data['type'] == 'button') {
        $bodyfile = PHPWS_SOURCE_DIR . 'javascript/prompt/body2.js';
    }
}

$data['question'] = str_replace("\n", '\n', strip_tags($data['question']));

if (isset($data['answer'])) {
    $data['answer'] = preg_replace("/([^\\\])'/", "\\1\'", $data['answer']);
    $data['answer'] = str_replace("&#039;", "\\'", $data['answer']);
}

?>