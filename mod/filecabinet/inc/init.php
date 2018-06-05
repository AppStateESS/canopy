<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

PHPWS_Text::addTag('filecabinet', array('document'));
\phpws\PHPWS_Core::requireInc('filecabinet', 'parse.php');
\phpws\PHPWS_Core::requireInc('filecabinet', 'defines.php');
\phpws\PHPWS_Core::initModClass('filecabinet', 'Cabinet.php');
