<?php
namespace phpws2\Http;
/**
 * Temporary Redirection - to clarify further, use either SeeOtherResponse (303) or
 * TemporaryRedirectResponse (307)
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class FoundResponse extends RedirectResponse
{
    protected function getHttpResponseCode()
    {
        return 302;
    }
}
