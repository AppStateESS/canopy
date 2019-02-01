<?php

namespace phpws2\View;

/**
 * Description
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 */
class JsonErrorView extends JsonView
{

    public function __construct(\Canopy\Request $request,
            \phpws2\Http\ErrorResponse $response)
    {
        $json = array();
        $json['url'] = $request->getUrl();
        $json['method'] = $request->getMethod();
        $json['module'] = $request->getModule();
        $json['code'] = $response->getCode();
        $json['phrase'] = $response->getPhrase();
        $json['backtrace'] = $response->getBacktrace();
        $json['exception'] = $response->getException();
        if (is_a($json['exception'], '\Exception')) {
            $json['exception_code'] = $response->getException()->getCode();
            $json['exception_file'] = $response->getException()->getFile();
            $json['exception_line'] = $response->getException()->getLine();
            $json['exception_message'] = $response->getException()->getMessage();
        }

        parent::__construct(array('error' => $json));
    }

    public function render()
    {
        if (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS) {
            http_response_code($this->data['error']['code']);
            $error = $this->data['error'];
            $this->displayError($error);
            exit;
        } else {
            if (is_object($this->data)) {
                \phpws2\Error::errorPage($this->data->error->code);
            } else {
                \phpws2\Error::errorPage($this->data['error']['code']);
            }
        }
    }

    private function displayError($error)
    {
        echo "url : ", $error['url'];
        echo "\nmethod : ", $error['method'];
        if(is_object($error['module'])){
            echo "\nmodule : ", $error['module']->getProperName();
	}else{
	    echo "\nmodule : ", $error['module'];
        }

        if (!empty($error['exception'])) {
            echo "\nexception_file : ", $error['exception_file'];
            echo "\nexception_line : ", $error['exception_line'];
            echo "\nexception_message : ", $error['exception_message'];
            echo "\n-------------------------------------\n";
            echo $error['exception']->getTraceAsString();
        }
    }

}

/**
 * From http://php.net/manual/en/function.http-response-code.php
 */
if (!function_exists('http_response_code')) {

    function http_response_code($code = NULL)
    {

        if ($code !== NULL) {

            switch ($code) {
                case 100: $text = 'Continue';
                    break;
                case 101: $text = 'Switching Protocols';
                    break;
                case 200: $text = 'OK';
                    break;
                case 201: $text = 'Created';
                    break;
                case 202: $text = 'Accepted';
                    break;
                case 203: $text = 'Non-Authoritative Information';
                    break;
                case 204: $text = 'No Content';
                    break;
                case 205: $text = 'Reset Content';
                    break;
                case 206: $text = 'Partial Content';
                    break;
                case 300: $text = 'Multiple Choices';
                    break;
                case 301: $text = 'Moved Permanently';
                    break;
                case 302: $text = 'Moved Temporarily';
                    break;
                case 303: $text = 'See Other';
                    break;
                case 304: $text = 'Not Modified';
                    break;
                case 305: $text = 'Use Proxy';
                    break;
                case 400: $text = 'Bad Request';
                    break;
                case 401: $text = 'Unauthorized';
                    break;
                case 402: $text = 'Payment Required';
                    break;
                case 403: $text = 'Forbidden';
                    break;
                case 404: $text = 'Not Found';
                    break;
                case 405: $text = 'Method Not Allowed';
                    break;
                case 406: $text = 'Not Acceptable';
                    break;
                case 407: $text = 'Proxy Authentication Required';
                    break;
                case 408: $text = 'Request Time-out';
                    break;
                case 409: $text = 'Conflict';
                    break;
                case 410: $text = 'Gone';
                    break;
                case 411: $text = 'Length Required';
                    break;
                case 412: $text = 'Precondition Failed';
                    break;
                case 413: $text = 'Request Entity Too Large';
                    break;
                case 414: $text = 'Request-URI Too Large';
                    break;
                case 415: $text = 'Unsupported Media Type';
                    break;
                case 500: $text = 'Internal Server Error';
                    break;
                case 501: $text = 'Not Implemented';
                    break;
                case 502: $text = 'Bad Gateway';
                    break;
                case 503: $text = 'Service Unavailable';
                    break;
                case 504: $text = 'Gateway Time-out';
                    break;
                case 505: $text = 'HTTP Version not supported';
                    break;
                default:
                    exit('Unknown http status code "' . htmlentities($code) . '"');
                    break;
            }

            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

            header($protocol . ' ' . $code . ' ' . $text);

            $GLOBALS['http_response_code'] = $code;
        } else {
            $code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
        }

        return $code;
    }

}
