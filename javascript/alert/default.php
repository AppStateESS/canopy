<?php

if (!strstr("\'", $data['content'])) {
    $data['content'] = str_replace("'", "\'", $data['content']);
}

$data['content'] = strip_tags($data['content']);

if (empty($data['label'])) {
    $headfile = PHPWS_SOURCE_DIR . 'javascript/alert/head2.js';
}

?>