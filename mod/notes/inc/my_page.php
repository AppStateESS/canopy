<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

function my_page()
{
    translate('notes');
    PHPWS_Core::initModClass('notes', 'My_Page.php');
    $my_page = new Notes_My_Page;
    $result = $my_page->main();
    translate();
    return $result;
}

?>