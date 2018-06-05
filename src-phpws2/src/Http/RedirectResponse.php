<?php

namespace phpws2\Http;

/**
 * Description
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */

abstract class RedirectResponse extends \Canopy\Response
{
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
        $this->code = $this->getHttpResponseCode();
    }

    public function getUrl()
    {
        return $this->url;
    }

    protected abstract function getHttpResponseCode();

    public function forward()
    {
        header($this->getStatusLine());
        header('Location: ' . $this->getUrl());
        exit(); // TODO: Never ever exit early
    }
}
