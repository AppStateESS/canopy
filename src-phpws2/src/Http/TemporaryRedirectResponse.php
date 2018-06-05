<?php
namespace phpws2\Http;
/**
 * Use this to redirect to another URI temporarily.  Explicitly does alert the
 * user after a POST; if you don't want that, use SeeOtherResponse.
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class TemporaryRedirectResponse extends RedirectResponse
{
    protected function getHttpResponseCode()
    {
        return 302;
    }
}
