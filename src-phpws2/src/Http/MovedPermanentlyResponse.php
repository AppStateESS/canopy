<?php
namespace phpws2\Http;
/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
abstract class MovedPermanentlyResponse extends RedirectResponse
{
    protected function getHttpResponseCode()
    {
        return 301;
    }
}
