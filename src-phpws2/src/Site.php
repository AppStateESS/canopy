<?php

namespace phpws2;

/**
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

class Site extends \phpws2\Resource {
    /**
     * Site objects live in the sites table
     * @var string
     */
    protected $table = 'sites';
    protected $name = null;

    public static function getCurrentSite()
    {
        return null;
    }

}

