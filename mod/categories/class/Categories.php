<?php

/**
 * General category administration
 *
 * @version $Id$
 * @author  Matt McNaney <matt at tux dot appstate dot edu>
 * @package categories
 */

require_once PHPWS_SOURCE_DIR . 'mod/categories/inc/errorDefines.php';
PHPWS_Core::initModClass('categories', 'Category.php');

define('CAT_LINK_DIVIDERS', '&gt;&gt;');

class Categories{

    function show()
    {
        if (!Current_User::allow('categories')) {
            return;
        }
        $remove_list = $content = NULL;

        $key = Key::getCurrent();

        if (empty($key) || $key->isDummy()) {
            return;
        }

        if (!$key->allowEdit()) {
            return NULL;
        }

        if (javascriptEnabled()) {
            $js_vars['label'] = _('Categorize');
            $js_vars['width'] = 640;
            $js_vars['height'] = 200;

            $vars['action'] = 'admin';
            $vars['subaction'] = 'set_item_category';
            $vars['key_id'] = $key->id;

            $js_vars['address'] = PHPWS_Text::linkAddress('categories', $vars, TRUE);
            $link = javascript('open_window', $js_vars);
            MiniAdmin::add('categories', $link);
        } else {
            $content = Categories::showForm($key);
            if (!empty($content)) {
                Layout::add($content, 'categories', 'Admin_Menu');
            }
        }
    }


    function showForm(&$key, $popup=FALSE)
    {
        $add_list = Categories::getCategories('list');

        if (empty($add_list)) {
            return _('You need to add some categories first.');
        }

        $current_cat_ids = Categories::getCurrent($key->id);

        if (!empty($current_cat_ids)) {
            foreach ($add_list as $cat_id => $cat) {
                if (in_array($cat_id, $current_cat_ids)) {
                    $remove_list[$cat_id] = $cat;
                    unset($add_list[$cat_id]);
                }
            }
        }

        $form = & new PHPWS_Form('category_list');
        $form->addHidden('module', 'categories');
        $form->addHidden('action', 'admin');
        $form->addHidden('subaction', 'post_item');
        $form->addHidden('key_id', $key->id);

        if (!empty($add_list)) {
            $form->addSelect('add_category', $add_list);
            $form->addSubmit('add', _('Add category'));
        } else {
            $form->addTplTag('ADD_CATEGORY', _('All categories assigned.'));
        }
        
        if (empty($remove_list)) {
            $form->addTplTag('REMOVE_CATEGORY', _('No categories assigned.'));
        } else {
            $form->addSelect('remove_category', $remove_list);
            $form->addSubmit('remove', _('Remove category'));
        }

        $template = $form->getTemplate();
        
        
        if ($key->allowEdit()) {
            $template['CONTENT'] = 'test';
        }

        $template['CAT_TITLE'] = _('Categorize');
        $template['ITEM_TITLE'] = $key->title;

        if ($popup) {
            $template['CLOSE'] = sprintf('<input type="button" value="%s" onclick="opener.location.href=\'%s\'; window.close();" />',
                                         _('Save and close'), $key->url);
            $template['AVAILABLE'] = _('Available categories');
            $template['CURRENT'] = _('Currently assigned');
            $content = PHPWS_Template::process($template, 'categories', 'popup_menu.tpl');
        } else {
            $content = PHPWS_Template::process($template, 'categories', 'menu_bar.tpl');
        }
        return $content;
    }

    /**
     * Returns a list of category links for a specific module
     */

    function getCategoryList($module)
    {
        Layout::addStyle('categories');
        $result = Categories::getCategories();
        $list = Categories::_makeLink($result, $module);
        return $list;
    }

    /**
     * Creates the links based on categories sent to it
     */
    function _makeLink($list, $module)
    {
        $vars['action'] = 'view';

        $db = & new PHPWS_DB('phpws_key');

        if (!empty($module)) {
            $vars['ref_mod'] = $module;
        }

        foreach ($list as $category) {
            $db->addWhere('id', 'category_items.key_id');
            $db->addWhere('category_items.cat_id', $category->id);

            if (!empty($module)) {
                $db->addWhere('module', $module);
            }

            $result = $db->select('count');

            if (PEAR::isError($result)) {
                PHPWS_Error::log($result);
                return NULL;
            }
            $db->resetWhere();
            $count = (int)$result;
            $items = ' - ' . $count . ' ' . _('item(s)');

            $vars['id'] = $category->id;

            $title = $category->title . $items;

            $link = PHPWS_Text::moduleLink($title, 'categories', $vars);

            if (!empty($category->children)) {
                $link .= Categories::_makeLink($category->children, $module);
            }

            $template['link_row'][] = array('LINK' => $link);
        }

        $links = PHPWS_Template::process($template, 'categories', 'simple_list.tpl');
        return $links;
    }


    function _getItemsCategories($key)
    {
        $db = & new PHPWS_DB('categories');
        $db->addWhere('category_items.key_id', $key->id);
        $db->addWhere('id', 'category_items.cat_id');
        $cat_result = $db->getObjects('Category');
        return $cat_result;
    }

    /**
     * Returns an array of category links applicable to the item
     *
     * If show_uncategorized is FALSE, then an uncategorized item
     * will not return the uncategorized category link.
     *
     * @author Matthew McNaney
     */
    function getSimpleLinks($key=NULL, $show_uncategorized=FALSE)
    {
        $link = NULL;

        if (empty($key)) {
            $key = Key::getCurrent();
        } elseif (is_numeric($key)) {
            $key = & new Key($key);
        }

        if (!$key->id) {
            return NULL;
        }

        $cat_result = Categories::_getItemsCategories($key);

        if (empty($cat_result)) {
            return NULL;
        }

        foreach ($cat_result as $cat){
            if (!$cat->id && !$show_uncategorized) {
                continue;
            }
            $link[] = $cat->getViewLink($key->module);
        }

        return $link;
    }

    function _createExtendedLink($category, $mode)
    {
        $link[] = $category->getViewLink();

        if ($mode == 'extended') {
            if ($category->parent) {
                $parent = & new Category($category->parent);
                $link[] = Categories::_createExtendedLink($parent, 'extended');
            }
        }

        return implode(' ' . CAT_LINK_DIVIDERS . ' ', array_reverse($link));
    }


    /**
     * Retrieves current categories for a key id
     */
    function getCurrent($key_id)
    {
        $db = & new PHPWS_DB('category_items');
        $db->addWhere('key_id', (int)$key_id);
        $db->addColumn('cat_id');
        return $db->select('col');
    }


    function getCategories($mode='sorted', $drop=NULL)
    {
        $db = & new PHPWS_DB('categories');

        switch ($mode){
        case 'sorted':
            $db->addWhere('parent', 0);
            $db->addOrder('title');

            $cats = $db->getObjects('Category');

            $uncat = & new Category(0);

            if (empty($cats)) {
                $cats[] = $uncat;
            }
            else {
                array_unshift($cats, $uncat);
            }

            $result = Categories::initList($cats);
            break;

        case 'idlist':
            $db->addColumn('title');
            $db->setIndexBy('id');
            $result = $db->select('col');
            break;

        case 'list':
            $list = Categories::getCategories();
            $indexed = Categories::_buildList($list, $drop);

            return $indexed;
            break;
        }

        return $result;
    }

    function initList($list)
    {
        foreach ($list as $cat){
            //            $cat->loadIcon();
            $cat->loadChildren();
            $children[$cat->id] = $cat;
        }
        return $children;
    }


    function _buildList($list, $drop=NULL)
    {
        if (empty($list)) {
            return NULL;
        }

        foreach ($list as $category){
            if ($category->id == $drop) {
                continue;
            }
            $indexed[$category->id] = $category->title;
            if (!empty($category->children)) {
                $sublist = Categories::_buildList($category->children, $drop);
                if (isset($sublist)) {
                    foreach ($sublist as $subkey => $subvalue){
                        $indexed[$subkey] = $category->title . ' ' . CAT_LINK_DIVIDERS . ' ' . $subvalue;
                    }
                }
            }
        }

        if (isset($indexed)) {
            return $indexed;
        } else {
            return NULL;
        }
    }

    function getTopLevel()
    {
        $db = & new PHPWS_DB('categories');
        $db->addWhere('parent', 0);
        return $db->getObjects('Category');
    }

    function cookieCrumb($category=NULL, $module=NULL)
    {
        Layout::addStyle('categories');

        $top_level = Categories::getTopLevel();

        $tpl = & new PHPWS_Template('categories');
        $tpl->setFile('list.tpl');

        if (!empty($top_level)) {
            foreach ($top_level as $top_cats) {
                $tpl->setCurrentBlock('child-row');
                $tpl->setData(array('CHILD' => $top_cats->getViewLink($module)));
                $tpl->parseCurrentBlock();
            }
        }

        $vars['action'] = 'view';
        if (isset($module)) {
            $vars['ref_mod'] = $module;
        }

        $tpl->setCurrentBlock('parent-row');
        $tpl->setData(array('PARENT' => PHPWS_Text::moduleLink( _('Top Level'), 'categories', $vars)));
        $tpl->parseCurrentBlock();

        if (!empty($category)) {
            $family_list = $category->getFamily();

            foreach ($family_list as $parent){
                if (isset($parent->children)) {
                    foreach ($parent->children as $child) {
                        $tpl->setCurrentBlock('child-row');
                        $tpl->setData(array('CHILD' => $child->getViewLink($module)));
                        $tpl->parseCurrentBlock();
                    }
                }
        
                $tpl->setCurrentBlock('parent-row');
                $tpl->setData(array('PARENT' => $parent->getViewLink($module)));
                $tpl->parseCurrentBlock();
            }
        }

        $content = $tpl->get();
        return $content;
    }

    function getModuleListing($cat_id=NULL)
    {
        PHPWS_Core::initCoreClass('Module.php');
        $db = & new PHPWS_DB('category_items');
        if (isset($cat_id)) {
            $db->addWhere('cat_id' , (int)$cat_id);
        }
        $db->addColumn('key_id');

        $result = $db->select('col');
        if (empty($result)) {
            return NULL;
        }

        $mod_names = PHPWS_Core::getModuleNames();

        foreach ($result as $key_id) {
            $key = & new Key($key_id);
            if (!isset($mod_count[$key->module])) {
                $mod_count[$key->module] = 1;
            } else {
                $mod_count[$key->module]++;
            }
        }

        foreach ($mod_count as $mod_title => $items) {
            $mod_list[$mod_title] = sprintf(_('%s - %s item(s)'), $mod_names[$mod_title], $mod_count[$mod_title]);
        }

        return $mod_list;
    }

    function listModuleItems(&$category)
    {
        $module_list = Categories::getModuleListing($category->getId());

        if (empty($module_list)) {
            return _('No items available in this category.');
        }

        $vars['action'] = 'view';
        $vars['id'] = $category->getId();

        $tpl = & new PHPWS_Template('categories');
        $tpl->setFile('module_list.tpl');

        $tpl->setCurrentBlock('module-row');
        foreach ($module_list as $mod_key => $module){
            $vars['ref_mod'] = $mod_key;
            $template['module-row'][] = array('MODULE_ROW' => PHPWS_Text::moduleLink($module, 'categories', $vars));
        }

        return PHPWS_Template::process($template, 'categories', 'module_list.tpl');
    }

    function removeModule($module)
    {
        $db = & new PHPWS_DB('category_items');
        $db->addWhere('module', $module);
        $db->delete();
    }

    function delete($category)
    {
        $category->kill();
    }

}

?>