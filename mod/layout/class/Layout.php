<?php

/**
 * Controls the viewing and layout of the site
 *
 
 * @author  Matt McNaney <mcnaneym@appstate.edu>
 * @package Core
 */
\phpws\PHPWS_Core::requireConfig('layout');
require_once PHPWS_SOURCE_DIR . 'mod/layout/class/Functions.php';

if (!defined('LAYOUT_IGNORE_JS_CHECK')) {
    define('LAYOUT_IGNORE_JS_CHECK', false);
}

/* * ******** Errors *************** */

define('LAYOUT_SESSION_NOT_SET', -1);
define('LAYOUT_NO_CONTENT', -2);
define('LAYOUT_NO_THEME', -3);
define('LAYOUT_BAD_JS_DATA', -4);
define('LAYOUT_JS_FILE_NOT_FOUND', -5);
define('LAYOUT_BOX_ORDER_BROKEN', -6);
define('LAYOUT_INI_FILE', -7);
define('LAYOUT_BAD_THEME_VAR', -8);

if (!defined('LAYOUT_THEME_EXEC')) {
    define('LAYOUT_THEME_EXEC', false);
}

if (!defined('XML_MODE')) {
    define('XML_MODE', false);
}

if (!defined('LAYOUT_FORCE_MOD_JS')) {
    define('LAYOUT_FORCE_MOD_JS', false);
}

\phpws\PHPWS_Core::initModClass('layout', 'Layout_Settings.php');
\phpws\PHPWS_Core::initCoreClass('Template.php');

class Layout
{
    static $hideDefaultThemeVar = false;

    /**
     * Adds your content to the layout queue.
     *
     * If the module and content_var are set, layout will remember
     * the "position" of the content. The admin will then be able
     * to shift this position using the administrative options in Layout.
     *
     * If the module and content_var are NOT set, the data goes into the
     * BODY tag.
     *
     * If default_body is true, then the content we will placed into the
     * BODY tag by default instead of the DEFAULT tag. This occurs when
     * a preset theme variable was not created for the content variable.
     *
     * @author Matt McNaney <mcnaneym@appstate.edu>
     */
    public static function add($text, $module = NULL, $content_var = NULL, $default_body = FALSE)
    {
        if (!is_string($text)) {
            return;
        }
        Layout::checkSettings();
        // If content variable is not in system (and not NULL) then make
        // a new box for it.

        if (isset($module) && isset($content_var)) {
            if (!$_SESSION['Layout_Settings']->isContentVar($module, $content_var)) {
                if ($default_body) {
                    $theme_var = DEFAULT_THEME_VAR;
                } else {
                    $theme_var = NULL;
                }
                Layout::addBox($content_var, $module, $theme_var);
            }
        } else {
            $module = 'layout';
            $content_var = DEFAULT_CONTENT_VAR;
        }

        Layout::_loadBox($text, $module, $content_var);
    }

    public static function plug($content, $theme_var, $overwrite=false)
    {
        if (empty($content) || empty($theme_var) || preg_match('/\W/', $theme_var)) {
            return false;
        }
        if ($overwrite) {
            $GLOBALS['Layout_Plugs'][strtoupper($theme_var)] = array($content);
        } else {
            $GLOBALS['Layout_Plugs'][strtoupper($theme_var)][] = $content;
        }
    }
    
    /**
     * If set to true, the DEFAULT theme variable will be nulled out prior
     * to display.
     * @param boolean $hide
     */
    public static function hideDefault($hide=true)
    {
        self::$hideDefaultThemeVar = (bool) $hide;
    }
    
    public function getPlugs()
    {
        if (!isset($GLOBALS['Layout_Plugs'])) {
            return null;
        } else {
            foreach ($GLOBALS['Layout_Plugs'] as $theme_var => $content) {
                $tpl[$theme_var] = implode('', $content);
            }
            return $tpl;
        }
    }

    public static function _loadBox($text, $module, $contentVar)
    {
        $GLOBALS['Layout'][$module][$contentVar][] = $text;
    }

    public static function addBox($content_var, $module, $theme_var = NULL, $theme = NULL)
    {
        \phpws\PHPWS_Core::initModClass('layout', 'Box.php');

        if (!isset($theme)) {
            $theme = $_SESSION['Layout_Settings']->current_theme;
        }

        if (!isset($theme_var)) {
            $mod_theme_var = strtoupper(sprintf('%s_%s', $module, $content_var));

            if (!empty($_SESSION['Layout_Settings']->_theme_variables) && in_array($mod_theme_var, $_SESSION['Layout_Settings']->_theme_variables)) {
                $theme_var = $mod_theme_var;
            } else {
                $theme_var = DEFAULT_BOX_VAR;
            }
        }

        $box = new Layout_Box;
        $box->setTheme($theme);
        $box->setContentVar($content_var);
        $box->setModule($module);
        $box->setThemeVar($theme_var);

        $result = $box->save();
        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            \phpws\PHPWS_Core::errorPage();
        }

        Layout::resetBoxes();
    }

    /**
     * Path to javascript file you want included. PHPWS_SOURCE_HTTP is added automatically.
     * @param string $file
     */
    public static function includeJavascript($file)
    {
        $path = PHPWS_SOURCE_HTTP . $file;

        self::addJSHeader("<script type='text/javascript' src='$path'></script>", md5($path));
    }

    /**
     * Adds a script to the header of the theme.
     * @staticvar int $index_count
     * @param string $script Content of the javascript
     * @param string $index Unique id passed to prevent duplicate scripts
     */
    public static function addJSHeader($script, $index = NULL)
    {
        static $index_count = 0;

        if (empty($index)) {
            $index = $index_count++;
        }
        if (empty($script)) {
            return;
        }
        $GLOBALS['Layout_JS'][$index]['head'] = $script;
    }

    public static function removeJSHeader($index)
    {
        unset($GLOBALS['Layout_JS'][$index]);
    }

    public static function extraStyle($filename)
    {
        $styles = Layout::getExtraStyles();
        if (!isset($styles[$filename])) {
            return;
        }

        $link['file'] = Layout::getThemeDir() . $filename;
        $GLOBALS['Extra_Style'] = $link;
    }

    public static function addLink($link)
    {
        $GLOBALS['Layout_Links'][] = $link;
    }

    /**
     * Adds a module's style sheet to the style sheet list
     */
    public static function addStyle($module, $filename = NULL)
    {
        if (!LAYOUT_ALLOW_STYLE_LINKS) {
            return;
        }

        if (!PHPWS_Settings::get('layout', 'include_css_order')) {
            return;
        }

        if (!isset($filename)) {
            $filename = 'style.css';
        }

        $tag = md5($module . $filename);

        if (isset($GLOBALS['Style'][$tag])) {
            return;
        }

        $cssFile['tag'] = & $tag;
        $moduleLoc = sprintf('mod/%s/templates/%s', $module, $filename);

        $cssFile['file'] = $moduleLoc;

        $themeFile['file'] = PHPWS_Template::getTplDir($module) . $filename;
        if (is_file($themeFile['file'])) {
            Layout::addToStyleList(str_replace(PHPWS_SOURCE_DIR, '', $themeFile));
        } else {
            Layout::addToStyleList($cssFile);
        }
    }

    /**
     * Receives a file string or a value array of style information and adds it
     * to the GLOBAL Style variable.
     *
     * @param mixed $value
     * @return void
     */
    public static function addToStyleList($value)
    {
        $alternate = FALSE;
        $title = NULL;
        $tag = NULL;
        $media = NULL;

        if (!is_array($value)) {
            $file = $value;
        } else {
            extract($value);
        }

        $style = array('file' => $file,
            'alternate' => $alternate,
            'title' => $title,
            'media' => $media
        );

        if (isset($tag)) {
            $GLOBALS['Style'][$tag] = $style;
        } else {
            $GLOBALS['Style'][] = $style;
        }
    }

    /**
     * Displays content passed to it disregarding all other information
     * passed to it.
     *
     * This is a good function to use for a popup window. The display
     * retains style sheet information.
     *
     */
    public static function nakedDisplay($content = NULL, $title = NULL, $use_blank = false)
    {
        Layout::disableRobots();
        echo Layout::wrap($content, $title, $use_blank);
        exit();
    }

    public static function checkSettings()
    {
        if (!isset($_SESSION['Layout_Settings'])) {
            $_SESSION['Layout_Settings'] = new Layout_Settings;
        }
    }

    public function clear($module, $contentVar)
    {
        unset($GLOBALS['Layout'][$module][$contentVar]);
    }

    public static function disableRobots()
    {
        $GLOBALS['Layout_Robots'] = '00';
    }

    public function disableFollow()
    {
        if (!isset($GLOBALS['Layout_Robots'])) {
            Layout::initLayout();
        }

        switch ($GLOBALS['Layout_Robots']) {
            case '01':
                $GLOBALS['Layout_Robots'] = '00';
                break;

            case '11':
                $GLOBALS['Layout_Robots'] = '10';
                break;
        }
    }

    public function disableIndex()
    {
        if (!isset($GLOBALS['Layout_Robots'])) {
            Layout::initLayout();
        }

        switch ($GLOBALS['Layout_Robots']) {
            case '10':
                $GLOBALS['Layout_Robots'] = '00';
                break;

            case '11':
                $GLOBALS['Layout_Robots'] = '01';
                break;
        }
    }

    public static function processHeld()
    {
        if (empty($GLOBALS['Layout_Held'])) {
            return;
        }

        foreach ($GLOBALS['Layout_Held'] as $module => $content_info) {
            foreach ($content_info as $content_var => $info) {
                $display = PHPWS_Template::process($info['values'], $module, $info['template']);
                if ($module != 'layout') {
                    Layout::add($display, $module, $content_var);
                } else {
                    Layout::add($display);
                }
            }
        }
    }

    /**
     * Main function controlling the display of data passed
     * to layout
     */
    public static function display()
    {
        if (LAYOUT_THEME_EXEC) {
            $theme_exec = sprintf('%sthemes/%s/theme.php', PHPWS_SOURCE_DIR, Layout::getCurrentTheme());
            if (is_file($theme_exec)) {
                include_once $theme_exec;
            }
        }

        Layout::processHeld();
        $themeVarList = array();

        $contentList = Layout::getBoxContent();
        // if content list is blank
        // 404 error?
        foreach ($contentList as $module => $content) {
            foreach ($content as $contentVar => $template) {

                if (!($theme_var = $_SESSION['Layout_Settings']->getBoxThemeVar($module, $contentVar))) {
                    $theme_var = DEFAULT_THEME_VAR;
                }

                if (!in_array($theme_var, $themeVarList)) {
                    $themeVarList[] = $theme_var;
                }

                $order = $_SESSION['Layout_Settings']->getBoxOrder($module, $contentVar);

                if (empty($order)) {
                    $order = MAX_ORDER_VALUE;
                }

                if (isset($unsortedLayout[$theme_var][$order])) {
                    PHPWS_Error::log(LAYOUT_BOX_ORDER_BROKEN, 'layout', 'Layout::display', $theme_var);
                }
                if (isset($unsortedLayout[$theme_var][$order])) {
                    $unsortedLayout[$theme_var][$order] .= $template;
                } else {
                    $unsortedLayout[$theme_var][$order] = $template;
                }
            }
        }

        if (isset($GLOBALS['Layout_Plugs'])) {
            foreach ($GLOBALS['Layout_Plugs'] as $plug_var => $content) {
                if (!in_array($plug_var, $themeVarList)) {
                    $themeVarList[] = $plug_var;
                    $unsortedLayout[$plug_var][0] = implode('', $content);
                }
            }
        }
        if (isset($themeVarList)) {
            foreach ($themeVarList as $theme_var) {
                ksort($unsortedLayout[$theme_var]);
                $upper_theme_var = strtoupper($theme_var);
                $bodyLayout[$upper_theme_var] = implode('', $unsortedLayout[$theme_var]);
            }

            if (self::$hideDefaultThemeVar) {
                $bodyLayout['DEFAULT'] = null;
            }
            Layout::loadHeaderTags($bodyLayout);
            $finalTheme = Layout::loadTheme(Layout::getCurrentTheme(), $bodyLayout);

            if (PHPWS_Error::isError($finalTheme)) {
                PHPWS_Error::log($finalTheme);
                $content = implode('', $bodyLayout);
            } else {
                $content = $finalTheme->get();
                if (LABEL_TEMPLATES) {
                    $content = "\n<!-- START TPL: " . $finalTheme->lastTemplatefile . " -->\n"
                            . $content
                            . "\n<!-- END TPL: " . $finalTheme->lastTemplatefile . " -->\n";
                }
            }
        } else {
            $plain = implode('<br />', $unsortedLayout[$theme_var]);
            $content = Layout::wrap($plain);
        }
        return $content;
    }

    public static function getBox($module, $contentVar)
    {
        if (isset($_SESSION['Layout_Settings']->_boxes[$module][$contentVar])) {
            return $_SESSION['Layout_Settings']->_boxes[$module][$contentVar];
        } else {
            return NULL;
        }
    }

    public function getContentVars()
    {
        Layout::checkSettings();
        return $_SESSION['Layout_Settings']->getContentVars();
    }

    public static function getCurrentTheme()
    {   Layout::checkSettings();
        return $_SESSION['Layout_Settings']->current_theme;
    }

    /**
     * Loads information sent to add function
     */
    public static function getBoxContent()
    {
        $list = NULL;
        if (!isset($GLOBALS['Layout'])) {
            return PHPWS_Error::get(LAYOUT_SESSION_NOT_SET, 'layout', 'getBoxContent');
        }

        foreach ($GLOBALS['Layout'] as $module => $content) {
            foreach ($content as $contentVar => $contentList) {
                if (empty($contentList) || !is_array($contentList)) {
                    continue;
                }
                $list[$module][$contentVar] = implode('', $contentList);
            }
        }

        return $list;
    }

    public static function getDefaultTheme()
    {
        return $_SESSION['Layout_Settings']->default_theme;
    }

    /**
     * Loads a javascript file into memory
     * @param string $directory
     * @param array $data
     * @param string $base  Base directory of Javascript. Defaults to null, looking in the main javascript directory.
     *                      For a module, base would be 'mod/modulename/'
     * @param boolean $wrap_header If true, wrap the contents of head.js with <script> tags
     * @param boolean $wrap_body If true, wrap the contents of body.js with <script> tags
     * @return unknown_type
     */
    public static function getJavascript($directory, array $data = NULL, $base = NULL, $wrap_header = false, $wrap_body = false)
    {
        // previously a choice, now mandated. Leaving this in for backwards
        // compatibility
        if (preg_match('/^modules\//', $directory)) {
            $directory = preg_replace('@^\./@', '', $directory);
            $js_dir = explode('/', $directory);
            foreach ($js_dir as $key => $dir) {
                if ($dir == 'modules') {
                    $start_key = $key + 1;
                    break;
                }
            }
            $js = null;
            $directory = sprintf('mod/%s/javascript/%s', $js_dir[$start_key++], $js_dir[$start_key]);
        } else {
            $js = 'javascript/';
        }

        \phpws\PHPWS_Core::initCoreClass('File.php');
        $headfile = PHPWS_SOURCE_DIR . $base . $js . $directory . '/head.js';
        $bodyfile = PHPWS_SOURCE_DIR . $base . $js . $directory . '/body.js';
        $defaultfile = PHPWS_SOURCE_DIR . $base . $js . $directory . '/default.php';

        if (is_file($defaultfile)) {
            require $defaultfile;
        }

        if (isset($default)) {
            if (isset($data)) {
                $data = array_merge($default, $data);
            } else {
                $data = $default;
            }
        }

        $data['source_http'] = PHPWS_SOURCE_HTTP;
        $data['source_dir'] = PHPWS_SOURCE_DIR;
        $data['home_http'] = \phpws\PHPWS_Core::getHomeHttp();
        $data['home_dir'] = PHPWS_HOME_DIR;

        try {
            Layout::loadJavascriptFile($headfile, $directory, $data, $wrap_header);
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        if (is_file($bodyfile)) {
            if (!empty($data)) {
                if ($wrap_body) {
                    return '<script type="text/javascript">' . PHPWS_Template::process($data, 'layout', $bodyfile, TRUE) . '</script>';
                } else {
                    return PHPWS_Template::process($data, 'layout', $bodyfile, TRUE);
                }
            } else {
                if ($wrap_body) {
                    return '<script type="text/javascript">' . file_get_contents($bodyfile) . '</script>';
                } else {
                    return file_get_contents($bodyfile);
                }
            }
        }
    }

    public static function getMetaRobot($meta_robots)
    {
        switch ((string) $meta_robots) {
            case '11':
                return 'all';
                break;

            case '10':
                return 'index, nofollow';
                break;

            case '01':
                return 'noindex, follow';
                break;

            case '00':
                return 'none';
                break;
        }
    }

    public static function getMetaTags($page_metatags = null)
    {
        if (!$page_metatags) {
            $meta_tags_settings = $_SESSION['Layout_Settings']->getMetaTags();
            extract($meta_tags_settings);
        } else {
            extract($page_metatags);
        }

        // Say it loud
        $metatags[] = '<meta name="generator" content="Canopy" />';

        $metatags[] = '<meta content="text/html; charset=UTF-8"  http-equiv="Content-Type" />';
        if (!empty($author)) {
            $metatags[] = '<meta name="author" content="' . $meta_author . '" />';
        } else {
            $metatags[] = '<meta name="author" content="Canopy" />';
        }

        if (!empty($meta_keywords)) {
            $metatags[] = '<meta name="keywords" content="' . $meta_keywords . '" />';
        }

        if (isset($GLOBALS['Layout_Description'])) {
            $meta_description = & $GLOBALS['Layout_Description'];
        }

        if (!empty($meta_description)) {
            $metatags[] = '<meta name="description" content="' . $meta_description . '" />';
        }

        if (!empty($meta_owner)) {
            $metatags[] = '<meta name="owner" content="' . $meta_owner . '" />';
        }

        $robot = Layout::getMetaRobot($meta_robots);
        $metatags[] = '<meta name="robots" content="' . $robot . '" />';

        if (isset($GLOBALS['extra_meta_tags']) && is_array($GLOBALS['extra_meta_tags'])) {
            $metatags = array_merge($metatags, $GLOBALS['extra_meta_tags']);
        }

        return implode("\n", $metatags);
    }

    /**
     * Uses meta tags to load or refresh a new page
     */
    public static function metaRoute($address = NULL, $time = 5)
    {
        if (empty($address)) {
            $address = './index.php';
        }

        $time = (int) $time;

        $GLOBALS['extra_meta_tags'][] = sprintf('<meta http-equiv="refresh" content="%s; url=%s" />', $time, $address);
    }

    /**
     * 0 : don't include module css
     * 1 : modules first
     * 2 : theme first
     */
    public static function getStyleLinks($header = FALSE)
    {
        if (!isset($GLOBALS['Style'])) {
            return TRUE;
        }

        if (PHPWS_Settings::get('layout', 'include_css_order') == 2) {
            $hold = true;
        } else {
            $hold = false;
        }

        foreach ($GLOBALS['Style'] as $key => $link) {
            // lazy test BUT module style sheets will be a 32 character hash
            if ($hold && strlen((string) $key) < 4) {
                $hold_css[] = Layout::styleLink($link, $header);
            } else {
                $links[] = Layout::styleLink($link, $header);
            }
        }

        if (isset($GLOBALS['Extra_Style'])) {
            $links[] = Layout::styleLink($GLOBALS['Extra_Style'], $header);
        }

        if (!empty($hold_css)) {
            $links = array_merge((array) $hold_css, (array) $links);
        }

        return implode("\n", $links);
    }

    public static function getTheme()
    {
        Layout::checkSettings();
        return $_SESSION['Layout_Settings']->current_theme;
    }

    public static function getThemeDir()
    {
        Layout::checkSettings();
        $themeDir = Layout::getTheme();
        //return PHPWS_SOURCE_DIR . "themes/$themeDir/";
        return "themes/$themeDir/";
    }

    /*
     * Loads a javascript file into the header of the theme.
     *
     * @param string $filename  The file name of the javascript to be included
     * @param string $index  The name of javascript. prevents repeats
     * @param array $data
     * @param boolean $wrap_contents If true, wrap the contents of head.js with <script> tags
     * @throws \Exception
     */

    public static function loadJavascriptFile($filename, $index, $data = NULL, $wrap_contents = false)
    {
        if (!is_file($filename)) {
            // Throw an exception because the file is missing.
            throw new \Exception("Missing javascript file: $filename");
            return FALSE;
        }

        if (isset($data)) {
            $result = PHPWS_Template::process($data, 'layout', $filename, TRUE);

            if (empty($result)) {
                $result = file_get_contents($filename);
            }
        } else {
            $result = file_get_contents($filename);
        }
        if ($wrap_contents) {
            $result = "<script type='text/javascript'>$result</script>";
        }
        Layout::addJSHeader($result, $index);
    }

    public function getModuleJavascript($module, $script_name, $data = NULL)
    {
        $base = "mod/$module/";
        $dir_check = "/javascript/$script_name";

        if (!is_dir(PHPWS_SOURCE_DIR . $base . $dir_check)) {
            return FALSE;
        }

        return Layout::getJavascript($script_name, $data, $base);
    }

    public static function importStyleSheets()
    {
        if (isset($_SESSION['Layout_Settings']->_style_sheets)) {
            foreach ($_SESSION['Layout_Settings']->_style_sheets as $css) {
                Layout::addToStyleList($css);
            }
        }
    }

    /**
     * Inserts the content data into the current theme
     */
    public static function loadTheme($theme, $template)
    {
        $tpl = new PHPWS_Template;
        $tpl->setRoot(PHPWS_SOURCE_DIR);
        $themeDir = Layout::getThemeDir();

        if (PHPWS_Error::isError($themeDir)) {
            PHPWS_Error::log($themeDir);
            \phpws\PHPWS_Core::errorPage();
        }

        $result = $tpl->setFile($themeDir . 'theme.tpl', TRUE);
        if (PHPWS_Error::isError($result)) {
            return $result;
        }
        if (!empty($GLOBALS['Layout_Collapse'])) {
            $template['COLLAPSE'] = 'id="layout-collapse"';
        }

        $template['THEME_DIRECTORY'] = Layout::getThemeDirRoot() . $theme . '/';
        $template['THEME_HTTP'] = Layout::getThemeHttpRoot() . $theme . '/';
        $template['SOURCE_THEME_HTTP'] = PHPWS_SOURCE_HTTP . 'themes/';
        $template['SOURCE_THEME_DIR'] = PHPWS_SOURCE_DIR . 'themes/';
        $tpl->setData($template);
        return $tpl;
    }

    public static function reset($theme = null)
    {
        if ($theme) {
            $_SESSION['Layout_Settings'] = new Layout_Settings($theme);
        } else {
            $_SESSION['Layout_Settings'] = new Layout_Settings;
        }
    }

    public static function getThemeDirRoot()
    {
        return PHPWS_SOURCE_DIR . 'themes/';
    }

    public static function getThemeHttpRoot()
    {
        return PHPWS_SOURCE_HTTP . 'themes/';
    }

    public static function resetBoxes()
    {
        $_SESSION['Layout_Settings']->loadContentVars();
        $_SESSION['Layout_Settings']->loadBoxes();
    }

    public static function resetDefaultBoxes()
    {
        $db = new PHPWS_DB('layout_box');
        $db->addWhere('theme', Layout::getDefaultTheme());
        $result = $db->delete();

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
        }
    }

    /**
     * Unlike the add function, which appends a content variable's
     * data, set OVERWRITES the current values
     */
    public static function set($text, $module = null, $contentVar = null)
    {
        Layout::checkSettings();
        if (!isset($contentVar)) {
            $contentVar = DEFAULT_CONTENT_VAR;
        }

        $GLOBALS['Layout'][$module][$contentVar] = NULL;
        Layout::add($text, $module, $contentVar);
    }

    public static function styleLink($link, $header = FALSE)
    {
        // NEED TO CHECK if using xml-stylesheet
        extract($link);

        if (!empty($title)) {
            $cssTitle = 'title="' . preg_replace('/\W/', '_', $title) . '"';
        } else {
            $cssTitle = NULL;
        }

        if (!empty($media)) {
            $media_tag = sprintf(' media="%s"', $media);
        } else {
            $media_tag = NULL;
        }

        $file = PHPWS_SOURCE_HTTP . $file;
        if ($header == TRUE) {
            if (isset($alternate) && $alternate == TRUE) {
                return sprintf('<?xml-stylesheet alternate="yes" %s href="%s" type="text/css"%s?>', $cssTitle, $file, $media_tag);
            } else {
                return sprintf('<?xml-stylesheet %s href="%s" type="text/css"%s?>', $cssTitle, $file, $media_tag);
            }
        } else {
            if (isset($alternate) && $alternate == TRUE) {
                return sprintf('<link rel="alternate stylesheet" %s href="%s" type="text/css"%s />', $cssTitle, $file, $media_tag);
            } else {
                return sprintf('<link rel="stylesheet" %s href="%s" type="text/css"%s />', $cssTitle, $file, $media_tag);
            }
        }
    }

    public static function submitHeaders($theme, &$template)
    {
        if (!defined('CURRENT_LANGUAGE')) {
            if (defined('DEFAULT_LANGUAGE')) {
                define('CURRENT_LANGUAGE', DEFAULT_LANGUAGE);
            } else {
                define('CURRENT_LANGUAGE', 'en_US');
            }
        }

        if (XML_MODE == TRUE && stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml')) {
            header('Content-Type: application/xhtml+xml; charset=UTF-8');
            $template['XML'] = '<?xml version="1.0" encoding="UTF-8"?>';
            $template['DOCTYPE'] = "\n" . '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
';
            $template['XHTML'] = '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . CURRENT_LANGUAGE . '">';
            $template['XML_STYLE'] = Layout::getStyleLinks(TRUE);
        } else {
            header('Content-Type: text/html; charset=UTF-8');
            $template['DOCTYPE'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
';
            $template['XHTML'] = '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . CURRENT_LANGUAGE . '" lang="' . CURRENT_LANGUAGE . '">';
            $template['STYLE'] = Layout::getStyleLinks(FALSE);
        }

        header('Content-Language: ' . CURRENT_LANGUAGE);
        header('Content-Script-Type: text/javascript');
        header('Content-Style-Type: text/css');
    }

    public static function getBase()
    {
        return '<base href="' . \phpws\PHPWS_Core::getBaseURL() . '" />';
    }

    public static function getPageTitle($only_root = FALSE)
    {
        return $_SESSION['Layout_Settings']->getPageTitle($only_root);
    }

    public static function addPageTitle($title)
    {
        $GLOBALS['Layout_Page_Title_Add'] = $title;
    }

    public static function getMetaPage($key_id)
    {
        $db = new PHPWS_DB('layout_metatags');
        $db->addWhere('key_id', $key_id);
        return $db->select('row');
    }

    public static function loadHeaderTags(&$template)
    {
        $page_metatags = null;

        $theme = Layout::getCurrentTheme();
        $key = \Canopy\Key::getCurrent();
        if (\Canopy\Key::checkKey($key, false)) {
            $page_metatags = Layout::getMetaPage($key->id);

            if (PHPWS_Error::isError($page_metatags)) {
                PHPWS_Error::log($page_metatags);
                $page_metatags = null;
            }
        }

        if (!isset($_SESSION['javascript_enabled'])) {
            $jsHead[] = '<noscript><meta http-equiv="refresh" content="0;url=index.php?nojs=1&ret=' . urlencode(\phpws\PHPWS_Core::getCurrentUrl()) . '"/></noscript>';
        }

        if (!isset($_SESSION['javascript_enabled'])) {
            $_SESSION['javascript_enabled'] = true;
        }

        if (isset($GLOBALS['Layout_JS'])) {
            foreach ($GLOBALS['Layout_JS'] as $script => $javascript) {
                $jsHead[] = $javascript['head'];
            }
        }

        if (!empty($jsHead)) {
            $template['JAVASCRIPT'] = implode("\n", $jsHead);
        }

        Layout::importStyleSheets();
        Layout::submitHeaders($theme, $template);
        if (!empty($GLOBALS['Layout_Links'])) {
            $template['STYLE'] .= "\n" . implode("\n", $GLOBALS['Layout_Links']);
        }

        $template['METATAGS'] = Layout::getMetaTags($page_metatags);
        if ($page_metatags) {
            $template['PAGE_TITLE'] = $_SESSION['Layout_Settings']->getPageTitle();
        } else {
            $template['PAGE_TITLE'] = $_SESSION['Layout_Settings']->getPageTitle();
        }

        $template['ONLY_TITLE'] = $_SESSION['Layout_Settings']->getPageTitle(TRUE); // Depricated
        // The Site's Name, as set in Layout 'Meta Tags' interface.
        $template['SITE_NAME'] = $_SESSION['Layout_Settings']->getPageTitle(TRUE);

        $template['BASE'] = Layout::getBase();
        $template['HTTP'] = \phpws\PHPWS_Core::getHttp(); // 'http' or 'https'
        // Complete URL of the site's home page
        $template['HOME_URL'] = \phpws\PHPWS_Core::getHomeHttp(true, true, true);
    }

    /**
     * Wraps the content with the layout header
     */
    public static function wrap($content, $title = NULL, $use_blank = false)
    {
        $theme = self::getCurrentTheme();
        $template['THEME_HTTP'] = Layout::getThemeHttpRoot() . $theme . '/';
        $template['CONTENT'] = $content;
        Layout::loadHeaderTags($template);

        if (isset($title)) {
            $template['PAGE_TITLE'] = strip_tags($title);
        }

        if ($use_blank) {
            $empty_tpl = sprintf('%sthemes/%s/blank.tpl', PHPWS_SOURCE_DIR, Layout::getCurrentTheme());

            if (is_file($empty_tpl)) {
                $result = PHPWS_Template::process($template, 'layout', $empty_tpl, TRUE);
                return $result;
            }
        }
        $result = PHPWS_Template::process($template, 'layout', 'header.tpl');
        return $result;
    }

    /**
     * Removes a content variable from the layout_box table.
     * @param string $content_var
     * @return boolean
     */
    public static function purgeBox($content_var)
    {
        $db = new PHPWS_DB('layout_box');
        $db->addWhere('content_var', $content_var);
        $result = $db->getObjects('Layout_Box');
        if (PHPWS_Error::isError($result)) {
            return $result;
        }

        if (empty($result)) {
            return true;
        }
        foreach ($result as $box) {
            $check = $box->kill();
            if (PHPWS_Error::isError($check)) {
                return $check;
            }
        }

        return TRUE;
    }

    /**
     * Returns an array of alternate style sheets for the current theme
     */
    public static function getAlternateStyles()
    {
        $sheets = null;
        $settings = $_SESSION['Layout_Settings'];

        if (!isset($settings->_style_sheets)) {
            return NULL;
        }

        foreach ($settings->_style_sheets as $css) {
            if (isset($css['title'])) {
                $filename = str_ireplace('themes/' . $_SESSION['Layout_Settings']->current_theme . '/', '', $css['file']);
                $sheets[$filename] = $css['title'];
            }
        }

        return $sheets;
    }

    public static function getKeyStyle($key_id)
    {
        if (!isset($_SESSION['Layout_Settings']->_key_styles[$key_id])) {
            $_SESSION['Layout_Settings']->loadKeyStyle($key_id);
        }

        return $_SESSION['Layout_Settings']->_key_styles[$key_id];
    }

    public static function getExtraStyles()
    {
        return $_SESSION['Layout_Settings']->_extra_styles;
    }

    public static function showKeyStyle()
    {
        $key = \Canopy\Key::getCurrent();
        if (!\Canopy\Key::checkKey($key)) {
            return NULL;
        }
        $style = Layout::getKeyStyle($key->id);
        if (!empty($style)) {
            Layout::extraStyle($style);
        }
    }

    public static function collapse()
    {
        $GLOBALS['Layout_Collapse'] = true;
    }

    /**
     * Fills in the meta description with the current key summary.
     */
    public static function keyDescriptions()
    {
        if (!PHPWS_Settings::get('layout', 'use_key_summaries')) {
            return;
        }
        $key = \Canopy\Key::getCurrent();
        if (!\Canopy\Key::checkKey($key, false)) {
            return NULL;
        }
        if (!empty($key->summary)) {
            $GLOBALS['Layout_Description'] = & $key->summary;
        } elseif (!empty($key->title)) {
            $GLOBALS['Layout_Description'] = & $key->title;
        }
    }

}
