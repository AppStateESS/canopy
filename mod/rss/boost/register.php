<?php
/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

function rss_register($module, &$content)
{
    PHPWS_Core::initModClass('rss', 'RSS.php');
    return RSS::registerModule($module, $content);
}

?>