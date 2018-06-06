<?php
namespace phpws;
/**
 * Controls templates
 *
 * An extention of Pear's HTML_Template_IT class.
 * Fills in information specific to Canopy
 *
 * @version $Id$
 * @author  Matt McNaney <mcnaneym@appstate.edu>
 * @package Core
 */

require_once 'HTML/Template/Sigma.php';
PHPWS_Core::requireConfig('core', 'Template.php');

if (!defined('CACHE_TPL_LOCALLY')) {
    define('CACHE_TPL_LOCALLY', Wfalse);
}

class PHPWS_Template extends \HTML_Template_Sigma {
    public $module           = NULL;
    public $error            = NULL;
    public $lastTemplatefile = NULL;
    public $ignore_cache     = false;

    public function __construct($module=NULL, $file=NULL)
    {
        //$this->HTML_Template_Sigma();
        parent::__construct();

        if (isset($module)){
            $this->setModule($module);
        }

        if (isset($file)){
            $result = $this->setFile($file);

            if (\phpws\PHPWS_Error::isError($result)){
                \phpws\PHPWS_Error::log($result);
                $this->error = $result;
            }
        }
    }

    /**
     * Grabs the THEME template directory unless layout is not
     * operational
     */
    public static function getTplDir($module)
    {
        if ($module == 'core') {
            return PHPWS_SOURCE_DIR . 'core/templates/';
        }

        if (!class_exists('Layout')) {
            return PHPWS_SOURCE_DIR . "mod/$module/templates/";
        }

        $theme = \Layout::getThemeDirRoot() . \Layout::getTheme();
        return sprintf('%s/templates/%s/', $theme, $module);
    }

    public function setIgnoreCache($ignore=true)
    {
        $this->ignore_cache = (bool)$ignore;
    }

    public function setCache()
    {
        if ($this->ignore_cache || !\phpws\PHPWS_Template::allowSigmaCache()) {
            return;
        }

        static $root_dir = null;

        if (empty($root_dir) && defined('CACHE_LIFETIME')) {
            if (CACHE_TPL_LOCALLY) {
                $root_dir = PHPWS_SOURCE_DIR . 'templates/cache/';
            } elseif(defined('CACHE_DIRECTORY')) {
                $root_dir = CACHE_DIRECTORY;
            } else {
                $root_dir = 'unusable';
                return;
            }

            if (!is_writable($root_dir)) {
                $root_dir = 'unusable';
                return;
            }
        } elseif ($root_dir == 'unusable') {
            return;
        }

        $this->setCacheRoot($root_dir);
    }

    public function allowSigmaCache()
    {
        if (defined('ALLOW_SIGMA_CACHE')) {
            return ALLOW_SIGMA_CACHE;
        } else {
            return false;
        }
    }

    /**
     * Updated comment:
     * Returns a theme directory if the FORCE_THEME_TEMPLATES is defined and
     * it exists. Returns menu otherwise. If you are trying to decide whether to
     * use a theme template or a base module template, this function is not
     * very helpful.
     *
     * returns the expected template directory based on settings in
     * the template.php config file and the existence of files
     *
     *
     */
    public static function getTemplateDirectory($module, $directory=NULL)
    {
        $theme_dir  = \phpws\PHPWS_Template::getTplDir($module) . $directory;
        $module_dir = sprintf('%smod/%s/templates/%s', PHPWS_SOURCE_DIR, $module, $directory);

        if (FORCE_THEME_TEMPLATES && is_dir($theme_dir)) {
            return $theme_dir;
        } elseif (is_dir($module_dir)) {
            return $module_dir;
        } else {
            return NULL;
        }
    }

    public static function getTemplateHttp($module, $directory=NULL)
    {
        $theme_dir  = \phpws\PHPWS_Template::getTplDir($module) . $directory;
        $module_dir = sprintf('%smod/%s/templates/%s', PHPWS_SOURCE_HTTP, $module, $directory);
        if (FORCE_THEME_TEMPLATES) {
            return $theme_dir;
        } else {
            return $module_dir;
        }
    }
    /**
     * Lists the template files in a directory.
     * Can be called statically.
     */
    public function listTemplates($module, $directory=NULL)
    {
        $tpl_dir = \phpws\PHPWS_Template::getTemplateDirectory($module, $directory);

        if (!$result = scandir($tpl_dir)) {
            return NULL;
        }

        foreach ($result as $file) {
            if (!preg_match('/(^\.)|(~$)/iU', $file) && preg_match('/\.tpl$/i', $file)) {
                $file_list[$file] = $file;
            }
        }

        return $file_list;
    }

    /**
     * Sets the template file specified. Function decides which file to use based
     * on template.php settings and file availability
     */
    public function setFile($file, $strict=false)
    {
        $module = $this->getModule();
        $this->setCache();
        if ($strict == true) {
            $result = $this->loadTemplateFile($file);
            $used_tpl = & $file;
        } else {
            $theme_tpl    = \phpws\PHPWS_Template::getTplDir($module) . $file;
            $mod_tpl      = PHPWS_SOURCE_DIR . "mod/$module/templates/$file";

            if (\phpws\PHPWS_Error::isError($theme_tpl)) {
                return $theme_tpl;
            }

            if (is_file($theme_tpl)) {
                $result = $this->loadTemplateFile($theme_tpl);
                $used_tpl = & $theme_tpl;
            } elseif (is_file($mod_tpl)) {
                $result = $this->loadTemplateFile($mod_tpl);
                $used_tpl = & $mod_tpl;
            } else {
                throw new \Exception(sprintf('Missing template file: %s', $file));
            }
        }

        if ($result) {
            \phpws\PHPWS_Error::logIfError($result);
            $this->lastTemplatefile = $used_tpl;
            return $result;
        } else {
            return $this->err[0];
        }
    }

    public function setModule($module)
    {
        $this->module = $module;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function setData($data)
    {
        if (\phpws\PHPWS_Error::isError($data)){
            \phpws\PHPWS_Error::log($data);
            return NULL;
        }

        if (!is_array($data) || empty($data)) {
            return NULL;
        }

        foreach($data as $tag=>$content) {
            if ( (is_string($tag) || is_numeric($tag)) &&
            (is_string($content) || is_numeric($content)) ) {
                $this->setVariable($tag, $content);
            }
        }
    }


    public static function process($template, $module, $file, $strict=false, $ignore_cache=false)
    {
        if (!is_array($template)) {
            return \phpws\PHPWS_Error::log(PHPWS_VAR_TYPE, 'core',
                                    '\phpws\PHPWS_Template::process',
                                    'template=' . gettype($template));
            return NULL;
        }

        if (\phpws\PHPWS_Error::isError($template)){
            \phpws\PHPWS_Error::log($template);
            return NULL;
        }

        if ($strict) {
            $tpl = new \phpws\PHPWS_Template;
            $tpl->setFile($file, true);
        } else {
            $tpl = new \phpws\PHPWS_Template($module, $file);
        }

        $tpl->ignore_cache = (bool)$ignore_cache;

        if (\phpws\PHPWS_Error::isError($tpl->error)) {
            \phpws\PHPWS_Error::log($tpl->error);
            return 'Template error.';
        }

        foreach ($template as $key => $value) {
            if (!is_array($value) || !isset($tpl->_blocks[$key])) {
                continue;
            }

            foreach ($value as $content) {
                $tpl->setCurrentBlock($key);
                $tpl->setData($content);
                $tpl->parseCurrentBlock();
            }
            unset($template[$key]);
        }

        $template['source_http'] = $template['SOURCE_HTTP'] = PHPWS_SOURCE_HTTP;
        $tpl->setData($template);

        $result = $tpl->get();

        if (\phpws\PHPWS_Error::isError($result)) {
            return $result;
        }

        if (LABEL_TEMPLATES == true){
            $start = "\n<!-- START TPL: " . $tpl->lastTemplatefile . " -->\n";
            $end = "\n<!-- END TPL: " . $tpl->lastTemplatefile . " -->\n";
        } else {
            $start = $end = NULL;
        }

        if (!isset($result) && RETURN_BLANK_TEMPLATES) {
            return $start . $tpl->lastTemplatefile . $end;
        } else {
            return $start . $result . $end;
        }
    }

    public static function processTemplate($template, $module, $file, $defaultTpl=true)
    {
        if ($defaultTpl) {
            return \phpws\PHPWS_Template::process($template, $module, $file);
        } else {
            $tpl = new \phpws\PHPWS_Template($module);
            $tpl->setFile($file, true);
            $tpl->setData($template);
            return $tpl->get();
        }
    }
}
