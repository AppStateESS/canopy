<?php
/**
 * @version $Id$
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 */

$default['class'] = 'js-slider';
$default['id'] = 'span-' . time();
$default['speed'] = 'fast';

$speed = !empty($data['speed']) ? $data['speed'] : 1;
switch ($speed) {
 case 'fast':
 case 'normal':
 case 'slow':
     break;

 default:
     $data['speed'] = 'fast';
     break;
}
