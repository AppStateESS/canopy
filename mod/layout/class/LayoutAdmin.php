<?php

/**
 * This class handles the administrative functionality
 * for layout. Changing themes, meta tags, etc. is handled
 * here.
 *
 * @author Matthew McNaney <matt at tux dot appstate.edu dot>
 * @version $Id$
 */
define('DEFAULT_LAYOUT_TAB', 'boxes');

class Layout_Admin
{

    public static function admin()
    {
        if (!Current_User::allow('layout')) {
            Current_User::disallow();
        }
        \phpws\PHPWS_Core::initModClass('controlpanel', 'Panel.php');
        $title = $content = null;
        $panel = Layout_Admin::adminPanel();

        if (isset($_REQUEST['command'])) {
            $command = $_REQUEST['command'];
        } else {
            $command = $panel->getCurrentTab();
        }

        switch ($command) {
            case 'arrange':
                $title = 'Arrange Layout';
                $content[] = Layout_Admin::arrangeForm();
                break;

            case 'post_style_change':
                $result = Layout_Admin::postStyleChange();
                if (PHPWS_Error::isError($result)) {
                    PHPWS_Error::log($result);
                }
                javascript('close_refresh');
                break;

            case 'reset_boxes':
                if (!Current_User::authorized('layout')) {
                    Current_User::disallow();
                }
                Layout::resetDefaultBoxes();
                unset($_SESSION['Layout_Settings']);
                \phpws\PHPWS_Core::reroute('index.php?module=layout&action=admin&authkey=' . Current_User::getAuthKey());
                break;

            case 'confirmThemeChange':
                $title = 'Themes';
                if (isset($_POST['confirm'])) {
                    Layout_Admin::changeTheme();
                    \phpws\PHPWS_Core::reroute('index.php?module=layout&action=admin&tab=theme');
                    exit();
                } else {
                    Layout::reset();
                }

                $content[] = Layout_Admin::adminThemes();
                break;

            case 'meta':
                $title = 'Edit Meta Tags';
                $content[] = Layout_Admin::metaForm();
                break;

            case 'clear_templates':
                if (!Current_User::authorized('layout')) {
                    Current_User::disallow();
                }
                $files = PHPWS_File::readDirectory(PHPWS_SOURCE_DIR . 'templates/cache', false, true);
                if (!empty($files) && is_array($files)) {
                    foreach ($files as $fn) {
                        $delete_cache_path = "templates/cache/$fn";
                        if (is_file($delete_cache_path)) {
                            unlink('templates/cache/' . $fn);
                        }
                    }
                }
                \phpws\PHPWS_Core::goBack();
                break;

            case 'clear_cache':
                if (!Current_User::authorized('layout')) {
                    Current_User::disallow();
                }
                PHPWS_Cache::clearCache();
                \phpws\PHPWS_Core::goBack();
                break;

            case 'moveBox':
                $result = Layout_Admin::moveBox();
                PHPWS_Error::logIfError($result);
                exit;
                javascript('close_refresh');
                Layout::nakedDisplay();
                break;

            case 'postMeta':
                if (!Current_User::authorized('layout')) {
                    Current_User::disallow();
                }
                Layout_Admin::postMeta();
                if (isset($_POST['key_id'])) {
                    javascript('close_refresh');
                    Layout::nakedDisplay();
                    exit();
                }
                Layout::reset();
                $title = 'Edit Meta Tags';
                $template['MESSAGE'] = 'Meta Tags updated.';
                $content[] = Layout_Admin::metaForm();
                break;

            case 'demo_fail':
                unset($_SESSION['Layout_Settings']);
                Layout::checkSettings();
                \phpws\PHPWS_Core::reroute('index.php?module=layout&amp;action=admin&amp;command=confirmThemeChange');
                break;

            case 'demo_theme':
                $title = 'Confirm Theme Change';
                $content[] = dgettext('layout', 'If you are happy with the change, click the appropiate button.');
                $content[] = dgettext('layout', 'Failure to respond in ten seconds, reverts phpWebSite to the default theme.');
                $content[] = Layout_Admin::confirmThemeChange();
                break;

            case 'postTheme':
                if (!Current_User::authorized('layout')) {
                    Current_User::disallow();
                }
                if ($_POST['default_theme'] != $_SESSION['Layout_Settings']->current_theme) {
                    Layout::reset($_POST['default_theme']);
                    \phpws\PHPWS_Core::reroute('index.php?module=layout&action=admin&command=demo_theme&authkey=' . Current_User::getAuthKey());
                } else {
                    PHPWS_Settings::set('layout', 'include_css_order', (int) $_POST['include_css_order']);
                    PHPWS_Settings::save('layout');

                    $title = 'Themes';
                    $content[] = Layout_Admin::adminThemes();
                }
                break;

            case 'theme':
                $title = 'Themes';
                $content[] = Layout_Admin::adminThemes();
                break;

            case 'js_style_change':
                $content = Layout_Admin::jsStyleChange();
                if (empty($content)) {
                    javascript('close_refresh');
                }
                Layout::nakedDisplay($content, 'Change CSS');
                break;

            case 'page_meta_tags':
                $content = Layout_Admin::pageMetaTags((int) $_REQUEST['key_id']);
                if (empty($content)) {
                    javascript('close_refresh');
                }
                Layout::nakedDisplay($content, 'Set meta tags');
                break;

            case 'boxMoveForm':
                self::boxMoveForm();
                exit;
        }

        $template['TITLE'] = $title;
        if (isset($content)) {
            $template['CONTENT'] = implode('<br />', $content);
        }
        if (isset($message))
            $template['MESSAGE'] = $message;

        $final = PHPWS_Template::process($template, 'layout', 'main.tpl');
        $panel->setContent($final);

        Layout::add(PHPWS_ControlPanel::display($panel->display()));
    }

    public function jsStyleChange()
    {
        $styles = Layout::getExtraStyles();

        if (empty($styles) || !isset($_REQUEST['key_id'])) {
            return false;
        }
        $styles[0] = dgettext('layout', '-- Use default style --');
        ksort($styles, SORT_NUMERIC);

        $key_id = (int) $_REQUEST['key_id'];

        $current_style = Layout::getKeyStyle($key_id);

        if (empty($current_style)) {
            $current_style = 0;
        }

        $form = new PHPWS_Form('change_styles');
        $form->addHidden('module', 'layout');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'post_style_change');
        $form->addHidden('key_id', $key_id);

        $form->addSelect('style', $styles);
        $form->setLabel('style', 'Style sheet');
        $form->setMatch('style', $current_style);
        $form->addSubmit('Save');

        $form->addButton('cancel', 'Cancel');
        $form->setExtra('cancel', 'onclick="window.close()"');

        $template = $form->getTemplate();

        $template['TITLE'] = 'Change CSS';
        return PHPWS_Template::process($template, 'layout', 'style_change.tpl');
    }

    public static function adminPanel()
    {
        \phpws\PHPWS_Core::initModClass('controlpanel', 'Panel.php');
        $link = 'index.php?module=layout&amp;action=admin';
        $tabs['arrange'] = array('title' => 'Arrange', 'link' => $link);
        $tabs['meta'] = array('title' => 'Meta Tags', 'link' => $link);
        $tabs['theme'] = array('title' => 'Themes', 'link' => $link);

        $panel = new PHPWS_Panel('layout');
        $panel->quickSetTabs($tabs);
        return $panel;
    }

    public static function adminThemes()
    {
        $form = new PHPWS_Form('themes');
        $form->addHidden('module', 'layout');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postTheme');

        $form->addSubmit('update', 'Update Theme Settings');
        $themeList = Layout_Admin::getThemeList();
        if (PHPWS_Error::isError($themeList)) {
            PHPWS_Error::log($themeList);
            return 'There was a problem reading the theme directories.';
        }

        $form->addSelect('default_theme', $themeList);
        $form->reindexValue('default_theme');
        $form->setMatch('default_theme', Layout::getDefaultTheme());
        $form->setLabel('default_theme', 'Default Theme');

        $include_order[0] = 'Do not include module style sheets';
        $include_order[1] = 'Modules before theme';
        $include_order[2] = 'Theme before modules';

        $form->addSelect('include_css_order', $include_order);
        $form->setMatch('include_css_order', PHPWS_Settings::get('layout', 'include_css_order'));
        $form->setLabel('include_css_order', 'CSS inclusion order');

        $template = $form->getTemplate();
        return PHPWS_Template::process($template, 'layout', 'themes.tpl');
    }

    public static function arrangeForm()
    {
        $vars['action'] = 'admin';

        $template['MOVE_BOXES'] = '<button class="btn btn-primary" id="move-boxes">Move boxes</button>';
        $template['MOVE_BOXES_DESC'] = 'Allows you to shift content to other area of your layout. Movement options depend on the current theme.';

        $vars['command'] = 'reset_boxes';
        $template['RESET_BOXES'] = PHPWS_Text::secureLink('Reset boxes', 'layout', $vars, null, null, 'btn btn-primary');
        $template['RESET_DESC'] = 'Resets all content back to its original location. Use if problems with Box Move occurred.';

        $vars['command'] = 'clear_templates';
        $template['CLEAR_TEMPLATES'] = PHPWS_Text::secureLink('Clear templates', 'layout', $vars, null, null, 'btn btn-primary');
        $template['CLEAR_TEMPLATES_DESC'] = 'Removes all files from the current template cache directory. Good to try if your theme is not displaying properly.';

        $vars['command'] = 'clear_cache';
        $template['CLEAR_CACHE'] = PHPWS_Text::secureLink('Clear cache', 'layout', $vars, null, null, 'btn btn-primary');
        $template['CLEAR_CACHE_DESC'] = 'Clears all Cache Lite files. Good to try if module updates do not display.';

        javascript('jquery');
        $script = '<script type="text/javascript" src="' . PHPWS_SOURCE_HTTP . 'mod/layout/javascript/move_boxes.js"></script>';
        \Layout::addJSHeader($script, 'moveboxes');
        $modal = new \Modal('box-move', '', 'Move boxes');
        $modal->sizeLarge();

        $template['MODAL'] = $modal->get();
        return PHPWS_Template::process($template, 'layout', 'arrange.tpl');
    }

    private static function boxMoveForm()
    {
        $current_theme = \Layout::getCurrentTheme();

        $db = \phpws2\Database::getDB();
        $tbl = $db->addTable('layout_box');
        $tbl->addFieldConditional('theme', $current_theme);
        $tbl->addFieldConditional('active', 1);
        $tbl->addOrderBy('theme_var');
        $tbl->addOrderBy('box_order');

        $boxes = $db->select();

        $theme_vars = $_SESSION['Layout_Settings']->_allowed_move;

        $move_select = '<optgroup label="Shift within current variable">'
                . '<option>Click below to move this block</option>'
                . '<option value="move_box_top">Top</option>'
                . '<option value="move_box_up">Up</option>'
                . '<option value="move_box_down">Down</option>'
                . '<option value="move_box_bottom">Bottom</option>'
                . '</optgroup>'
                . '<optgroup label="Move to theme variable">';
        foreach ($theme_vars as $tv) {
            $listing[$tv] = null;
            $move_select .= "<option>$tv</option>";
        }
        $move_select .= '</optgroup></select>';

        foreach ($boxes as $box) {
            $box_name = $box['module'] . ':' . $box['content_var'];
            $listing[$box['theme_var']][$box['box_order']] = array('id' => $box['id'], 'name' => $box_name);
        }
        //var_dump($listing);exit;
        $template = new \Template(array('rows' => $listing, 'move_select' => $move_select));
        $template->setModuleTemplate('layout', 'box_move.html');

        echo $template->get();
        exit;
    }

    public static function changeTheme()
    {
        $result = $_SESSION['Layout_Settings']->saveSettings();
        unset($_SESSION['Layout_Settings']);
    }

    public static function confirmThemeChange()
    {
        $form = new PHPWS_Form('confirmThemeChange');
        $form->addHidden('module', 'layout');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'confirmThemeChange');
        $form->addSubmit('confirm', 'Complete the theme change');
        $form->addSubmit('decline', 'Restore the default theme');
        $address = 'index.php?module=layout&amp;action=admin&amp;command=demo_fail';
        Layout::metaRoute($address, 10);
        $tpl = $form->getTemplate();
        return $tpl['START_FORM'] . $tpl['CONFIRM'] . $tpl['DECLINE'] . $tpl['END_FORM'];
    }

    public static function getThemeList()
    {
        \phpws\PHPWS_Core::initCoreClass('File.php');
        return PHPWS_File::readDirectory(Layout::getThemeDirRoot(), 1);
    }

    /**
     * Form for meta tags. Used for site mata tags and individual key
     * meta tags.
     */
    public static function metaForm($key_id = 0)
    {
        $meta_description = $meta_keywords = $page_title = null;
        $meta_robots = '11';

        if (!$key_id) {
            $vars = $_SESSION['Layout_Settings']->getMetaTags();
        } else {
            $vars = $_SESSION['Layout_Settings']->getPageMetaTags($key_id);
            if (empty($vars)) {
                $vars = $_SESSION['Layout_Settings']->getMetaTags();
                $key = new \Canopy\Key($key_id);
                $vars['page_title'] = $key->title;
            }
        }

        if (is_array($vars)) {
            extract($vars);
        }

        $index = substr($meta_robots, 0, 1);
        $follow = substr($meta_robots, 1, 1);

        $form = new PHPWS_Form('metatags');
        if ($key_id) {
            $form->addHidden('key_id', $key_id);
            $form->addSubmit('reset', 'Restore to default');
        }
        $form->addHidden('module', 'layout');
        $form->addHidden('action', 'admin');
        $form->addHidden('command', 'postMeta');
        $form->addText('page_title', $page_title);
        $form->setClass('page_title', 'form-control');
        $form->setLabel('page_title', 'Site Name');
        $form->addTextArea('meta_keywords', $meta_keywords);
        $form->setLabel('meta_keywords', 'Keywords');
        $form->setClass('meta_keywords', 'form-control');
        $form->addTextArea('meta_description', $meta_description);
        $form->setLabel('meta_description', 'Description');
        $form->setClass('meta_description', 'form-control');
        $form->addCheckBox('index', 1);
        $form->setMatch('index', $index);
        $form->setLabel('index', 'Allow indexing');
        $form->addCheckBox('follow', 1);
        $form->setMatch('follow', $follow);
        $form->setLabel('follow', 'Allow link following');

        $form->addCheckBox('use_key_summaries', 1);
        $form->setMatch('use_key_summaries', PHPWS_Settings::get('layout', 'use_key_summaries'));
        $form->setLabel('use_key_summaries', 'Use Key summaries for meta description');

        $form->addSubmit('submit', 'Update');

        $template = $form->getTemplate();
        $template['ROBOT_LABEL'] = 'Default Robot Settings';
        return PHPWS_Template::process($template, 'layout', 'metatags.tpl');
    }

    /**
     * Receives the post results of the box change form.
     */
    public static function moveBox()
    {
        \phpws\PHPWS_Core::initModClass('layout', 'Box.php');
        $box = new Layout_Box($_GET['box_source']);
        $result = $box->move($_GET['box_dest']);

        if (PHPWS_Error::isError($result)) {
            PHPWS_Error::log($result);
            Layout::add('An unexpected error occurred when trying to save the new box position.');
            return;
        }

        Layout::resetBoxes();

        return true;
    }

    public function postStyleChange()
    {
        Layout::reset();
        if (!isset($_POST['style']) || !isset($_POST['key_id'])) {
            return;
        }

        $db = new PHPWS_DB('layout_styles');
        $db->addWhere('key_id', (int) $_POST['key_id']);
        $db->delete();
        $db->reset();
        if ($_POST['style'] != '0') {
            $db->addValue('key_id', (int) $_POST['key_id']);
            $db->addValue('style', $_POST['style']);
            $result = $db->insert();
        }
    }

    public static function postMeta()
    {
        $values['page_title'] = strip_tags($_POST['page_title']);
        $values['meta_keywords'] = strip_tags($_POST['meta_keywords']);
        $values['meta_description'] = strip_tags($_POST['meta_description']);

        if (isset($_POST['index'])) {
            $index = 1;
        } else {
            $index = 0;
        }

        if (isset($_POST['follow'])) {
            $follow = 1;
        } else {
            $follow = 0;
        }

        PHPWS_Settings::set('layout', 'use_key_summaries', (int) isset($_POST['use_key_summaries']));
        PHPWS_Settings::save('layout');

        $values['meta_robots'] = $index . $follow;

        if (isset($_POST['key_id'])) {
            $key_id = (int) $_POST['key_id'];
        }

        if (isset($key_id)) {
            $values['key_id'] = $key_id;
            $db = new PHPWS_DB('layout_metatags');
            $db->addWhere('key_id', $key_id);
            $db->delete();
            if (isset($_POST['reset'])) {
                return true;
            }
            $db->reset();
            $db->addValue($values);
            return $db->insert();
        } else {
            $db = new PHPWS_DB('layout_config');
            $db->addValue($values);
            return $db->update();
        }
    }

    public function pageMetaTags($key_id)
    {
        $content = Layout_Admin::metaForm($key_id);
        return $content;
    }
}

