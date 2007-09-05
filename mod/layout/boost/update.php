<?php

  /**
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

function layout_update(&$content, $currentVersion)
{
    switch ($currentVersion) {

    case version_compare($currentVersion, '2.2.0', '<'):
        $content[] = 'This package will not update versions under 2.2.0.';
        return false;

    case version_compare($currentVersion, '2.2.1', '<'):
        $content[] = '+ Fixed improper sql call in update_220.sql file.';

    case version_compare($currentVersion, '2.3.0', '<'):
        $content[] = '<pre>';
        if (PHPWS_Boost::updateFiles(array('conf/config.php', 'conf/error.php'), 'layout')) {
            $content[] = 'Updated conf/config.php and conf/error.php file.';
        } else {
            $content[] = 'Unable to update conf/config.php and conf/error.php file.';
        }
        $content[] = '
2.3.0 changes
-------------
+ Removed references from object constructors.
+ Added the plug function to allow users to inject content directly
  into a theme.
+ Added translate functions.
+ Layout now looks for and includes a theme\'s theme.php file.
+ Fixed unauthorized access.
+ Added XML mode to config.php file. Puts Layout in XHTML+XML content mode.
+ Added missing media parameters to XML mode.
</pre>';

    case version_compare($currentVersion, '2.4.0', '<'):
        $files = array('img/layout.png', 'templates/no_cookie.tpl');
        $content[] = '<pre>';
        if (PHPWS_Boost::updateFiles($files, 'layout')) {
            $content[] = '--- Successfully updated the following files:';
        } else {
            $content[] = '--- Was unable to copy the following files:';
        }
        $content[] = '     ' . implode("\n     ", $files);
        $content[] = '
2.4.0 changes
-------------
+ Layout now checks and forces a user to enable cookies on their
  browser. 
+ Rewrote Javascript detection. Was buggy before as session
  destruction could disrupt it.
+ Added German translations
+ Updated language functions
+ Fixed: bug in Layout confused a user\'s style sheet settings after
  the theme was changed.
+ Rewrote theme change code.
+ Added ability to force theme on layout settings construction.
+ Changed Control Panel icon
</pre>';

    case version_compare($currentVersion, '2.4.1', '<'):
        $files = array('conf/config.php');
        $content[] = '<pre>';
        if (PHPWS_Boost::updateFiles($files, 'layout')) {
            $content[] = '--- Successfully updated the following files:';
        } else {
            $content[] = '--- Was unable to copy the following files:';
        }
        $content[] = '     ' . implode("\n     ", $files);
        $content[] = '
2.4.1 changes
-------------
+ Bug #1741111 - Fixed moving a top box up and a bottom box down.
+ The cookie check is not determined by a define in the config file.
+ The cookie check was interfering with the rss feed. Cut off the page
  too quickly. Moved cookie check to the close.php file.
</pre>';

    case version_compare($currentVersion, '2.4.2', '<'):
        $content[] = '<pre>';
        $files = array('templates/arrange.tpl', 'conf/error.php', 'templates/move_box_select.tpl');
        layoutUpdateFiles($files, $content);

        if (!PHPWS_Boost::inBranch()) {
            $content[] = file_get_contents(PHPWS_SOURCE_DIR . 'mod/layout/boost/changes/2_4_2.txt');
        }
        $content[] = '</pre>';
    }
    return true;
}

function layoutUpdateFiles($files, &$content)
{
    if (PHPWS_Boost::updateFiles($files, 'layout')) {
        $content[] = '--- Updated the following files:';
    } else {
        $content[] = '--- Unable to update the following files:';
    }
    $content[] = "     " . implode("\n     ", $files);
}
?>