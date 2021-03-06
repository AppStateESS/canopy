<?php
namespace phpws;

/**
 * Handles the logging and routing of security problems
 *
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 
 */

class Security {
    public static function log($message)
    {
        if (class_exists('\Current_User') && isset($_SESSION['User'])) {
            $username = \Current_User::getUsername();
        } else {
            $username = 'Unknown user';
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        if (isset($_SERVER['HTTP_REFERER'])) {
            $via = sprintf('Coming from: %s', $_SERVER['HTTP_REFERER']);
        }
        else {
            $via = 'Unknown source';
        }

        $infraction = sprintf('%s@%s %s -- %s', $username, $ip, $via, $message);

        \phpws\PHPWS_Core::log(escapeshellcmd($infraction), 'security.log', 'Warning');
    }
}

