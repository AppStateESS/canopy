<?php

namespace phpws2\Variable;

/**
 * A string variable designed for just URLs.
 * 
 * This version only works with offsite urls, not relative.
 * 
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package phpws2
 * @subpackage Variable
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Url extends StringVar {

    /**
     * @var string
     */
    protected $input_type = 'url';

    protected $prepend_http = false;
    
    /**
     * This brain-frying regular expression was written by Diego Perini @ https://gist.github.com/dperini/729294
     * @var string
     */
    protected $regexp_match = '_^(?:(?:https?)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$_iuS';

    public function setPrependHttp($prepend)
    {
        $this->prepend_http = (bool) $prepend;
    }
    
    public function set($value)
    {
        if (!empty($value) && $this->prepend_http && !preg_match('@^https?://@', $value)) {
            $value = 'http://' . $value;
        }
        return parent::set($value);
    }
}
