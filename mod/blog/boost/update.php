<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

function blog_update(&$content, $currentVersion)
{
    switch ($currentVersion) {

    case version_compare($currentVersion, '1.2.2', '<'):
        $content[] = 'This package will not update versions prior to 1.2.2.';
        return false;

    case version_compare($currentVersion, '1.2.3', '<'):
        $content[] = '<pre>
1.2.3 Changes
-------------
+ Make call to resetKeywords in search to prevent old search word retention.
</pre>';

    case version_compare($currentVersion, '1.4.1', '<'):
        $content[] = '<pre>';

        $db = new PHPWS_DB('blog_entries');
        $result = $db->addTableColumn('image_id', 'int NOT NULL default 0');
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'Unable to add image_id colume to blog_entries table.</pre>';
            return false;
        }

        $files = array('templates/edit.tpl',
                       'templates/settings.tpl',
                       'templates/view.tpl',
                       'templates/submit.tpl',
                       'templates/user_main.tpl',
                       'templates/list_view.tpl');

        blogUpdateFiles($files, $content);
        $content[] = '
1.4.1 Changes
-------------
+ Added missing category tags to entry listing.
+ Added ability for anonymous and users without blog permission to
  submit entries for later approval.
+ Added setting to allow anonymous submission.
+ Added ability to place images on Blog entries without editor.
+ Added pagination to Blog view.
+ Added link to reset the view cache.
+ Added ability to add images to entry without editor.
+ Added missing translate calls.
+ Changed edit form layout.
</pre>';
    case version_compare($currentVersion, '1.4.2', '<'):
        $content[] = '<pre>';
        $files = array('templates/list.tpl');
        blogUpdateFiles($files, $content);
        $content[] = '1.4.2 Changes
-------------
+ Fixed bug causing error message when Blog listing moved off front page.
+ Changes "Entry" column to "Summary" on admin list. Was not updated since summary was added.
</pre>';

    case version_compare($currentVersion, '1.4.3', '<'):
        $content[] = '<pre>1.4.3 Changes
-------------';

        $db = new PHPWS_DB('blog_entries');
        $result = $db->addTableColumn('expire_date', 'int not null default 0', 'publish_date');
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'Unable to create table column "expire_date" on blog_entries table.</pre>';
            return false;
        } else {
            $content[] = '+ Created "expire_date" column on blog_entries table.';
        }

        $result = $db->addTableColumn('sticky', 'smallint not null default 0');
        if (PEAR::isError($result)) {
            PHPWS_Error::log($result);
            $content[] = 'Unable to create table column "sticky" on blog_entries table.</pre>';
            return false;
        } else {
            $content[] = '+ Created "sticky" column on blog_entries table.';
        }
  
        $files = array('img/blog.png', 'templates/edit.tpl', 'templates/list.tpl');
        blogUpdateFiles($files, $content);

        $content[] = '+ Priviledged blog entries now forward to login page.
+ Added sticky option.
+ Added expiration options.
+ Removed fake French translation.
+ Changed Control Panel icon.
</pre>';

    case version_compare($currentVersion, '1.5.0', '<'):
        $content[] = '<pre>';
        $files = array('templates/settings.tpl', 'templates/edit.tpl', 'conf/config.php', 'templates/list_view.tpl');
        blogUpdateFiles($files);

        $content[] = '
1.5.0 Changes
-------------
+ Increased default blog entry title size to 100.
+ Added setting to control whether to allow anonymous comments by
  default on new blog entries
+ Added Captcha option to submissions.
+ Fixed cache reset
+ Added define to determine the highest amount of blog pages to cache
+ Added extra checks for anonymous submission
+ Changed coding of image manager call.
+ Changed to new language functionality.
+ Fixed: logErrors called on blog object instead of image object
  on empty image id.
+ Fixed pagination on list view.
+ Now uses new File Cabinet module.
</pre>';

    case version_compare($currentVersion, '1.5.1', '<'):
        $content[] = '<pre>
1.5.1 Changes
-------------
+ Comments link points to comments anchor.</pre>';

    case version_compare($currentVersion, '1.5.2', '<'):
        $content[] = '<pre>
1.5.2 Changes
-------------
+ Fixed previous blog listing.</pre>';

    case version_compare($currentVersion, '1.6.0', '<'):
        $content[] = '<pre>';

        $columns = array();
        $columns['update_date'] = 'int not null default 0';
        $columns['updater']     = 'varchar(50) NOT NULL';
        $columns['updater_id']  = 'int not null default 0';

        $db = new PHPWS_DB('blog_entries');
        foreach ($columns as $column_name => $col_info) {
            $result = $db->addTableColumn($column_name, $col_info, 'create_date');
            if (PHPWS_Error::logIfError($result)) {
                $content[] = "--- Unable to create table column '$column_name' on blog_entries table.</pre>";
                return false;
            } else {
                $content[] = "--- Created '$column_name' column on blog_entries table.";
            }
        }

        $files = array('templates/settings.tpl', 'templates/view.tpl');
        blogUpdateFiles($files, $content);
        
        if (!PHPWS_Boost::inBranch()) {
            $content[] = file_get_contents(PHPWS_SOURCE_DIR . 'mod/blog/boost/changes/1_6_0.txt');
        }
        $content[] = '</pre>';
    } // end of switch
    return true;
}

function blogUpdateFiles($files, &$content)
{
    if (PHPWS_Boost::updateFiles($files, 'blog')) {
        $content[] = '--- Updated the following files:';
    } else {
        $content[] = '--- Unable to update the following files:';
    }
    $content[] = "     " . implode("\n     ", $files);
}

?>