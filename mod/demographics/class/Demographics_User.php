<?php
  /**
   * Demographics user is a class that you extend to store user
   * information.
   * If you set a _table variable, demographics will load information
   * from that table as well. Not having a _table saves all the information
   * in demographics
   *
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */ 


class Demographics_User {
    var $user_id = 0;
    var $_error = NULL;
    // indicates a new demographics user
    var $_new_user = FALSE;
    var $_table = NULL;

    function load() {
        if (!$this->user_id) {
            return;
        }

        if (isset($this->_table)) {
            $db = new PHPWS_DB($this->_table);
            $db->addJoin('left', $this->_table, 'demographics', 'user_id', 'user_id');
            $db->addColumn($this->_table . '.*');
            $db->addColumn('demographics.*');
        } else {
            $db = new PHPWS_DB('demographics');
        }

        $db->addWhere('user_id', (int)$this->user_id);

        $result = $db->loadObject($this);
        if (PEAR::isError($result)) {
            $this->_error = $result;
            return FALSE;
        } elseif ($result) {
            return TRUE;
        } else {
            $this->_new_user = TRUE;
            return FALSE;
        }

    }

    function isNew()
    {
        return $this->_new_user;
    }

    /**
     * Updates a current user demographic
     */
    function save()
    {
        if (!$this->user_id) {
            return FALSE;
        }

        $db = new PHPWS_DB('demographics');
        if (!$this->_new_user) {
            $db->addWhere('user_id', $this->user_id);
        }

        $result = $db->saveObject($this);
        if (PEAR::isError($result)) {
            $this->_error = $result;
            return $result;
        }

        if (isset($this->_table)) {
            $db = new PHPWS_DB($this->_table);
            if (!$this->_new_user) {
                $db->addWhere('user_id', $this->user_id);
            }

            $result = $db->saveObject($this);

            if (PEAR::isError($result)) {
                $this->_error = $result;
                return $result;
            }

        } 
        return TRUE;
    }

    function delete()
    {
        if (!$this->user_id) {
            return FALSE;
        }

        $db = new PHPWS_DB('demographics');
        $db->addWhere('user_id', $this->user_id);
        $result = $db->delete();
        if (PEAR::isError($result)) {
            $this->_error = $result;
            return $result;
        }

        if (isset($this->_table)) {
            $db = new PHPWS_DB($this->_table);
            $db->addWhere('user_id', $this->user_id);

            $result = $db->delete();
            if (PEAR::isError($result)) {
                $this->_error = $result;
                return $result;
            }
        } 

        return TRUE;

    }
}

?>