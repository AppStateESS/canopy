<?php

namespace Canopy;

/**
 * Class to assist with _SERVER super globals.
 * @author Matthew McNaney <mcnaneym@appstate.edu>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Server
{

    private static $REQUEST_SINGLETON;

    /**
     * For now, the Request object is global. Eventually it will be swapped out
     * for a framework version.
     * @return \Canopy\Request
     */
    public static function getCurrentRequest()
    {
        if (is_null(self::$REQUEST_SINGLETON)) {
            if (strpos($_SERVER['REQUEST_URI'], $_SERVER['PHP_SELF']) === FALSE) {
                self::forwardInfo();
            }
            $url = self::getCurrentUrl();
            $method = $_SERVER['REQUEST_METHOD'];
            $vars = $_REQUEST;
            $data = file_get_contents('php://input');

            // The 'Accept' header might not always be set. If not,
            // we'll assume the client wants 'text/html'
            if (isset($_SERVER['HTTP_ACCEPT'])) {
                $accept = new \phpws2\Http\Accept($_SERVER['HTTP_ACCEPT']);
            } else {
                $accept = new \phpws2\Http\Accept('text/html');
            }

            self::$REQUEST_SINGLETON = new \Canopy\Request($url, $method, $vars,
                    $data, $accept);

            $dataValues = array();
            parse_str($data, $dataValues);

            $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : null;

            switch ($method) {
                case 'PATCH':
                    self::$REQUEST_SINGLETON->setPatchVars($dataValues);
                    break;

                case 'DELETE':
                    self::$REQUEST_SINGLETON->setDeleteVars($dataValues);
                    break;

                case 'PUT':
                    self::$REQUEST_SINGLETON->setPutVars($dataValues);
                    break;

                case 'POST':
                    if (strpos($content_type, 'multipart/form-data') !== false) {
                        self::$REQUEST_SINGLETON->setPostVars($_POST);
                    } else {
                        self::$REQUEST_SINGLETON->setPostVars($dataValues);
                    }
                    break;
            }
            self::$REQUEST_SINGLETON->setGetVars($_GET);
        }
        return self::$REQUEST_SINGLETON;
    }

    private static function forwardInfo()
    {
        $url = \phpws\PHPWS_Core::getCurrentUrl();

        if ($url == 'index.php' || $url == '') {
            return;
        }

        if (UTF8_MODE) {
            $preg = '/[^\w\-\pL]/u';
        } else {
            $preg = '/[^\w\-]/';
        }

        // Should ignore the ? and everything after it
        $qpos = strpos($url, '?');
        if ($qpos !== FALSE) {
            $url = substr($url, 0, $qpos);
        }

        $aUrl = explode('/', preg_replace('|/+$|', '', $url));
        $module = array_shift($aUrl);

        $mods = \phpws\PHPWS_Core::getModules(true, true);

        if (!in_array($module, $mods)) {
            $GLOBALS['Forward'] = $module;
            return;
        }

        if (preg_match('/[^\w\-]/', $module)) {
            return;
        }

        $_REQUEST['module'] = $_GET['module'] = & $module;

        $count = 1;
        $continue = 1;
        $i = 0;

        // Try and save some old links references
        if (count($aUrl) == 1) {
            $_GET['id'] = $_REQUEST['id'] = $aUrl[0];
            return;
        }

        while (isset($aUrl[$i])) {
            $key = $aUrl[$i];
            if (!$i && is_numeric($key)) {
                $_GET['id'] = $key;
                return;
            }
            $i++;
            if (isset($aUrl[$i])) {
                $value = $aUrl[$i];
                if (preg_match('/&/', $value)) {
                    $remain = explode('&', $value);
                    $j = 1;
                    $value = $remain[0];
                    while (isset($remain[$j])) {
                        $sub = explode('=', $remain[$j]);
                        $_REQUEST[$sub[0]] = $_GET[$sub[0]] = $sub[1];
                        $j++;
                    }
                }

                $_GET[$key] = $_REQUEST[$key] = $value;
            }
            $i++;
        }
    }

    /**
     * Returns the beginning of a web address based on secure socket status.
     * @return string
     */
    public static function getHttp()
    {
        if (isset($_SERVER['HTTPS']) &&
                strtolower($_SERVER['HTTPS']) == 'on') {
            return 'https://';
        } else {
            return 'http://';
        }
    }

    /**
     * Returns the current user's ip address.
     * @return string
     */
    public static function getUserIp()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        } else {
            throw new \Exception('SERVER REMOTE ADDRESS not set');
        }
    }

    /**
     * Returns the url of the current page
     * If redirect is true and a redirect occurs at the root level,
     * index.php is returned.
     * @param boolean $relative Returned site url does not contain web root.
     * @param boolean $use_redirect Returns the address as mod_rewrite format.
     * @return string
     */
    public static function getCurrentUrl($relative = true, $use_redirect = true)
    {
        static $saveUrl = [];
        if (isset($saveUrl[(int) $relative][(int) $use_redirect])) {
            return $saveUrl[(int) $relative][(int) $use_redirect];
        }

        $address = array();

        if (!$relative) {
            $address[] = self::getSiteUrl();
        }

        $self = $_SERVER['PHP_SELF'];

        if ($use_redirect && isset($_SERVER['REQUEST_URI'])) {
            // some users reported problems using redirect_url so parsing uri instead
            if ($_SERVER['REQUEST_URI'] != '/') {
                $root_url = substr($self, 0, strrpos($self, '/'));
                $address[] = preg_replace("@^$root_url/@", '',
                        $_SERVER['REQUEST_URI']);
            } else {
                $address[] = 'index.php';
            }
            $saveUrl[(int) $relative][(int) $use_redirect] = implode('',
                    $address);
        } else {
            $stack = explode('/', $self);
            $url = array_pop($stack);
            if (!empty($url)) {
                $address[] = $url;
            }

            if (!empty($_SERVER['QUERY_STRING'])) {
                $address[] = '?';
                $address[] = $_SERVER['QUERY_STRING'];
            }
            $address = implode('', $address);
            $address = preg_replace('@^/?@', '', $address);
            $saveUrl[(int) $relative][(int) $use_redirect] = $address;
        }
        return $saveUrl[(int) $relative][(int) $use_redirect];
    }

    /**
     *
     * @param boolean $with_http
     * @param boolean $with_directory
     * @return string
     */
    public static function getSiteUrl($with_http = true, $with_directory = true,
            $end_slash = true)
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            throw new \Exception('$_SERVER[HTTP_HOST] superglobal does not exist');
        }
        if ($with_http) {
            $address[] = self::getHttp();
        }
        $address[] = $_SERVER['HTTP_HOST'];
        if ($with_directory) {
            $address[] = dirname($_SERVER['PHP_SELF']);
        }

        $url = preg_replace('@\\\@', '/', implode('', $address));
        if ($end_slash && !preg_match('@/$@', $url)) {
            $url .= '/';
        }
        return $url;
    }

    /**
     * Sends the user to a new web page automatically based on the url.
     * @param string $url Address to forward to
     */
    public static function forward($url)
    {
        if (!preg_match('/^http(s)?:/i', $url)) {
            $url = self::getSiteUrl() . $url;
        }
        header('location: ' . $url);
        exit();
    }

    // @todo decide what to do for error pages
    public static function pageNotFound()
    {
        // @todo turn header back on
        header("HTTP/1.0 404 Not Found");
        echo '<html><head><title>404 - Page not found</title></head><body><h1>404 - Page not found</h1></body></html>';
    }

}
