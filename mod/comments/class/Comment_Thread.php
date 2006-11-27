<?php

/**
 * Class for comment threads. Threads hold all the comments for
 * a specific item.
 *
 * @author Matt McNaney <matt at tux dot appstate dot edu>
 * @version $Id$
 */ 

PHPWS_Core::initModClass('comments', 'Comment_Item.php');

define('NO_COMMENTS_FOUND', 'none');

class Comment_Thread {
    var $id             = 0;
    var $key_id         = NULL;
    var $total_comments = 0;
    var $last_poster    = NULL;
    var $allow_anon     = 0;
    var $_key           = NULL;
    var $_comments      = NULL;
    var $_error         = NULL;


    function Comment_Thread($id=NULL)
    {
        if (empty($id)) {
            return;
        }

        $this->setId($id);
        $this->init();
    }

    function init()
    {
        $db = & new PHPWS_DB('comments_threads');
        $db->addWhere('id', $this->id);
        $result = $db->loadObject($this);
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $this->_error = $result->getMessage();
        }

        $this->loadKey();
    }

    function allowAnonymous($anon)
    {
        $this->allow_anon = (int)(bool)$anon;
    }

    function countComments($formatted=FALSE)
    {
        if ($formatted) {
            if (empty($this->total_comments)) {
                return _('No comments');
            } elseif ($this->total_comments == 1) {
                return _('1 comment');
            } else {
                return sprintf(_('%s comments'), $this->total_comments);
            }
        } else {
            return $this->total_comments;
        }
    }

    function loadKey()
    {
        $this->_key = & new Key($this->key_id);
    }

    function getLastPoster()
    {
        return $this->last_poster;
    }

    /**
     * Creates a new thread
     *
     * If there is a thread in the database, it is loaded.
     * If there is NOT then one is created.
     */
    function buildThread()
    {
        $db = & new PHPWS_DB('comments_threads');
        $db->addWhere('key_id', $this->key_id);
        $result = $db->loadObject($this);

        if (PEAR::isError($result)) {
            $this->_error = $result->getMessage();
            return $result;
        } elseif (empty($result)) {
            $result = $this->save();
            if (PEAR::isError($result)) {
                PHPWS_Error::log($result);
                $this->_error = _('Error occurred trying to create new thread.');
            }
            return TRUE;
        } else {
            return TRUE;
        }
    }


    function setId($id)
    {
        $this->id = (int)$id;
    }

    function getId()
    {
        return $this->id;
    }

    function setSourceUrl($link)
    {
        $link = str_replace('&amp;', '&', $link);
        $this->source_url = stristr($link, 'index.php?');
    }

    function getSourceUrl($full=FALSE, $comment_id=0)
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        $url = DBPager::getLastView('comments_items');

        if ($comment_id) {
            $url .= "#cm_$comment_id";
        }

        if ($full==TRUE) {
            return sprintf('<a href="%s">%s</a>', $url, _('Go back'));
        } else {
            return $url;
        }
    }

    function setKey($key)
    {
        $this->_key = $key;
    }

    function postLink()
    {
        $vars['user_action']   = 'post_comment';
        $vars['thread_id']     = $this->id;
        return PHPWS_Text::moduleLink(_('Post New Comment'), 'comments', $vars);
    }

    function save()
    {
        $db = & new PHPWS_DB('comments_threads');
        return $db->saveObject($this);
    }

    function delete()
    {
        $db = & new PHPWS_DB('comments_items');
        $db->addWhere('thread_id', $this->id);
        $item_result = $db->delete();

        if (PEAR::isError($item_result)) {
            return $item_result;
        }

        $db = & new PHPWS_DB('comments_threads');
        $db->addWhere('id', $this->id);
        $thread_result = $db->delete();

        if (PEAR::isError($thread_result)) {
            return $thread_result;
        }

        return TRUE;
    }

    function _getTimePeriod()
    {
        switch ($_GET['time_period']) {
        case 'today':
            return gmmktime(0,0,0);
            break;

        case 'yd':
            return gmmktime(0,0,0, gmdate('m'), gmdate('d')-1);
            break;

        case 'week':
            return gmmktime(0,0,0, gmdate('m'), gmdate('d')-7);
            break;

        case 'month':
            return gmmktime(0,0,0, gmdate('m')-1);
            break;
        }

    }

    function view($parent_id=0)
    {
        if (Current_User::allow('comments')) {
            $this->miniAdmin();
        }

        Layout::addStyle('comments');
        PHPWS_Core::initCoreClass('DBPager.php');

        $time_period = array('all'    => _('All'),
                             'today'  => _('Today'),
                             'yd'     => _('Since yesterday'),
                             'week'   => _('This week'),
                             'month'  => _('This month')
                             );

        $order_list = array('old_all'  => _('Oldest first'),
                            'new_all'  => _('Newest first'));


        $pager = & new DBPager('comments_items', 'Comment_Item');
        $pager->saveLastView();
        $form = & new PHPWS_Form;
        $form->addHidden('module', 'comments');
        $form->addHidden('user_action', 'change_view');
        $form->addSelect('time_period', $time_period);
        $form->addSelect('order', $order_list);

        // set where clauses
        if (isset($_GET['time_period']) && $_GET['time_period'] != 'all') {
            $form->setMatch('time_period', $_GET['time_period']);
            $time_period = $this->_getTimePeriod();
            $pager->addWhere('create_time', $time_period, '>=');
        }

        if (!empty($parent_id)) {
            $pager->addWhere('parent', (int)$parent_id);
        }
        $pager->addWhere('thread_id', $this->id);

        if (isset($_GET['order'])) {
            $default_order = &$_GET['order'];
        } else {
            $default_order = PHPWS_Settings::get('comments', 'default_order');
        }

        switch ($default_order) {
        case 'new_all':
            $pager->setOrder('create_time', 'desc');
            break;
            
        case 'old_all':
            $pager->setOrder('create_time', 'asc');
            break;
        }
        $form->setMatch('order', $default_order);

        $form->noAuthKey();
        $form->addSubmit(_('Go'));
        $form->setMethod('get');

        $page_tags = $form->getTemplate();

        if ($this->canComment()) {
            $page_tags['NEW_POST_LINK'] = $this->postLink();
        }

        $pager->setModule('comments');
        $pager->setTemplate(COMMENT_VIEW_TEMPLATE);
        $pager->addPageTags($page_tags);
        $pager->addRowTags('getTpl', $this->allow_anon);
        $pager->setLimitList(array(10, 20, 50));
        $pager->setDefaultLimit(COMMENT_DEFAULT_LIMIT);
        $pager->setEmptyMessage(_('No comments'));
        $pager->initialize();
        $rows = $pager->getRows();
        if (!empty($rows)) {
            $this->_createUserList($rows);
        }

        $content = $pager->get();
        return $content;
    }

    function canComment()
    {
        return ($this->allow_anon || Current_User::isLogged()) ? TRUE : FALSE;
    }

    function _createUserList($comment_list)
    {
        $author_list = array();
        foreach ($comment_list as $comment) {
            $author_id = &$comment->author_id;
            if ($author_id == 0 || in_array($author_id, $author_list)) {
                continue;
            }

            $author_list[] = $author_id;
        }

        $result = Demographics::getList($author_list, 'comments_users', 'Comment_User');

        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            return;
        }
        $GLOBALS['Comment_Users'] = $result;

        return TRUE;
    }

    function increaseCount()
    {
        $this->total_comments++;
    }

    function decreaseCount()
    {
        $this->total_comments--;
    }

    function postLastUser($author_id)
    {
        $author = & new Comment_User($author_id);
        $this->last_poster = $author->display_name;
    }

    function miniAdmin()
    {
        $vars['thread_id'] = $this->id;
        if ($this->allow_anon) {
            $vars['admin_action'] = 'disable_anon_posting';
            $link = PHPWS_Text::secureLink(_('Disable anonymous posting'), 'comments', $vars);
        } else {
            $vars['admin_action'] = 'enable_anon_posting';
            $link = PHPWS_Text::secureLink(_('Enable anonymous posting'), 'comments', $vars);
        }

        MiniAdmin::add('comments', $link);
    }

}

?>