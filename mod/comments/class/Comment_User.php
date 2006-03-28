<?php

  /**
   * Stores the user information specific to comments
   *
   * @author Matthew McNaney <matt at tux dot appstate dot edu>
   * @version $Id$
   */
PHPWS_Core::requireConfig('comments');
PHPWS_Core::initModClass('demographics', 'Demographics.php');

class Comment_User extends Demographics_User {

    var $display_name  = NULL;
    var $signature     = NULL;
    var $comments_made = 0;
    var $joined_date   = 0;
    var $avatar        = NULL;
    var $contact_email = NULL;
    var $website       = NULL;
    var $location      = NULL;
    var $locked        = 0;

    // using a second table with demographics
    var $_table        = 'comments_users';


    function Comment_User($user_id=NULL)
    {
        if ($user_id == 0) {
            $this->loadAnonymous();
            return;
        }
        $this->user_id = (int)$user_id;
        $this->load();
    }


    function loadAnonymous()
    {
        $this->display_name = DEFAULT_ANONYMOUS_TITLE;
    }


    function setSignature($sig)
    {
        if (empty($sig)) {
            $this->signature = NULL;
            return TRUE;
        }

        if (PHPWS_Settings::get('comments', 'allow_image_signatures')) {
            $this->signature = trim(strip_tags($sig, '<img>'));
        } else {
            if (preg_match('/<img/', $_POST['signature'])) {
                $this->_error[] = _('Image signatures not allowed.');
            }
            $this->signature = trim(strip_tags($sig));
        }

        return TRUE;
    }

    function getSignature()
    {
        return $this->signature;
    }

    function bumpCommentsMade()
    {
        if (!$this->user_id) {
            return;
        }

        $db = & new PHPWS_DB($this->_table);
        $result = $db->incrementColumn('comments_made');
    }


    function loadJoinedDate($date=NULL)
    {
        if (!isset($date)) {
            $this->joined_date = Current_User::getCreatedDate();
        } else {
            $this->joined_date = $date;
        }
    }

    function getJoinedDate($format=FALSE)
    {
        if ($format) {
            return strftime(COMMENT_DATE_FORMAT, $this->joined_date);
        } else {
            return $this->joined_date;
        }
    
    }

    function setAvatar($avatar_url)
    {
        $this->avatar = $avatar_url;
    }

    function getAvatar($format=TRUE)
    {
        if (empty($this->avatar)) {
            return NULL;
        }
        if ($format) {
            return sprintf('<img src="%s" />', $this->avatar);
        } else {
            return $this->avatar;
        }
    }

    function setContactEmail($email_address)
    {
        if (PHPWS_Text::isValidInput($email_address, 'email')) {
            $this->contact_email = $email_address;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function getContactEmail($format=FALSE)
    {
        if ($format) {
            return '<a href="mailto:' . $this->contact_email . '" />' . $this->display_name . '</a>';
        } else {
            return $this->contact_email;
        }
    }

    function setWebsite($website)
    {
        $this->website = strip_tags($website);
    }

    function getWebsite($format=FALSE)
    {
        if ($format && isset($this->website)) {
            return sprintf('<a href="%s" title="%s">%s</a>',
                           $this->website,
                           sprintf(_('%s\'s Website'), $this->display_name),
                           _('Website'));
        } else {
            return $this->website;
        }
    }

    function setLocation($location)
    {
        $this->location = strip_tags($location);
    }

    function lock()
    {
        $this->locked = 1;
    }

    function unlock()
    {
        $this->locked = 0;
    }


    function kill()
    {
        return $this->delete();
    }

    function hasError()
    {
        return isset($this->_error);
    }

    function getError()
    {
        return $this->_error;
    }

    function logError()
    {
        if (PEAR::isError($this->_error)) {
            PHPWS_Error::log($this->_error);
        }
    }

    function getTpl()
    {
        $template['AUTHOR_NAME']   = $this->display_name;
        $template['COMMENTS_MADE'] = $this->comments_made;

        $signature = $this->getSignature();

        if (!empty($signature)) {
            $template['SIGNATURE'] = $signature;
        }

        if (!empty($this->joined_date)) {
            $template['JOINED_DATE'] = $this->getJoinedDate(TRUE);
            $template['JOINED_DATE_LABEL'] = _('Joined');
        }

        if (isset($this->avatar)) {
            $template['AVATAR'] = $this->getAvatar();
        }

        if (isset($this->contact_email)) {
            $template['CONTACT_EMAIL'] = $this->getContactEmail(TRUE);
        }
    
        if (isset($this->website)) {
            $template['WEBSITE'] = $this->getWebsite(TRUE);
        }

        if (isset($this->location)) {
            $template['LOCATION'] = $this->location;
            $template['LOCATION_LABEL'] = _('Location');
        }
        return $template;
    }

    function saveOptions()
    {
        PHPWS_Core::initModClass('filecabinet', 'Image.php');
        if (PHPWS_Settings::get('comments', 'allow_signatures')) {
            $this->setSignature($_POST['signature']);
        } else {
            $this->signature = NULL;
        }

        if (empty($_POST['avatar'])) {
            $val['avatar'] = NULL;
        } else {
            $image_info = @getimagesize($_POST['avatar']);
            if (!$image_info) {
                $errors[] = _('Could not access image url.');
            }
        }

        if (PHPWS_Settings::get('comments', 'allow_avatars')) {
            if (PHPWS_Settings::get('comments', 'local_avatars')) {
                $image = & new PHPWS_Image;
                $image->setDirectory('images/comments/');
                $image->setMaxWidth(COMMENT_MAX_AVATAR_WIDTH);
                $image->setMaxHeight(COMMENT_MAX_AVATAR_HEIGHT);
                
                if (!$image->importPost('avatar')) {
                    if (isset($image->_errors)) {
                        foreach ($image->_errors as $oError) {
                            $errors[] = $oError->getMessage();
                        }
                    }
                } else {
                    $result = $image->write();
                    if (PEAR::isError($result)) {
                        PHPWS_Error::log($result);
                        $errors[] = array(_('There was a problem saving your image.'));
                    } else {
                        $this->setAvatar($image->getPath());
                    }
                }
            } else {
                if ($this->testAvatar(trim($_POST['avatar']))) {
                    $this->setAvatar($_POST['avatar']);
                }
            }
        } else {
            $this->avatar = NULL;
        }

        // need some error checking here
        if (empty($_POST['contact_email'])) {
            $this->contact_email = NULL;
        } else {
            if (!$this->setContactEmail($_POST['contact_email'])) {
                $errors[] = _('Your contact email is formatted improperly.');
            }
        }

        if (isset($errors)) {
            return $errors;
        } else {
            $this->saveUser();
            return TRUE;
        }
    }

    function saveUser()
    {
        if ($this->isNew()) {
            $user = & new PHPWS_User($this->user_id);
            $this->display_name = $user->getDisplayName();
        }
        return $this->save();
    }

    /**
     * Tests an image's url to see if it is the correct file type,
     * dimensions, etc.
     */
    // Not finished
    function testAvatar($url)
    {
        $test = @getimagesize($url);
        if (!$test) {
            return FALSE;
        }

        return TRUE;
    }

}

?>