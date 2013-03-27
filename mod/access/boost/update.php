<?php

/**
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */
function access_update(&$content, $version)
{
    switch (1) {
        case version_compare($version, '0.1.0', '<'):
            if (PHPWS_Boost::updateFiles(array('conf/config.php'), 'access')) {
                $content[] = '- Copied config.php locally.';
            } else {
                $content[] = '- Unable to copy config.php locally.';
            }
            $content[] = '- Added rewrite conditionals to .htaccess write.';

        case version_compare($version, '0.1.1', '<'):
            $content[] = '<pre>';
            $files = array('templates/main.tpl', 'templates/box.tpl', 'templates/shortcut_menu.tpl');
            if (PHPWS_Boost::updateFiles($files, 'access')) {
                $content[] = '-- Copied following template files locally:';
            } else {
                $content[] = '-- Failed to copy the following files locally:';
            }
            $content[] = implode("\n", $files);
            $content[] = '+ Fixed header tags (Bug #1652279)';
            $content[] = '</pre>';

        case version_compare($version, '0.1.2', '<'):
            $content[] = '<pre>
0.1.2 changes
---------------
+ Added translate functions.
</pre>
';

        case version_compare($version, '0.2.0', '<'):
            $content[] = '<pre>
0.2.0 changes
---------------';
            $files = array('conf/error.php',
                'templates/forms/administrator.tpl',
                'templates/forms/update_file.tpl',
                'img/access.png');
            if (PHPWS_Boost::updateFiles($files, 'access')) {
                $content[] = '+ The following files were updated successfully.';
            } else {
                $content[] = '+ The following files were not updated successfully.';
            }
            $content[] = '    ' . implode("\n    ", $files);

            $content[] = '+ Update panel shows the current .htaccess file as well as
  the one the admin is about to save.
+ Changed the admin panel to turn off different components.
+ Rewrite engine enabled by default.
+ Shortcuts now separated by dashes and not underlines
+ Keywords in shortcuts parsed better.
+ Admins can now edit shortcut keywords from the admin panel.
+ Deny/Allow tab changed to Allow/Deny since it is set that way every where else.
+ Allow/Deny can now be disabled in the Admin panel.
+ Added a way to restore the default .htaccess file.
+ Removed symbolic link option from htaccess writes.
+ New control panel icon.
</pre>
';

        case version_compare($version, '0.2.1', '<'):
            $content[] = '<pre>0.2.1 changes
---------------
+ Updated to new language format.</pre>';

        case version_compare($version, '0.2.2', '<'):
            $content[] = '<pre>';
            $files = array('conf/error.php',
                'templates/forms/administrator.tpl',
                'templates/forms/update_file.tpl',
                'img/access.png',
                'conf/config.php');
            if (PHPWS_Boost::updateFiles($files, 'access')) {
                $content[] = '+ The following files were updated successfully.';
            } else {
                $content[] = '+ The following files were not updated successfully.';
            }
            $content[] = '    ' . implode("\n    ", $files);

            $content[] = '
0.2.2 changes
---------------
+ The default rewrite conditional was missing a file check.
+ Previous update had updated files going to incorrect directory.
+ Template was removing curly brackets from review mode. Fixed.
</pre>';

        case version_compare($version, '0.2.3', '<'):
            $content[] = '<pre>
0.2.3 changes
---------------
+ Fixed bug #1690698: Cannot create a new .htaccess file if original
  is deleted. Thanks singletrack
+ Fixed bug #1690544: If the .htaccess file is not writable or
  missing, give the user a warning message.</pre>';

        case version_compare($version, '1.0.0', '<'):
            $content[] = '<pre>';
            $files = array('templates/forms/allow_deny.tpl', 'templates/forms/shortcut_list.tpl');
            if (PHPWS_Boost::updateFiles($files, 'access')) {
                $content[] = '--- The following files were updated successfully.';
            } else {
                $content[] = '--- The following files were not updated successfully.';
            }
            $content[] = implode("\n", $files);
            $content[] = '1.0.0 changes
---------------
+ Rewritten for phpwebsite 1.5.0 changes.
+ addIP and removeIP allow modules to restrict users.
</pre>';

        case version_compare($version, '1.0.1', '<'):
            $content[] = '<pre>1.0.1 changes
---------------
+ Fixed Access option not appearing on MiniAdmin
+ .html completely removed from shortcuts
</pre>';

        case version_compare($version, '1.0.2', '<'):
            $content[] = '<pre>1.0.2 changes
---------------
+ Removed htaccess file. Now expect core/inc/htaccess.
</pre>';

        case version_compare($version, '1.1.0', '<'):
            PHPWS_Boost::updateFiles(array('templates/htaccess.tpl'), 'access');
            $content[] = '<pre>1.1.0 changes
---------------
+ New ability to added a RewriteBase to a .htaccess file.
+ Updated to PHP 5 standards.
</pre>';

        case version_compare($version, '1.1.1', '<'):
            $content[] = '<pre>1.1.1 changes
---------------
+ Reformated shortcut creation method. Should work with old version
  as well as any new longer links.</pre>';

        case version_compare($version, '1.1.2', '<'):
            $content[] = '<pre>1.1.2 changes
---------------
+ Fixed some error notices
+ Fixed access shortcuts to work with GET arrays</pre>';

        case version_compare($version, '1.1.3', '<'):
            $content[] = '<pre>1.1.3 changes
---------------
+ Access forces bad urls to 404 error
</pre>';

        case version_compare($version, '1.1.4', '<'):
            $content[] = '<pre>1.1.4 changes
---------------
+ Trimming the title to prevent extra spaces in shortcuts
</pre>';

        case version_compare($version, '1.1.5', '<'):
            $module = new PHPWS_Module('access');
            PHPWS_Error::logIfError($module->save());
            $content[] = '<pre>1.1.5 changes
---------------
+ Lowered Access priority to assure its init.php is called early.</pre>';

        case version_compare($version, '1.1.6', '<'):
            $content[] = '<pre>1.1.6 changes
---------------
+ Added link check on url setting to prevent ./ from suffixing and
  breaking storage.</pre>';

        case version_compare($version, '1.1.7', '<'):
            $content[] = '<pre>1.1.7 changes
---------------
+ Code changes to make PHP 5 strict compatible.</pre>';

        case version_compare($version, '1.1.8', '<'):
            $content[] = '<pre>1.1.8 changes
---------------
+ Fixed shortcuts not working with some older pages
+ Pager links added to shortcut list</pre>';

        case version_compare($version, '1.1.9', '<'):
            $content[] = '<pre>1.1.9 changes
---------------
+ Fixed a bug in Access module which was causing the RewriteBase? to be set to the empty string</pre>';

        case version_compare($version, '1.2.0', '<'):
            $sql = "ALTER TABLE  access_shortcuts CHANGE  keyword  keyword VARCHAR( 255 ) NOT NULL DEFAULT ''";
            PHPWS_DB::query($sql);
            $content[] = '<pre>1.2.0 changes
---------------
+ Shortcut length increased and observed in code.</pre>';

            case version_compare($version, '1.2.1', '<'):
            $sql = "ALTER TABLE  access_shortcuts CHANGE  keyword  keyword VARCHAR( 255 ) NOT NULL DEFAULT ''";
            PHPWS_DB::query($sql);
            $content[] = '<pre>1.2.1 changes
---------------
+ Added tools to shortcuts to give all pages shortcuts and to autoforward on id calls.
+ Made sure the varchar is changed since the install did not reflect the change.</pre>';

            case version_compare($version, '1.2.2', '<'):
                $content[] = '<pre>1.2.2 changes
--------------
+ Fixed autoforwarding</pre>';
    }

    return true;
}

?>