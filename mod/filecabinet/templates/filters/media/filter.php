<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */


if ($is_video) {
        $tpl['THUMBNAIL'] = $thumbnail;
} else {
    $tpl['HEIGHT'] = 20;
    $tpl['WIDTH'] = 260;
}
