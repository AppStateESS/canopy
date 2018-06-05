<?php

/**
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function javascriptEnabled()
{
    if (isset($_SESSION['javascript_enabled'])) {
        return $_SESSION['javascript_enabled'];
    } else {
        return false;
    }
}

function javascript($directory, $data = NULL, $base = null, $wrap_header = false, $wrap_body = false)
{
    return Layout::getJavascript($directory, $data, $base, $wrap_header, $wrap_body);
}

function check_cookie()
{
    $cookie =  \phpws\PHPWS_Cookie::read('cookie_enabled');
    if (!$cookie) {
        if (!isset($_GET['cc'])) {
             \phpws\PHPWS_Cookie::write('cookie_enabled', 'y');
            \phpws\PHPWS_Core::reroute('index.php?cc=1');
        } else {
            $tpl['MESSAGE'] = 'This site requires you to enable cookies on your browser.';
            $message = PHPWS_Template::process($tpl, 'layout', 'no_cookie.tpl');
            Layout::nakedDisplay($message);
        }
    }
}

/**
 * Works like javascript function but uses a module directory instead
 * @see Layout::getJavascript
 * @param string $module
 * @param string $directory
 * @param array $data
 * @param boolean $wrap_header If true, wrap the contents of head.js with <script> tags
 * @param boolean $wrap_body If true, wrap the contents of body.js with <script> tags
 * @return string
 */
function javascriptMod($module, $directory, $data = null, $wrap_header = false, $wrap_body = false)
{
    if (preg_match('/\W/', $module)) {
        return false;
    }
    $root_directory = "mod/$module/";
    return Layout::getJavascript($directory, $data, $root_directory, $wrap_header, $wrap_body);
}

