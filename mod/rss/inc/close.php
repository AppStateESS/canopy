<?php
/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */
$key = Key::getCurrent();

if (empty($key) || $key->isDummy() || $key->restricted) {
    return;
}

PHPWS_Core::initModClass('rss', 'RSS.php');
RSS::showIcon($key);

?>