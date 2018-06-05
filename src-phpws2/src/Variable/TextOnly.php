<?php

namespace phpws2\Variable;

/**
 * Text without html tags
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class TextOnly extends \phpws2\Variable\StringVar
{
    public function set($value)
    {
        if (preg_match('/<\/?[^>]+>/i', $value)) {
            throw new \Exception('Tags are not permitted in TextOnly');
        }
        parent::set($value);
    }
    
    public function addAllowedTags()
    {
        throw new \Exception('Tags are not permitted in TextOnly');
    }

}
