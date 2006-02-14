<?php

/**
 * unregisters deleted keys from comments
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */


function comments_unregister(&$key)
{
    if (empty($key) || empty($key->id)) {
        return FALSE;
    }

    PHPWS_Core::initModClass('comments', 'Comment_Thread.php');

    $thread = & new Comment_Thread;

    $db = & new PHPWS_DB('comments_threads');
    $db->addWhere('key_id', $key->id);
    $result = $db->loadObject($thread);

    if (PEAR::isError($result)) {
        return $result;
    } elseif (empty($result)) {
        return TRUE;
    }

    return $thread->delete();
}

?>